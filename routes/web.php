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
});


Route::prefix(MyApp::EMPLOYEE_SUBDIR)->middleware('auth:employee')->name('employee.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('client.home');
    })->withoutMiddleware('auth:client');
    Route::get('/home', [Employee\EmployeeController::class, 'index'])->name('home');
    Route::resource('client', Employee\ClientController::class);
    Route::resource('technicals', Employee\TecnicoController::class);
    Route::resource('advisers', Employee\AsesorController::class);
    Route::get('technicals/{technical}/requests/search', [Employee\SolicitudTecnicoController::class, 'search'])->name('employee.technicals.requests.search');
    Route::delete('technicals/{tecnico}/requests/bulk-delete', [Employee\SolicitudTecnicoController::class, 'destroyMultiple'])->name('employee.technicals.requests.bulk-delete');
    
    Route::resource('technicals.requests', Employee\SolicitudTecnicoController::class);
    Route::resource('technicals.record', Employee\HistorialController::class);
    Route::any('technicals-getrecords-request', [Employee\SolicitudTecnicoController::class, 'obtenerRegistros'])->name('obtenerRegistros');
    Route::get('/getDataIndex/{id}', [Employee\SolicitudTecnicoController::class, 'obtenerSolicitudesIndex'])->name('obtenerSolicitudesIndex');
    Route::post('/change', [Employee\ClientController::class, 'change'])->name('change');
    Route::get('/getFullSolicitudDetails/{id}',[Employee\SolicitudController::class, 'getFullSolicitudDetails'])->name('getFullSolicitudDetails');
    Route::get('/check-progress/{fileId}', [Employee\ClientController::class, 'checkProgress'])->name('check-progress');
});
