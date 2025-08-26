<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('mime_type')->nullable()->after('ruta');
            $table->longText('content')->nullable()->after('mime_type');
            // Si 'solicitud_id' no es nullable, primero debes hacer:
            // $table->unsignedBigInteger('solicitud_id')->nullable()->change();
            // Y luego añadir la foreign key si no existe:
            // $table->foreign('solicitud_id')->references('id')->on('solicitudes')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'mime_type', 'content']);
            // Si 'solicitud_id' lo cambiaste a nullable, aquí lo puedes revertir si es necesario:
            // $table->unsignedBigInteger('solicitud_id')->nullable(false)->change();
        });
    }
};