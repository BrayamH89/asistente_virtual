<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Importar BelongsTo si aún no lo tienes

// *** ¡AÑADE ESTA LÍNEA! Esto es crucial para que Laravel encuentre el modelo Role ***
use App\Models\Role; 
use App\Models\Area; // Asegúrate de que este también esté importado para la relación de área

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id', // Asegúrate de que 'role_id' esté en fillable si lo asignas directamente
        'area_id', // Asegúrate de que 'area_id' esté en fillable si lo asignas directamente
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Define la relación del usuario con su rol.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id'); // Asegúrate que 'role_id' es el nombre de la FK
    }

    /**
     * Define la relación del usuario con su área.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function solicitudesCreadas()
    {
        return $this->hasMany(Solicitud::class, 'user_id');
    }

    public function solicitudesAsignadas()
    {
        return $this->hasMany(Solicitud::class, 'asesor_id');
    }

    public function mensajes()
    {
        return $this->hasMany(Message::class);
    }
}
