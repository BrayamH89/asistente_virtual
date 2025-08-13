@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Solicitudes de Usuarios</h2>

    @foreach($requests as $request)
        <div class="card mb-3 p-3">
            <p><strong>Usuario:</strong> {{ $request->user->name }}</p>
            <form action="{{ route('advisor.accept', $request->id) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-success">Aceptar</button>
            </form>
            <form action="{{ route('advisor.reject', $request->id) }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-danger">Rechazar</button>
            </form>
        </div>
    @endforeach
</div>
@endsection
