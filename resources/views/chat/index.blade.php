@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 text-center">💬 Asistente Virtual</h2>

    <div id="chat-box" 
         class="border rounded p-3 mb-3 bg-light" 
         style="height: 60vh; overflow-y: auto;">
        @foreach ($messages as $msg)
            <div class="mb-2 d-flex {{ $msg->sender == 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="p-2 rounded" 
                    style="max-width: 75%; background: {{ $msg->sender == 'user' ? '#007bff' : '#e9ecef' }}; 
                            color: {{ $msg->sender == 'user' ? '#fff' : '#000' }};">
                    <strong>{{ ucfirst($msg->sender) }}:</strong> 
                    <div>{{ $msg->content }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('chat.send') }}" class="d-flex">
        @csrf
        <textarea name="message" 
                  rows="2" 
                  class="form-control me-2" 
                  placeholder="Escribe tu mensaje..."></textarea>
        <button class="btn btn-primary">Enviar</button>
    </form>
</div>

<script>
    // Auto-scroll al final
    var chatBox = document.getElementById('chat-box');
    chatBox.scrollTop = chatBox.scrollHeight;
</script>
@endsection

