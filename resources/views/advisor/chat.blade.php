<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.3/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Chat con Usuario</title>

    {{-- TailwindCSS y/o Bootstrap ya los cargas en layouts.app con Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Contenedor del chat */
        #chat-box {
            background: linear-gradient(to bottom, #fdfdfd, #f1f5f9);
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            scroll-behavior: smooth;
            overflow-y: auto;
        }

        /* Scrollbar elegante */
        #chat-box::-webkit-scrollbar {
            width: 6px;
        }
        #chat-box::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        #chat-box::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Burbuja base */
        .message-bubble {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            position: relative;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.3s ease-in-out;
            word-wrap: break-word;
            transition: transform 0.2s ease;
        }

        .message-bubble:hover {
            transform: scale(1.02);
        }

        /* Colita estilo chat */
        .message-bubble::after {
            content: "";
            position: absolute;
            bottom: 0;
            width: 0;
            height: 0;
            border: 8px solid transparent;
        }

        /* Mensajes del asesor */
        .message-asesor-own {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            border-bottom-right-radius: 6px;
            margin-left: auto;
        }
        .message-asesor-own::after {
            right: -8px;
            border-left-color: #1d4ed8;
        }

        /* Mensajes del cliente */
        .message-user-client {
            background: #e5e7eb;
            color: #111827;
            border-bottom-left-radius: 6px;
            margin-right: auto;
        }
        .message-user-client::after {
            left: -8px;
            border-right-color: #e5e7eb;
        }

        /* Mensajes de la IA */
        .message-ia-client {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e3a8a;
            border-bottom-left-radius: 6px;
            margin-right: auto;
        }
        .message-ia-client::after {
            left: -8px;
            border-right-color: #bfdbfe;
        }

        /* Etiqueta de remitente */
        .message-bubble strong {
            display: block;
            font-size: 0.8rem;
            margin-bottom: 4px;
            opacity: 0.9;
        }

        /* Hora */
        .message-bubble small {
            display: block;
            font-size: 0.7rem;
            opacity: 0.65;
            margin-top: 6px;
            text-align: right;
        }

        /* Animación de entrada */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Textarea más estilizada */
        #message-input {
            resize: none;
            min-height: 52px;
            max-height: 140px;
            border-radius: 12px;
            transition: border 0.25s ease, box-shadow 0.25s ease;
            font-size: 0.95rem;
        }

        #message-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            outline: none;
        }

        /* Botón enviar */
        #chat-form button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border-radius: 12px;
            transition: background 0.25s ease, transform 0.1s ease;
        }

        #chat-form button:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        #chat-form button:active {
            transform: translateY(1px);
        }

    </style>
</head>
<body class="bg-gray-100">

    <div class="max-w-4xl mx-auto p-6 bg-white shadow-md rounded-lg mt-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">
            Chat con el usuario: {{ $solicitud->user->name ?? 'Invitado (' . $solicitud->guest_id . ')' }}
        </h3>
        
        {{-- Caja del chat --}}
        <div id="chat-box" class="h-96 overflow-y-auto border border-gray-300 rounded-lg p-4 mb-4 bg-gray-50 flex flex-col space-y-2">
            @forelse($mensajes as $mensaje)
                @php
                    $isAdvisorMessage = ($mensaje->sender_type === 'asesor');
                    $messageClass = match(true) {
                        $isAdvisorMessage => 'message-asesor-own',
                        in_array($mensaje->sender_type, ['user','guest']) => 'message-user-client',
                        $mensaje->sender_type === 'ia' => 'message-ia-client',
                        default => ''
                    };
                @endphp
                <div class="d-flex {{ $isAdvisorMessage ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="message-bubble {{ $messageClass }}">
                        <strong>{{ $mensaje->getSenderLabelAttribute() }}:</strong>
                        <div>{{ $mensaje->contenido }}</div>
                        <small class="text-xs text-right mt-1 block">
                            {{ $mensaje->created_at->format('d/m/Y H:i') }}
                        </small>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center">No hay mensajes en este chat aún.</p>
            @endforelse
        </div>

        {{-- Formulario de envío --}}
        <form id="chat-form" class="flex items-center space-x-2">
            @csrf
            <input type="hidden" name="solicitud_id" value="{{ $solicitud->id }}">
            <textarea name="contenido" id="message-input"
                class="flex-grow p-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                placeholder="Escribe tu mensaje..." required></textarea>
            <button type="submit"
                class="px-5 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                Enviar
            </button>
        </form>
    </div>

    {{-- Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chat-box');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const solicitudId = document.querySelector('input[name="solicitud_id"]').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const clienteUserId = {{ $clienteUserId ?? 'null' }};
            const clienteGuestId = "{{ $clienteGuestId ?? 'null' }}";
            const sessionId = "{{ $sessionId ?? 'null' }}";

            function addMessageToChat(message) {
                const div = document.createElement('div');
                const isAdvisorMessage = message.sender_type === 'asesor';

                let messageClass = '';
                if (isAdvisorMessage) messageClass = 'message-asesor-own';
                else if (['user','guest'].includes(message.sender_type)) messageClass = 'message-user-client';
                else if (message.sender_type === 'ia') messageClass = 'message-ia-client';

                div.classList.add('d-flex', isAdvisorMessage ? 'justify-content-end' : 'justify-content-start');

                const messageBubble = document.createElement('div');
                messageBubble.classList.add('message-bubble', messageClass);
                messageBubble.innerHTML = `
                    <strong>${message.sender_label}:</strong>
                    <div>${message.contenido}</div>
                    <small class="text-xs text-right mt-1 block">${message.created_at}</small>
                `;

                div.appendChild(messageBubble);
                chatBox.appendChild(div);
                chatBox.scrollTop = chatBox.scrollHeight;
            }

            chatForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const contenido = messageInput.value.trim();
                if (!contenido) return;

                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('contenido', contenido);
                formData.append('solicitud_id', solicitudId);

                try {
                    const response = await fetch(`/advisors/chat/${solicitudId}/send`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();

                    if (data.success) {
                        addMessageToChat(data.data);
                        messageInput.value = '';
                    } else {
                        alert('Error al enviar mensaje.');
                    }
                } catch (error) {
                    alert('Error de conexión.');
                }
            });

            if (clienteUserId) {
                window.Echo.private(`chat.user.${clienteUserId}`)
                    .listen('.new-message', (e) => addMessageToChat(e.message));
            } else if (clienteGuestId !== 'null') {
                window.Echo.private(`chat.guest.${clienteGuestId}`)
                    .listen('.new-message', (e) => addMessageToChat(e.message));
            }

            chatBox.scrollTop = chatBox.scrollHeight;
        });
    </script>
</body>
</html>
