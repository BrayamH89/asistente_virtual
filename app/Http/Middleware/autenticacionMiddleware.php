<?php

namespace App\Http\Middleware; // <-- ¡ESTE NAMESPACE ES CRÍTICO!

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AutenticacionMiddleware // <-- ¡ESTE NOMBRE DE CLASE ES CRÍTICO! (AutenticacionMiddleware, no autenticacionMiddleware)
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     *          * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->withErrors(['error' => 'Debes iniciar sesión para acceder.']);
        }

        return $next($request);
    }
}
    