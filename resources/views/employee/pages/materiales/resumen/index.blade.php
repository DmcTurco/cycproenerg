@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Resumen — Panel de control de Materiales</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Ver catálogo
            </a>
        </div>

        <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-3 sm:p-4 lg:p-6">

            @if (session('message'))
                <div class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 ring-1 ring-green-200">
                    {{ session('message') }}
                </div>
            @endif

            {{-- Indicadores generales, RESUMEN!C4:C15 --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Valor del inventario (a costo, S/IGV)</p>
                    <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['valor_inventario'], 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Ítems SIN STOCK</p>
                    <p class="text-lg font-semibold {{ $generales['items_sin_stock'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $generales['items_sin_stock'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Ítems POR REPONER</p>
                    <p class="text-lg font-semibold {{ $generales['items_por_reponer'] > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $generales['items_por_reponer'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Alarmas de precio (subidas en Ingresos)</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $generales['alarmas_precio_ingresos'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Precios que BAJARON (revisar precio base)</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $generales['precios_bajaron'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Total valorizado a CONTRATISTAS (S/IGV)</p>
                    <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['total_valorizado_contratistas'], 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Ejecutado PERSONAL DIRECTO (a costo, S/IGV)</p>
                    <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['ejecutado_personal_directo'], 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Cotizaciones PENDIENTES de descuento</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $generales['cotizaciones_pendientes'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Monto pendiente de descuento (C/IGV)</p>
                    <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['monto_pendiente_descuento'], 2) }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Herramientas en campo (sin devolver)</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $generales['herramientas_en_campo'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Herramientas PERDIDAS</p>
                    <p class="text-lg font-semibold {{ $generales['herramientas_perdidas'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $generales['herramientas_perdidas'] }}</p>
                </div>
                <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                    <p class="text-xs text-gray-400">Valor de herramientas PERDIDAS</p>
                    <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['valor_herramientas_perdidas'], 2) }}</p>
                </div>
            </div>
            <p class="text-xs text-gray-400">Generado {{ \Illuminate\Support\Carbon::parse($generales['generado_en'])->format('d/m/Y H:i') }} (caché de 5 min).</p>

            {{-- Registro de cuadrillas y valorizado por persona, RESUMEN!B18:K69 --}}
            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-900">Registro de cuadrillas y valorizado por persona</h3>
                <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Nombre</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Tipo</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">N° retiros</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Total valorizado S/IGV</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Pendiente de descuento C/IGV</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Corte</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($cuadrillas as $fila)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $fila['cuadrilla']->nombre }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $fila['cuadrilla']->tipo }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ $fila['num_retiros'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">S/ {{ number_format($fila['total_valorizado'], 2) }}</td>
                                    <td class="px-3 py-2 text-right {{ $fila['pendiente_descuento'] > 0 ? 'text-amber-600 font-medium' : 'text-gray-600' }}">S/ {{ number_format($fila['pendiente_descuento'], 2) }}</td>
                                    <td class="px-3 py-2">
                                        <form method="GET" action="{{ route('employee.materiales.resumen.corte', $fila['cuadrilla']) }}" class="flex items-center gap-1">
                                            <input type="date" name="hasta" value="{{ $hastaDefault }}"
                                                class="w-36 rounded-lg border border-gray-300 px-2 py-1 text-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                                            <button type="submit" class="rounded-lg bg-brand-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-brand-700">
                                                Ver corte
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3 py-6 text-center text-gray-400">No hay cuadrillas activas registradas.</td>
                                </tr>
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
