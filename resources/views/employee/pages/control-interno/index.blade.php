@extends('employee.layouts.user_type.auth')

{{--
    OJO: fullBleed se define AQUI ADENTRO a propósito, no en su propia
    directiva de PHP en línea separada como en las demás vistas fullBleed.
    Bug real encontrado el 13/09/2026: una directiva de PHP en línea (de una
    sola expresión) escrita justo antes de este bloque de PHP de varias
    líneas hacía que el compilador de Blade los fusionara mal, dejando este
    bloque sin compilar y visible como texto plano en la parte de arriba de
    la página. (Nota aparte, para quien edite este comentario: escribir la
    palabra reservada de la directiva de bloque de PHP, seguida de un punto
    y aparte y su cierre, dentro de este mismo comentario, también rompe la
    compilación — el compilador de Blade no siempre respeta los límites de
    los comentarios al buscar esas palabras clave. Por eso esta explicación
    evita nombrarlas literalmente.)
--}}
@php
    $fullBleed = true;

    $etiquetasFase = [
        'GENERAL' => 'General',
        'CONSTRUIDO' => 'Construido',
        'TC' => 'TC',
        'PEND_ANULACION' => 'Pend. anulación',
    ];
    $coloresSemaforo = [
        'VERDE' => 'bg-green-100 text-green-700',
        'AMBAR' => 'bg-amber-100 text-amber-700',
        'ROJO' => 'bg-red-100 text-red-700',
        'TC' => 'bg-blue-100 text-blue-700',
        'ANULAR' => 'bg-red-100 text-red-700',
        'SIN RED' => 'bg-gray-100 text-gray-600',
    ];
    $coloresFase = [
        'GENERAL' => 'bg-gray-100 text-gray-700',
        'CONSTRUIDO' => 'bg-sky-100 text-sky-700',
        'TC' => 'bg-brand-100 text-brand-700',
        'PEND_ANULACION' => 'bg-red-100 text-red-700',
    ];

    if ($alertasPortal > 0) {
        $titlePage = $alertasPortal . ' ' . Str::plural('solicitud', $alertasPortal) . ' que el portal reporta como Rechazada/Anulada y todavía no '
            . ($alertasPortal === 1 ? 'está marcada' : 'están marcadas') . ' para anular (⚠ en la columna Fase).';
        $titleVariant = 'warning';
    }
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Control Interno</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('employee.control-interno.resumen') }}"
                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    Resumen
                </a>
                <a href="{{ route('employee.control-interno.parametros.edit') }}"
                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    Parámetros y feriados
                </a>
            </div>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">

            {{-- Pestañas por fase, equivalentes a las hojas GENERAL / CONSTRUIDO / TC / PEND_ANULACION del Excel --}}
            @php($queryBase = request()->except(['fase', 'page']))
            <div class="mb-3 flex flex-wrap gap-2">
                <a href="{{ route('employee.control-interno.index', $queryBase) }}"
                    class="rounded-full px-3 py-1.5 text-sm font-medium {{ $faseActual ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-brand-600 text-white' }}">
                    Todas
                    <span class="ml-1 opacity-75">({{ $conteos->sum() }})</span>
                </a>
                @foreach ($fases as $f)
                    <a href="{{ route('employee.control-interno.index', array_merge($queryBase, ['fase' => $f])) }}"
                        class="rounded-full px-3 py-1.5 text-sm font-medium {{ $faseActual === $f ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        {{ $etiquetasFase[$f] ?? $f }}
                        <span class="ml-1 opacity-75">({{ $conteos->get($f, 0) }})</span>
                    </a>
                @endforeach
            </div>

            <form method="GET" action="{{ route('employee.control-interno.index') }}">
                @if ($faseActual)
                    <input type="hidden" name="fase" value="{{ $faseActual }}" />
                @endif
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Empresa</label>
                        <select name="empresa"
                            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">Todas</option>
                            @foreach ($empresas as $empresa)
                                <option value="{{ $empresa->codigo }}" {{ $empresaActual === $empresa->codigo ? 'selected' : '' }}>
                                    {{ $empresa->codigo }} — {{ $empresa->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Categoría</label>
                        <select name="categoria"
                            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">Todas</option>
                            @foreach ($categorias as $texto => $codigo)
                                <option value="{{ $codigo }}" {{ $categoriaActual === $codigo ? 'selected' : '' }}>
                                    {{ $texto }} ({{ $codigo }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="N° de solicitud"
                            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
                        <a href="{{ route('employee.control-interno.index', $faseActual ? ['fase' => $faseActual] : []) }}"
                            class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                    </div>
                </div>
            </form>

            <div class="mt-4 mb-3 flex shrink-0 items-center justify-between border-t border-gray-100 pt-3">
                <h2 class="text-lg font-semibold text-gray-900">Solicitudes</h2>
                <p class="text-xs text-gray-500">Total: <strong>{{ $solicitudes->total() }}</strong></p>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">N° Solicitud</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Empresa</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Categoría</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Asesor</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Cuadrilla</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Fase</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Desface</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Semáforo</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Fuera de plazo</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($solicitudes as $solicitud)
                            @php($ind = $solicitud->ci_indicadores)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $solicitud->numero_solicitud }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ optional($solicitud->empresa)->codigo ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $categorias[optional($solicitud->proyecto)->categoria_proyecto] ?? (optional($solicitud->proyecto)->categoria_proyecto ?? 'N/A') }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ optional($solicitud->asesor)->nombre ?? 'Sin asesor' }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $solicitud->tecnico->pluck('nombre')->implode(', ') ?: 'Sin asignar' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="badge {{ $coloresFase[$ind['fase']] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $etiquetasFase[$ind['fase']] ?? $ind['fase'] }}
                                    </span>
                                    @if ($ind['nuevo'])
                                        <span class="badge bg-brand-100 text-brand-700">NUEVO</span>
                                    @endif
                                    @if (($solicitud->instalacion?->rechazada || $solicitud->instalacion?->anulada) && $ind['fase'] !== 'PEND_ANULACION')
                                        <span class="badge bg-amber-100 text-amber-700"
                                            title="El portal reporta esta solicitud como {{ $solicitud->instalacion?->anulada ? 'Anulada' : '' }}{{ $solicitud->instalacion?->anulada && $solicitud->instalacion?->rechazada ? ' / ' : '' }}{{ $solicitud->instalacion?->rechazada ? 'Rechazada' : '' }}{{ $solicitud->instalacion?->motivo_anulacion ? ' — ' . $solicitud->instalacion->motivo_anulacion : '' }}. No se anuló sola: revisar y marcar a mano si corresponde.">
                                            ⚠ Portal
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                    @if (!is_null($ind['desface_dias']))
                                        <span title="{{ $ind['desface_label'] }}">{{ $ind['desface_dias'] }} d.</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($ind['semaforo'] === '')
                                        <span class="text-gray-400">—</span>
                                    @else
                                        <span class="badge {{ $coloresSemaforo[$ind['semaforo']] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ $ind['semaforo'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if (is_null($ind['fuera_de_plazo']))
                                        <span class="text-gray-400">—</span>
                                    @elseif ($ind['fuera_de_plazo'])
                                        <span class="badge bg-red-100 text-red-700">Sí</span>
                                    @else
                                        <span class="badge bg-green-100 text-green-700">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('employee.solicitudes.control-interno', $solicitud->id) }}" class="btn-icon" title="Ver Control Interno">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('employee.solicitudes.detalle', $solicitud->id) }}" class="btn-icon" title="Ver detalle de la solicitud">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="10" message="No hay solicitudes para este filtro." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 shrink-0">
                {{ $solicitudes->links('pagination::tailwind') }}
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
