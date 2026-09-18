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
    // Vista propia de Control Interno por solicitud (separada del Detalle de
    // Solicitud para que ese detalle siga siendo una vista rápida de solo
    // lectura, sin pagar el costo de cargar/editar Control Interno).
    Route::get('/solicitudes/{id}/control-interno', function ($id) {
        return view('employee.pages.clients.control-interno', compact('id'));
    })->name('solicitudes.control-interno');
    Route::get('/check-progress/{fileId}', [Employee\ClientController::class, 'checkProgress'])->name('check-progress');

    // Control Interno (migración de CONTROL INTERNAS - CYC CLB v5.4.xlsm).
    Route::prefix('control-interno')->name('control-interno.')->group(function () {
        // CI-9: listado de solicitudes con su fase/semáforo de Control Interno.
        Route::get('/', [Employee\ControlInternoController::class, 'index'])->name('index');
        // CI-10: dashboard / resumen ejecutivo (conteo por fase/empresa, bitácora de la última carga, IND2/puntaje de CI-7).
        Route::get('/resumen', [Employee\ControlInternoDashboardController::class, 'index'])->name('resumen');
        Route::get('/parametros', [Employee\ParametroControlInternoController::class, 'edit'])->name('parametros.edit');
        Route::put('/parametros', [Employee\ParametroControlInternoController::class, 'update'])->name('parametros.update');
        Route::resource('feriados', Employee\FeriadoController::class)
            ->only(['index', 'store', 'edit', 'destroy'])
            ->names('feriados');

        // CI-3: columnas manuales (F. CONSTRUCCIÓN control, OBSERVACIÓN, ANULAR)
        // editables desde el detalle de la solicitud.
        Route::get('/solicitudes/{solicitud}/manual', [Employee\ControlInternoManualController::class, 'show'])->name('solicitudes.manual.show');
        Route::put('/solicitudes/{solicitud}/manual', [Employee\ControlInternoManualController::class, 'update'])->name('solicitudes.manual.update');
    });

    // Control de Materiales (migración de CONTROL_MATERIALES_CC_08-2026 rev 02.xlsm).
    // Módulo nuevo e independiente de Control Interno (ver docs/modulos/control-materiales.md).
    Route::prefix('materiales')->name('materiales.')->group(function () {
        // CM-1: pantalla de Catálogo (pestañas Materiales / Herramientas).
        Route::get('/', [Employee\CatalogoController::class, 'index'])->name('index');
        Route::get('/parametros', [Employee\ParametroControlMaterialController::class, 'edit'])->name('parametros.edit');
        Route::put('/parametros', [Employee\ParametroControlMaterialController::class, 'update'])->name('parametros.update');
        // Rutas explícitas (en vez de Route::resource) para no depender de
        // que Str::singular() adivine bien el nombre del parámetro en
        // español ("items" -> {item} es correcto, pero no vale la pena
        // arriesgarse con "herramientas" -> {herramienta}).
        Route::get('items', [Employee\MaterialController::class, 'index'])->name('items.index');
        Route::post('items', [Employee\MaterialController::class, 'store'])->name('items.store');
        Route::get('items/{item}/edit', [Employee\MaterialController::class, 'edit'])->name('items.edit');
        Route::delete('items/{item}', [Employee\MaterialController::class, 'destroy'])->name('items.destroy');

        Route::get('herramientas', [Employee\HerramientaController::class, 'index'])->name('herramientas.index');
        Route::post('herramientas', [Employee\HerramientaController::class, 'store'])->name('herramientas.store');
        Route::get('herramientas/{herramienta}/edit', [Employee\HerramientaController::class, 'edit'])->name('herramientas.edit');
        Route::delete('herramientas/{herramienta}', [Employee\HerramientaController::class, 'destroy'])->name('herramientas.destroy');

        // CM-2: registro de cuadrillas (personas que retiran materiales).
        Route::get('cuadrillas', [Employee\CuadrillaController::class, 'index'])->name('cuadrillas.index');
        Route::post('cuadrillas', [Employee\CuadrillaController::class, 'store'])->name('cuadrillas.store');
        Route::get('cuadrillas/{cuadrilla}/edit', [Employee\CuadrillaController::class, 'edit'])->name('cuadrillas.edit');
        Route::delete('cuadrillas/{cuadrilla}', [Employee\CuadrillaController::class, 'destroy'])->name('cuadrillas.destroy');

        // CM-3: ingresos (kardex de entradas de materiales al almacén).
        Route::get('ingresos', [Employee\IngresoController::class, 'index'])->name('ingresos.index');
        Route::post('ingresos', [Employee\IngresoController::class, 'store'])->name('ingresos.store');
        Route::get('ingresos/{ingreso}/edit', [Employee\IngresoController::class, 'edit'])->name('ingresos.edit');
        Route::delete('ingresos/{ingreso}', [Employee\IngresoController::class, 'destroy'])->name('ingresos.destroy');

        // CM-4/CM-5: cotización (contratistas) / vale de entrega (personal
        // directo) + su registro. Formulario de página completa (no
        // crudModal): tiene una lista dinámica de ítems, a diferencia de
        // los CRUDs simples de arriba.
        Route::get('cotizaciones', [Employee\CotizacionController::class, 'index'])->name('cotizaciones.index');
        Route::get('cotizaciones/crear', [Employee\CotizacionController::class, 'create'])->name('cotizaciones.create');
        Route::post('cotizaciones', [Employee\CotizacionController::class, 'store'])->name('cotizaciones.store');
        Route::get('cotizaciones/{cotizacion}/editar', [Employee\CotizacionController::class, 'edit'])->name('cotizaciones.edit');
        Route::put('cotizaciones/{cotizacion}', [Employee\CotizacionController::class, 'update'])->name('cotizaciones.update');
        Route::get('cotizaciones/{cotizacion}/pdf', [Employee\CotizacionController::class, 'pdf'])->name('cotizaciones.pdf');
        Route::post('cotizaciones/{cotizacion}/descontar', [Employee\CotizacionController::class, 'marcarDescontado'])->name('cotizaciones.descontar');
        Route::delete('cotizaciones/{cotizacion}', [Employee\CotizacionController::class, 'destroy'])->name('cotizaciones.destroy');
    });
});
