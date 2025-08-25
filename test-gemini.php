<?php

// IMPORTANTE: Reemplaza "TU_API_KEY_PERSONAL_Y_VALIDA_AQUI" con tu clave de API real.
// Puedes obtener una en: https://aistudio.google.com/app/apikey
$apiKey = "AIzaSyD5Ns0LAZzVWfrOOII1_E2ynmovCQ0iQuY";

// ✅ Usamos el modelo más actual disponible públicamente: gemini-1.5-pro
// Es crucial usar el modelo correcto y el endpoint 'v1beta'
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";

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
        'ignore_errors' => true // Permite ver las respuestas de error de la API
    ],
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "❌ Error al conectarse a la API. Verifica tu conexión a internet o la URL de la API.\n";
    if (isset($http_response_header)) {
        echo "📡 Encabezados de respuesta:\n";
        print_r($http_response_header);
    }
} else {
    $json = json_decode($result, true);

    // Verificamos si hay errores explícitos en la respuesta JSON
    if (isset($json['error'])) {
        echo "❌ Error de la API:\n";
        echo "Código: " . $json['error']['code'] . "\n";
        echo "Mensaje: " . $json['error']['message'] . "\n";
        if (isset($json['error']['status'])) {
            echo "Estado: " . $json['error']['status'] . "\n";
        }
    }
    // Si no hay errores, intentamos obtener el texto de la respuesta
    else if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        echo "✅ Respuesta del modelo:\n\n";
        echo $json['candidates'][0]['content']['parts'][0]['text'] . "\n";
    } else {
        echo "⚠️ No se recibió texto de respuesta esperado. Esto podría ser por: \n";
        echo "   - Una API Key inválida.\n";
        echo "   - Un modelo no disponible o incorrecto.\n";
        echo "   - Un problema con la solicitud (ej. contenido bloqueado por políticas de seguridad).\n\n";
        echo "Respuesta completa de la API:\n";
        echo $result; // Mostrar todo en caso de error para depuración
    }
}