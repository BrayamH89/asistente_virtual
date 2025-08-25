<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany; // Asegúrate de importar HasMany

class Solicitud extends Model
{
    protected $table = 'solicitudes'; // Esto ya lo tenías, ¡es correcto!

    protected $fillable = [
        'session_id',
        'user_id',
        'guest_id',
        'asesor_id',
        'area_id',
        'estado',
        // 'mensaje' // Si tienes esta columna en tu tabla, añádela
    ];

    /**
     * Define la relación con el usuario que creó la solicitud (si está logueado).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Define la relación con el asesor asignado a esta solicitud.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id'); // Asumiendo que 'asesor_id' es la FK en Solicitud
    }

    /**
     * Define la relación con el área de la solicitud (si aplica).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Define la relación con los mensajes asociados a esta solicitud.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mensajes(): HasMany
    {
        // Asume que la tabla 'messages' tiene una columna 'solicitud_id' que enlaza con 'solicitudes.id'
        return $this->hasMany(Message::class, 'solicitud_id');
    }
}
