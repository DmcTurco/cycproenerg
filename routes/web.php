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
        return redirect()->route('employee.home');
    })->withoutMiddleware('auth:employee');
    Route::get('/home', [Employee\EmployeeController::class, 'index'])->name('home');
    Route::get('/client/carga-excel', function () {
        return view('employee.pages.clients.excel');
    })->name('client.excel');
    Route::resource('client', Employee\ClientController::class);
    Route::resource('technicals', Employee\TecnicoController::class);
    Route::resource('advisers', Employee\AsesorController::class);
    Route::delete('technicals/{tecnico}/requests/bulk-delete', [Employee\SolicitudTecnicoController::class, 'destroyMultiple'])->name('technicals.requests.bulk-delete');

    Route::resource('technicals.requests', Employee\SolicitudTecnicoController::class);
    Route::resource('technicals.record', Employee\HistorialController::class);
    Route::post('/change', [Employee\ClientController::class, 'change'])->name('change');
    Route::get('/getFullSolicitudDetails/{id}',[Employee\SolicitudController::class, 'getFullSolicitudDetails'])->name('getFullSolicitudDetails');
    Route::get('/solicitudes/{id}/detalle', function ($id) {
        return view('employee.pages.clients.detail', compact('id'));
    })->name('solicitudes.detalle');
    Route::get('/check-progress/{fileId}', [Employee\ClientController::class, 'checkProgress'])->name('check-progress');

    // Control Interno (migración de CONTROL INTERNAS - CYC CLB v5.4.xlsm).
    Route::prefix('control-interno')->name('control-interno.')->group(function () {
        Route::get('/parametros', [Employee\ParametroControlInternoController::class, 'edit'])->name('parametros.edit');
        Route::put('/parametros', [Employee\ParametroControlInternoController::class, 'update'])->name('parametros.update');
        Route::resource('feriados', Employee\FeriadoController::class)
            ->only(['index', 'store', 'edit', 'destroy'])
            ->names('feriados');
    });
});
