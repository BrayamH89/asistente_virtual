// resources/js/bootstrap.js o app.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js'; // O el driver que uses

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: true
});