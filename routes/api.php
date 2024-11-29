<?php

use App\Http\Controllers\Api\ApiSolicitudTecnico;
use App\Http\Controllers\Api\ApiTecnicoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('login', [ApiTecnicoController::class, 'login']);
Route::get('/check-connection', function () {
    return response()->json(['status' => 'ok']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('logout', [ApiTecnicoController::class, 'logout']);
    Route::get('profile', [ApiTecnicoController::class, 'profile']);
    Route::get('/solicitudes', [ApiSolicitudTecnico::class, 'getSolicitudTecnico']);
    Route::put('/solicitudes/estado', [ApiSolicitudTecnico::class, 'updateEstado']);


});
