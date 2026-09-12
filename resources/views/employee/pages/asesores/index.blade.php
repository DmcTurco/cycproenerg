@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div
        x-data="crudModal({
            baseUrl: '{{ route('employee.advisers.index') }}',
            unwrap: 'asesor',
            defaults: { id: null, nombre: '', tipo_documento: '', numero_documento_identificacion: '', telefono: '', email: '', direccion: '', fecha_contratacion: '', comision: '', estado: '', observaciones: '' },
        })"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Asesores</h2>
            <button type="button" @click="openCreate()" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Registrar</button>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Documento</th>
                            <th class="px-4 py-3">Contacto</th>
                            <th class="px-4 py-3 text-center">Ventas</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($asesores as $asesor)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $asesor->nombre }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="badge bg-gray-100 text-gray-700">{{ $asesor->tipo_documento ?? 'N/A' }}</span>
                                    <span class="ml-1 text-gray-600">{{ $asesor->numero_documento_identificacion ?? '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <p class="text-sm">{{ $asesor->email ?? '—' }}</p>
                                    <p class="text-xs text-gray-400">{{ $asesor->telefono ?? '—' }}</p>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $asesor->numero_ventas ?? 0 }}</td>
                                <td class="px-4 py-3">
                                    @if ($asesor->estado === 'activo')
                                        <span class="badge bg-green-100 text-green-700">Activo</span>
                                    @elseif ($asesor->estado === 'inactivo')
                                        <span class="badge bg-gray-100 text-gray-700">Inactivo</span>
                                    @else
                                        <span class="badge bg-gray-100 text-gray-500">Sin definir</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="openEdit({{ $asesor->id }})" class="btn-icon" title="Editar asesor">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="remove({{ $asesor->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar asesor">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="6" message="No existen asesores registrados." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $asesores->links('pagination::tailwind') }}
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>

        <x-crud-modal>
            @include('employee.pages.asesores.form')
        </x-crud-modal>
    </div>
@endsection
