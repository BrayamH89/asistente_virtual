<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Asesor</title>
    <link rel="stylesheet" href="{{ asset('css/panelAsesor.css') }}">
</head>
<body>

    <header class="header">
        <div class="header-container">
            <h1>Panel de Asesor</h1>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
            <a href="#" class="logout-btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                Cerrar Sesión
            </a>
    </header>

    <main>
        <div class="container">
            <h2>Solicitudes recibidas</h2>

            <ul id="lista-solicitudes">
                @forelse($solicitudes as $solicitud)
                    <li class="solicitud-item">
                        <div class="solicitud-info">
                            <p class="solicitud-title">
                                Solicitud #{{ $solicitud->id }}
                                <span class="estado {{ $solicitud->estado }}">
                                    {{ ucfirst($solicitud->estado) }}
                                </span>
                            </p>
                            <p class="solicitud-user">
                                De: {{ $solicitud->user->name ?? 'Invitado (' . $solicitud->guest_id . ')' }}
                            </p>
                            <p class="solicitud-fecha">
                                Fecha: {{ $solicitud->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        <div class="solicitud-actions">
                            @if($solicitud->estado === 'pendiente')
                                <form action="{{ route('solicitudes.aceptar', $solicitud) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn aceptar">Aceptar</button>
                                </form>
                                <form action="{{ route('solicitudes.rechazar', $solicitud) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn rechazar">Rechazar</button>
                                </form>
                            @endif
                            <a href="{{ route('advisors.chat', $solicitud) }}" class="btn chat">
                                Ver Chat
                            </a>
                        </div>
                    </li>
                @empty
                    <li class="no-solicitudes">No tienes solicitudes pendientes.</li>
                @endforelse
            </ul>
        </div>
    </main>

    <script>
        window.userId = {{ auth()->check() ? auth()->id() : 'null' }};
    </script>
    @vite(['resources/js/panelAsesor.js'])

</body>
</html>
