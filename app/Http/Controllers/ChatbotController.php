<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Solicitud;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Events\NuevaSolicitudAsesor;
use Illuminate\Support\Facades\Log;
use App\Events\NuevoMensajeChatAsesor; // Importar este evento

class ChatbotController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->session()->has('chat_session_id')) {
            $request->session()->put('chat_session_id', (string) Str::uuid());
        }

        $sessionId = $request->session()->get('chat_session_id');
        $messages = Message::where('session_id', $sessionId)
                           ->orderBy('created_at', 'asc')
                           ->get();

        return view('chat.index', compact('messages'));
    }

    public function send(Request $request)
    {
        try {
            $request->validate(['message' => 'required|string|max:1000']);
            $sessionId = $request->session()->get('chat_session_id');
            $mensaje = $request->message;

            Log::info("ChatbotController@send - Mensaje ORIGINAL recibido: '{$mensaje}' | Session ID: {$sessionId}");

            // --- Determinar si ya hay una solicitud_id activa para esta sesión ---
            $solicitudActiva = Solicitud::where('session_id', $sessionId)
                                        ->whereIn('estado', ['pendiente', 'en_progreso'])
                                        ->first();
            $solicitudIdParaMensajes = $solicitudActiva ? $solicitudActiva->id : null;
            // ------------------------------------------------------------------

            // Guardar el mensaje del usuario/guest
            $userMessage = Message::create([
                'session_id'   => $sessionId,
                'guest_id'     => auth()->check() ? null : $sessionId,
                'user_id'      => auth()->id(),
                'solicitud_id' => $solicitudIdParaMensajes, // Asocia al ID de solicitud si existe
                'contenido'    => $mensaje,
                'sender_type'  => auth()->check() ? 'user' : 'guest',
            ]);

            $intencion = $this->detectarIntencion($mensaje);

            Log::info("ChatbotController@send - Intención detectada: '{$intencion}' | Mensaje normalizado: '{$this->normalize($mensaje)}'");

            $respuesta = ''; 
            $botMessage = null; // Inicializar para que siempre esté definido

            switch ($intencion) {
                case 'solicitar_asesor':
                    session(['esperando_asesor' => true]);

                    $asesores = User::with('area')->where('role_id', 2)->get(); 
                    
                    if ($asesores->isEmpty()) {
                        $respuesta = "Actualmente no hay asesores disponibles, intenta más tarde.";
                    } else {
                        $respuesta = "Claro, estos son los asesores disponibles:\n";
                        foreach ($asesores as $asesor) {
                            $areaNombre = $asesor->area->nombre ?? 'Sin Área';
                            $respuesta .= "- {$asesor->name} (Área: {$areaNombre})\n";
                        }
                        $respuesta .= "\nEscribe el nombre del asesor con quien deseas hablar.";
                    }
                    // La respuesta de la IA se guardará después del switch
                    break;

                case 'saludo':
                    $respuesta = "¡Hola! ¿En qué puedo ayudarte hoy?";
                    break;

                case 'despedida':
                    $respuesta = "¡Gracias por comunicarte con nosotros! Hasta luego.";
                    break;

                default:
                    Log::info("ChatbotController@send - Entrando DEFAULT. session('esperando_asesor'): " . (session('esperando_asesor') ? 'true' : 'false'));
                    
                    if (session('esperando_asesor')) {
                        $mensajeNormalizadoParaBusqueda = $this->normalize($mensaje);
                        $asesorSeleccionado = User::where('role_id', 2)
                            ->whereRaw("LOWER(name) LIKE ?", ["%{$mensajeNormalizadoParaBusqueda}%"])
                            ->first();

                        Log::info("ChatbotController@send - Buscando asesor: '%{$mensajeNormalizadoParaBusqueda}%'. Resultado: " . ($asesorSeleccionado ? $asesorSeleccionado->name : 'Ninguno'));

                        if ($asesorSeleccionado) {
                            $solicitud = Solicitud::create([
                                'session_id' => $sessionId,
                                'user_id'    => auth()->id(),
                                'guest_id'   => auth()->check() ? null : $sessionId,
                                'asesor_id'  => $asesorSeleccionado->id,
                                'estado'     => 'pendiente',
                            ]);
                            $solicitudIdParaMensajes = $solicitud->id; 

                            // *** CLAVE: ASOCIA TODOS LOS MENSAJES PREVIOS DE LA SESIÓN A LA NUEVA SOLICITUD ***
                            Message::where('session_id', $sessionId)
                                   ->whereNull('solicitud_id') // Solo los mensajes no asociados
                                   ->update(['solicitud_id' => $solicitud->id]);
                            // *****************************************************************************

                            broadcast(new NuevaSolicitudAsesor($solicitud))->toOthers();
                            session()->forget('esperando_asesor');

                            $respuesta = "Has solicitado hablar con {$asesorSeleccionado->name}. En breve se pondrá en contacto contigo.";
                        } else {
                            $respuesta = "No encontré a ese asesor. ¿Podrías escribir el nombre exacto?";
                        }
                    } else {
                        $respuesta = $this->consultarGemini($mensaje);
                    }
                    break;
            }

            // Guardar la respuesta del bot/IA
            // Solo si $respuesta no está vacía o si se generó una respuesta de IA
            if (!empty($respuesta) || ($intencion === 'general' && !$solicitudActiva && !session('esperando_asesor'))) {
                $botMessage = Message::create([
                    'session_id'   => $sessionId,
                    'guest_id'     => auth()->check() ? null : $sessionId,
                    'user_id'      => null, // La IA no tiene user_id
                    'solicitud_id' => $solicitudIdParaMensajes, // Asocia la respuesta de la IA a la solicitud si existe
                    'contenido'    => $respuesta,
                    'sender_type'  => 'ia',
                ]);
            }
            
            // --- Broadcasting de mensajes del USUARIO (solo si hay una solicitud activa) ---
            if ($solicitudIdParaMensajes) {
                broadcast(new NuevoMensajeChatAsesor($userMessage))->toOthers(); // Notifica al asesor del mensaje del usuario
                if ($botMessage) { // Si la IA también respondió, notifica al asesor sobre esa respuesta
                    broadcast(new NuevoMensajeChatAsesor($botMessage))->toOthers();
                }
            }
            // --------------------------------------------------------------------------

            // Retornar JSON para la solicitud AJAX del usuario
            return response()->json([
                'success' => true,
                'user_message' => [
                    'id' => $userMessage->id,
                    'contenido' => $userMessage->contenido,
                    'sender_type' => $userMessage->sender_type,
                    'sender_label' => $userMessage->getSenderLabelAttribute(),
                    'created_at' => $userMessage->created_at->format('d/m/Y H:i'),
                ],
                'bot_message' => $botMessage ? [
                    'id' => $botMessage->id,
                    'contenido' => $botMessage->contenido,
                    'sender_type' => $botMessage->sender_type,
                    'sender_label' => $botMessage->getSenderLabelAttribute(),
                    'created_at' => $botMessage->created_at->format('d/m/Y H:i'),
                ] : null // Null si no hay mensaje del bot
            ]);

        } catch (\Throwable $e) {
            Log::error("Error en ChatbotController@send: " . $e->getMessage() . " en " . $e->getFile() . " linea " . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado al enviar el mensaje.'], 500);
        }
    }

    public function responder(Request $request) 
    {
        Log::warning('ChatbotController@responder fue llamado. Esto sugiere que tu frontend está usando AJAX con esta ruta. Si es así, esta función DEBE contener la misma lógica que `send`.');
        return response()->json(['respuesta' => 'Función de respuesta AJAX no implementada para este flujo directo. Considera consolidar la lógica en `send` o copiarla aquí si usas AJAX.']);
    }

    private function normalize($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, 
            'áéíóúüñ',
            'aeiouun'
        );
        
        $text = preg_replace('/[^a-z0-9\s]/', '', $text); 
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }

    private function detectarIntencion($mensaje)
    {
        $mensajeNormalizado = $this->normalize($mensaje);

        $patronesRegex = [
            'solicitar_asesor' => '/\b(asesor|ayuda|hablar|contacto|soporte|consultor|orientacion|asistencia|un\s+asesor|una\s+ayuda|un\s+consultor)\b|\b(necesito|quiero|busco|requiero)\s+(un|una)?\s*(asesor|ayuda|orientacion|asistencia|consultor|soporte)\b/i',
            'saludo'           => '/\b(hola|buenos dias|buenas tardes|buenas noches|que tal|que onda)\b/i',
            'despedida'        => '/\b(adios|gracias|hasta luego|chao|finalizar)\b/i'
        ];

        foreach ($patronesRegex as $intencion => $regex) {
            if (preg_match($regex, $mensajeNormalizado)) {
                return $intencion;
            }
        }

        return 'general';
    }

    private function consultarGemini(string $mensaje)
    {
        try {
            $apiKey = env('GEMINI_API_KEY');
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $mensaje],
                        ],
                    ],
                ],
            ]);
            
            $data = $response->json();
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No tengo una respuesta clara para eso.';
        } catch (\Throwable $e) {
            Log::error("Error al contactar con Gemini en consultarGemini: " . $e->getMessage() . " en " . $e->getFile() . " linea " . $e->getLine());
            return 'Error al contactar con Gemini: ' . $e->getMessage() . '.';
        }
    }
}
