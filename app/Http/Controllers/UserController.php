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
    // public function login(Request $request)
    // {
    //     $credentials = $request->validate([
    //         'email' => ['required', 'email'],
    //         'password' => ['required']
    //     ]);

    //     if (Auth::attempt($credentials)) {
    //         $request->session()->regenerate();

    //         $roleName = Auth::user()->role->name;

    //         if ($roleName === 'administrador') {
    //             return redirect()->route('admin.panel');
    //         } elseif ($roleName === 'asesor') {
    //             return redirect()->route('advisor.panel');
    //         }

    //         // Si no es ninguno, cerramos sesión
    //         Auth::logout();
    //         return redirect()->route('login')->withErrors([
    //             'email' => 'No tienes permisos para acceder a esta área.',
    //         ]);
    //     }

    //     return back()->withErrors([
    //         'email' => 'Las credenciales no son correctas.',
    //     ]);
    // }

    public function login(Request $request)
    {
        // dd($request);
        $validate = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        $user = User::where('email', $request->email)->first();
        if($user){
            if(Hash::check($request->password, $user->password)){
                Auth::login($user);
                $request->session()->regenerate();
                switch($user->rol_id){
                    case 1: // Administrador
                        return redirect()->route('admin.panel');
                    default: // Asesor
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
            'role' => 'required|in:admin,asesor,usuario'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
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
