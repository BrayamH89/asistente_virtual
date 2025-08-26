@extends('layouts.app') {{-- O tu layout específico para admin, ej. layouts.admin --}}

@section('content')
<div class="container mx-auto p-6 bg-white shadow-lg rounded-lg mt-6 text-center">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Bienvenido al Panel de Administración</h1>
    <p class="text-gray-600 mb-6">Desde aquí puedes gestionar varias configuraciones de tu aplicación.</p>
    
    <div class="space-y-4">
        <a href="{{ route('admin.files.index') }}" class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Gestionar Archivos de la IA
        </a>
        {{-- Puedes añadir más enlaces a otras secciones de administración aquí --}}
    </div>
</div>
@endsection
