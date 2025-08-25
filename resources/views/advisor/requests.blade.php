@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">📩 Solicitudes de usuarios</h2>

    <table class="table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($solicitudes as $solicitud)
                <tr>
                    <td>{{ $solicitud->guest_id }}</td>
                    <td>
                        <span class="badge bg-{{ $solicitud->estado == 'pendiente' ? 'warning' : 'success' }}">
                            {{ ucfirst($solicitud->estado) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('advisors.chat', $solicitud->id) }}" class="btn btn-sm btn-primary">
                            Abrir chat
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
