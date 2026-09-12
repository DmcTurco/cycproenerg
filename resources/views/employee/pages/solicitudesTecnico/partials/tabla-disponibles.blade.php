<div x-data="{ pageIds: @js($solicitudesDisponibles->pluck('id')->map(fn ($id) => (string) $id)->all()) }" class="flex flex-1 flex-col lg:min-h-0">
    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="sticky top-0 z-10 bg-white px-4 py-3">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                            title="Seleccionar todos (página actual)"
                            :checked="pageIds.length > 0 && pageIds.every((id) => selectedAvailable.includes(id))"
                            @change="selectedAvailable = $event.target.checked
                                ? [...new Set([...selectedAvailable, ...pageIds])]
                                : selectedAvailable.filter((id) => !pageIds.includes(id))">
                    </th>
                    <th class="sticky top-0 z-10 bg-white px-4 py-3">N° Soli.</th>
                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Solicitante</th>
                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Categoría</th>
                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Ubicación</th>
                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Estado</th>
                    <th class="sticky top-0 z-10 bg-white px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($solicitudesDisponibles as $solicitud)
                    <tr>
                        <td class="px-4 py-3">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                value="{{ $solicitud->id }}" x-model="selectedAvailable">
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $solicitud->numero_solicitud }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $solicitud->solicitante_nombre }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $solicitud->categoria_proyecto }}</td>
                        <td class="px-4 py-3">
                            <button type="button"
                                @class([
                                    'btn-icon',
                                    'text-brand-600 hover:bg-brand-50 hover:text-brand-700' => !empty($solicitud->ubicacion),
                                ])
                                title="Ver ubicación"
                                @disabled(empty($solicitud->ubicacion))
                                @click="openUbicacion(@js($solicitud->ubicacion ?? ''), @js($solicitud->departamento), @js($solicitud->provincia), @js($solicitud->distrito))">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                </svg>
                            </button>
                            <span class="text-xs text-gray-500">{{ $solicitud->departamento }} - {{ $solicitud->distrito }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $solicitud->estado_badge }}">{{ $solicitud->estado_nombre }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" class="btn-secondary px-3 py-1.5 text-xs"
                                :disabled="busyRowId === {{ $solicitud->id }}"
                                @click="assignOne({{ $solicitud->id }})">
                                <span x-show="busyRowId !== {{ $solicitud->id }}">Asignar</span>
                                <span x-show="busyRowId === {{ $solicitud->id }}" x-cloak>Asignando...</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <x-empty-state colspan="7" message="No existen solicitudes registradas." />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 shrink-0">
        {{ $solicitudesDisponibles->links('pagination::tailwind') }}
    </div>
</div>
