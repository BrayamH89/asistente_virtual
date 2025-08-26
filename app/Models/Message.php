<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'session_id',    // Para usuarios no logueados
        'guest_id',      // Identificador del visitante no registrado
        'user_id',       // Si está autenticado
        'solicitud_id',  // Relación con solicitudes de asesoría
        'contenido',     // Texto del mensaje
        'sender_type' ,   // ESTO ES CRUCIAL: 'user', 'ia', 'asesor'
        'last_message_at',
    ];

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Opcional: para traducir sender_type a texto legible
    public function getSenderLabelAttribute(): string
    {
        return match ($this->sender_type) {
            'user'   => 'Tú', // O 'Usuario' si prefieres
            'guest'  => 'Tú', // El invitado también es 'Tú' desde su perspectiva
            'ia'     => 'IA',
            'asesor' => 'Asesor',
            default  => 'Desconocido', // O 'Yo' si prefieres
        };
    }
}