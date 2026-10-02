@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div
        x-data="crudModal({
            baseUrl: '{{ route('employee.usuarios.index') }}',
            unwrap: 'usuario',
            defaults: { id: null, name: '', email: '', password: '', rol: '' },
        })"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Usuarios</h2>
            <div class="flex gap-2">
                <a href="{{ route('employee.roles.index') }}" class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-brand-400">Roles y permisos</a>
                <button type="button" @click="openCreate()" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Nuevo usuario</button>
            </div>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            @if (session('message'))
                <div class="mb-3 rounded-lg bg-green-50 px-3 py-2 text-sm text-green-700 ring-1 ring-green-200">{{ session('message') }}</div>
            @endif

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Nombre</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Correo</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Rol</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($usuarios as $usuario)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900">
                                    {{ $usuario->name }}
                                    @if ($usuario->is(auth('employee')->user()))
                                        <span class="ml-1 text-xs text-gray-400">(usted)</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-gray-600">{{ $usuario->email }}</td>
                                <td class="px-4 py-2">
                                    @forelse ($usuario->roles as $rol)
                                        <span class="badge {{ $rol->name === config('permisos.rol_administrador') ? 'bg-brand-100 text-brand-700' : 'bg-gray-100 text-gray-700' }}">{{ $rol->name }}</span>
                                    @empty
                                        <span class="badge bg-amber-100 text-amber-700">Sin rol (sin acceso)</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="openEdit({{ $usuario->id }})" class="btn-icon h-8 w-8" title="Editar usuario">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        @unless ($usuario->is(auth('employee')->user()))
                                            <button type="button" @click="remove({{ $usuario->id }})" class="btn-icon h-8 w-8 text-red-500 hover:bg-red-50" title="Eliminar usuario">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="4" message="No hay usuarios." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <x-crud-modal>
            <div>
                <label class="form-label" for="u_name">Nombre</label>
                <input id="u_name" type="text" x-model="form.name" class="form-input" />
                <p class="form-error" x-show="errors.name" x-text="errors.name"></p>
            </div>
            <div>
                <label class="form-label" for="u_email">Correo (para iniciar sesión)</label>
                <input id="u_email" type="email" x-model="form.email" class="form-input" />
                <p class="form-error" x-show="errors.email" x-text="errors.email"></p>
            </div>
            <div>
                <label class="form-label" for="u_password">Contraseña</label>
                <input id="u_password" type="password" x-model="form.password" class="form-input" autocomplete="new-password"
                    :placeholder="mode === 'edit' ? 'Dejar vacío para no cambiarla' : 'Mínimo 8 caracteres'" />
                <p class="form-error" x-show="errors.password" x-text="errors.password"></p>
            </div>
            <div>
                <label class="form-label" for="u_rol">Rol</label>
                <select id="u_rol" x-model="form.rol" class="form-input">
                    <option value="">Seleccione un rol…</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol }}">{{ $rol }}</option>
                    @endforeach
                </select>
                <p class="form-error" x-show="errors.rol" x-text="errors.rol"></p>
                <p class="mt-1 text-xs text-gray-400">Los permisos de cada rol se configuran en <a href="{{ route('employee.roles.index') }}" class="text-brand-600 hover:underline">Roles y permisos</a>.</p>
            </div>
        </x-crud-modal>
    </div>
@endsection
