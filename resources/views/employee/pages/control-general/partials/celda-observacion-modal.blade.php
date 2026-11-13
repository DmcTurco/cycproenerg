{{--
    Celda de Observaciones: muestra el texto y, si se puede editar, un lápiz
    que abre el modal de Asesor/Cuadrilla/Observaciones (los 3 se guardan
    juntos con un solo "Guardar"). Qué campos trae el modal lo decide
    $camposModal según la etapa (algunas no tienen Asesor/Cuadrilla).
--}}
@if ($editable('observacion'))
    <td class="px-2 py-1.5 {{ $celdaManual }}">
        <div class="flex items-center gap-1.5">
            <span class="max-w-[12rem] flex-1 truncate" :title="f.observacion" x-text="f.observacion || '—'"></span>
            <button type="button" @click="abrirModal()" class="btn-icon shrink-0" title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                </svg>
            </button>
        </div>
    </td>

    {{-- x-teleport mueve el modal al final de <body>: así no queda "atrapado"
         dentro de la fila de la tabla (un <tr> solo admite <td>/<th>), pero
         sigue viendo "modal", "f", etc. de esta fila con normalidad. --}}
    <template x-teleport="body">
        <div x-show="modal.abierto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 p-4" @keydown.escape.window="cerrarModal()">
            <div class="w-full max-w-md rounded-lg bg-white p-5 shadow-xl">
                <h3 class="text-sm font-semibold text-gray-900">Editar datos</h3>
                <p class="mt-0.5 truncate text-xs text-gray-500">
                    {{ $cliente?->nombre ?? ('Suministro ' . ($s?->numero_suministro ?? '—')) }} ·
                    N° {{ $s?->numero_solicitud }}
                </p>

                <div class="mt-4 space-y-4">
                    @if (in_array('asesor_id', $camposModal, true))
                        @include('employee.pages.control-general.partials.modal-buscador', ['campo' => 'asesor_id', 'etiqueta' => 'Asesor'])
                    @endif
                    @if (in_array('cuadrilla_id', $camposModal, true))
                        @include('employee.pages.control-general.partials.modal-buscador', ['campo' => 'cuadrilla_id', 'etiqueta' => 'Cuadrilla'])
                    @endif
                    <div>
                        <label class="form-label">Observaciones</label>
                        <textarea x-model="modal.observacion" rows="4" class="{{ $input }} mt-1 w-full"
                            placeholder="Escribe una observación…"></textarea>
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="cerrarModal()" class="btn-secondary px-3 py-1.5 text-sm">Cancelar</button>
                    <button type="button" @click="guardarModal(@js($camposModal))" class="btn-brand px-3 py-1.5 text-sm">Guardar</button>
                </div>
            </div>
        </div>
    </template>
@else
    <td class="max-w-[16rem] truncate px-2 py-1.5 text-gray-600" title="{{ $cg->observacion }}">{{ $cg->observacion ?? '—' }}</td>
@endif
