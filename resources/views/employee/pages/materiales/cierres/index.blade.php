@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Cierre de Mes</h2>
            <a href="{{ route('employee.materiales.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Ver catálogo
            </a>
        </div>

        <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-3 sm:p-4 lg:p-6">

            @if (session('message'))
                <div class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 ring-1 ring-green-200">{{ session('message') }}</div>
            @endif

            @if ($ultimoCierre)
                <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600 ring-1 ring-gray-100">
                    Último cierre: <strong>{{ $ultimoCierre->etiqueta }}</strong>, hasta el {{ $ultimoCierre->fecha_cierre->format('d/m/Y') }}.
                </div>
            @endif

            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Estado actual (antes de cerrar)</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Valor del inventario (S/IGV)</p>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['valor_inventario'], 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Cotizaciones PENDIENTES (no se archivan)</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $generales['cotizaciones_pendientes'] }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Total valorizado a contratistas</p>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['total_valorizado_contratistas'], 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-100">
                        <p class="text-xs text-gray-400">Ejecutado personal directo</p>
                        <p class="text-lg font-semibold text-gray-900">S/ {{ number_format($generales['ejecutado_personal_directo'], 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <h3 class="mb-2 text-sm font-semibold text-amber-800">Cerrar el mes hará lo siguiente</h3>
                <ul class="mb-4 list-inside list-disc space-y-1 text-sm text-amber-800">
                    <li>Guardará un resumen (snapshot) del estado actual, ligado a este cierre, para siempre.</li>
                    <li>Pasará el STOCK ACTUAL de cada material como STOCK INICIAL del nuevo periodo.</li>
                    <li>Consolidará el precio vigente como precio base (solo si subió).</li>
                    <li>Archivará (no borrará) los Ingresos, lo Ejecutado, y los vales/cotizaciones ya descontadas hasta la fecha de corte — dejan de contar en los saldos del mes nuevo, pero quedan disponibles en el historial de este cierre.</li>
                    <li>Las cotizaciones a contratistas todavía PENDIENTES <strong>no se tocan</strong>: sigue abiertas hasta que se descuenten.</li>
                </ul>

                <form method="POST" action="{{ route('employee.materiales.cierres.store') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    @csrf
                    <div>
                        <label class="form-label" for="etiqueta">Etiqueta del mes que cierras</label>
                        <input type="text" id="etiqueta" name="etiqueta" value="{{ old('etiqueta', $etiquetaSugerida) }}" class="form-input" required />
                    </div>
                    <div>
                        <label class="form-label" for="fecha_cierre">Fecha de corte</label>
                        <x-date-input id="fecha_cierre" name="fecha_cierre" value="{{ old('fecha_cierre', $fechaSugerida) }}" required />
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn-brand w-full px-4 py-2 text-sm" onclick="return confirm('¿Cerrar el mes? El stock se consolida y los movimientos hasta la fecha de corte quedan archivados. Esta acción no se puede deshacer (solo se puede consultar el historial).');">
                            Cerrar mes
                        </button>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-amber-800 sm:col-span-3">
                        <input type="checkbox" name="confirmacion" value="1" class="rounded border-amber-400" required />
                        Entiendo que esta acción archiva movimientos y no se puede deshacer.
                    </label>
                </form>
            </div>

            <div>
                <h3 class="mb-2 text-sm font-semibold text-gray-700">Historial de cierres</h3>
                <div class="overflow-auto rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Etiqueta</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Fecha de corte</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2">Cerrado el</th>
                                <th class="sticky top-0 z-10 bg-white px-3 py-2 text-center">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($cierres as $cierre)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $cierre->etiqueta }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $cierre->fecha_cierre->format('d/m/Y') }}</td>
                                    <td class="px-3 py-2 text-gray-500">{{ $cierre->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <a href="{{ route('employee.materiales.cierres.show', $cierre) }}" class="text-sm font-medium text-brand-600 hover:underline">Ver</a>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state colspan="4" message="Todavía no se cerró ningún mes." />
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $cierres->links('pagination::tailwind') }}</div>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
