<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Asistente Virtual</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
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
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            padding: 28px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .chat-title {
            text-align: center;
            color: #333;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        #chat-box {
            height: 60vh;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
            background: linear-gradient(to bottom, #fdfdfd, #f8fafc);
            display: flex;
            flex-direction: column;
            gap: 12px;
            scroll-behavior: smooth;
        }

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

        .message-bubble {
            max-width: 75%;
            padding: 12px 18px;
            border-radius: 18px;
            line-height: 1.5;
            font-size: 0.95rem;
            position: relative;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
            word-wrap: break-word;
            transition: transform 0.2s ease;
            animation: fadeIn 0.3s ease-in-out;
        }

        .message-bubble:hover {
            transform: scale(1.03);
        }

        .message-bubble strong {
            display: block;
            margin-bottom: 5px;
            font-size: 0.8rem;
            opacity: 0.85;
        }

        .message-user {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 6px;
        }

        .message-ia {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
            color: #1f2937;
            align-self: flex-start;
            border-bottom-left-radius: 6px;
        }

        .message-asesor {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #1f2937;
            align-self: flex-start;
            border-bottom-left-radius: 6px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .chat-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 12px;
            resize: none;
            font-size: 1rem;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .chat-input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 4px rgba(0, 123, 255, 0.15);
            outline: none;
        }

        .chat-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.25s ease, transform 0.1s ease;
        }

        .chat-button:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .chat-button:active {
            transform: translateY(1px);
        }

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
            .chat-input,
            .chat-button {
                width: 100%;
                border-radius: 10px;
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
                    $messageClass = $isUserMessage
                        ? 'message-user'
                        : ($msg->sender_type === 'ia'
                            ? 'message-ia'
                            : 'message-asesor');
                @endphp
                <div class="d-flex {{ $isUserMessage ? 'justify-content-end' : 'justify-content-start' }}">
                    <div class="message-bubble {{ $messageClass }}">
                        <strong>{{ $msg->sender_label }}:</strong>
                        <div>{{ $msg->contenido }}</div>
                        <small class="text-xs text-right mt-1 block">
                            {{ $msg->created_at->format('d/m/Y H:i') }}
                        </small>
                    </div>
                </div>
            @endforeach
        </div>

        <form id="chat-form" class="chat-form">
            @csrf
            <textarea name="message" id="message-input" class="chat-input" placeholder="Escribe tu mensaje..." required></textarea>
            <button type="submit" class="chat-button">Enviar</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chat-box');
            const chatForm = document.getElementById('chat-form');
            const messageInput = document.getElementById('message-input');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function addMessageToChat(message) {
                const div = document.createElement('div');
                const isUserMessage = ['user', 'guest'].includes(message.sender_type);
                let messageClass = isUserMessage
                    ? 'message-user'
                    : (message.sender_type === 'ia'
                        ? 'message-ia'
                        : 'message-asesor');

                div.classList.add('d-flex', isUserMessage ? 'justify-content-end' : 'justify-content-start');

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
                formData.append('message', contenido);

                try {
                    const response = await fetch(`/chat/send`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();

                    if (data.success) {
                        addMessageToChat(data.user_message);
                        if (data.bot_message?.contenido) {
                            addMessageToChat(data.bot_message);
                        }
                        messageInput.value = '';
                    } else {
                        alert('Error al enviar mensaje.');
                    }
                } catch (error) {
                    alert('Error de conexión.');
                }
            });

            const currentUserId = {{ auth()->check() ? auth()->id() : 'null' }};
            const currentGuestId = "{{ session('chat_session_id') ?? 'null' }}";

            if (currentUserId) {
                window.Echo.private(`chat.user.${currentUserId}`)
                    .listen('.new-message', (e) => addMessageToChat(e.message));
            } else if (currentGuestId !== 'null') {
                window.Echo.private(`chat.guest.${currentGuestId}`)
                    .listen('.new-message', (e) => addMessageToChat(e.message));
            }

            chatBox.scrollTop = chatBox.scrollHeight;
        });
    </script>
</body>
</html>
