@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $titlePage = 'Catálogo';

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
        {{-- Pestañas en la barra azul, igual que en Control General --}}
        <div class="flex flex-wrap items-center gap-1.5 bg-brand-600 px-3 py-2 sm:px-4">
            @foreach (['materiales' => ['Materiales', $materiales->total()], 'herramientas' => ['Herramientas', $herramientas->total()]] as $clave => [$nombre, $total])
                <a href="{{ route('employee.materiales.index', ['tab' => $clave] + ($search ? ['search' => $search] : [])) }}"
                    class="rounded-full px-3 py-1 text-sm font-medium transition {{ $tab === $clave ? 'bg-white text-brand-700 shadow-sm' : 'text-white/90 hover:bg-white/15 hover:text-white' }}">
                    {{ $nombre }}
                    <span class="ml-0.5 opacity-75">({{ $total }})</span>
                </a>
            @endforeach
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0">

            @if ($tab === 'materiales')
                <div
                    x-data="crudModal({
                        baseUrl: '{{ route('employee.materiales.items.index') }}',
                        unwrap: 'material',
                        defaults: { id: null, codigo: '', descripcion: '', unidad: '', precio_base: '', margen_pct: '', stock_inicial: 0, stock_minimo: 0, factor_metros_por_unidad: 1, ultimo_precio_ingresos: null },
                    })"
                    class="flex min-h-0 flex-1 flex-col"
                >
                    <div class="mb-2 flex shrink-0 flex-wrap items-center gap-2">
                        @include('employee.pages.materiales.catalogo-buscador')
                        @can('catalogo.crear')
                        <a href="{{ route('employee.materiales.items.create') }}" class="btn-brand ml-auto px-3 py-1.5 text-sm">Agregar material</a>
                        @endcan
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Código</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Correlativo</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Descripción</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Unid.</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Precio vigente</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Margen %</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Venta S/IGV</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Venta C/IGV</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Stock actual</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Estado</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($materiales as $material)
                                    <tr>
                                        <td class="px-2 py-1.5 font-medium text-gray-900">{{ $material->codigo }}</td>
                                        <td class="px-2 py-1.5 text-gray-500">{{ $material->serie }}-{{ $material->correlativo }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ $material->descripcion }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ $material->unidad }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">
                                            {{ number_format($material->precioVigente(), 2) }}
                                            @php($alertaMaterial = $material->alertaPrecio())
                                            @if ($alertaMaterial && $alertaMaterial !== 'ESTABLE')
                                                <span class="ml-1 inline-block rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ str_starts_with($alertaMaterial, 'SUBIO') ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}" title="{{ $alertaMaterial }}">
                                                    {{ str_starts_with($alertaMaterial, 'SUBIO') ? 'SUBIO' : 'BAJO' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-1.5 text-gray-600">
                                            {{ number_format($material->margenEfectivo() * 100, 2) }}%
                                            @if ($material->margen_pct === null)
                                                <span class="text-xs text-gray-400" title="Usa el margen general de Parámetros">(general)</span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ number_format($material->precio_venta_sin_igv, 2) }}</td>
                                        <td class="px-2 py-1.5 font-medium text-gray-900">{{ number_format($material->precio_venta_con_igv, 2) }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">
                                            {{ number_format($material->stockActual(), 2) }}
                                            @php($sinVale = $material->ejecutadoSinVale())
                                            @if ($sinVale > 0)
                                                <span class="ml-1 inline-block rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700" title="Se reportó más uso del que se entregó con vale: falta emitir un vale por {{ number_format($sinVale, 2) }}">
                                                    SIN VALE {{ number_format($sinVale, 2) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <span class="badge {{ $coloresEstado[$material->estado()] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $material->estado() }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-1.5">
                                            <div class="flex items-center justify-center gap-1">
                                                @can('catalogo.editar')
                                                <button type="button" @click="openEdit({{ $material->id }})" class="btn-icon" title="Editar material">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                @endcan
                                                @can('catalogo.eliminar')
                                                <button type="button" @click="remove({{ $material->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar material">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="11" message="No hay materiales registrados." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2 shrink-0">
                        {{ $materiales->links('pagination::tailwind') }}
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
                    <div class="mb-2 flex shrink-0 flex-wrap items-center gap-2">
                        @include('employee.pages.materiales.catalogo-buscador')
                        @can('catalogo.crear')
                        <button type="button" @click="openCreate()" class="btn-brand ml-auto px-3 py-1.5 text-sm">Registrar herramienta</button>
                        @endcan
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Código</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Correlativo</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Descripción</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Marca / modelo</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Estado</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Responsable</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Ubicación</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($herramientas as $herramienta)
                                    <tr>
                                        <td class="px-2 py-1.5 font-medium text-gray-900">{{ $herramienta->codigo }}</td>
                                        <td class="px-2 py-1.5 text-gray-500">{{ $herramienta->serie }}-{{ $herramienta->correlativo }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ $herramienta->descripcion }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ $herramienta->marca_modelo ?? '—' }}</td>
                                        <td class="px-2 py-1.5">
                                            <span class="badge {{ $coloresEstadoHerramienta[$herramienta->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $herramienta->estado }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ $herramienta->responsableActual() ?? 'Sin asignar' }}</td>
                                        <td class="px-2 py-1.5 text-gray-600">{{ $herramienta->ubicacion() }}</td>
                                        <td class="px-2 py-1.5">
                                            <div class="flex items-center justify-center gap-1">
                                                @can('catalogo.editar')
                                                <button type="button" @click="openEdit({{ $herramienta->id }})" class="btn-icon" title="Editar herramienta">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>
                                                @endcan
                                                @can('catalogo.eliminar')
                                                <button type="button" @click="remove({{ $herramienta->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar herramienta">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <x-empty-state colspan="8" message="No hay herramientas registradas." />
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2 shrink-0">
                        {{ $herramientas->links('pagination::tailwind') }}
                    </div>

                    <x-crud-modal>
                        @include('employee.pages.materiales.herramienta-form')
                    </x-crud-modal>
                </div>
            @endif
        </div>
    </div>
@endsection
