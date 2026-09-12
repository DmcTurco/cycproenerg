<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Código corto de empresa (CYC / CLB) usado por Control Interno para
     * clasificar cada solicitud, igual que PARAM!B6:B7 en el Excel. Se
     * resuelve por RUC (numero_documento) en el seeder; el nombre del
     * portal queda solo como respaldo (igual que en el VBA original).
     */
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('codigo', 10)->nullable()->unique()->after('numero_documento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
