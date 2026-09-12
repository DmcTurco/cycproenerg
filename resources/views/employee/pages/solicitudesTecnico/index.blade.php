@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div
        x-data="tecnicoSolicitudes({
            assignUrl: '{{ route('employee.technicals.requests.store', $tecnico->id) }}',
            destroyUrlBase: '{{ route('employee.technicals.requests.index', $tecnico->id) }}',
            bulkDeleteUrl: '{{ route('employee.technicals.requests.bulk-delete', $tecnico->id) }}',
            mapsKey: '{{ config('services.google_maps.key') }}',
        })"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Asignar Solicitudes — {{ $tecnico->nombre }}</h2>
            <a href="{{ route('employee.technicals.index') }}" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Volver</a>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900">Solicitudes Asignadas</h2>
                <button type="button" @click="deleteSelected()" :disabled="selectedAssigned.length === 0 || busyDeleteAll"
                    class="btn-secondary text-red-600 hover:bg-red-50">
                    <span x-show="!busyDeleteAll">Eliminar seleccionadas</span>
                    <span x-show="busyDeleteAll" x-cloak>Eliminando...</span>
                </button>
            </div>

            @include('employee.pages.solicitudesTecnico.partials.tabla-asignadas')

            <div class="mt-8 border-t border-gray-100 pt-6">
                <form action="{{ route('employee.technicals.requests.index', $tecnico->id) }}" method="GET"
                    class="mb-4 flex flex-wrap items-center gap-3">
                    <h2 class="flex-1 text-lg font-semibold text-gray-900">Solicitudes Disponibles</h2>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Buscar por N° solicitud, distrito o categoría..." class="form-input max-w-xs" />
                    <button type="submit" class="btn-secondary">Buscar</button>
                    <a href="{{ route('employee.technicals.requests.index', $tecnico->id) }}" class="btn-icon" title="Limpiar filtro">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </a>
                    <button type="button" @click="assignSelected()" :disabled="selectedAvailable.length === 0 || busyAssignAll" class="btn-brand">
                        <span x-show="!busyAssignAll">Asignar seleccionadas</span>
                        <span x-show="busyAssignAll" x-cloak>Asignando...</span>
                    </button>
                </form>

                @include('employee.pages.solicitudesTecnico.partials.tabla-disponibles')
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>

        @include('employee.pages.solicitudesTecnico.ubicacion')
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tecnico-solicitudes.js')
@endpush
