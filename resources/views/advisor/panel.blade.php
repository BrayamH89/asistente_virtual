@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Panel de Asesor</h1>

    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold text-gray-700 mb-2">Solicitudes recibidas</h2>

        <ul id="lista-solicitudes" class="mt-4 space-y-2">
            {{-- Verifica si hay solicitudes y las itera --}}
            @forelse($solicitudes as $solicitud)
                <li class="p-3 bg-gray-50 border border-gray-200 rounded-md shadow-sm">
                    <p class="font-semibold text-gray-900">Solicitud #{{ $solicitud->id }} - Estado: {{ ucfirst($solicitud->estado) }}</p>
                    <p class="text-gray-700">De: {{ $solicitud->user->name ?? 'Invitado (' . $solicitud->guest_id . ')' }}</p>
                    <p class="text-gray-600 text-sm">Fecha: {{ $solicitud->created_at->format('d/m/Y H:i') }}</p>
                    
                    {{-- Botones de acción --}}
                    <div class="mt-2 space-x-2">
                        @if($solicitud->estado === 'pendiente')
                            <form action="{{ route('solicitudes.aceptar', $solicitud) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">Aceptar</button>
                            </form>
                            <form action="{{ route('solicitudes.rechazar', $solicitud) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50">Rechazar</button>
                            </form>
                        @endif
                        <a href="{{ route('advisors.chat', $solicitud) }}" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">Ver Chat</a>
                    </div>
                </li>
            @empty
                <li class="text-gray-500">No tienes solicitudes pendientes.</li>
            @endforelse
        </ul>
    </div>
</div>

<script>
    // Asegúrate de que esta variable sea necesaria para tu frontend JS
    // y que el asesor_id en Solicitud sea el Auth::id() cuando se crea.
    window.userId = {{ auth()->check() ? auth()->id() : 'null' }}; 
</script>
@endsection

@push('scripts')
@vite(['resources/js/panelAsesor.js'])
@endpush
