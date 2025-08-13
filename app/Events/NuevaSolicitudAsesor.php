<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevaSolicitudAsesor implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $asesorId;

    public function __construct(Message $message, $asesorId)
    {
        $this->message = $message;
        $this->asesorId = $asesorId;
    }

    public function broadcastOn()
    {
        return new Channel("asesor.{$this->asesorId}");
    }

    public function broadcastAs()
    {
        return 'nueva-solicitud';
    }
}
