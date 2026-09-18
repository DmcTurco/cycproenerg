<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// CM-4/CM-5: Cotización (a contratistas) / Vale de entrega (a personal
// directo) + su registro (equivalente a las hojas COTIZACION + COTIZACIONES
// del Excel — Turco pidió hacerlas juntas, "van juntos, como CI-3/CI-4").
//
// Una sola tabla para ambos tipos de documento (igual que el Excel: una
// sola hoja COTIZACIONES para cotizaciones y vales), diferenciados por
// `es_vale` (snapshot al emitir, no se recalcula si la cuadrilla cambia de
// tipo después — así el número/documento ya emitido no cambia de
// naturaleza retroactivamente).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            // Numeración correlativa por prefijo+fecha, ej. "C&C-260826-01"
            // / "VALE-260716-01" — ver Cotizacion::siguienteNumero().
            $table->string('numero', 30)->unique();
            $table->date('fecha');
            $table->foreignId('cuadrilla_id')->constrained()->restrictOnDelete();
            // true = VALE (PERSONAL DIRECTO, a costo, sin IGV, no descuenta
            // stock al emitir); false = COTIZACION (CONTRATISTA, con IGV,
            // descuenta stock al emitir).
            $table->boolean('es_vale');
            $table->decimal('monto_sin_igv', 10, 2)->default(0);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total_con_igv', 10, 2)->default(0);
            // PENDIENTE | VALE - USO INTERNO | DESCONTADO EN VALORIZACION
            $table->string('estado', 40);
            // Se llena a mano cuando el staff marca DESCONTADO EN
            // VALORIZACION (columna H de COTIZACIONES).
            $table->string('n_valorizacion', 60)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
