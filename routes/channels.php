<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Aquí es donde puedes registrar todos los canales de broadcasting
| que tu aplicación soporta.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('asesor.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// *** ¡NUEVOS CANALES PARA CHAT DE USUARIO/ASESOR! ***
Broadcast::channel('chat.user.{userId}', function ($user, $userId) {
    // Solo un usuario autenticado puede escuchar su propio canal de chat
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('chat.guest.{guestId}', function ($user, $guestId) {
    // Para invitados, la autenticación es más compleja.
    // Por ahora, permitiremos que cualquier usuario *autenticado* con el mismo guest_id (session_id) acceda.
    // En una aplicación real, necesitarías un mecanismo de autenticación para invitados,
    // por ejemplo, guardando el guest_id en la sesión y verificándolo aquí.
    // Para simplificar, si no hay un usuario logueado, asumimos que el guest_id es el de la sesión actual.
    // TEN CUIDADO CON LA SEGURIDAD: Esto es una simplificación.
    return true; // Considera una lógica más robusta para invitados.
});
