@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;

    $coloresMovimiento = [
        \App\Models\Ejecutado::MOVIMIENTO_SALIDA => 'bg-blue-100 text-blue-700',
        \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION => 'bg-amber-100 text-amber-700',
    ];
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Registro rápido / Ejecutado</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            <p class="mb-3 max-w-3xl text-sm text-gray-500">
                Lo que el personal directo realmente usó (SALIDA) o devolvió (DEVOLUCION) — se valoriza a costo con el
                precio vigente actual del material (no una foto: si el precio cambia, el total mostrado acá cambia
                también, igual que en el Excel). Los contratistas no reportan acá.
            </p>

            <form method="GET" action="{{ route('employee.materiales.ejecutados.index') }}" class="mb-3 flex flex-wrap items-end gap-2">
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
                    <label class="form-label" for="filter_material">Material</label>
                    <select id="filter_material" name="material_id" class="form-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        @foreach ($materiales as $material)
                            <option value="{{ $material->id }}" @selected((string) $materialId === (string) $material->id)>{{ $material->codigo }} — {{ $material->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="filter_movimiento">Movimiento</label>
                    <select id="filter_movimiento" name="movimiento" class="form-input" onchange="this.form.submit()">
                        <option value="">Todos</option>
                        <option value="{{ \App\Models\Ejecutado::MOVIMIENTO_SALIDA }}" @selected($movimiento === \App\Models\Ejecutado::MOVIMIENTO_SALIDA)>SALIDA</option>
                        <option value="{{ \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION }}" @selected($movimiento === \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION)>DEVOLUCION</option>
                    </select>
                </div>
                @if ($cuadrillaId || $materialId || $movimiento)
                    <a href="{{ route('employee.materiales.ejecutados.index') }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                @endif
            </form>

            <div class="mb-3 flex shrink-0 items-center justify-between">
                <p class="text-xs text-gray-500">Total: <strong>{{ $ejecutados->total() }}</strong></p>
                <a href="{{ route('employee.materiales.ejecutados.create') }}" class="btn-brand px-3 py-1.5 text-sm">Nuevo registro rápido</a>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Fecha</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Cuadrilla</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Material</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Cantidad</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Tipo trabajo</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">N° suministro</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Movimiento</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-right">Total S/IGV</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($ejecutados as $ejecutado)
                            <tr>
                                <td class="px-4 py-3 text-gray-600">{{ $ejecutado->fecha->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $ejecutado->cuadrilla->nombre ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-900">
                                    <div class="font-medium">{{ $ejecutado->material->codigo ?? '—' }}</div>
                                    <div class="text-xs text-gray-500">{{ $ejecutado->material->descripcion ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($ejecutado->cantidad, 2) }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $ejecutado->tipo_trabajo ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $ejecutado->n_suministro ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge {{ $coloresMovimiento[$ejecutado->movimiento] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $ejecutado->movimiento }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">{{ number_format($ejecutado->total(), 2) }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('employee.materiales.ejecutados.destroy', $ejecutado) }}" onsubmit="return confirm('¿Eliminar este registro? Queda en la papelera (soft delete).');">
                                        @csrf
                                        @method('DELETE')
                                        <div class="flex justify-center">
                                            <button type="submit" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="9" message="No hay registros de ejecutado todavía." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 shrink-0">
                {{ $ejecutados->links('pagination::tailwind') }}
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
