@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    @php($cuadrilla = $corte['cuadrilla'])
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Corte — {{ $cuadrilla->nombre }}</h2>
            <a href="{{ route('employee.materiales.resumen.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Volver al resumen
            </a>
        </div>

        <div class="flex flex-1 flex-col gap-5 overflow-y-auto p-3 sm:p-4 lg:p-6">

            @if (session('message'))
                <div class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 ring-1 ring-green-200">
                    {{ session('message') }}
                </div>
            @endif

            <form method="GET" action="{{ route('employee.materiales.resumen.corte', $cuadrilla) }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Desde (solo referencia, no filtra)</label>
                    <input type="date" name="desde" value="{{ $desde?->toDateString() }}"
                        class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Hasta (fecha de corte)</label>
                    <input type="date" name="hasta" value="{{ $corte['hasta']->toDateString() }}"
                        class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Actualizar</button>
                <a href="{{ route('employee.materiales.resumen.pdf', $cuadrilla) }}?hasta={{ $corte['hasta']->toDateString() }}{{ $desde ? '&desde=' . $desde->toDateString() : '' }}"
                    class="ml-auto rounded-lg bg-gray-800 px-3 py-1.5 text-sm font-semibold text-white hover:bg-gray-900">
                    Descargar PDF
                </a>
            </form>

            @if ($corte['tipo'] === 'personal_directo')
                {{-- LIQUIDACION DE MATERIALES (acumulado al hasta) --}}
                <p class="text-xs text-gray-500">
                    Liquidación acumulada al {{ $corte['hasta']->format('d/m/Y') }}. Saldo &gt; 0: material en su poder o por sustentar (se valoriza a costo).
                    Saldo &lt; 0: usó material que no retiró con vale — no va a su favor, valor = 0 (revisar).
                </p>

                <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Código</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Descripción</th>
                                <th class="px-3 py-2 text-center font-medium text-gray-500">Unid.</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Retirado (vales)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Ejecutado</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Devuelto</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Saldo en su poder</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">P. unit. S/ (costo)</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Valor S/</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($corte['items'] as $item)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $item['material']->codigo }}</td>
                                    <td class="px-3 py-2 text-gray-900">{{ $item['material']->descripcion }}</td>
                                    <td class="px-3 py-2 text-center text-gray-600">{{ $item['unidad'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($item['retirado'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($item['ejecutado'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($item['devuelto'], 2) }}</td>
                                    <td class="px-3 py-2 text-right font-medium {{ $item['saldo'] < 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($item['saldo'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($item['precio_unitario'], 2) }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900">{{ number_format($item['valor'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-6 text-center text-gray-400">Sin movimientos de {{ $cuadrilla->nombre }} hasta esa fecha.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($corte['items']))
                            <tfoot>
                                <tr class="bg-amber-50 font-semibold">
                                    <td colspan="8" class="px-3 py-2 text-right text-gray-700">Valor del saldo en su poder (a costo, sin IGV) S/</td>
                                    <td class="px-3 py-2 text-right text-gray-900">{{ number_format($corte['valor_total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            @else
                {{-- COTIZACIONES PENDIENTES DE DESCUENTO --}}
                <p class="text-xs text-gray-500">Cotizaciones PENDIENTES de descuento emitidas hasta el {{ $corte['hasta']->format('d/m/Y') }}.</p>

                <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Fecha</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">N° Cotización</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Monto S/IGV</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">IGV</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Total C/IGV</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($corte['cotizaciones'] as $cot)
                                <tr>
                                    <td class="px-3 py-2 text-gray-600">{{ $cot->fecha->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-gray-900">
                                        <a href="{{ route('employee.materiales.cotizaciones.pdf', $cot) }}" class="text-brand-700 hover:underline">{{ $cot->numero }}</a>
                                    </td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($cot->monto_sin_igv, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($cot->igv, 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-900">{{ number_format($cot->total_con_igv, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-400">Sin cotizaciones pendientes de {{ $cuadrilla->nombre }} hasta esa fecha.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if (count($corte['cotizaciones']))
                            <tfoot>
                                <tr class="bg-amber-50 font-semibold">
                                    <td colspan="2" class="px-3 py-2 text-right text-gray-700">Total a descontar en la valorización</td>
                                    <td class="px-3 py-2 text-right text-gray-900">{{ number_format($corte['totales']['monto_sin_igv'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-900">{{ number_format($corte['totales']['igv'], 2) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-900">{{ number_format($corte['totales']['total_con_igv'], 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                @if (count($corte['cotizaciones']))
                    <form method="POST" action="{{ route('employee.materiales.resumen.cerrar', $cuadrilla) }}"
                        class="flex flex-wrap items-end gap-3 rounded-lg bg-amber-50 p-3 ring-1 ring-amber-200"
                        onsubmit="return confirm('¿Marcar las {{ count($corte['cotizaciones']) }} cotización(es) listadas como DESCONTADO EN VALORIZACION? Esto no se puede deshacer desde acá.');">
                        @csrf
                        <input type="hidden" name="hasta" value="{{ $corte['hasta']->toDateString() }}" />
                        <input type="hidden" name="desde" value="{{ $desde?->toDateString() }}" />
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">N° de valorización</label>
                            <input type="text" name="n_valorizacion" required placeholder="ej. VAL-2026-15"
                                class="w-48 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                        </div>
                        <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-700">
                            Cerrar corte — marcar como descontado
                        </button>
                    </form>
                @endif
            @endif
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
