{{--
    Campo "buscador y seleccionar" dentro del modal de Asesor/Cuadrilla/
    Observaciones: se escribe para filtrar la lista de nombres ya registrados
    (Asesores o Personal de campo) y se hace clic para elegirlo. Si no hay
    coincidencias, igual se puede guardar lo escrito (nombre nuevo), igual
    que antes con el datalist.

    Variables: $campo ('asesor' | 'cuadrilla'), $etiqueta.
--}}
@php $opciones = $sugerencias[$campo] ?? []; @endphp
<div x-data="{ todas: @js($opciones), abierto: false }" @click.outside="abierto = false">
    <label class="form-label">{{ $etiqueta }}</label>
    <div class="relative mt-1">
        <input type="text" x-model="modal.{{ $campo }}" @focus="abierto = true" @input="abierto = true"
            autocomplete="off" placeholder="Buscar {{ mb_strtolower($etiqueta) }}…"
            class="form-input w-full pr-8" />
        <button type="button" x-show="modal.{{ $campo }}" x-cloak
            @click="modal.{{ $campo }} = ''; abierto = false"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" title="Quitar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <ul x-show="abierto" x-cloak
            class="absolute left-0 right-0 z-10 mt-1 max-h-44 overflow-y-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-gray-200">
            <template x-for="op in todas.filter(o => o.toUpperCase().includes(modal.{{ $campo }}.trim().toUpperCase())).slice(0, 30)" :key="op">
                <li @mousedown.prevent="modal.{{ $campo }} = op; abierto = false"
                    class="cursor-pointer px-3 py-1.5 text-gray-700 hover:bg-brand-50 hover:text-brand-700" x-text="op"></li>
            </template>
            <li x-show="!todas.some(o => o.toUpperCase().includes(modal.{{ $campo }}.trim().toUpperCase()))"
                class="px-3 py-1.5 text-xs text-gray-400"
                x-text="modal.{{ $campo }}.trim() ? 'Sin coincidencias — se guardará tal como lo escribiste.' : 'Sin coincidencias.'"></li>
        </ul>
    </div>
</div>
