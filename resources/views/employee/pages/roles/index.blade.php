@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Roles y permisos</h2>
            <div class="flex gap-2">
                <a href="{{ route('employee.usuarios.index') }}" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-400">Usuarios</a>
                <a href="{{ route('employee.roles.create') }}" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Nuevo rol</a>
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-3 p-3 sm:p-4 lg:min-h-0 lg:p-4">
            @if (session('message'))
                <div class="rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 ring-1 ring-green-200">{{ session('message') }}</div>
            @endif
            <x-form-errors />

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Rol</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Permisos</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5 text-center">Usuarios</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($roles as $rol)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $rol->name }}</td>
                                <td class="px-4 py-2 text-gray-600">
                                    @if ($rol->name === $administrador)
                                        <span class="badge bg-brand-100 text-brand-700">Acceso total</span>
                                    @else
                                        {{ $rol->permissions_count }} de {{ $totalPermisos }}
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-center text-gray-600">{{ $rol->users_count }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($rol->name === $administrador)
                                            <span class="text-xs text-gray-400">No editable</span>
                                        @else
                                            <a href="{{ route('employee.roles.edit', $rol) }}" class="btn-icon h-8 w-8" title="Editar rol">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('employee.roles.destroy', $rol) }}"
                                                data-confirm="¿Eliminar el rol {{ $rol->name }}?" data-confirm-button="Sí, eliminar">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon h-8 w-8 text-red-500 hover:bg-red-50" title="Eliminar rol">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="4" message="No hay roles." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
