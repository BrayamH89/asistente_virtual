<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class authMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $rol): Response
    {
        $user = Auth::user();

        if (!$user || $user->rol_id != $rol) {
            return redirect()->route('login')->withErrors(['error' => 'No tienes permisos para acceder a esta página.']);
        }

        return $next($request);
    }
}
