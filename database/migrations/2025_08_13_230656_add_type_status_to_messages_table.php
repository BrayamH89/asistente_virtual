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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type')->default('mensaje'); // puede ser 'mensaje' o 'solicitud'
            $table->string('status')->default('pendiente'); // pendiente, aceptada, rechazada
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'status']);
        });
    }

};
