window.Echo.private('asesor.' + window.userId)
    .listen('NuevaSolicitudAsesor', (e) => {
        console.log("📩 Nueva solicitud recibida:", e);

        const solicitudesList = document.getElementById('lista-solicitudes');
        if (solicitudesList) {
            const li = document.createElement('li');
            li.textContent = `Nueva solicitud de usuario: ${e.solicitud.guest_id}`;
            solicitudesList.appendChild(li);
        }

        if (window.Notification && Notification.permission === "granted") {
            new Notification("📩 Nueva solicitud", {
                body: `Un usuario (${e.solicitud.guest_id}) quiere hablar contigo`,
                icon: "/images/asesor-icon.png"
            });
        }
    });
