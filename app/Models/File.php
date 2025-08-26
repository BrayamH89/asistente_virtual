<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'solicitud_id', // Se mantiene por si hay casos específicos
        'nombre',       // Nombre original del archivo
        'ruta',         // Ruta de almacenamiento
        'mime_type',    // Tipo MIME
        'content',      // Contenido de texto extraído
    ];

    /**
     * Define la relación con el usuario que subió el archivo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define la relación con la solicitud a la que el archivo puede estar adjunto.
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class);
    }
}
