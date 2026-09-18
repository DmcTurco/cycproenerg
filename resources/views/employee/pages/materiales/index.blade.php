@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;

    $coloresEstado = [
        'OK' => 'bg-green-100 text-green-700',
        'REPONER' => 'bg-amber-100 text-amber-700',
        'SIN STOCK' => 'bg-red-100 text-red-700',
    ];
    $coloresEstadoHerramienta = [
        'OPERATIVA' => 'bg-green-100 text-green-700',
        'MALOGRADA' => 'bg-amber-100 text-amber-700',
        'PERDIDA' => 'bg-red-100 text-red-700',
    ];
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Catálogo</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('employee.materiales.cotizaciones.index') }}"
                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    Cotizaciones
                </a>
                <a href="{{ route('employee.materiales.ingresos.index') }}"
                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    Ingresos
                </a>
                <a href="{{ route('employee.materiales.cuadrillas.index') }}"
                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    Cuadrillas
                </a>
                <a href="{{ route('employee.materiales.parametros.edit') }}"
                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    Parámetros
                </a>
            </div>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">

            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('employee.materiales.index', ['tab' => 'materiales'] + ($search ? ['search' => $search] : [])) }}"
                        class="rounded-full px-3 py-1.5 text-sm font-medium {{ $tab === 'materiales' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Materiales
                        <span class="ml-1 opacity-75">({{ $materiales->count() }})</span>
                    </a>
                    <a href="{{ route('employee.materiales.index', ['tab' => 'herramientas'] + ($search ? ['search' => $search] : [])) }}"
                        class="rounded-full px-3 py-1.5 text-sm font-medium {{ $tab === 'herramientas' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        Herramientas
                        <span class="ml-1 opacity-75">({{ $herramientas->count() }})</span>
                    </a>
                </div>

                <form method="GET" action="{{ route('employee.materiales.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $tab }}" />
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por código o descripción"
                        class="w-64 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
                    @if ($search)
                        <a href="{{ route('employee.materiales.index', ['tab' => $tab]) }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                    @endif
                </form>
            </div>

            @if ($tab === 'materiales')
                <div
                    x-data="crudModal({
                        baseUrl: '{{ route('employee.materiales.items.index') }}',
                        unwrap: 'material',
                        defaults: { id: null, codigo: '', descripcion: '', unidad: '', precio_base: '', margen_pct: '', stock_inicial: 0, stock_minimo: 0, factor_metros_por_unidad: 1 },
                    })"
                    class="flex min-h-0 flex-1 flex-col"
                >
                    <div class="mb-3 flex shrink-0 justify-end">
                        <button type="button" @click="openCreate()" class="btn-brand px-3 py-1.5 text-sm">Agregar material</button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Código</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Descripción</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Unid.</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Precio vigente</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Stock actual</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Estado</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($materiales as $material)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $material->codigo }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $material->descripcion }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $material->unidad }}</td>
                                        <td class="px-4 py-3 text-gray-600">
                                            {{ number_format($material->precioVigente(), 2) }}
                                            @php($alertaMaterial = $material->alertaPrecio())
                                            @if ($alertaMaterial && $alertaMaterial !== 'ESTABLE')
                                                <span class="ml-1 inline-block rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ str_starts_with($alertaMaterial, 'SUBIO') ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}" title="{{ $alertaMaterial }}">
                                                    {{ str_starts_with($alertaMaterial, 'SUBIO') ? 'SUBIO' : 'BAJO' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ number_format($material->stockActual(), 2) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="badge {{ $coloresEstado[$material->estado()] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $material->estado() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="openEdit({{ $material->id }})" class="btn-icon" title="Editar material">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="remove({{ $material->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar material">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" message="No hay materiales registrados." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-crud-modal>
                        @include('employee.pages.materiales.material-form')
                    </x-crud-modal>
                </div>
            @else
                <div
                    x-data="crudModal({
                        baseUrl: '{{ route('employee.materiales.herramientas.index') }}',
                        unwrap: 'herramienta',
                        defaults: { id: null, codigo: '', descripcion: '', marca_modelo: '', numero_serie: '', fecha_compra: '', precio: '', estado: 'OPERATIVA', observacion: '' },
                    })"
                    class="flex min-h-0 flex-1 flex-col"
                >
                    <div class="mb-3 flex shrink-0 justify-end">
                        <button type="button" @click="openCreate()" class="btn-brand px-3 py-1.5 text-sm">Registrar herramienta</button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Código</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Descripción</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Marca / modelo</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Estado</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Responsable</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Ubicación</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($herramientas as $herramienta)
                                    <tr>
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $herramienta->codigo }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $herramienta->descripcion }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $herramienta->marca_modelo ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="badge {{ $coloresEstadoHerramienta[$herramienta->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $herramienta->estado }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $herramienta->responsableActual() ?? 'Sin asignar' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $herramienta->ubicacion() }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-center gap-1">
                                                <button type="button" @click="openEdit({{ $herramienta->id }})" class="btn-icon" title="Editar herramienta">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                <button type="button" @click="remove({{ $herramienta->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar herramienta">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="7" message="No hay herramientas registradas." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <x-crud-modal>
                        @include('employee.pages.materiales.herramienta-form')
                    </x-crud-modal>
                </div>
            @endif
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
