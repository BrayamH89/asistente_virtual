<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('solicituds', function (Blueprint $table) {
            $table->id();
            $table->string('guest_id')->nullable(); // Para usuarios no registrados
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario autenticado
            $table->unsignedBigInteger('asesor_id'); // Asesor seleccionado
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada'])->default('pendiente');
            $table->text('mensaje')->nullable();
            $table->timestamps();

            // Claves foráneas opcionales si quieres relaciones
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('asesor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicituds');
    }
};
