<?php

namespace App\Http\Controllers;

use App\Events\NuevaSolicitudAsesor;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class SolicitudController extends Controller
{
    /**
     * Crear una solicitud de asesor desde el usuario o invitado.
     */
    public function crear(Request $request)
    {
        $request->validate([
            'asesor_id' => 'required|exists:users,id',
            'guest_id'  => 'required',
        ]);

        // Buscar asesor
        $asesorSeleccionado = User::where('role', 'asesor')
            ->findOrFail($request->asesor_id);

        // Crear mensaje en base de datos
        $message = Message::create([
            'sender_id'   => $request->guest_id, // invitado o usuario autenticado
            'receiver_id' => $asesorSeleccionado->id,
            'content'     => 'Solicitud para hablar con un asesor',
            'type'        => 'solicitud', // nuevo campo
            'status'      => 'pendiente'
        ]);

        // Notificar en tiempo real
        event(new NuevaSolicitudAsesor($message));

        return response()->json([
            'message' => 'Solicitud enviada correctamente',
            'data'    => $message
        ]);
    }

}
