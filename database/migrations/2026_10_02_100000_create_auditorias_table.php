<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría: quién hizo qué y qué cambió, para poder rastrear de quién fue
 * un problema. Solo ACCIONES (crear, editar, eliminar, asignar, cargar,
 * iniciar sesión…), nunca visitas a pantallas. Solo se escribe (no se edita
 * ni se borra desde el sistema). Ver App\Models\Auditoria.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            // Quién: el usuario del guard que hizo la acción (Employee,
            // PersonaCampo desde la app, Company, Admin) + su nombre tal cual
            // era en ese momento (por si después lo renombran o eliminan).
            $table->string('usuario_type')->nullable();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('usuario_nombre')->nullable();

            // Qué: creado / actualizado / eliminado / restaurado / login /
            // asignado / importado / cierre_mes … y sobre qué registro.
            $table->string('accion', 40);
            $table->string('modulo', 60)->nullable();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('descripcion', 500)->nullable();

            // Qué cambió: solo los campos tocados, antes y después.
            $table->json('antes')->nullable();
            $table->json('despues')->nullable();

            // Desde dónde.
            $table->string('ip', 45)->nullable();
            $table->string('metodo', 10)->nullable();
            $table->string('url', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['usuario_type', 'usuario_id']);
            $table->index('accion');
            $table->index('modulo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
