<div x-data="{ pageIds: @js($solicitudesAsignadas->pluck('id')->map(fn ($id) => (string) $id)->all()) }">
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <th class="px-4 py-3">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                            title="Seleccionar todos (página actual)"
                            :checked="pageIds.length > 0 && pageIds.every((id) => selectedAssigned.includes(id))"
                            @change="selectedAssigned = $event.target.checked
                                ? [...new Set([...selectedAssigned, ...pageIds])]
                                : selectedAssigned.filter((id) => !pageIds.includes(id))">
                    </th>
                    <th class="px-4 py-3">N° Solicitud</th>
                    <th class="px-4 py-3">Solicitante</th>
                    <th class="px-4 py-3">Ubicación</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($solicitudesAsignadas as $solicitud)
                    <tr>
                        <td class="px-4 py-3">
                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500"
                                value="{{ $solicitud->id }}" x-model="selectedAssigned">
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $solicitud->numero_solicitud }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ $solicitud->solicitante_nombre }}</td>
                        <td class="px-4 py-3">
                            <button type="button" class="btn-icon" title="Ver ubicación"
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
                            <span class="badge {{ $solicitud->estado_badge }}">
                                {{ $solicitud->estado_nombre }}@if($solicitud->abreviatura) - {{ $solicitud->abreviatura }}@endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button type="button" class="btn-icon text-red-500 hover:bg-red-50" title="Quitar del técnico"
                                :disabled="busyRowId === {{ $solicitud->id }}"
                                @click="deleteOne({{ $solicitud->id }})">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <x-empty-state colspan="6" message="No hay solicitudes asignadas." />
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $solicitudesAsignadas->links('pagination::tailwind') }}
    </div>
</div>
