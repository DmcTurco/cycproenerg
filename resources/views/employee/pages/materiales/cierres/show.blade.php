@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Cierre "{{ $cierre->etiqueta }}" — hasta {{ $cierre->fecha_cierre->format('d/m/Y') }}</h2>
            <a href="{{ route('employee.materiales.cierres.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Volver al historial
            </a>
        </div>

        <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-3 sm:p-4 lg:p-6">
            <p class="text-xs text-gray-500">
                Snapshot tomado el {{ $cierre->created_at->format('d/m/Y H:i') }}, justo antes de archivar los movimientos de este periodo.
            </p>

            @php($g = $cierre->resumen['generales'] ?? [])
            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Indicadores generales al momento del cierre</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Valor del inventario</p>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($g['valor_inventario'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Ítems SIN STOCK</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $g['items_sin_stock'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Total valorizado a contratistas</p>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($g['total_valorizado_contratistas'] ?? 0, 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Ejecutado personal directo</p>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($g['ejecutado_personal_directo'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Registro de cuadrillas al momento del cierre</h3>
                <div class="overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Cuadrilla</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Tipo</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">N° retiros</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Total valorizado</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Pendiente de descuento</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse (($cierre->resumen['cuadrillas'] ?? []) as $fila)
                                <tr>
                                    <td class="px-3 py-2 text-gray-900">{{ $fila['nombre'] }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ $fila['tipo'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ $fila['num_retiros'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">S/ {{ number_format($fila['total_valorizado'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">S/ {{ number_format($fila['pendiente_descuento'], 2) }}</td>
                                </tr>
                            @empty
                                <x-empty-state colspan="5" message="Sin cuadrillas activas al momento del cierre." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Catálogo de materiales al momento del cierre</h3>
                <div class="max-h-96 overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Código</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Descripción</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Stock inicial anterior</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Ingresos del periodo</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Salidas del periodo</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Stock final (nuevo inicial)</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Precio base anterior</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-right">Precio vigente final</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse (($cierre->resumen['materiales'] ?? []) as $m)
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-900">{{ $m['codigo'] }}</td>
                                    <td class="px-3 py-1.5 text-gray-600">{{ $m['descripcion'] }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-500">{{ number_format($m['stock_inicial_anterior'], 2) }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-500">{{ number_format($m['ingresos_periodo'], 2) }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-500">{{ number_format($m['salidas_periodo'], 2) }}</td>
                                    <td class="px-3 py-1.5 text-right font-medium text-gray-900">{{ number_format($m['stock_actual_final'], 2) }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-500">{{ number_format($m['precio_base_anterior'], 2) }}</td>
                                    <td class="px-3 py-1.5 text-right text-gray-500">{{ number_format($m['precio_vigente_final'], 2) }}</td>
                                </tr>
                            @empty
                                <x-empty-state colspan="8" message="Sin materiales." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Herramientas al momento del cierre</h3>
                <div class="max-h-80 overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Código</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Descripción</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Ubicación</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Responsable</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse (($cierre->resumen['herramientas'] ?? []) as $h)
                                <tr>
                                    <td class="px-3 py-1.5 text-gray-900">{{ $h['codigo'] }}</td>
                                    <td class="px-3 py-1.5 text-gray-600">{{ $h['descripcion'] }}</td>
                                    <td class="px-3 py-1.5 text-gray-500">{{ $h['ubicacion'] }}</td>
                                    <td class="px-3 py-1.5 text-gray-500">{{ $h['responsable'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <x-empty-state colspan="4" message="Sin herramientas." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
