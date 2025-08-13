<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Solicitud;
use App\Events\NuevaSolicitudAsesor;

class ChatbotController extends Controller
{
    public function index(Request $request)
    {
        $session_id = $request->session()->get('chat_session_id');

        if (!$session_id) {
            $session_id = (string) Str::uuid();
            $request->session()->put('chat_session_id', $session_id);
        }

        $messages = Message::where('session_id', $session_id)
            ->orderBy('created_at', 'asc') // Ordenar por fecha
            ->get();

        return view('chat.index', compact('messages'));
    }

    public function send(Request $request)
    {
        // Recuperar session_id
        $session_id = $request->session()->get('chat_session_id');
        if (!$session_id) {
            $session_id = (string) Str::uuid();
            $request->session()->put('chat_session_id', $session_id);
        }

        // Generar guest_id si no está logueado
        if (!auth()->check()) {
            if (!$request->session()->has('guest_id')) {
                $request->session()->put('guest_id', uniqid('guest_', true));
            }
            $guest_id = $request->session()->get('guest_id');
        }

        // Validar mensaje
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $input = trim($request->message);

        // Guardar mensaje del usuario
        Message::create([
            'session_id'=> $session_id,
            'content'   => $input,
            'sender'    => 'user',
            'user_id'   => auth()->check() ? auth()->id() : null,
            'guest_id'  => auth()->check() ? null : $guest_id,
        ]);

        // Si está esperando que elija asesor
        if (session('esperando_asesor')) {

            $inputNorm = $this->normalize($input);

            $asesores = User::where('role', 'asesor')->get();

            // 1) Coincidencia directa
            $asesorSeleccionado = $asesores->first(function ($a) use ($inputNorm) {
                return str_contains($inputNorm, $this->normalize($a->name));
            });

            // 2) Respaldo por tokens
            if (!$asesorSeleccionado) {
                $tokens = array_filter(
                    explode(' ', $inputNorm),
                    fn($t) => strlen($t) >= 3
                );

                $asesorSeleccionado = $asesores->first(function ($a) use ($tokens) {
                    $name = $this->normalize($a->name);
                    $hits = 0;
                    foreach ($tokens as $t) {
                        if (str_contains($name, $t)) $hits++;
                    }
                    return $hits >= 2 || (count($tokens) === 1 && $hits === 1);
                });
            }

            if ($asesorSeleccionado) {
                session()->forget('esperando_asesor');
                session(['asesor_id' => $asesorSeleccionado->id]);

                $solicitud = Solicitud::create([
                    'guest_id'  => auth()->check() ? null : $guest_id,
                    'user_id'   => auth()->check() ? auth()->id() : null,
                    'asesor_id' => $asesorSeleccionado->id,
                    'estado'    => 'pendiente',
                    'mensaje'   => "El usuario solicita hablar con el asesor.",
                ]);

                event(new NuevaSolicitudAsesor($solicitud));

                $respuesta = "✅ Has seleccionado al asesor {$asesorSeleccionado->name}. En breve se pondrá en contacto contigo.";
            } else {
                $respuesta = "❌ No encontré ningún asesor con ese nombre. Por favor, verifica y vuelve a escribirlo.";
            }
        }
        // Si no está esperando asesor, detectar intención
        else {
            $intencion = $this->detectarIntencion($input);

            if ($intencion === 'solicitud_asesor') {
                session(['esperando_asesor' => true]);

                $asesores = User::with('area')
                    ->where('role', 'asesor')
                    ->get();

                $lista = $asesores->map(function ($a) {
                    return "{$a->name} - Área: {$a->area->nombre}";
                })->implode("\n");

                $respuesta = "Parece que necesitas un asesor. Estos son los disponibles:\n\n$lista\n\nPor favor, escribe el nombre del asesor que deseas contactar.";
            } else {
                $respuesta = $this->consultarGemini($input);
            }
        }

        // Guardar respuesta del bot
        Message::create([
            'session_id'=> $session_id,
            'content'   => $respuesta,
            'sender'    => 'bot',
            'user_id'   => null,
            'guest_id'  => auth()->check() ? null : $guest_id,
        ]);

        return redirect()->back();
    }

    // 🔹 Función para normalizar texto
    private function normalize(string $s): string
    {
        $s = \Illuminate\Support\Str::ascii($s); // Quita acentos
        $s = mb_strtolower($s, 'UTF-8');         // Minúsculas
        $s = preg_replace('/\s+/', ' ', $s);     // Colapsa espacios
        return trim($s);
    }


    private function detectarIntencion(string $texto)
    {
        $texto = strtolower($texto);

        // Detecta frases genéricas de solicitud
        if (preg_match('/(quiero hablar|necesito|requiero).*(asesor|ayuda)/', $texto)) {
            return 'solicitud_asesor';
        }

        return 'general';
    }

    public function responder(Request $request)
    {
        $mensaje = $request->input('mensaje');
        $intencion = $this->detectarIntencion($mensaje);

        // Paso 1: Solicitud de asesor
        if ($intencion == 'solicitud_asesor') {
            session(['esperando_asesor' => true]);

            $asesores = User::with('area')
                ->where('role', 'asesor')
                ->get();

            $respuesta = "Claro, estos son los asesores disponibles:\n";
            foreach ($asesores as $asesor) {
                $respuesta .= "- {$asesor->name} (Área: {$asesor->area->nombre})\n";
            }
            $respuesta .= "\nEscribe el nombre del asesor con el que deseas hablar.";

            return response()->json(['respuesta' => $respuesta]);
        }

        // Paso 2: Selección de asesor
        if (session('esperando_asesor')) {
            $asesorSeleccionado = User::where('role', 'asesor')
                ->where('name', 'LIKE', "%{$mensaje}%")
                ->first();

            if ($asesorSeleccionado) {
                session()->forget('esperando_asesor');
                session(['asesor_id' => $asesorSeleccionado->id]);

                return response()->json([
                    'respuesta' => "✅ Has seleccionado al asesor *{$asesorSeleccionado->name}*. En breve se pondrá en contacto contigo."
                ]);
            } else {
                return response()->json([
                    'respuesta' => "❌ No encontré ningún asesor con ese nombre. Por favor, verifica y vuelve a escribirlo."
                ]);
            }
        }

        // Paso 3: Caso general -> consulta a Gemini
        $respuesta = $this->consultarGemini($mensaje);

        return response()->json(['respuesta' => $respuesta]);
    }

    private function consultarGemini(string $mensaje)
    {
        try {
            $apiKey = env('GEMINI_API_KEY');

            $url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent?key={$apiKey}";

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

            // 🔹 Decodificar respuesta
            $data = $response->json();

            // 🔹 Revisar si viene texto
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            } else {
                // Para depuración: ver qué devolvió la API
                \Log::error('Respuesta inesperada de Gemini:', $data);
                return 'No tengo una respuesta clara para eso.';
            }
        } catch (\Throwable $e) {
            return 'Error al contactar con Gemini: ' . $e->getMessage();
        }
    }

    public function enviarSolicitud(Request $request)
    {
        $asesorId = $request->asesor_id;
        $guestId = auth()->id(); // o el ID del visitante

        $solicitud = Solicitud::create([
            'guest_id' => $guestId,
            'asesor_id' => $asesorId,
            'estado' => 'pendiente',
        ]);

        event(new NuevaSolicitudAsesor($solicitud));

        return response()->json(['success' => true]);
    }
}
