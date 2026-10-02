@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $backUrl = route('employee.roles.index');
@endphp

@section('content')
    <form method="POST"
        action="{{ $rol->exists ? route('employee.roles.update', $rol) : route('employee.roles.store') }}"
        x-data="{ marcados: @js(array_values($marcados)) }"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        @csrf
        @if ($rol->exists)
            @method('PUT')
        @endif

        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">{{ $rol->exists ? 'Editar rol: ' . $rol->name : 'Nuevo rol' }}</h2>
            <a href="{{ $backUrl }}" class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Salir</a>
        </div>

        <div class="flex-1 space-y-4 overflow-auto p-3 sm:p-4 lg:p-6">
            <x-form-errors />

            <div class="flex flex-wrap items-end gap-3">
                <div class="w-full max-w-sm">
                    <label class="form-label" for="name">Nombre del rol</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $rol->name) }}" class="form-input" placeholder="ej. Almacén, Supervisor, Consulta" />
                </div>
                <div class="flex gap-2 text-sm">
                    <button type="button" class="btn-secondary" @click="marcados = @js(\App\Support\Permisos::todos())">Marcar todo</button>
                    <button type="button" class="btn-secondary" @click="marcados = @js(collect(\App\Support\Permisos::todos())->filter(fn ($p) => str_ends_with($p, '.ver'))->values())">Solo ver</button>
                    <button type="button" class="btn-secondary" @click="marcados = []">Desmarcar todo</button>
                </div>
            </div>

            <p class="text-xs text-gray-500">
                Marque lo que puede hacer este rol en cada módulo. Si marca cualquier acción de un módulo, también se le da "Ver"
                (sin poder abrir la pantalla las demás acciones no sirven). Lo que no esté marcado se oculta del menú y se bloquea.
            </p>

            <div class="overflow-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-2.5">Módulo</th>
                            <th class="px-4 py-2.5">Acciones permitidas</th>
                            <th class="px-4 py-2.5 text-center">Todo el módulo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($modulos as $modulo => $datos)
                            @php($permisosModulo = collect(array_keys($datos['acciones']))->map(fn ($a) => "{$modulo}.{$a}")->all())
                            <tr x-data="{ delModulo: @js($permisosModulo) }">
                                <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900">{{ $datos['label'] }}</td>
                                <td class="px-4 py-2">
                                    <div class="flex flex-wrap gap-x-5 gap-y-1">
                                        @foreach ($datos['acciones'] as $accion => $label)
                                            <label class="inline-flex items-center gap-1.5 text-gray-700">
                                                <input type="checkbox" name="permisos[]" value="{{ $modulo }}.{{ $accion }}" x-model="marcados"
                                                    class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-2 text-center">
                                    <input type="checkbox" title="Marcar / desmarcar todo el módulo"
                                        class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                        :checked="delModulo.every(p => marcados.includes(p))"
                                        @change="marcados = $event.target.checked
                                            ? [...new Set([...marcados, ...delModulo])]
                                            : marcados.filter(p => !delModulo.includes(p))" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-2 border-t border-gray-100 px-4 py-3 sm:px-6">
            <a href="{{ $backUrl }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-brand">Guardar rol</button>
        </div>
    </form>
@endsection
