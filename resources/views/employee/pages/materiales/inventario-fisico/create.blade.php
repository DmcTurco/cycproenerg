@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Nuevo Inventario Físico</h2>
            <a href="{{ route('employee.materiales.inventario-fisico.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <form method="POST" action="{{ route('employee.materiales.inventario-fisico.store') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="flex-1 overflow-auto p-3 sm:p-4 lg:p-4">
                @if ($errors->any())
                    <div class="mb-3 rounded-lg bg-red-50 px-4 py-2 text-sm text-red-700">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <p class="mb-3 text-xs text-gray-500">
                    Cuenta físicamente el almacén y anota el conteo físico de lo que revises. La diferencia se calcula sola al guardar. Deja en blanco lo que no cuentes — no se guarda fila para eso.
                </p>

                <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label class="form-label" for="fecha">Fecha del inventario</label>
                        <x-date-input id="fecha" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required />
                    </div>
                    <div>
                        <label class="form-label" for="realizado_por">Realizado por</label>
                        <input type="text" id="realizado_por" name="realizado_por" value="{{ old('realizado_por') }}" class="form-input" required />
                    </div>
                    <div>
                        <label class="form-label" for="nombre_almacenero">Almacenero (firma)</label>
                        <input type="text" id="nombre_almacenero" name="nombre_almacenero" value="{{ old('nombre_almacenero') }}" class="form-input" />
                    </div>
                    <div>
                        <label class="form-label" for="nombre_supervisor">Supervisor del día (firma)</label>
                        <input type="text" id="nombre_supervisor" name="nombre_supervisor" value="{{ old('nombre_supervisor') }}" class="form-input" />
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="observacion_general">Observación general (opcional)</label>
                    <textarea id="observacion_general" name="observacion_general" rows="2" class="form-input">{{ old('observacion_general') }}</textarea>
                </div>

                <h3 class="mb-2 text-sm font-semibold text-gray-700">Materiales</h3>
                <div class="mb-6 overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Código</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Descripción</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Unid.</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Stock sistema</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Conteo físico</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Observación / ubicación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($materiales as $i => $fila)
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-900">
                                        <input type="hidden" name="materiales[{{ $i }}][material_id]" value="{{ $fila['material_id'] }}" />
                                        {{ $fila['codigo'] }}
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600">{{ $fila['descripcion'] }}</td>
                                    <td class="px-3 py-1.5 text-gray-500">{{ $fila['unidad'] }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-500">{{ number_format($fila['stock_sistema'], 2) }}</td>
                                    <td class="px-3 py-1.5 text-right">
                                        <input type="number" step="0.01" min="0" name="materiales[{{ $i }}][conteo_fisico]"
                                            value="{{ old('materiales.' . $i . '.conteo_fisico') }}"
                                            class="w-24 rounded border border-gray-300 px-2 py-1 text-right text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                    </td>
                                    <td class="px-3 py-1.5">
                                        <input type="text" name="materiales[{{ $i }}][observacion_ubicacion]"
                                            value="{{ old('materiales.' . $i . '.observacion_ubicacion') }}"
                                            class="w-full rounded border border-gray-300 px-2 py-1 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h3 class="mb-2 text-sm font-semibold text-gray-700">
                    Herramientas
                    <span class="font-normal text-gray-400">— verifica presencia física; las que el sistema espera "EN CAMPO" se validan contra la hoja de Entregas</span>
                </h3>
                <div class="overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Código</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Descripción</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Ubicación (sistema)</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">¿Está en el almacén?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($herramientas as $i => $fila)
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-900">
                                        <input type="hidden" name="herramientas[{{ $i }}][herramienta_id]" value="{{ $fila['herramienta_id'] }}" />
                                        {{ $fila['codigo'] }}
                                    </td>
                                    <td class="px-3 py-1.5 text-gray-600">{{ $fila['descripcion'] }}</td>
                                    <td class="px-3 py-1.5 text-gray-500">{{ $fila['ubicacion_sistema'] }}</td>
                                    <td class="px-3 py-1.5 text-right">
                                        <select name="herramientas[{{ $i }}][conteo_fisico]"
                                            class="rounded border border-gray-300 px-2 py-1 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                            <option value="" @selected(old('herramientas.' . $i . '.conteo_fisico') === null)>— No contada —</option>
                                            <option value="1" @selected(old('herramientas.' . $i . '.conteo_fisico') === '1')>Sí, está en almacén</option>
                                            <option value="0" @selected(old('herramientas.' . $i . '.conteo_fisico') === '0')>No está en almacén</option>
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2 border-t border-gray-100 bg-gray-50 px-4 py-3 sm:px-6">
                <a href="{{ route('employee.materiales.inventario-fisico.index') }}" class="btn-secondary px-4 py-2 text-sm">Cancelar</a>
                <button type="submit" class="btn-brand px-4 py-2 text-sm">Guardar inventario</button>
            </div>
        </form>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
