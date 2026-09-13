@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Resumen ejecutivo — Control Interno</h2>
            <a href="{{ route('employee.control-interno.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Ver listado
            </a>
        </div>

        <div class="flex flex-1 flex-col gap-6 overflow-y-auto p-3 sm:p-4 lg:p-6">

            <form method="GET" action="{{ route('employee.control-interno.resumen') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Año</label>
                    <input type="number" name="anio" value="{{ $anioActual }}" min="2020" max="2100"
                        class="w-28 rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Trimestre</label>
                    <select name="trimestre"
                        class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        @foreach ([1, 2, 3, 4] as $t)
                            <option value="{{ $t }}" {{ $trimestreActual === $t ? 'selected' : '' }}>T{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Ver</button>
                <p class="ml-auto text-xs text-gray-400">Generado {{ \Illuminate\Support\Carbon::parse($resumen['generado_en'])->format('d/m/Y H:i') }} (caché de 5 min)</p>
            </form>

            {{-- RESUMEN por empresa, equivalente a INICIO!B34:D45 del Excel --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($resumen['empresas'] as $codigo => $datos)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <h3 class="mb-3 text-sm font-semibold text-gray-900">{{ $codigo }} — {{ $datos['nombre'] }}</h3>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-lg bg-white p-3 ring-1 ring-gray-100">
                                <p class="text-xs text-gray-400">GENERAL</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $datos['general']['total'] }}</p>
                                <p class="mt-1 text-[11px] text-gray-500">
                                    {{ $datos['general']['nuevas'] }} nuevas · {{ $datos['general']['sin_red'] }} sin red · {{ $datos['general']['rojo'] }} rojo
                                </p>
                            </div>
                            <div class="rounded-lg bg-white p-3 ring-1 ring-gray-100">
                                <p class="text-xs text-gray-400">CONSTRUIDO</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $datos['construido']['total'] }}</p>
                                <p class="mt-1 text-[11px] text-gray-500">{{ $datos['construido']['sin_fecha_fin_interna'] }} sin fecha de fin</p>
                            </div>
                            <div class="rounded-lg bg-white p-3 ring-1 ring-gray-100">
                                <p class="text-xs text-gray-400">TC</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $datos['tc'] }}</p>
                            </div>
                            <div class="rounded-lg bg-white p-3 ring-1 ring-gray-100">
                                <p class="text-xs text-gray-400">PEND. ANULACIÓN</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $datos['pend_anulacion'] }}</p>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-white p-3 ring-1 ring-gray-100">
                                <p class="text-xs text-gray-400">IND 2 del trimestre ({{ $resumen['trimestre'] }} {{ $resumen['anio'] }})</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ $datos['ind2'] !== null ? number_format($datos['ind2'] * 100, 1) . '%' : '—' }}
                                </p>
                                <p class="mt-1 text-[11px] text-gray-500">Meta: {{ number_format(($resumen['meta_ind2'] ?? 0) * 100, 0) }}%</p>
                            </div>
                            <div class="rounded-lg bg-white p-3 ring-1 ring-gray-100">
                                <p class="text-xs text-gray-400">Puntaje IND 2 (máx 5)</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $datos['puntaje'] ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- BITÁCORA DE LA ÚLTIMA CARGA, equivalente a INICIO!B5:B15 del Excel (CI-8) --}}
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <h3 class="mb-3 text-sm font-semibold text-gray-900">Bitácora de la última carga</h3>

                @if (!$resumen['ultima_carga'])
                    <p class="text-sm text-gray-500">Todavía no se registró ninguna carga de Excel.</p>
                @else
                    @php($carga = $resumen['ultima_carga'])
                    <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-3 lg:grid-cols-4">
                        <div>
                            <p class="text-xs text-gray-400">Fecha y hora</p>
                            <p class="font-medium text-gray-900">{{ \Illuminate\Support\Carbon::parse($carga['fecha'])->format('d/m/Y H:i') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Archivo</p>
                            <p class="truncate font-medium text-gray-900" title="{{ $carga['nombre_archivo'] }}">{{ $carga['nombre_archivo'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Estado</p>
                            <p class="font-medium text-gray-900">{{ $carga['estado'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Registros leídos del portal</p>
                            <p class="font-medium text-gray-900">{{ $carga['total_filas'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Filas procesadas</p>
                            <p class="font-medium text-gray-900">{{ $carga['filas_procesadas'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Filas con error</p>
                            <p class="font-medium text-gray-900">{{ $carga['filas_con_error'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">NUEVAS agregadas a GENERAL</p>
                            <p class="font-medium text-gray-900">{{ $carga['nuevas_general'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Movidas GENERAL → CONSTRUIDO</p>
                            <p class="font-medium text-gray-900">{{ $carga['movidas_general_construido'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Movidas GENERAL → TC</p>
                            <p class="font-medium text-gray-900">{{ $carga['movidas_general_tc'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Movidas CONSTRUIDO → TC</p>
                            <p class="font-medium text-gray-900">{{ $carga['movidas_construido_tc'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Movidas → PEND. ANULACIÓN</p>
                            <p class="font-medium text-gray-900">{{ $carga['movidas_a_pend_anulacion'] ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Omitidas por validación mínima</p>
                            <p class="font-medium text-gray-900">{{ $carga['filas_omitidas_validacion'] ?? '—' }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-400">
                        "Eliminadas: anulación confirmada" e "Ignoradas: fuera de Lima/Callao" del Excel original no se muestran acá: la primera depende de una decisión de negocio todavía pendiente (CI-6) y la segunda de un filtro de ámbito que no está enganchado en la carga (CI-1).
                    </p>
                @endif
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
