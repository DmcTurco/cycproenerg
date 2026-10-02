<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Personas de campo: une `tecnicos` (Gestión de Técnicos / app móvil) y
// `cuadrillas` (Control de Materiales) en una sola tabla — confirmado con
// Turco el 28/09/2026: son la misma gente, lo que cambia es el TIPO
// (PERSONAL DIRECTO / CONTRATISTA), y los dos tipos pueden recibir
// solicitudes y usar la app. El `cargo` de técnicos queda cubierto por el
// tipo y se elimina.
//
// Las columnas que apuntan acá conservan su nombre de ROL (como un
// `autor_id` que apunta a `users`): `cotizaciones/ejecutados/entregas
// .cuadrilla_id` y `solicitud_tecnico/historials.tecnico_id`.
//
// Pasaje de datos (todavía en desarrollo, así que simple):
//   - cuadrillas -> personas_campo conservando el id (cotizaciones,
//     ejecutados y entregas no se tocan).
//   - tecnicos -> personas_campo como PERSONAL DIRECTO; si ya hay una
//     persona con el mismo n° de documento, se junta en esa fila. Se
//     reapuntan solicitud_tecnico/historials al id nuevo.
//   - Los tokens de la app (Sanctum) de técnicos se borran: hay que volver
//     a iniciar sesión.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas_campo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // CONTRATISTA | PERSONAL DIRECTO — decide qué puede hacer cada
            // uno (ver PersonaCampo).
            $table->string('tipo', 20);
            $table->string('estado', 20)->default('ACTIVO');
            // config('const.tipo_documeto'): 1 DNI, 2 RUC, 3 CE.
            $table->smallInteger('tipo_documento')->nullable();
            // Texto (no entero como el viejo tecnicos.numero_documento_identificacion)
            // para no perder ceros a la izquierda.
            $table->string('numero_documento', 20)->nullable()->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('celular', 20)->nullable();
            // Acceso a la app móvil: opcional, solo quien la usa.
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('empresa_persona_campo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('persona_campo_id');
            $table->unsignedBigInteger('empresa_id');
            $table->unique(['persona_campo_id', 'empresa_id']);
            $table->timestamps();
        });

        $this->pasarCuadrillas();
        $this->pasarTecnicos();

        Schema::table('entregas', function (Blueprint $table) {
            $table->dropForeign(['cuadrilla_id']);
        });
        Schema::table('entregas', function (Blueprint $table) {
            $table->foreign('cuadrilla_id')->references('id')->on('personas_campo')->restrictOnDelete();
        });

        Schema::dropIfExists('cuadrilla_empresa');
        Schema::dropIfExists('cuadrillas');
        Schema::dropIfExists('tecnicos');
    }

    public function down(): void
    {
        throw new RuntimeException('create_personas_campo_table no es reversible: une y borra las tablas tecnicos y cuadrillas.');
    }

    private function pasarCuadrillas(): void
    {
        $cuadrillas = DB::table('cuadrillas')->orderBy('id')->get();

        foreach ($cuadrillas as $c) {
            DB::table('personas_campo')->insert([
                'id' => $c->id,
                'nombre' => $c->nombre,
                'tipo' => $c->tipo,
                'estado' => $c->estado,
                'tipo_documento' => $c->dni ? 1 : null,
                'numero_documento' => $this->documentoLibre($c->dni),
                'fecha_nacimiento' => $c->fecha_nacimiento,
                'celular' => $c->celular,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
                'deleted_at' => $c->deleted_at,
            ]);
        }

        foreach (DB::table('cuadrilla_empresa')->get() as $pivote) {
            DB::table('empresa_persona_campo')->insert([
                'persona_campo_id' => $pivote->cuadrilla_id,
                'empresa_id' => $pivote->empresa_id,
                'created_at' => $pivote->created_at,
                'updated_at' => $pivote->updated_at,
            ]);
        }

        // Postgres no mueve la secuencia cuando se inserta con id explícito
        // (MySQL sí mueve el AUTO_INCREMENT solo).
        if ($cuadrillas->isNotEmpty() && DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT setval(pg_get_serial_sequence('personas_campo', 'id'), (SELECT MAX(id) FROM personas_campo))");
        }
    }

    private function pasarTecnicos(): void
    {
        $nuevoId = [];

        foreach (DB::table('tecnicos')->orderBy('id')->get() as $t) {
            $documento = (string) $t->numero_documento_identificacion;
            $existente = DB::table('personas_campo')->where('numero_documento', $documento)->first();

            if ($existente) {
                DB::table('personas_campo')->where('id', $existente->id)->update([
                    'tipo_documento' => $existente->tipo_documento ?? $t->tipo_documento,
                    'email' => $existente->email ?? $t->email,
                    'password' => $existente->password ?? $t->password,
                ]);
                $id = $existente->id;
            } else {
                $id = DB::table('personas_campo')->insertGetId([
                    'nombre' => $t->nombre,
                    'tipo' => 'PERSONAL DIRECTO',
                    'estado' => 'ACTIVO',
                    'tipo_documento' => $t->tipo_documento,
                    'numero_documento' => $documento,
                    'email' => $t->email,
                    'password' => $t->password,
                    'created_at' => $t->created_at,
                    'updated_at' => $t->updated_at,
                    'deleted_at' => $t->deleted_at,
                ]);
            }

            if ($t->empresa_id) {
                DB::table('empresa_persona_campo')->updateOrInsert(
                    ['persona_campo_id' => $id, 'empresa_id' => $t->empresa_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            $nuevoId[$t->id] = $id;
        }

        // Fila por fila (por su propio id) para que un id viejo que coincide
        // con uno nuevo no se reapunte dos veces.
        foreach (['solicitud_tecnico', 'historials'] as $tabla) {
            foreach (DB::table($tabla)->select('id', 'tecnico_id')->get() as $fila) {
                DB::table($tabla)->where('id', $fila->id)->update([
                    'tecnico_id' => $nuevoId[$fila->tecnico_id] ?? $fila->tecnico_id,
                ]);
            }
        }

        DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\Tecnico')->delete();
    }

    /**
     * Un DNI repetido entre cuadrillas (cuadrillas.dni no era único) queda
     * solo en la primera; las demás pierden el documento en vez de romper
     * el índice único.
     */
    private function documentoLibre(?string $dni): ?string
    {
        $dni = $dni !== null ? trim($dni) : null;

        if ($dni === null || $dni === '' || DB::table('personas_campo')->where('numero_documento', $dni)->exists()) {
            return null;
        }

        return $dni;
    }
};
