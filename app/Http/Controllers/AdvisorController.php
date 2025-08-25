<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Events\NuevoMensajeChatAsesor; // Importar este evento

class AdvisorController extends Controller
{
    /**
     * Panel principal del asesor: lista de solicitudes.
     */
    public function index()
    {
        $asesor = Auth::user();

        if (!$asesor || $asesor->role_id !== 2) {
             Log::warning("Intento de acceso al panel de asesor sin ser asesor. User ID: " . ($asesor ? $asesor->id : 'null'));
            return redirect()->route('login')->withErrors(['error' => 'Acceso denegado: Solo asesores.']);
        }

        $solicitudes = Solicitud::with(['user', 'asesor', 'area'])
            ->where('asesor_id', $asesor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info("AdvisorController@index - Asesor ID: {$asesor->id} accedió al panel. Solicitudes encontradas: {$solicitudes->count()}");

        return view('advisor.panel', compact('asesor', 'solicitudes'));
    }

    /**
     * Chat entre asesor y usuario.
     */
    public function chat(Solicitud $solicitud)
    {
        abort_if($solicitud->asesor_id !== Auth::id(), 403);

        $mensajes = $solicitud->mensajes()->orderBy('created_at', 'asc')->get();

        // Para el frontend del asesor, necesitamos el guest_id o user_id del cliente
        $clienteUserId = $solicitud->user_id;
        $clienteGuestId = $solicitud->guest_id;
        $sessionId = $solicitud->session_id; // Asegurarse de tener la session_id del cliente

        return view('advisor.chat', compact('solicitud', 'mensajes', 'clienteUserId', 'clienteGuestId', 'sessionId'));
    }

    /**
     * Enviar mensaje desde el asesor al usuario.
     */
    public function sendMessage(Request $request, Solicitud $solicitud)
    {
        $request->validate(['contenido' => 'required|string|max:1000']);

        abort_if($solicitud->asesor_id !== Auth::id(), 403);

        $message = Message::create([
            'solicitud_id' => $solicitud->id,
            'session_id'   => $solicitud->session_id, // Usar el session_id de la solicitud
            'user_id' => Auth::id(), // El asesor es un User autenticado
            'guest_id' => null, // El asesor no es un guest
            'contenido' => $request->contenido,
            'sender_type' => 'asesor',
        ]);

        // ¡CLAVE! Transmitir el nuevo mensaje al canal del usuario/invitado
        broadcast(new NuevoMensajeChatAsesor($message))->toOthers();

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
     * Aceptar solicitud de asesoría.
     */
    public function accept(Solicitud $solicitud)
    {
        abort_if($solicitud->asesor_id !== Auth::id(), 403);
        $solicitud->update(['estado' => 'en_progreso']);
        Log::info("AdvisorController@accept - Solicitud #{$solicitud->id} aceptada por asesor ID: " . Auth::id());

        // Opcional: Notificar al usuario que su solicitud ha sido aceptada por el asesor
        $message = Message::create([
            'solicitud_id' => $solicitud->id,
            'session_id'   => $solicitud->session_id,
            'contenido' => "Tu solicitud ha sido aceptada por {$solicitud->asesor->name}. En breve comenzará a chatear contigo.",
            'sender_type' => 'ia', // O 'asesor' si quieres que parezca que el asesor lo dijo
        ]);
        broadcast(new NuevoMensajeChatAsesor($message))->toOthers();

        return back()->with('success', 'Solicitud aceptada.');
    }

    /**
     * Rechazar solicitud de asesoría.
     */
    public function reject(Solicitud $solicitud)
    {
        abort_if($solicitud->asesor_id !== Auth::id(), 403);
        $solicitud->update(['estado' => 'rechazada']);
        Log::info("AdvisorController@reject - Solicitud #{$solicitud->id} rechazada por asesor ID: " . Auth::id());
        
        // Opcional: Notificar al usuario que su solicitud ha sido rechazada
        $message = Message::create([
            'solicitud_id' => $solicitud->id,
            'session_id'   => $solicitud->session_id,
            'contenido' => "Tu solicitud ha sido rechazada por {$solicitud->asesor->name}. Por favor, inténtalo de nuevo o selecciona otro asesor.",
            'sender_type' => 'ia',
        ]);
        broadcast(new NuevoMensajeChatAsesor($message))->toOthers();

        return back()->with('success', 'Solicitud rechazada.');
    }
}
