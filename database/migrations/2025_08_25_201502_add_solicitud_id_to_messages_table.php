<?php

// database/migrations/..._add_solicitud_id_to_messages_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'solicitud_id')) {
                $table->unsignedBigInteger('solicitud_id')->nullable()->after('session_id'); // O donde prefieras
                $table->foreign('solicitud_id')->references('id')->on('solicitudes')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'solicitud_id')) {
                $table->dropForeign(['solicitud_id']);
                $table->dropColumn('solicitud_id');
            }
        });
    }
};