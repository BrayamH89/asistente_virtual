<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Modifica la columna 'solicitud_id' para que sea nullable
            $table->unsignedBigInteger('solicitud_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Si necesitas revertir, puedes volver a hacerla no-nullable aquí
            // Esto solo funcionaría si no hay filas con solicitud_id NULL
            // $table->unsignedBigInteger('solicitud_id')->nullable(false)->change();
        });
    }
};
