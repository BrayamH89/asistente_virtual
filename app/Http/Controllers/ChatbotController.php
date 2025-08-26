<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use App\Models\Solicitud;
use App\Models\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Events\NuevaSolicitudAsesor;
use Illuminate\Support\Facades\Log;
use App\Events\NuevoMensajeChatAsesor;
use Carbon\Carbon;

class ChatbotController extends Controller
{
    /**
     * Muestra la vista del chat al usuario o invitado.
     */
    public function index(Request $request)
    {
        // Asegura que la sesión de chat tenga un ID
        if (!$request->session()->has('chat_session_id')) {
            $request->session()->put('chat_session_id', (string) Str::uuid());
        }

        $sessionId = $request->session()->get('chat_session_id');
        // Recupera los mensajes asociados a la sesión actual
        $messages = Message::where('session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Pasa los mensajes a la vista
        return view('chat.index', compact('messages'));
    }

    /**
     * Envía un mensaje y gestiona la respuesta del bot o la transferencia a un asesor.
     */
    public function send(Request $request)
    {
        try {
            // Valida el mensaje recibido
            $request->validate(['message' => 'required|string|max:1000']);
            $sessionId = $request->session()->get('chat_session_id');
            $mensaje = $request->message;

            Log::info("ChatbotController@send - Mensaje ORIGINAL recibido: '{$mensaje}' | Session ID: {$sessionId}");

            // --- Limpiar el mensaje del usuario de respuestas de la IA ---
            $mensajeLimpio = $this->cleanUserInput($mensaje);

            // --- Determinar si hay una solicitud de asesoría activa y EN PROGRESO ---
            $solicitudActiva = Solicitud::where('session_id', $sessionId)
                ->where('estado', 'en_progreso')
                ->first();
            $solicitudIdParaMensajes = $solicitudActiva ? $solicitudActiva->id : null;
            // ----------------------------------------------------------------------

            // Guarda el mensaje del usuario/invitado en la base de datos
            $userMessage = Message::create([
                'session_id'     => $sessionId,
                'guest_id'     => auth()->check() ? null : $sessionId,
                'user_id'      => auth()->id(),
                'solicitud_id' => $solicitudIdParaMensajes,
                'contenido'      => $mensaje,
                'sender_type'  => auth()->check() ? 'user' : 'guest',
                'last_message_at' => Carbon::now(),
            ]);

            // Si hay una solicitud activa, actualiza su tiempo de último mensaje
            if ($solicitudActiva) {
                $solicitudActiva->update(['last_message_at' => Carbon::now()]);
                session()->forget('inactivity_prompt_sent.' . $solicitudActiva->id);
            }

            // Detecta la intención del mensaje del usuario
            $intencion = $this->detectarIntencion($mensajeLimpio);

            Log::info("ChatbotController@send - Intención detectada: '{$intencion}' | Mensaje normalizado: '{$this->normalize($mensajeLimpio)}'");

            $respuesta = '';
            $botMessage = null;

            // --- Lógica para decidir si el BOT debe responder ---
            $shouldBotRespond = true;
            if ($solicitudActiva) {
                $shouldBotRespond = false;
            } elseif (session('esperando_asesor')) {
                $shouldBotRespond = true;
            } else {
                $shouldBotRespond = true;
            }
            // ---------------------------------------------------

            if ($shouldBotRespond) {
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
                        break;

                    case 'saludo':
                        $respuesta = "¡Hola! ¿En qué puedo ayudarte hoy?";
                        break;

                    case 'despedida':
                        $respuesta = "¡Gracias por comunicarte con nosotros! Hasta luego.";
                        break;

                    default:
                        // --- Lógica RAG: Consulta a Gemini con el contexto de los archivos ---
                        // Intenta encontrar un documento y usa su contenido
                        $documentResponse = $this->checkDocumentAndRespond($mensajeLimpio);
                        
                        if ($documentResponse) {
                            $respuesta = $documentResponse;
                        } else {
                            // Si no se encontró un documento o la respuesta no fue concluyente, usa Gemini
                            $respuesta = $this->consultarGemini($mensajeLimpio);
                        }
                        
                        Log::info("ChatbotController@send - Entrando DEFAULT. session('esperando_asesor'): " . (session('esperando_asesor') ? 'true' : 'false'));
                        
                        if (session('esperando_asesor')) {
                            $mensajeNormalizadoParaBusqueda = $this->normalize($mensajeLimpio);
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
                                    'last_message_at' => Carbon::now(),
                                ]);
                                $solicitudIdParaMensajes = $solicitud->id;

                                Message::where('session_id', $sessionId)
                                    ->whereNull('solicitud_id')
                                    ->update(['solicitud_id' => $solicitud->id]);

                                broadcast(new NuevaSolicitudAsesor($solicitud))->toOthers();
                                session()->forget('esperando_asesor');

                                $respuesta = "Has solicitado hablar con {$asesorSeleccionado->name}. En breve se pondrá en contacto contigo.";
                            } else {
                                $respuesta = "No encontré a ese asesor. ¿Podrías escribir el nombre exacto?";
                            }
                        }
                        break;
                }
            }

            if (!empty($respuesta)) {
                $botMessage = Message::create([
                    'session_id'     => $sessionId,
                    'guest_id'     => auth()->check() ? null : $sessionId,
                    'user_id'      => null,
                    'solicitud_id' => $solicitudIdParaMensajes,
                    'contenido'      => $respuesta,
                    'sender_type'  => 'ia',
                    'last_message_at' => Carbon::now(),
                ]);
            }

            if ($solicitudIdParaMensajes) {
                broadcast(new NuevoMensajeChatAsesor($userMessage))->toOthers();
                if ($botMessage) {
                    broadcast(new NuevoMensajeChatAsesor($botMessage))->toOthers();
                }
            }

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
                ] : null
            ]);
        } catch (\Throwable $e) {
            Log::error("Error en ChatbotController@send: " . $e->getMessage() . " en " . $e->getFile() . " linea " . $e->getLine());
            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado al enviar el mensaje.'], 500);
        }
    }
    
    /**
     * Verifica si el mensaje del usuario se refiere a un documento específico
     * y gestiona la respuesta en función de si el contenido del documento es válido.
     */
    private function checkDocumentAndRespond(string $userQuery): ?string
    {
        $normalizedQuery = $this->normalize($userQuery);
        $searchTerms = explode(' ', $normalizedQuery);
        
        // Busca documentos por nombre que coincidan con los términos de la consulta
        $file = File::where(function($query) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                if (strlen($term) > 2) { // Evita buscar con términos muy cortos
                    $query->orWhere('nombre', 'like', '%' . $term . '%');
                }
            }
        })->first();

        if ($file) {
            if (!empty($file->content) && !Str::contains($file->content, 'Error al extraer texto')) {
                // El documento se encontró y su contenido es válido.
                // Usamos la IA para generar una respuesta basada en el contenido.
                $promptWithContext = $this->buildPromptWithContext($userQuery, $file->content);
                return $this->callGeminiApi($promptWithContext);
            } else {
                // El documento se encontró, pero su contenido está vacío o tiene un error.
                Log::warning("Archivo '{$file->nombre}' ({$file->id}) encontrado, pero su contenido no es válido.");
                return "El documento **'{$file->nombre}'** se encontró en la base de datos, pero no puedo acceder a su contenido. Por favor, verifica que el archivo se haya procesado correctamente.";
            }
        }
        
        return null; // No se encontró un documento relevante
    }

    /**
     * Maneja la lógica de inactividad del bot (llamada por cron job).
     */
    public function handleInactivity()
    {
        $inactiveSolicitudes = Solicitud::where('estado', 'en_progreso')
            ->whereNotNull('last_message_at')
            ->where(function($query) {
                $query->where('last_message_at', '<=', Carbon::now()->subMinutes(15))
                    ->where('inactivity_prompt_sent', false);
            })
            ->orWhere(function($query) {
                $query->where('last_message_at', '<=', Carbon::now()->subMinutes(30))
                    ->where('inactivity_prompt_sent', true);
            })
            ->get();

        foreach ($inactiveSolicitudes as $solicitud) {
            $minutesSinceLastMessage = Carbon::now()->diffInMinutes($solicitud->last_message_at);

            if ($minutesSinceLastMessage >= 15 && !$solicitud->inactivity_prompt_sent) {
                $promptMessage = "Hola, veo que no hemos hablado en un rato. ¿Necesitas ayuda en algo más?";
                $botMessage = Message::create([
                    'session_id'     => $solicitud->session_id,
                    'guest_id'     => $solicitud->guest_id,
                    'user_id'      => null,
                    'solicitud_id' => $solicitud->id,
                    'contenido'      => $promptMessage,
                    'sender_type'  => 'ia',
                    'last_message_at' => Carbon::now(),
                ]);
                broadcast(new NuevoMensajeChatAsesor($botMessage))->toOthers();
                $solicitud->update(['inactivity_prompt_sent' => true]);
                Log::info("Bot sent inactivity prompt for Solicitud ID: {$solicitud->id}");
            } elseif ($minutesSinceLastMessage >= 30 && $solicitud->inactivity_prompt_sent) {
                $goodbyeMessage = "Parece que no hay más preguntas. ¡Gracias por usar nuestro servicio! Que tengas un excelente día.";
                $botMessage = Message::create([
                    'session_id'     => $solicitud->session_id,
                    'guest_id'     => $solicitud->guest_id,
                    'user_id'      => null,
                    'solicitud_id' => $solicitud->id,
                    'contenido'      => $goodbyeMessage,
                    'sender_type'  => 'ia',
                    'last_message_at' => Carbon::now(),
                ]);
                broadcast(new NuevoMensajeChatAsesor($botMessage))->toOthers();
                $solicitud->update(['estado' => 'finalizada', 'inactivity_prompt_sent' => false]);
                Log::info("Bot sent goodbye message and finalized Solicitud ID: {$solicitud->id}");
            }
        }
    }

    public function responder(Request $request)
    {
        Log::warning('ChatbotController@responder fue llamado. Esto sugiere que tu frontend está usando AJAX con esta ruta. Si es así, esta función DEBE contener la misma lógica que `send`.');
        return response()->json(['respuesta' => 'Función de respuesta AJAX no implementada para este flujo directo. Considera consolidar la lógica en `send` o copiarla aquí si usas AJAX.']);
    }

    /**
     * Limpia el mensaje del usuario de cualquier respuesta de la IA para evitar confusión.
     */
    private function cleanUserInput(string $text): string
    {
        $patterns = [
            'IA:',
            'IA: No tengo suficiente información',
            'Lo siento, pero no tengo acceso al contenido',
            'No tengo suficiente información para explicar el Manual de Usuario',
        ];

        foreach ($patterns as $pattern) {
            $pos = stripos($text, $pattern);
            if ($pos !== false) {
                $text = substr($text, 0, $pos);
                break;
            }
        }

        return trim($text);
    }

    /**
     * Normaliza un texto para procesamiento.
     */
    private function normalize($text)
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr(
            $text,
            'áéíóúüñ',
            'aeiouun'
        );
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Detecta la intención de un mensaje.
     */
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

    /**
     * Consulta el modelo Gemini, incluyendo contexto de archivos subidos.
     */
    private function consultarGemini(string $mensaje)
    {
        try {
            $promptWithContext = $this->buildPromptWithContext($mensaje, $this->retrieveRelevantContext($mensaje));
            return $this->callGeminiApi($promptWithContext);
        } catch (\Throwable $e) {
            Log::error("Error al contactar con Gemini en consultarGemini: " . $e->getMessage() . " en " . $e->getFile() . " linea " . $e->getLine());
            return 'Ocurrió un error inesperado al contactar con el servicio de asistencia. Por favor, intenta de nuevo más tarde.';
        }
    }
    
    /**
     * Llama a la API de Gemini con el prompt especificado.
     */
    private function callGeminiApi(string $prompt): string
    {
        $apiKey = env('GEMINI_API_KEY');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
        
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);
        
        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No tengo una respuesta clara para eso, pero puedo buscar un asesor si lo deseas.';
    }

    /**
     * Recupera contenido relevante de los archivos de la base de conocimiento.
     */
    private function retrieveRelevantContext(string $query): string
    {
        $queryNormalized = $this->normalize($query);
        $keywords = explode(' ', $queryNormalized);

        $relevantFilesContent = [];

        $filteredKeywords = array_filter($keywords, function($keyword) {
            return strlen($keyword) > 2 && !in_array($keyword, ['que', 'el', 'la', 'los', 'las', 'un', 'una', 'es', 'de', 'en', 'y', 'o', 'con', 'para']);
        });

        if (empty($filteredKeywords)) {
            return "";
        }

        $files = File::where(function($q) use ($filteredKeywords) {
            foreach ($filteredKeywords as $keyword) {
                $q->orWhere('content', 'like', '%' . $keyword . '%');
                $q->orWhere('nombre', 'like', '%' . $keyword . '%');
            }
        })
        ->limit(3)
        ->get();

        foreach ($files as $file) {
            if ($file->content && !Str::contains($file->content, 'Error al extraer texto del archivo DOCX')) {
                $relevantFilesContent[] = "--- Fuente: {$file->nombre} ---\n" . Str::limit($file->content, 1000, '...');
            } else {
                Log::warning("Archivo '{$file->nombre}' ({$file->id}) contiene mensaje de error o está vacío, no se usará como contexto.");
            }
        }

        return implode("\n\n", $relevantFilesContent);
    }

    /**
     * Construye el prompt para Gemini con el contexto recuperado.
     */
    private function buildPromptWithContext(string $userQuery, string $context): string
    {
        if (!empty($context)) {
            return "Eres un asistente virtual experto. Utiliza la siguiente información para responder a la pregunta del usuario. Si la pregunta no puede ser respondida con la información proporcionada, indica que no tienes suficiente información.\n\n"
                 . "INFORMACIÓN PROPORCIONADA:\n"
                 . $context . "\n\n"
                 . "PREGUNTA DEL USUARIO: " . $userQuery;
        } else {
            return "Eres un asistente virtual experto. Responde a la pregunta del usuario. Si no sabes la respuesta, indica que no tienes suficiente información.\n\n"
                 . "PREGUNTA DEL USUARIO: " . $userQuery;
        }
    }
}
