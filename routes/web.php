<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdvisorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatbotController;
use App\Http\Middleware\authMiddleware;
use App\Http\Middleware\autenticacionMiddleware;
/*
|--------------------------------------------------------------------------
| Rutas de autenticación y usuarios
|--------------------------------------------------------------------------
*/

// Login y Registro
Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserController::class, 'login'])->name('login.post');
Route::get('/register', [UserController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [UserController::class, 'register'])->name('register');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

// Ruta principal después de login
Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

/*
|--------------------------------------------------------------------------
| Dashboard según el rol
|--------------------------------------------------------------------------
*/
Route::post('/solicitudes', [SolicitudController::class, 'crear'])->name('solicitudes.crear');


Route::middleware(['auth', 'role:administrador'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::resource('/admin/users', UserController::class); // Gestión de usuarios
});

Route::middleware([autenticacionMiddleware::class])->group(function(){
    Route::get('/panel-asesor', function(){
        return view('advisor.panel');
    })->name('advisor.panel');

    Route::get('/advisor/requests', [AdvisorController::class, 'requests'])->name('advisor.requests');
    Route::post('/advisor/respond/{id}', [AdvisorController::class, 'respond'])->name('advisor.respond');
});


    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
    Route::get('/chat', [ChatbotController::class, 'index'])->name('chat.index');
    Route::post('/chat/send', [ChatbotController::class, 'send'])->name('chat.send');


/*
|--------------------------------------------------------------------------
| Página de error por rol
|--------------------------------------------------------------------------
*/

Route::get('/unauthorized', function () {
    return view('errors.unauthorized');
})->name('unauthorized');

Route::post('/solicitudes/{id}/aceptar', [AdvisorPanelController::class, 'accept'])->name('solicitudes.aceptar')->middleware('auth','role:advisor');
Route::post('/solicitudes/{id}/rechazar', [AdvisorPanelController::class, 'reject'])->name('solicitudes.rechazar')->middleware('auth','role:advisor');
