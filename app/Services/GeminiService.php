<?php

namespace App\Services;

class GeminiService
{
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY=AIzaSyD5Ns0LAZzVWfrOOII1_E2ynmovCQ0iQuY');
        $this->model = 'gemini-2.5-pro';
    }

    public function enviarPregunta($pregunta)
    {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:generateContent?key={$this->apiKey}";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $pregunta]
                    ]
                ]
            ]
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'ignore_errors' => true
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            return "❌ Error al conectarse a Gemini.";
        }

        $json = json_decode($result, true);

        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }

        return "⚠️ No se recibió respuesta válida de Gemini.";
    }
}
