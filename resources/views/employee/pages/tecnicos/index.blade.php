@extends('employee.layouts.user_type.auth')

@section('content')
    <div
        x-data="crudModal({
            baseUrl: '{{ route('employee.technicals.index') }}',
            unwrap: 'tecnico',
            defaults: { id: null, nombre: '', tipo_documento: '', numero_documento_identificacion: '', cargo: '', email: '', password: '' },
        })"
    >
        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Técnicos</h2>
                <button type="button" @click="openCreate()" class="btn-brand">Registrar</button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">N° Documento</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Correo</th>
                            <th class="px-4 py-3">Cargo</th>
                            <th class="px-4 py-3 text-center">Historial</th>
                            <th class="px-4 py-3 text-center">Solicitudes</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($tecnicos as $tecnico)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="badge bg-gray-100 text-gray-700">{{ $tecnico->tipo_documento_nombre }}</span>
                                    <span class="ml-1 font-medium text-gray-700">{{ $tecnico->numero_documento_identificacion }}</span>
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $tecnico->nombre }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $tecnico->email }}</td>
                                <td class="px-4 py-3">
                                    <span class="badge bg-brand-100 text-brand-700">{{ $tecnico->tipo_cargo_name }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('employee.technicals.record.index', $tecnico->id) }}"
                                        class="btn-icon" title="Ver historial de solicitudes">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('employee.technicals.requests.index', $tecnico->id) }}"
                                        class="btn-icon relative" title="Asignar solicitudes">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 7h6m-6 4h6" />
                                        </svg>
                                        <span class="badge absolute -right-1 -top-1 bg-brand-600 text-white">{{ $tecnico->solicitudes_count }}</span>
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="openEdit({{ $tecnico->id }})" class="btn-icon" title="Editar técnico">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="remove({{ $tecnico->id }})" class="btn-icon text-red-500 hover:bg-red-50" title="Eliminar técnico">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7" message="No existen técnicos registrados." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $tecnicos->links('pagination::tailwind') }}
            </div>
        </div>

        <x-crud-modal>
            @include('employee.pages.tecnicos.form')
        </x-crud-modal>
    </div>
@endsection
