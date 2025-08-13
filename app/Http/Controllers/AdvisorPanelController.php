<?php

namespace App\Http\Controllers;

use App\Models\AdvisorRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\User;    
use App\Models\Advisor; 

class AdvisorPanelController extends Controller
{

    public function index()
    {
        $advisor = Auth::user(); // Asumiendo que el asesor también es un usuario autenticado
        $messages = Message::where('advisor_id', $advisor->id)->latest()->get();

        return view('advisor.panel', compact('advisor', 'messages'));
    }

    // Enviar respuesta al usuario
    public function reply(Request $request)
    {
        $request->validate([
            'message_id' => 'required|exists:messages,id',
            'reply' => 'required|string|max:1000'
        ]);

        $message = Message::find($request->message_id);
        $message->reply = $request->reply;
        $message->save();

        return redirect()->back()->with('success', 'Respuesta enviada correctamente.');
    }

    public function showRequests()
    {
        $requests = AdvisorRequest::where('advisor_id', Auth::user()->advisor->id)
            ->where('status', 'pending')
            ->with('user')
            ->get();

        return view('advisor_panel.requests', compact('requests'));
    }

    public function acceptRequest($id)
    {
        $request = AdvisorRequest::findOrFail($id);
        $request->update(['status' => 'accepted']);

        // Aquí podrías redirigir al chat con el usuario
        return back()->with('success', 'Solicitud aceptada.');
    }

    public function rejectRequest($id)
    {
        $request = AdvisorRequest::findOrFail($id);
        $request->update(['status' => 'rejected']);

        return back()->with('success', 'Solicitud rechazada.');
    }

    public function accept($id)
    {
        $sol = Solicitud::findOrFail($id);
        $sol->estado = 'aceptada';
        $sol->save();

        // Aquí puedes crear/abrir un "chat" (redirigir al panel de chat entre asesor y guest)
        return redirect()->route('advisor.panel')->with('success','Solicitud aceptada');
    }

    public function reject($id)
    {
        $sol = Solicitud::findOrFail($id);
        $sol->estado = 'rechazada';
        $sol->save();
        return redirect()->route('advisor.panel')->with('success','Solicitud rechazada');
    }


}
