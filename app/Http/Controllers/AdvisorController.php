<?php

namespace App\Http\Controllers;

use App\Models\Advisor;
use App\Models\AdvisorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvisorController extends Controller
{

    public function requestAdvisor($asesorId)
    {
        AdvisorRequest::create([
            'user_id' => Auth::id(),
            'advisor_id' => $advisorId,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Solicitud enviada al asesor.']);
    }

    /**
     * Display a listing of the resource.
     */
    // Mostrar lista de asesores disponibles
    public function index()
    {
        $advisors = Advisor::with('area')->where('available', true)->get();

        return response()->json([
            'status' => 'success',
            'advisors' => $advisors
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Advisor $advisor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Advisor $advisor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Advisor $advisor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Advisor $advisor)
    {
        //
    }
}
