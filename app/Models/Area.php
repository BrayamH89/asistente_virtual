<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $fillable = ['nombre'];

    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class);
    }
}
