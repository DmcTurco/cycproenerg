@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;

    $coloresEstado = [
        \App\Models\Cotizacion::ESTADO_PENDIENTE => 'bg-amber-100 text-amber-700',
        \App\Models\Cotizacion::ESTADO_VALE => 'bg-blue-100 text-blue-700',
        \App\Models\Cotizacion::ESTADO_DESCONTADO => 'bg-green-100 text-green-700',
    ];
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Cotizaciones / Vales de entrega</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            <p class="mb-3 max-w-3xl text-sm text-gray-500">
                Equivalente a COTIZACION (formulario) + COTIZACIONES (registro). CONTRATISTA genera una cotización
                con IGV que descuenta stock al toque; PERSONAL DIRECTO genera un vale a costo, sin IGV, que recién
                descuenta stock cuando se reporte lo ejecutado (CM-6).
            </p>

            <form method="GET" action="{{ route('employee.materiales.cotizaciones.index') }}" class="mb-3 flex flex-wrap items-end gap-2">
                <div>
                    <label class="form-label" for="filter_cuadrilla">Cuadrilla</label>
                    <select id="filter_cuadrilla" name="cuadrilla_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach ($cuadrillas as $cuadrilla)
                            <option value="{{ $cuadrilla->id }}" @selected((string) $cuadrillaId === (string) $cuadrilla->id)>{{ $cuadrilla->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter_estado">Estado</label>
                    <select id="filter_estado" name="estado" class="form-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="{{ \App\Models\Cotizacion::ESTADO_PENDIENTE }}" @selected($estado === \App\Models\Cotizacion::ESTADO_PENDIENTE)>PENDIENTE</option>
                        <option value="{{ \App\Models\Cotizacion::ESTADO_VALE }}" @selected($estado === \App\Models\Cotizacion::ESTADO_VALE)>VALE - USO INTERNO</option>
                        <option value="{{ \App\Models\Cotizacion::ESTADO_DESCONTADO }}" @selected($estado === \App\Models\Cotizacion::ESTADO_DESCONTADO)>DESCONTADO EN VALORIZACION</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="search">N° documento</label>
                    <input id="search" type="text" name="search" value="{{ $search }}" class="form-input w-48" placeholder="C&C-260826-01" />
                </div>
                <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
                @if ($search || $estado || $cuadrillaId)
                    <a href="{{ route('employee.materiales.cotizaciones.index') }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                @endif
            </form>

            <div class="mb-3 flex shrink-0 items-center justify-between">
                <p class="text-xs text-gray-500">Total: <strong>{{ $cotizaciones->total() }}</strong></p>
                <a href="{{ route('employee.materiales.cotizaciones.create') }}" class="btn-brand px-3 py-1.5 text-sm">Nueva cotización / vale</a>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">N° documento</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Fecha</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Cuadrilla</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Monto S/IGV</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Total C/IGV</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Estado</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">N° valorización</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cotizaciones as $cotizacion)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $cotizacion->numero }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $cotizacion->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $cotizacion->cuadrilla->nombre ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($cotizacion->monto_sin_igv, 2) }}</td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($cotizacion->total_con_igv, 2) }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $coloresEstado[$cotizacion->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $cotizacion->estado }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $cotizacion->n_valorizacion ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1" x-data="{ open: false, n_valorizacion: '' }">
                                        <a href="{{ route('employee.materiales.cotizaciones.pdf', $cotizacion) }}" class="btn-icon" title="Descargar PDF">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H8a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('employee.materiales.cotizaciones.edit', $cotizacion) }}" class="btn-icon" title="Corregir (mismo número)">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        @if (!$cotizacion->es_vale && $cotizacion->estado === \App\Models\Cotizacion::ESTADO_PENDIENTE)
                                            <button type="button" @click="open = true" class="btn-icon text-green-600 hover:bg-green-50" title="Marcar descontado en valorización">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                            <x-modal max-width="sm">
                                                <x-slot:title>Marcar descontado en valorización</x-slot:title>
                                                <form method="POST" action="{{ route('employee.materiales.cotizaciones.descontar', $cotizacion) }}" class="space-y-4">
                                                    @csrf
                                                    <p class="text-sm text-gray-600">{{ $cotizacion->numero }} — {{ $cotizacion->cuadrilla->nombre ?? '' }}</p>
                                                    <div>
                                                        <label class="form-label" for="n_valorizacion_{{ $cotizacion->id }}">N° de valorización</label>
                                                        <input id="n_valorizacion_{{ $cotizacion->id }}" name="n_valorizacion" type="text" x-model="n_valorizacion" class="form-input" required />
                                                    </div>
                                                    <x-slot:footer>
                                                        <button type="button" @click="open = false" class="btn-secondary">Cancelar</button>
                                                        <button type="submit" class="btn-brand">Confirmar</button>
                                                    </x-slot:footer>
                                                </form>
                                            </x-modal>
                                        @endif
                                        <form method="POST" action="{{ route('employee.materiales.cotizaciones.destroy', $cotizacion) }}" onsubmit="return confirm('¿Eliminar {{ $cotizacion->numero }}? Queda en la papelera (soft delete), no se borra el dato.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="8" message="No hay cotizaciones ni vales registrados." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 shrink-0">
                {{ $cotizaciones->links('pagination::tailwind') }}
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
