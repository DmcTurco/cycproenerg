@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Inventario Físico — {{ $inventario->fecha->format('d/m/Y') }}</h2>
            <a href="{{ route('employee.materiales.inventario-fisico.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex flex-1 flex-col overflow-auto p-3 sm:p-4 lg:p-4">
            @if (session('message'))
                <div class="mb-3 rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('message') }}</div>
            @endif

            <div class="mb-4 grid grid-cols-1 gap-3 rounded-lg bg-gray-50 p-3 text-sm sm:grid-cols-4">
                <div><span class="text-gray-500">Realizado por:</span> <strong>{{ $inventario->realizado_por }}</strong></div>
                <div><span class="text-gray-500">Almacenero:</span> {{ $inventario->nombre_almacenero ?? '—' }}</div>
                <div><span class="text-gray-500">Supervisor del día:</span> {{ $inventario->nombre_supervisor ?? '—' }}</div>
                <div><span class="text-gray-500">Ítems contados:</span> {{ $inventario->detalles->count() }}</div>
            </div>

            @if ($inventario->observacion_general)
                <div class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $inventario->observacion_general }}</div>
            @endif

            @if ($materialesConDiferencia->count() || $herramientasConDiferencia->count())
                <div class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
                    ⚠ {{ $materialesConDiferencia->count() + $herramientasConDiferencia->count() }} ítem(s) con descuadre — revisados abajo en rojo.
                </div>
            @endif

            <h3 class="mb-2 text-sm font-semibold text-gray-700">Materiales contados ({{ $inventario->detalles->where('tipo', 'MATERIAL')->count() }})</h3>
            <div class="mb-6 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-3 py-2">Código</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2">Descripción</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Stock sistema</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Conteo físico</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Diferencia</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2">Observación / ubicación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($inventario->detalles->where('tipo', 'MATERIAL') as $detalle)
                            <tr class="{{ $detalle->diferencia != 0 ? 'bg-red-50' : '' }}">
                                <td class="px-3 py-1.5 text-gray-900">{{ $detalle->codigo }}</td>
                                <td class="px-3 py-1.5 text-gray-600">{{ $detalle->descripcion }}</td>
                                <td class="px-3 py-1.5 text-right text-gray-500">{{ number_format($detalle->stock_sistema, 2) }}</td>
                                <td class="px-3 py-1.5 text-right text-gray-900">{{ number_format($detalle->conteo_fisico, 2) }}</td>
                                <td class="px-3 py-1.5 text-right font-semibold {{ $detalle->diferencia != 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $detalle->diferencia > 0 ? '+' : '' }}{{ number_format($detalle->diferencia, 2) }}
                                </td>
                                <td class="px-3 py-1.5 text-gray-500">{{ $detalle->observacion_ubicacion ?? '—' }}</td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" message="No se contó ningún material en este inventario." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h3 class="mb-2 text-sm font-semibold text-gray-700">Herramientas contadas ({{ $inventario->detalles->where('tipo', 'HERRAMIENTA')->count() }})</h3>
            <div class="overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-3 py-2">Código</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2">Descripción</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2">Ubicación (sistema)</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2 text-center">¿Encontrada en almacén?</th>
                            <th class="sticky top-0 z-10 bg-white px-3 py-2 text-center">Resultado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($inventario->detalles->where('tipo', 'HERRAMIENTA') as $detalle)
                            <tr class="{{ $detalle->diferencia != 0 ? 'bg-red-50' : '' }}">
                                <td class="px-3 py-1.5 text-gray-900">{{ $detalle->codigo }}</td>
                                <td class="px-3 py-1.5 text-gray-600">{{ $detalle->descripcion }}</td>
                                <td class="px-3 py-1.5 text-gray-500">{{ $detalle->observacion_ubicacion }}</td>
                                <td class="px-3 py-1.5 text-center text-gray-900">{{ $detalle->conteo_fisico == 1 ? 'Sí' : 'No' }}</td>
                                <td class="px-3 py-1.5 text-center">
                                    @if ($detalle->diferencia != 0)
                                        <span class="badge bg-red-100 text-red-700">DESCUADRE</span>
                                    @else
                                        <span class="badge bg-green-100 text-green-700">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="5" message="No se contó ninguna herramienta en este inventario." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
