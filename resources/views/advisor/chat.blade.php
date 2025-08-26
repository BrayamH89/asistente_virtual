<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat con Usuario</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.3/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }

        .chat-container {
            max-width: 900px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }

        h3.chat-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #chat-box {
            background: linear-gradient(to bottom, #fdfdfd, #f1f5f9);
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            height: 400px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
        }

        /* Scrollbar */
        #chat-box::-webkit-scrollbar { width: 6px; }
        #chat-box::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        #chat-box::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Burbujas base */
        .message-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 0.95rem;
            line-height: 1.5;
            word-wrap: break-word;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .message-bubble:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        }

        .message-bubble strong {
            display: block;
            font-size: 0.8rem;
            opacity: 0.85;
            margin-bottom: 5px;
        }

        .message-bubble small {
            display: block;
            font-size: 0.7rem;
            opacity: 0.6;
            margin-top: 6px;
            text-align: right;
        }

        /* Asesor */
        .message-asesor-own {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff;
            margin-left: auto;
            border-bottom-right-radius: 6px;
        }

        /* Usuario/Invitado */
        .message-user-client {
            background: #e5e7eb;
            color: #111827;
            margin-right: auto;
            border-bottom-left-radius: 6px;
        }

        /* IA */
        .message-ia-client {
            background: linear-gradient(135deg, #dbeafe, #bfdbfe);
            color: #1e3a8a;
            margin-right: auto;
            border-bottom-left-radius: 6px;
        }

        /* Textarea */
        #message-input {
            resize: none;
            min-height: 50px;
            max-height: 150px;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: border 0.25s ease, box-shadow 0.25s ease;
        }

        #message-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
            outline: none;
        }

        /* Botón */
        #chat-form button {
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            background: #2563eb;
            color: white;
            transition: background 0.25s ease, transform 0.1s ease;
        }

        #chat-form button:hover {
            background-color: #1d4ed8;
            transform: translateY(-1px);
        }

        #chat-form button:active { transform: translateY(1px); }

        @media (max-width: 768px) {
            .chat-container { margin: 20px; padding: 16px; }
            .message-bubble { max-width: 90%; }
            #chat-form { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <h3 class="chat-title">
            Chat con el usuario: {{ $solicitud->user->name ?? 'Invitado (' . $solicitud->guest_id . ')' }}
        </h3>

        <div id="chat-box">
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
                        <small>{{ $mensaje->created_at->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 text-center">No hay mensajes en este chat aún.</p>
            @endforelse
        </div>

        <form id="chat-form" class="mt-3 d-flex gap-2">
            @csrf
            <input type="hidden" name="solicitud_id" value="{{ $solicitud->id }}">
            <textarea name="contenido" id="message-input" class="form-control flex-grow-1"
                placeholder="Escribe tu mensaje..." required></textarea>
            <button type="submit" class="btn">
                Enviar
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chat-box');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const solicitudId = document.querySelector('input[name="solicitud_id"]').value;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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
                    <small>${message.created_at}</small>
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
        });
    </script>
</body>
</html>
