<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $sessionId = $request->session()->get('chat_session_id', (string) Str::uuid());
        $request->session()->put('chat_session_id', $sessionId);

        Message::create([
            'session_id'   => $sessionId,
            'guest_id'     => $sessionId,
            'user_id'      => auth()->id(),
            'contenido'    => $request->message,
            'sender_type'  => auth()->check() ? 'user' : 'guest',
        ]);

        return response()->json(['success' => true]);
    }
}
