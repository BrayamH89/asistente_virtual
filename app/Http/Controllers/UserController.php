<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Mostrar formulario de login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Procesar login
    public function login(Request $request)
    {
        $validate = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $user = User::where('email', $request->email)->first();
        if($user){
            if(Hash::check($request->password, $user->password)){
                Auth::login($user);
                $request->session()->regenerate();
                switch($user->role_id){ // <--- CAMBIO AQUÍ: de rol_id a role_id
                    case 1: // Administrador
                        return redirect()->route('admin.dashboard');
                    default: // Asesor (o cualquier otro rol que no sea 1)
                        return redirect()->route('advisor.panel');
                }
            }else{
                return back()->withErrors(['password' => 'Contraseña incorrecta']);
            }
        }else{
            return back()->withErrors([
                'email' => 'El email no existe'
            ])->withInput();
        }
    }

    // Mostrar formulario de registro
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    // Procesar registro
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,asesor,usuario' // Note: This might need adjustment based on your role system
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role // This should probably be 'role_id' and map to an integer
        ]);

        Auth::login($user);

        return redirect()->route('user.dashboard');
    }

    // Cerrar sesión
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
