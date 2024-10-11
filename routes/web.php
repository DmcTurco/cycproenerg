<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin as Admin;
use App\Http\Controllers\Company as Company;
use App\Http\Controllers\Employee as Employee;
use App\MyApp;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


Route::prefix(MyApp::ADMINS_SUBDIR)->middleware('auth:admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.home');
    })->withoutMiddleware('auth:admin');
    // Route::get('/home', [Admin\HomeController::class, 'index'])->name('home');

});

Route::prefix(MyApp::COMPANIES_SUBDIR)->middleware('auth:company')->name('company.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('company.home');
    })->withoutMiddleware('auth:company');
    Route::get('/home', [Company\CompanyController::class, 'index'])->name('home');
    Route::resource('client', Company\ClientController::class);
    Route::resource('technicals', Company\TecnicoController::class);
    Route::resource('advisers', Company\AsesorController::class);
    Route::resource('technicals.requests', Company\SolicitudTecnicoController::class);
    Route::any('technicals-getrecords-request', [Company\SolicitudTecnicoController::class, 'obtenerRegistros'])->name('obtenerRegistros');
    Route::get('/getDataIndex/{id}', [Company\SolicitudTecnicoController::class, 'obtenerSolicitudesIndex'])->name('obtenerSolicitudesIndex');
    Route::post('/change', [Company\ClientController::class, 'change'])->name('change');
    // Route::get('/home', [Company\HomeController::class, 'index'])->name('home');
});


Route::prefix(MyApp::EMPLOYEE_SUBDIR)->middleware('auth:client')->name('client.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('client.home');
    })->withoutMiddleware('auth:client');
    // Route::get('/home', [Client\HomeController::class, 'index'])->name('home');
});
