import Echo from 'laravel-echo';

window.Echo.channel('asesor.' + window.userId)
    .listen('NuevaSolicitudAsesor', (e) => {
        console.log("📩 Nueva solicitud recibida:", e);

        const solicitudesList = document.getElementById('solicitudes-list');
        if (solicitudesList) {
            const li = document.createElement('li');
            li.textContent = `Nueva solicitud de usuario ${e.message.sender_id}`;
            solicitudesList.appendChild(li);
        }

        // Notificación visual opcional
        if (window.Notification && Notification.permission === "granted") {
            new Notification("📩 Nueva solicitud", {
                body: "Un usuario quiere hablar contigo",
                icon: "/images/asesor-icon.png"
            });
        }
    });
