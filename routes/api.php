<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí puedes registrar rutas API para tu aplicación. 
| Estas rutas son cargadas por RouteServiceProvider dentro de un grupo 
| que está asignado al middleware "api".
|
*/

Route::post('/chatbot/message', [ChatbotController::class, 'responder']);
Route::post('/chatbot/solicitud', [ChatbotController::class, 'createSolicitud']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
