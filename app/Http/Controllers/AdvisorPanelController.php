<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use Illuminate\Support\Facades\Auth;

class AdvisorPanelController extends Controller
{

    public function index()
    {
        $asesor = Auth::user();
        $solicitudes = Solicitud::where('asesor_id', $asesor->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('advisor.panel', compact('asesor', 'solicitudes'));
    }
    
}
