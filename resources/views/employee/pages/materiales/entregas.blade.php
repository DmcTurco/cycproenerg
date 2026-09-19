@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Entregas de herramientas — Control de Materiales</h2>
            <a href="{{ route('employee.materiales.index', ['tab' => 'herramientas']) }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            <p class="mb-3 max-w-3xl text-sm text-gray-500">
                Equivalente a la hoja ENTREGAS: cada fila es un movimiento de una herramienta (ENTREGA o DEVOLUCIÓN).
                De acá sale el responsable actual y la ubicación que se ve en el catálogo de herramientas: el
                último movimiento registrado de cada herramienta decide si está en ALMACÉN o EN CAMPO, y con quién.
            </p>

            <form method="GET" action="{{ route('employee.materiales.entregas.index') }}" class="mb-3 flex flex-wrap items-end gap-2">
                <div>
                    <label class="form-label" for="filter_herramienta">Herramienta</label>
                    <select id="filter_herramienta" name="herramienta_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach ($herramientas as $herramienta)
                            <option value="{{ $herramienta->id }}" @selected((string) $herramientaId === (string) $herramienta->id)>
                                {{ $herramienta->codigo }} — {{ $herramienta->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter_cuadrilla">Cuadrilla</label>
                    <select id="filter_cuadrilla" name="cuadrilla_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Todas</option>
                        @foreach ($cuadrillas as $cuadrilla)
                            <option value="{{ $cuadrilla->id }}" @selected((string) $cuadrillaId === (string) $cuadrilla->id)>
                                {{ $cuadrilla->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="search">Buscar</label>
                    <input id="search" type="text" name="search" value="{{ $search }}" class="form-input w-56" placeholder="Código, descripción o cuadrilla..." />
                </div>
                <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
                @if ($search || $herramientaId || $cuadrillaId)
                    <a href="{{ route('employee.materiales.entregas.index') }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                @endif
            </form>

            <div
                x-data="crudModal({
                    baseUrl: '{{ route('employee.materiales.entregas.index') }}',
                    unwrap: 'entrega',
                    defaults: { id: null, fecha: '', herramienta_id: '', tipo: 'ENTREGA', cuadrilla_id: '', observacion: '' },
                })"
                class="flex min-h-0 flex-1 flex-col"
            >
                <div class="mb-3 flex shrink-0 items-center justify-between">
                    <p class="text-xs text-gray-500">Total: <strong>{{ $entregas->total() }}</strong></p>
                    <button type="button" @click="openCreate()" class="btn-brand px-3 py-1.5 text-sm">Registrar movimiento</button>
                </div>

                <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Fecha</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Herramienta</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Movimiento</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Cuadrilla</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3">Observación</th>
                                <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($entregas as $entrega)
                                <tr>
                                    <td class="px-4 py-3 text-gray-600">{{ $entrega->fecha->format('d/m/Y') }}</td>
                                    <td class="px-4 py-3 text-gray-900">
                                        <div class="font-medium">{{ $entrega->herramienta->codigo ?? '—' }}</div>
                                        <div class="text-xs text-gray-500">{{ $entrega->herramienta->descripcion ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="badge {{ $entrega->tipo === 'DEVOLUCION' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $entrega->tipo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $entrega->cuadrilla->nombre ?? '—' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ $entrega->observacion ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" @click="openEdit({{ $entrega->id }})" class="btn-icon" title="Editar movimiento">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button type="button" @click="remove({{ $entrega->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar movimiento">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state colspan="6" message="No hay entregas registradas." />
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 shrink-0">
                    {{ $entregas->links('pagination::tailwind') }}
                </div>

                <x-crud-modal>
                    @include('employee.pages.materiales.entrega-form')
                </x-crud-modal>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
