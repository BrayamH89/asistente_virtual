<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Solicitud extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'user_id',
        'asesor_id',
        'estado',
        'mensaje',
    ];

    public function asesor()
    {
        return $this->belongsTo(\App\Models\User::class, 'asesor_id');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
