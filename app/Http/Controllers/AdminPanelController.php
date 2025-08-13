<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminPanelController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $solicitudes = \App\Models\Solicitud::where('asesor_id', $user->id)
                        ->where('estado','pendiente')
                        ->latest()
                        ->get();

        return view('advisor.panel', compact('solicitudes'));
    }

}
