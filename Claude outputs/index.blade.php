@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)
@php($backUrl = route('employee.technicals.index'))

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Historial de {{ $tecnico->nombre }}</h2>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
        <form action="{{ route('employee.technicals.record.index', $tecnico->id) }}" method="GET" class="mb-4 flex items-center gap-3">
            <h2 class="flex-1 text-lg font-semibold text-gray-900">Historial</h2>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por N° solicitud" class="form-input max-w-xs" />
            <button type="submit" class="btn-secondary">Buscar</button>
            <a href="{{ route('employee.technicals.record.index', $tecnico->id) }}" class="btn-icon" title="Limpiar filtro">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </a>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <th class="px-4 py-3">N° Solicitud</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Registrado</th>
                        <th class="px-4 py-3">Fecha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($historial as $item)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $item->numero_solicitud }}</td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $item->estado_badge }}">{{ $item->estado_nombre }} - {{ $item->abreviatura }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $item->descripcion }}</td>
                            <td class="px-4 py-3 text-gray-600">Registrado por: {{ $item->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $item->created_at }}</td>
                        </tr>
                    @empty
                        <x-empty-state colspan="5" message="No existen solicitudes registradas." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $historial->links('pagination::tailwind') }}
        </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
