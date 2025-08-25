<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\AdvisorPanelController; // Necesitamos este para el panel index
use App\Http\Controllers\AdvisorController; // Este para las acciones de aceptar/rechazar/chat
use App\Http\Middleware\AutenticacionMiddleware; // Tu middleware de autenticación

/*
|--------------------------------------------------------------------------
| Rutas públicas (usuarios no logueados)
|--------------------------------------------------------------------------
*/

// Chat principal
Route::get('/chat', [ChatbotController::class, 'index'])->name('chat.index');
Route::post('/chat/send', [ChatbotController::class, 'send'])->name('chat.send');

// Solicitar un asesor
Route::post('/solicitar-asesor', [SolicitudController::class, 'solicitarAsesor'])->name('solicitar.asesor');

// Archivos públicos
Route::get('/file/{id}', [FileController::class, 'show'])->name('file.show');

/*
|--------------------------------------------------------------------------
| Autenticación y registro
|--------------------------------------------------------------------------
*/
Route::get('/login', [UserController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserController::class, 'login'])->name('login.post');
Route::get('/register', [UserController::class, 'showRegisterForm'])->name('register.form');
Route::post('/register', [UserController::class, 'register'])->name('register');
Route::post('/logout', [UserController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rutas autenticadas (usuarios logueados)
|--------------------------------------------------------------------------
*/
// Usamos AutenticacionMiddleware para todas las rutas logueadas
Route::middleware([AutenticacionMiddleware::class])->group(function () {

    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');

    // Administración de usuarios (protegido por AutenticacionMiddleware)
    Route::resource('/users', UserController::class);

    // Rutas de administración (protegidas por AutenticacionMiddleware, y si se requiere, un middleware de rol adicional)
    // Actualmente, no tienes un middleware de rol activo en Kernel.php, pero si lo tuvieras, sería así:
    // Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
        // Route::resource('/users', UserController::class); // Si users está dentro de admin, reubícalo
    });


    // Panel y acciones del asesor
    // *** CAMBIO CLAVE AQUÍ: 'advisor.panel' apunta a AdvisorPanelController@index ***
    // Asumo que AdvisorPanelController@index ya verifica el rol del asesor internamente
    Route::prefix('advisors')->group(function () {
        Route::get('/panel', [AdvisorPanelController::class, 'index'])->name('advisor.panel');
        // Las siguientes rutas para chat y acciones específicas deberían apuntar al AdvisorController,
        // ya que tu AdvisorController tiene esos métodos.
        Route::get('/chat/{solicitud}', [AdvisorController::class, 'chat'])->name('advisors.chat');
        Route::post('/chat/{solicitud}/send', [AdvisorController::class, 'sendMessage'])->name('advisors.send');

        // Aceptar/Rechazar solicitudes
        Route::post('/solicitudes/{solicitud}/aceptar', [AdvisorController::class, 'accept'])->name('solicitudes.aceptar');
        Route::post('/solicitudes/{solicitud}/rechazar', [AdvisorController::class, 'reject'])->name('solicitudes.rechazar');
    });

    // Tu ruta '/requests' parece redundante si ya tienes '/advisors/panel' para listar
    // Puedes eliminarla o fusionarla con el panel principal si es la misma funcionalidad.
    // Route::get('/requests', [SolicitudController::class, 'index'])->name('advisors.requests');
});

/*
|--------------------------------------------------------------------------
| Redirección raíz
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('chat.index');
});

/*
|--------------------------------------------------------------------------
| Página de error por rol
|--------------------------------------------------------------------------
*/
Route::get('/unauthorized', fn() => view('errors.unauthorized'))->name('unauthorized');
