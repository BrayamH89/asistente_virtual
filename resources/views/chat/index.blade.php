<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- ¡Es crucial tener el token CSRF aquí! --}}
    <title>Chat Asistente Virtual</title>
    <!-- Incluye Bootstrap CDN para los estilos básicos y el grid, o tu archivo chat.css si ya incluye Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/chat.css') }}">
    <style>
        /* Estilos generales para el cuerpo y el contenedor del chat */
        body {
            font-family: 'Inter', sans-serif; /* Usamos Inter como fuente predeterminada */
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            max-width: 800px;
            width: 100%;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .chat-title {
            text-align: center;
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #chat-box {
            height: 60vh;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background-color: #fcfcfc;
            display: flex;
            flex-direction: column;
            gap: 10px; /* Espacio entre mensajes */
        }

        /* Estilos base para las burbujas de mensaje */
        .message-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 20px;
            line-height: 1.5;
            font-size: 0.95rem;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            word-wrap: break-word; /* Asegura que el texto largo se ajuste */
        }

        .message-bubble strong {
            display: block;
            margin-bottom: 5px;
            font-size: 0.8rem;
            opacity: 0.8;
        }

        /* Estilos para el usuario/guest */
        .message-user {
            background-color: #007bff; /* Azul primario */
            color: #fff;
            align-self: flex-end; /* Alineado a la derecha */
            border-bottom-right-radius: 5px; /* Pequeño ajuste para la esquina */
        }

        /* Estilos para el bot (IA) */
        .message-ia {
            background-color: #e9ecef; /* Gris claro */
            color: #333;
            align-self: flex-start; /* Alineado a la izquierda */
            border-bottom-left-radius: 5px; /* Pequeño ajuste para la esquina */
        }

        /* Estilos para el asesor */
        .message-asesor {
            background-color: #ffc107; /* Amarillo de advertencia */
            color: #333;
            align-self: flex-start; /* Alineado a la izquierda */
            border-bottom-left-radius: 5px; /* Pequeño ajuste para la esquina */
        }
        
        /* Formulario de envío */
        .chat-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 25px;
            resize: none; /* Evita que el usuario redimensione el textarea */
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .chat-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .chat-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .chat-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .chat-button:active {
            transform: translateY(0);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            .chat-title {
                font-size: 1.5rem;
            }
            .message-bubble {
                max-width: 90%;
            }
            .chat-form {
                flex-direction: column;
                gap: 15px;
            }
            .chat-input {
                width: 100%;
                border-radius: 8px; /* Ajuste para móviles */
            }
            .chat-button {
                width: 100%;
                border-radius: 8px; /* Ajuste para móviles */
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="chat-title">💬 Asistente Virtual</h2>

    <div id="chat-box">
        @foreach ($messages as $msg)
            @php
                $isUserMessage = in_array($msg->sender_type, ['user', 'guest']);
                $messageClass = ''; // Inicializar
                if ($isUserMessage) {
                    $messageClass = 'message-user';
                } elseif ($msg->sender_type === 'ia') {
                    $messageClass = 'message-ia';
                } elseif ($msg->sender_type === 'asesor') {
                    $messageClass = 'message-asesor';
                }
            @endphp

            <div class="d-flex {{ $isUserMessage ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="message-bubble {{ $messageClass }}">
                    <strong>{{ $msg->sender_label }}:</strong>
                    <div>{{ $msg->contenido }}</div>
                    <small class="text-xs text-right mt-1 block">{{ $msg->created_at->format('d/m/Y H:i') }}</small>
                </div>
            </div>
        @endforeach
    </div>

    <form id="chat-form" method="POST" action="{{ route('chat.send') }}" class="chat-form">
        @csrf
        <textarea name="message" id="message-input" class="chat-input" placeholder="Escribe tu mensaje..." required></textarea>
        <button type="submit" class="chat-button">Enviar</button>
    </form>
</div>

@push('scripts')
{{-- Asegúrate de que tienes Laravel Echo configurado y Vite cargando app.js --}}
@vite(['resources/js/app.js']) 
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chatBox = document.getElementById('chat-box');
        const chatForm = document.getElementById('chat-form');
        const messageInput = document.getElementById('message-input');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content'); // Asegúrate de que el meta tag esté en el <head>

        // Función para añadir mensajes al chat box
        function addMessageToChat(message) {
            const div = document.createElement('div');
            let isUserMessage = message.sender_type === 'user' || message.sender_type === 'guest';
            let messageClass = '';

            if (isUserMessage) {
                messageClass = 'message-user';
            } else if (message.sender_type === 'ia') {
                messageClass = 'message-ia';
            } else if (message.sender_type === 'asesor') {
                messageClass = 'message-asesor';
            }
            
            // Usamos las clases de Bootstrap d-flex y justify-content-end/start
            div.classList.add('d-flex', isUserMessage ? 'justify-content-end' : 'justify-content-start'); 

            const messageBubble = document.createElement('div');
            // Aplicamos las clases CSS personalizadas
            messageBubble.classList.add('message-bubble', messageClass);
            
            // Usamos sender_label y el contenido del mensaje
            messageBubble.innerHTML = `
                <strong>${message.sender_label}:</strong>
                <div>${message.contenido}</div>
                <small class="text-xs text-right mt-1 block">${message.created_at}</small>
            `;
            
            div.appendChild(messageBubble);
            chatBox.appendChild(div);
            chatBox.scrollTop = chatBox.scrollHeight; // Scroll al final
        }

        // Manejar el envío del formulario del usuario con AJAX
        chatForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const contenido = messageInput.value.trim();
            if (!contenido) return;

            const formData = new FormData();
            formData.append('_token', csrfToken);
            formData.append('message', contenido);

            try {
                const response = await fetch(`/chat/send`, { 
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    addMessageToChat(data.user_message); // Añadir el mensaje del usuario
                    // Solo añadir la respuesta del bot si existe (ej. si no se seleccionó asesor)
                    if (data.bot_message && data.bot_message.contenido) {
                         addMessageToChat(data.bot_message);  
                    }
                    messageInput.value = ''; // Limpiar el input
                } else {
                    console.error('Error al enviar mensaje:', data.message);
                    alert('Error al enviar mensaje.');
                }
            } catch (error) {
                console.error('Error de red al enviar mensaje:', error);
                alert('Error de conexión.');
            }
        });

        // Configurar Echo para escuchar nuevos mensajes del asesor
        const currentUserId = {{ auth()->check() ? auth()->id() : 'null' }};
        // Para el guest_id, usamos el session_id actual del cliente.
        const currentGuestId = "{{ session('chat_session_id') ?? 'null' }}"; 

        if (currentUserId) {
            window.Echo.private(`chat.user.${currentUserId}`)
                .listen('.new-message', (e) => {
                    console.log('Mensaje de asesor recibido en chat de usuario:', e.message);
                    addMessageToChat(e.message);
                });
        } else if (currentGuestId !== 'null') { // Solo si currentGuestId tiene un valor
            window.Echo.private(`chat.guest.${currentGuestId}`)
                .listen('.new-message', (e) => {
                    console.log('Mensaje de asesor recibido en chat de invitado:', e.message);
                    addMessageToChat(e.message);
                });
        }
        chatBox.scrollTop = chatBox.scrollHeight; // Scroll al fondo al cargar
    });
</script>
@endpush
</body>
</html>
