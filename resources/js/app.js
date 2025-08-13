import './bootstrap';

window.Echo.channel('solicitudes')
    .listen('NuevaSolicitudAsesor', (data) => {
        console.log('📢 Nueva solicitud recibida:', data);
        alert('Nuevo asesor solicitado: ' + data.usuario);
    });
