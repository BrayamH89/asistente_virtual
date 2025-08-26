<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\NuevoMensajeChatAsesor;
use Carbon\Carbon; // Importar Carbon

class AdvisorController extends Controller
{
    /**
     * Muestra el panel principal del asesor con la lista de solicitudes.
     */
    public function index()
    {
        $asesor = Auth::user();

        // Verifica que el usuario autenticado sea un asesor
        if (!$asesor || $asesor->role_id !== 2) {
             Log::warning("Intento de acceso al panel de asesor sin ser asesor. User ID: " . ($asesor ? $asesor->id : 'null'));
            return redirect()->route('login')->withErrors(['error' => 'Acceso denegado: Solo asesores.']);
        }

        // Recupera las solicitudes asignadas a este asesor
        $solicitudes = Solicitud::with(['user', 'asesor', 'area'])
            ->where('asesor_id', $asesor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info("AdvisorController@index - Asesor ID: {$asesor->id} accedió al panel. Solicitudes encontradas: {$solicitudes->count()}");

        return view('advisor.panel', compact('asesor', 'solicitudes'));
    }

    /**
     * Muestra la vista de chat entre el asesor y un usuario/invitado.
     */
    public function chat(Solicitud $solicitud)
    {
        // Asegura que solo el asesor asignado pueda ver el chat
        abort_if($solicitud->asesor_id !== Auth::id(), 403);

        // Carga los mensajes de la solicitud
        $mensajes = $solicitud->mensajes()->orderBy('created_at', 'asc')->get();

        // Pasa los IDs necesarios para el frontend (canales de Echo)
        $clienteUserId = $solicitud->user_id;
        $clienteGuestId = $solicitud->guest_id;
        $sessionId = $solicitud->session_id;

        return view('advisor.chat', compact('solicitud', 'mensajes', 'clienteUserId', 'clienteGuestId', 'sessionId'));
    }

    /**
     * Envía un mensaje desde el asesor al usuario.
     */
    public function sendMessage(Request $request, Solicitud $solicitud)
    {
        $request->validate(['contenido' => 'required|string|max:1000']);

        abort_if($solicitud->asesor_id !== Auth::id(), 403);

        // Crea el mensaje del asesor
        $message = Message::create([
            'solicitud_id' => $solicitud->id,
            'session_id'   => $solicitud->session_id,
            'user_id' => Auth::id(),
            'guest_id' => null,
            'contenido' => $request->contenido,
            'sender_type' => 'asesor',
            'last_message_at' => Carbon::now(), // Registra el tiempo del mensaje
        ]);

        // Actualiza el tiempo del último mensaje en la solicitud
        $solicitud->update(['last_message_at' => Carbon::now()]);
        // Si el asesor envía un mensaje, se reinicia el flag de inactividad para esa solicitud
        session()->forget('inactivity_prompt_sent.' . $solicitud->id); 

        // Transmite el nuevo mensaje al canal del usuario/invitado
        broadcast(new NuevoMensajeChatAsesor($message))->toOthers();

        // Retorna la respuesta JSON
        return response()->json([
            'success' => true,
            'message' => 'Mensaje enviado.',
            'data' => [
                'id' => $message->id,
                'contenido' => $message->contenido,
                'sender_type' => $message->sender_type,
                'sender_label' => $message->getSenderLabelAttribute(),
                'created_at' => $message->created_at->format('d/m/Y H:i'),
            ]
        ]);
    }

    /**
     * Aceptar una solicitud de asesoría.
     */
    public function accept(Solicitud $solicitud)
    {
        abort_if($solicitud->asesor_id !== Auth::id(), 403);
        
        // Actualiza el estado de la solicitud y el tiempo del último mensaje
        $solicitud->update([
            'estado' => 'en_progreso',
            'last_message_at' => Carbon::now(), // Actualiza al aceptar la solicitud
            'inactivity_prompt_sent' => false, // Reinicia el flag de inactividad
        ]);
        Log::info("AdvisorController@accept - Solicitud #{$solicitud->id} aceptada por asesor ID: " . Auth::id());

        // Notifica al usuario que su solicitud ha sido aceptada
        $message = Message::create([
            'solicitud_id' => $solicitud->id,
            'session_id'   => $solicitud->session_id,
            'contenido' => "Tu solicitud ha sido aceptada por {$solicitud->asesor->name}. Ahora puedes chatear directamente con él/ella.",
            'sender_type' => 'ia', 
            'last_message_at' => Carbon::now(),
        ]);
        broadcast(new NuevoMensajeChatAsesor($message))->toOthers();
        // Al aceptar, se reinicia cualquier flag de inactividad en la sesión del usuario para esta solicitud
        session()->forget('inactivity_prompt_sent.' . $solicitud->id); 


        return back()->with('success', 'Solicitud aceptada.');
    }

    /**
     * Rechazar una solicitud de asesoría.
     */
    public function reject(Solicitud $solicitud)
    {
        abort_if($solicitud->asesor_id !== Auth::id(), 403);
        $solicitud->update(['estado' => 'rechazada']);
        Log::info("AdvisorController@reject - Solicitud #{$solicitud->id} rechazada por asesor ID: " . Auth::id());
        
        // Notifica al usuario que su solicitud ha sido rechazada
        $message = Message::create([
            'solicitud_id' => $solicitud->id,
            'session_id'   => $solicitud->session_id,
            'contenido' => "Tu solicitud ha sido rechazada por {$solicitud->asesor->name}. Por favor, inténtalo de nuevo o selecciona otro asesor.",
            'sender_type' => 'ia',
            'last_message_at' => Carbon::now(),
        ]);
        broadcast(new NuevoMensajeChatAsesor($message))->toOthers();
        session()->forget('inactivity_prompt_sent.' . $solicitud->id); // Limpiar por si acaso


        return back()->with('success', 'Solicitud rechazada.');
    }
}
