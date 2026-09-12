@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Gestión de Clientes</h2>
            <a href="{{ route('employee.client.excel') }}" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Cargar Excel</a>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
        <form method="GET" action="{{ route('employee.client.index') }}">
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio', $fechas['fecha_inicio']) }}"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin', $fechas['fecha_fin']) }}"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div class="col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600">Buscar</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="N° solicitud, DNI, nombre o suministro"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Estado</label>
                    <select name="estado"
                        class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                        <option value="">Todos</option>
                        @foreach ($estados_Portal as $estado)
                            <option value="{{ $estado->id }}" {{ request('estado') == $estado->id ? 'selected' : '' }}>
                                {{ $estado->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-brand px-3 py-1.5 text-sm">Buscar</button>
                    <a href="{{ route('employee.client.index') }}" class="btn-secondary px-3 py-1.5 text-sm">Limpiar</a>
                </div>
            </div>
        </form>

        <div class="mt-4 mb-3 flex shrink-0 flex-col gap-1 border-t border-gray-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Solicitudes</h2>
                <p class="text-xs text-gray-500">Total de solicitudes: <strong>{{ $totalSolicitudes }}</strong></p>
            </div>
            <p class="text-xs text-gray-500">Total según la búsqueda: <strong>{{ $totalSolicitudesFiltradas }}</strong></p>
        </div>

        <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="sticky top-0 z-10 bg-white px-4 py-3">Tipo y N° de Documento</th>
                        <th class="sticky top-0 z-10 bg-white px-4 py-3">Nombre</th>
                        <th class="sticky top-0 z-10 bg-white px-4 py-3">N° de Solicitud</th>
                        <th class="sticky top-0 z-10 bg-white px-4 py-3">N° de Suministro</th>
                        <th class="sticky top-0 z-10 bg-white px-4 py-3">N° de Contrato</th>
                        <th class="sticky top-0 z-10 bg-white px-4 py-3">Estado</th>
                        <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($clientesConSolicitudes as $solicitud)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="badge bg-gray-100 text-gray-700">{{ $solicitud->tipo_documento_nombre }}</span>
                                <span class="ml-1 font-medium text-gray-700">{{ optional($solicitud->solicitante)->numero_documento ?? 'N/A' }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ optional($solicitud->solicitante)->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $solicitud->numero_solicitud }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $solicitud->numero_suministro ?? 'Pendiente de Aprobación' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $solicitud->numero_contrato_suministro ?? 'Sin contrato' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ optional($solicitud->estadoPortal)->nombre ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('employee.solicitudes.detalle', $solicitud->id) }}" class="btn-icon" title="Ver detalle">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state colspan="7" message="No existen solicitudes registradas." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 shrink-0">
            {{ $clientesConSolicitudes->links('pagination::tailwind') }}
        </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
