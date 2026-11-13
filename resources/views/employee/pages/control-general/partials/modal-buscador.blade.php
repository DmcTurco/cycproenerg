{{--
    Campo "buscar y seleccionar" dentro del modal de Asesor/Cuadrilla/
    Observaciones: se escribe para filtrar la lista registrada (Asesores o
    Personal de campo) y se hace clic para elegir. NO se puede escribir un
    nombre nuevo: se guarda el id de quien se eligió.

    Variables: $campo ('asesor_id' | 'cuadrilla_id'), $etiqueta.
--}}
<div x-data="{ todas: @js($opciones[$campo] ?? []), abierto: false, buscar: '' }" @click.outside="abierto = false">
    <label class="form-label">{{ $etiqueta }}</label>
    <div class="relative mt-1">
        <input type="text" autocomplete="off" placeholder="Buscar {{ mb_strtolower($etiqueta) }}…"
            :value="abierto ? buscar : (modal.nombres.{{ $campo }} || '')"
            @focus="buscar = ''; abierto = true" @input="buscar = $event.target.value; abierto = true"
            @keydown.escape="abierto = false"
            class="form-input w-full pr-8" />
        <button type="button" x-show="modal.{{ $campo }}" x-cloak
            @click="modal.{{ $campo }} = null; modal.nombres.{{ $campo }} = ''; abierto = false"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" title="Quitar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <ul x-show="abierto" x-cloak
            class="absolute left-0 right-0 z-10 mt-1 max-h-44 overflow-y-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-gray-200">
            <template x-for="op in todas.filter(o => o.nombre.toUpperCase().includes(buscar.trim().toUpperCase())).slice(0, 30)" :key="op.id">
                <li @mousedown.prevent="modal.{{ $campo }} = op.id; modal.nombres.{{ $campo }} = op.nombre; abierto = false"
                    class="flex cursor-pointer items-center justify-between gap-2 px-3 py-1.5 text-gray-700 hover:bg-brand-50 hover:text-brand-700">
                    <span x-text="op.nombre"></span>
                    <span x-show="op.tipo" x-text="op.tipo === 'CONTRATISTA' ? 'Contratista' : 'Personal directo'"
                        class="badge shrink-0" :class="op.tipo === 'CONTRATISTA' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'"></span>
                </li>
            </template>
            <li x-show="!todas.some(o => o.nombre.toUpperCase().includes(buscar.trim().toUpperCase()))"
                class="px-3 py-1.5 text-xs text-gray-400">Sin coincidencias.</li>
        </ul>
    </div>
</div>
