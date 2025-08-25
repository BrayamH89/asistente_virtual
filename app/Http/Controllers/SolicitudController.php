<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Solicitud;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SolicitudController extends Controller
{
    public function crear(Request $request)
    {
        $request->validate([
            'asesor_id' => 'required|exists:users,id',
        ]);

        $guestId = Auth::check() ? null : (session()->get('guest_id') ?? Str::uuid());

        $solicitud = Solicitud::create([
            'user_id' => Auth::id(),
            'guest_id' => $guestId,
            'asesor_id' => $request->asesor_id,
            'estado' => 'pendiente',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Solicitud creada correctamente.',
            'solicitud' => $solicitud
        ]);
    }
}
