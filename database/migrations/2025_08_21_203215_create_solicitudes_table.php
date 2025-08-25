<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // usuario registrado
            $table->string('guest_id')->nullable(); // para invitados
            $table->unsignedBigInteger('asesor_id')->nullable(); // referencia a usuario con rol asesor
            $table->unsignedBigInteger('area_id')->nullable(); // opcional
            $table->string('estado')->default('pendiente'); // pendiente, en_progreso, cerrado
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('asesor_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('set null');
        });
    }

    public function down(): void {
        Schema::dropIfExists('solicitudes');
    }
};
