<?php

namespace App\Events;

use App\Models\Solicitud;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class NuevaSolicitudAsesor implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $solicitud;

    public function __construct(Solicitud $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('asesor.' . $this->solicitud->asesor_id);
    }

    public function broadcastWith()
    {
        return [
            'solicitud' => [
                'id' => $this->solicitud->id,
                'guest_id' => $this->solicitud->guest_id,
                'estado' => $this->solicitud->estado,
                'mensaje' => $this->solicitud->mensaje ?? 'Sin mensaje inicial'
            ]
        ];
    }
}
