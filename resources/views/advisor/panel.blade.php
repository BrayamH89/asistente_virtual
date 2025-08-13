@extends('layouts.app')

@section('content')

<div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Panel de Asesor</h1>

    <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="text-lg font-semibold text-gray-700 mb-2">Solicitudes recibidas</h2>

        <p class="text-gray-500">No tienes solicitudes pendientes.</p>

        <ul id="lista-solicitudes" class="mt-4 space-y-2">
            <!-- Aquí se insertarán las solicitudes nuevas -->
        </ul>
    </div>
</div>

<script>
    window.userId = {{ auth()->check() ? auth()->id() : 'null' }};
</script>


@endsection

@push('scripts')
@vite(['resources/js/panelAsesor.js'])
@endpush
