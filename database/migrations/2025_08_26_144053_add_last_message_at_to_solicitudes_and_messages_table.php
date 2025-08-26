<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->timestamp('last_message_at')->nullable()->after('estado');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('last_message_at')->nullable()->after('contenido'); // Asegurarse de que esté en la tabla messages
        });
    }

    public function down(): void
    {
        Schema::table('solicitudes', function (Blueprint $table) {
            $table->dropColumn('last_message_at');
        });
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('last_message_at');
        });
    }
};