@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;

    $coloresEstadoCuadrilla = [
        'ACTIVO' => 'bg-green-100 text-green-700',
        'INACTIVO' => 'bg-gray-100 text-gray-600',
    ];
    $coloresTipo = [
        'CONTRATISTA' => 'bg-blue-100 text-blue-700',
        'PERSONAL DIRECTO' => 'bg-purple-100 text-purple-700',
    ];
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Cuadrillas</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            <p class="mb-3 max-w-3xl text-sm text-gray-500">
                Equivalente a "REGISTRO DE CUADRILLAS" dentro de RESUMEN del Excel. Acá se registra a toda la gente que
                puede retirar materiales (CONTRATISTA o PERSONAL DIRECTO). N° de retiros, total valorizado y pendiente
                de descuento se calculan en CM-5 (todavía no implementado) y hoy muestran 0.
            </p>

            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <form method="GET" action="{{ route('employee.materiales.cuadrillas.index') }}" class="flex items-center gap-2">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre o DNI"
                        class="w-64 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
                    @if ($search)
                        <a href="{{ route('employee.materiales.cuadrillas.index') }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                    @endif
                </form>
            </div>

            <div
                x-data="crudModal({
                    baseUrl: '{{ route('employee.materiales.cuadrillas.index') }}',
                    unwrap: 'cuadrilla',
                    defaults: { id: null, nombre: '', tipo: 'CONTRATISTA', estado: 'ACTIVO', dni: '', fecha_nacimiento: '', celular: '', empresas: [] },
                })"
                class="flex min-h-0 flex-1 flex-col"
            >
                <div class="mb-3 flex shrink-0 justify-end">
                    <button type="button" @click="openCreate()" class="btn-brand px-3 py-1.5 text-sm">Registrar cuadrilla</button>
                </div>

                <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Nombre</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Tipo</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Estado</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">DNI</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Celular</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Empresa(s)</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">N° retiros</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Valorizado S/IGV</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Pend. descuento</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($cuadrillas as $cuadrilla)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $cuadrilla->nombre }}</td>
                                    <td class="px-4 py-3">
                                        <span class="badge {{ $coloresTipo[$cuadrilla->tipo] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $cuadrilla->tipo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge {{ $coloresEstadoCuadrilla[$cuadrilla->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                            {{ $cuadrilla->estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $cuadrilla->dni ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $cuadrilla->celular ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $cuadrilla->empresas->pluck('codigo')->filter()->implode(' / ') ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-400">{{ $cuadrilla->numRetiros() }}</td>
                                    <td class="px-4 py-3 text-right text-gray-400">{{ number_format($cuadrilla->totalValorizado(), 2) }}</td>
                                    <td class="px-4 py-3 text-right text-gray-400">{{ number_format($cuadrilla->pendienteDescuento(), 2) }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" @click="openEdit({{ $cuadrilla->id }})" class="btn-icon" title="Editar cuadrilla">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="remove({{ $cuadrilla->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar cuadrilla">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state colspan="10" message="No hay cuadrillas registradas." />
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-crud-modal>
                    @include('employee.pages.materiales.cuadrilla-form')
                </x-crud-modal>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
