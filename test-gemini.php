<?php

// ✅ Reemplaza con tu API KEY válida
$apiKey = "AIzaSyD5Ns0LAZzVWfrOOII1_E2ynmovCQ0iQuY";

// ✅ Usamos el modelo más actual disponible
$url = "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-pro:generateContent?key={$apiKey}";

$data = [
    'contents' => [
        [
            'parts' => [
                ['text' => '¿Cuál es la capital de Colombia?']
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

if ($result === FALSE) {
    echo "❌ Error al conectarse a la API.\n";
    if (isset($http_response_header)) {
        echo "📡 Encabezados de respuesta:\n";
        print_r($http_response_header);
    }
} else {
    $json = json_decode($result, true);
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        echo "✅ Respuesta del modelo:\n\n";
        echo $json['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "⚠️ No se recibió texto de respuesta.\n";
        echo $result; // Mostrar todo en caso de error
    }
}
