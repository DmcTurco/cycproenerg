<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Equivalente a la hoja PARAM del Excel de Control Interno: una única
     * fila de configuración con los plazos y el semáforo. Editable desde
     * una pantalla de administración, no desde código (así los cambia el
     * negocio sin tocar el repo).
     */
    public function up(): void
    {
        Schema::create('parametros_control_internos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('plazo_construccion_dias_habiles')->default(20)
                ->comment('Construida después de este plazo = FUERA DE PLAZO (PARAM!C10)');
            $table->unsignedSmallInteger('semaforo_verde_dias')->default(10)
                ->comment('Semáforo VERDE hasta N días hábiles desde la suscripción (PARAM!C11)');
            $table->unsignedSmallInteger('semaforo_ambar_dias')->default(20)
                ->comment('Semáforo ÁMBAR hasta N días hábiles; más de esto es ROJO (PARAM!C12)');
            $table->unsignedSmallInteger('espera_tc_verde_dias')->default(15)
                ->comment('CONSTRUIDO: días calendario desde fin de interna sin TC, VERDE hasta aquí (PARAM!C15)');
            $table->unsignedSmallInteger('espera_tc_ambar_dias')->default(30)
                ->comment('Más de este valor de espera de TC = ROJO (PARAM!C16)');
            $table->decimal('meta_ind2', 5, 4)->default(0.9500)
                ->comment('Meta IND 2 (% en plazo) para el semáforo del cuadro (PARAM!C14)');
            $table->json('ambito_departamentos')->nullable()
                ->comment('Departamentos del portal que entran al control, ej. ["LIMA","CALLAO"] (PARAM!B24:B25)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parametros_control_internos');
    }
};
