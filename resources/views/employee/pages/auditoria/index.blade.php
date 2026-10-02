@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;

    $coloresAccion = [
        'creado' => 'bg-green-100 text-green-700',
        'actualizado' => 'bg-sky-100 text-sky-700',
        'eliminado' => 'bg-red-100 text-red-700',
        'eliminado_definitivo' => 'bg-red-100 text-red-700',
        'restaurado' => 'bg-amber-100 text-amber-700',
        'asignado' => 'bg-indigo-100 text-indigo-700',
        'desasignado' => 'bg-orange-100 text-orange-700',
        'cambio_estado' => 'bg-violet-100 text-violet-700',
        'importado' => 'bg-teal-100 text-teal-700',
        'cierre_mes' => 'bg-fuchsia-100 text-fuchsia-700',
        'login' => 'bg-gray-100 text-gray-600',
        'logout' => 'bg-gray-100 text-gray-600',
        'login_fallido' => 'bg-red-100 text-red-700',
    ];

    // Valor legible para la tabla antes / después.
    $mostrar = function ($valor) {
        if ($valor === null || $valor === '') {
            return '—';
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if (is_array($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $valor;
    };
@endphp

@section('content')
    <div x-data="{ open: false, d: null }" class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200">
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Auditoría</h2>
            <span class="text-xs text-brand-100">Quién hizo qué y qué cambió</span>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:p-4">
            <form method="GET" action="{{ route('employee.auditoria.index') }}" class="mb-3 grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-7">
                <select name="usuario" class="form-input" onchange="this.form.submit()">
                    <option value="">Todos los usuarios</option>
                    @foreach ($usuarios as $u)
                        <option value="{{ $u }}" @selected(($filtros['usuario'] ?? '') === $u)>{{ $u }}</option>
                    @endforeach
                </select>
                <select name="modulo" class="form-input" onchange="this.form.submit()">
                    <option value="">Todos los módulos</option>
                    @foreach ($modulos as $m)
                        <option value="{{ $m }}" @selected(($filtros['modulo'] ?? '') === $m)>{{ $m }}</option>
                    @endforeach
                </select>
                <select name="accion" class="form-input" onchange="this.form.submit()">
                    <option value="">Todas las acciones</option>
                    @foreach ($acciones as $clave => $label)
                        <option value="{{ $clave }}" @selected(($filtros['accion'] ?? '') === $clave)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-date-input name="desde" value="{{ $filtros['desde'] ?? '' }}" placeholder="Desde" />
                <x-date-input name="hasta" value="{{ $filtros['hasta'] ?? '' }}" placeholder="Hasta" />
                <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" class="form-input" placeholder="Buscar (código, N°, nombre…)" />
                <div class="flex gap-2">
                    <button type="submit" class="btn-brand flex-1">Filtrar</button>
                    @if (array_filter($filtros))
                        <a href="{{ route('employee.auditoria.index') }}" class="btn-secondary" title="Limpiar filtros">✕</a>
                    @endif
                </div>
            </form>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Fecha y hora</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Usuario</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Acción</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Módulo</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">Descripción</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5">IP</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-2.5 text-center">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($auditorias as $a)
                            @php
                                $campos = array_values(array_unique(array_merge(array_keys($a->antes ?? []), array_keys($a->despues ?? []))));
                                $detalle = [
                                    'fecha' => $a->created_at->format('d/m/Y H:i:s'),
                                    'usuario' => $a->usuario_nombre ?? 'Sistema',
                                    'accion' => $a->accion_label,
                                    'color' => $coloresAccion[$a->accion] ?? 'bg-gray-100 text-gray-700',
                                    'modulo' => $a->modulo ?? '—',
                                    'descripcion' => $a->descripcion,
                                    'ip' => $a->ip ?? '—',
                                    'url' => trim(($a->metodo ?? '') . ' ' . ($a->url ?? '')),
                                    'cambios' => array_map(fn ($c) => [
                                        'campo' => $c,
                                        'antes' => $mostrar(($a->antes ?? [])[$c] ?? null),
                                        'despues' => $mostrar(($a->despues ?? [])[$c] ?? null),
                                    ], $campos),
                                ];
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-2 text-gray-600">{{ $a->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="whitespace-nowrap px-4 py-2 font-medium text-gray-900">{{ $a->usuario_nombre ?? 'Sistema' }}</td>
                                <td class="px-4 py-2">
                                    <span class="badge whitespace-nowrap {{ $detalle['color'] }}">{{ $a->accion_label }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-2 text-gray-600">{{ $a->modulo ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $a->descripcion }}</td>
                                <td class="whitespace-nowrap px-4 py-2 text-xs text-gray-400">{{ $a->ip ?? '—' }}</td>
                                <td class="px-4 py-2 text-center">
                                    <button type="button" @click="d = @js($detalle); open = true" class="btn-secondary px-2.5 py-1 text-xs">Ver</button>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="7" message="No hay acciones registradas con esos filtros." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 shrink-0">
                {{ $auditorias->links('pagination::tailwind') }}
            </div>
        </div>

        {{-- Detalle de la acción: solo lectura --}}
        <x-modal max-width="xl">
            <x-slot:title>Detalle de la acción</x-slot:title>

            <template x-if="d">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3 text-sm sm:grid-cols-4">
                        <div>
                            <p class="text-xs text-gray-400">Fecha y hora</p>
                            <p class="font-medium text-gray-900" x-text="d.fecha"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Usuario</p>
                            <p class="font-medium text-gray-900" x-text="d.usuario"></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Acción</p>
                            <span class="badge" :class="d.color" x-text="d.accion"></span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Módulo</p>
                            <p class="font-medium text-gray-900" x-text="d.modulo"></p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-700" x-text="d.descripcion"></p>

                    <div x-show="d.cambios.length" class="max-h-96 overflow-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-left text-gray-500">
                                <tr>
                                    <th class="px-3 py-2 font-semibold">Campo</th>
                                    <th class="px-3 py-2 font-semibold">Antes</th>
                                    <th class="px-3 py-2 font-semibold">Después</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="c in d.cambios" :key="c.campo">
                                    <tr>
                                        <td class="px-3 py-1.5 font-medium text-gray-700" x-text="c.campo"></td>
                                        <td class="break-all px-3 py-1.5 text-red-700" x-text="c.antes"></td>
                                        <td class="break-all px-3 py-1.5 text-green-700" x-text="c.despues"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <p x-show="!d.cambios.length" class="text-sm text-gray-400">Esta acción no tiene campos modificados para mostrar.</p>

                    <p class="break-all text-[11px] text-gray-400">
                        IP <span x-text="d.ip"></span><span x-show="d.url"> · <span x-text="d.url"></span></span>
                    </p>
                </div>
            </template>

            <x-slot:footer>
                <button type="button" @click="open = false" class="btn-secondary">Cerrar</button>
            </x-slot:footer>
        </x-modal>
    </div>
@endsection
