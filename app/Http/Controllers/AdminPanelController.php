<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\File; // Importa el modelo File
use Illuminate\Support\Facades\Auth; // Importa Auth para verificar el rol

class AdminPanelController extends Controller
{
    /**
     * Muestra el panel de administración principal.
     */
    public function index()
    {
        // Opcional: Proteger esta ruta para que solo los administradores puedan acceder
        if (Auth::user()->role_id !== 1) { // Asumiendo que role_id 1 es para administradores
            abort(403, 'Acceso no autorizado al panel de administración.');
        }

        // Puedes añadir lógica aquí para mostrar un dashboard general de administración
        // Por ahora, redirigiremos o simplemente mostraremos una vista base.
        // Si tienes una vista general para el panel, podrías hacer:
        // return view('admin.dashboard');

        // Para este caso, dado que el objetivo es la gestión de archivos,
        // podemos redirigir a la vista de archivos o cargar datos relacionados.
        // O simplemente puedes renderizar una vista simple aquí
        return view('admin.dashboard', ['message' => 'Bienvenido al panel de administración.']);
    }

    /**
     * Muestra la vista para gestionar los archivos del chatbot.
     * Esto asume que tienes una ruta como '/admin/files' que apunta aquí.
     */
    public function filesIndex()
    {
        // Proteger esta ruta para que solo los administradores puedan acceder
        if (Auth::user()->role_id !== 1) { // Asumiendo que role_id 1 es para administradores
            abort(403, 'Acceso no autorizado para gestionar archivos.');
        }

        $files = File::with('user')->latest()->get(); // Obtiene todos los archivos para mostrarlos en la tabla
        return view('admin.files.index', compact('files'));
    }
}
