@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@php($semaforoClases = [
    'VERDE' => 'bg-green-100 text-green-700',
    'AMBAR' => 'bg-amber-100 text-amber-700',
    'ROJO' => 'bg-red-100 text-red-700',
    'TC' => 'bg-blue-100 text-blue-700',
    'ANULAR' => 'bg-red-100 text-red-700',
    'SIN RED' => 'bg-gray-100 text-gray-600',
])

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Control Interno</h2>
            <a href="{{ route('employee.control-interno.parametros.edit') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Parámetros</a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            <form method="GET" action="{{ route('employee.control-interno.index') }}" class="mb-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Buscar por N° de solicitud</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="N° de solicitud"
                            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-600">Fase</label>
                        <select name="fase" onchange="this.form.submit()"
                            class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            <option value="">Todas</option>
                            @foreach ($fases as $fase)
                                <option value="{{ $fase }}" {{ $faseActual === $fase ? 'selected' : '' }}>{{ $fase }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="btn-secondary w-full sm:w-auto">Buscar</button>
                    </div>
                </div>
            </form>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">N° Solicitud</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Empresa</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Categoría</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Fase</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Semáforo</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Desface</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($solicitudes as $solicitud)
                            @php($ind = $solicitud->ci_indicadores)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $solicitud->numero_solicitud }}
                                    @if ($ind['nuevo'])
                                        <span class="ml-1 rounded-full bg-brand-100 px-2 py-0.5 text-[11px] font-semibold text-brand-700">NUEVO</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $solicitud->empresa->nombre ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $solicitud->proyecto->categoria_proyecto ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge bg-gray-100 text-gray-700">{{ $ind['fase'] }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $semaforoClases[$ind['semaforo']] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $ind['semaforo'] ?: '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($ind['desface_dias'] !== null)
                                        {{ $ind['desface_dias'] }} días
                                        <span class="block text-xs text-gray-400">{{ $ind['desface_label'] }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('employee.solicitudes.control-interno', $solicitud->id) }}" class="btn-icon" title="Ver Control Interno">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7" message="No hay solicitudes para este filtro." />
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
