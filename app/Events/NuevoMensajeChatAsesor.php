<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class NuevoMensajeChatAsesor implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Crea una nueva instancia del evento.
     *
     * @param \App\Models\Message $message
     * @return void
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Obtiene los canales en los que el evento debería ser transmitido.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Transmitir al canal del usuario cliente (o invitado) asociado a la solicitud
        if ($this->message->solicitud->user_id) {
            return [new PrivateChannel('chat.user.' . $this->message->solicitud->user_id)];
        } else {
            // Si es un invitado, usar el guest_id de la solicitud para el canal
            return [new PrivateChannel('chat.guest.' . $this->message->solicitud->guest_id)];
        }
    }

    /**
     * El nombre de broadcast del evento.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'new-message';
    }

    /**
     * Obtiene los datos a transmitir.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'contenido' => $this->message->contenido,
            'sender_type' => $this->message->sender_type,
            'sender_label' => $this->message->getSenderLabelAttribute(),
            'created_at' => $this->message->created_at->format('d/m/Y H:i'),
        ];
    }
}
