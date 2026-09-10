@extends('employee.layouts.user_type.auth')

@section('content')
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card">
            <p class="text-sm text-gray-500">Ingresos de hoy</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">$53k</p>
            <p class="mt-2 text-xs font-medium text-green-600">+55% respecto a la semana pasada</p>
        </div>
        <div class="card">
            <p class="text-sm text-gray-500">Usuarios de hoy</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">2,300</p>
            <p class="mt-2 text-xs font-medium text-green-600">+3% respecto al mes pasado</p>
        </div>
        <div class="card">
            <p class="text-sm text-gray-500">Nuevos clientes</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">3,462</p>
            <p class="mt-2 text-xs font-medium text-red-600">-2% respecto a ayer</p>
        </div>
        <div class="card">
            <p class="text-sm text-gray-500">Ventas</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900">$103,430</p>
            <p class="mt-2 text-xs font-medium text-green-600">+5% respecto a ayer</p>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="card">
            <div class="h-44"><canvas id="chart-bars"></canvas></div>
            <h6 class="mt-4 text-sm font-semibold text-gray-900">Visitas al sitio</h6>
            <p class="text-xs text-gray-500">Rendimiento de la última campaña</p>
        </div>
        <div class="card">
            <div class="h-44"><canvas id="chart-line"></canvas></div>
            <h6 class="mt-4 text-sm font-semibold text-gray-900">Ventas diarias</h6>
            <p class="text-xs text-gray-500">+15% de incremento hoy</p>
        </div>
        <div class="card">
            <div class="h-44"><canvas id="chart-line-tasks"></canvas></div>
            <h6 class="mt-4 text-sm font-semibold text-gray-900">Tareas completadas</h6>
            <p class="text-xs text-gray-500">Actualizado recientemente</p>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/pages/chart-demo.js')
    @endpush
@endsection
