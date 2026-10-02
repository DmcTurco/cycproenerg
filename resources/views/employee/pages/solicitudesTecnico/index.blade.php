@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)
@php($backUrl = route('employee.technicals.index', ['tipo' => $tecnico->tipo]))

@section('content')
    <div
        x-data="tecnicoSolicitudes({
            assignUrl: '{{ route('employee.technicals.requests.store', $tecnico->id) }}',
            destroyUrlBase: '{{ route('employee.technicals.requests.index', $tecnico->id) }}',
            bulkDeleteUrl: '{{ route('employee.technicals.requests.bulk-delete', $tecnico->id) }}',
            mapsKey: '{{ config('services.google_maps.key') }}',
            tecnicoActivo: @js($tecnico->estaActivo()),
            tecnicoNombre: @js($tecnico->nombre),
            tecnicoEstado: @js($tecnico->estado),
        })"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Asignar Solicitudes — {{ $tecnico->nombre }}</h2>
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0 lg:overflow-hidden lg:p-4">
            <div class="flex flex-1 flex-col gap-4 lg:min-h-0 lg:flex-row">

                <div class="flex min-w-0 flex-1 flex-col lg:min-h-0">
                    {{-- Un solo buscador: el texto busca por N° / nombre / categoría, y si
                         coincide con un distrito se puede elegir de la lista y queda como
                         recuadro (chip) dentro del mismo campo. La ✕ del chip lo quita. --}}
                    <form action="{{ route('employee.technicals.requests.index', $tecnico->id) }}" method="GET"
                        @submit.prevent="enviar()"
                        x-data="{
                            todos: @js($distritosDisponibles),
                            {{-- chips: { tipo: 'distrito' | 'texto', valor } --}}
                            chips: [
                                ...@js($distritos).map(v => ({ tipo: 'distrito', valor: v })),
                                ...@js($searchWords).map(v => ({ tipo: 'texto', valor: v })),
                            ],
                            texto: '',
                            abierto: false,
                            activo: 0,
                            get sugerencias() {
                                const t = this.texto.trim().toUpperCase();
                                if (t.length < 2) return [];
                                const ya = this.chips.filter(c => c.tipo === 'distrito').map(c => c.valor);
                                return this.todos.filter(d => d.toUpperCase().includes(t) && !ya.includes(d)).slice(0, 8);
                            },
                            existe(tipo, valor) {
                                return this.chips.some(c => c.tipo === tipo && c.valor.toUpperCase() === valor.toUpperCase());
                            },
                            agregarDistrito(d) {
                                if (!this.existe('distrito', d)) this.chips.push({ tipo: 'distrito', valor: d });
                                this.texto = '';
                                this.abierto = false;
                                this.$nextTick(() => this.$root.submit());
                            },
                            {{-- pasa lo escrito a chips: un distrito exacto queda como distrito; el resto, palabra por palabra --}}
                            textoAChips() {
                                const t = this.texto.trim();
                                this.texto = '';
                                if (!t) return;
                                const distrito = this.todos.find(d => d.toUpperCase() === t.toUpperCase());
                                if (distrito) {
                                    if (!this.existe('distrito', distrito)) this.chips.push({ tipo: 'distrito', valor: distrito });
                                    return;
                                }
                                t.split(/\s+/).forEach(p => { if (!this.existe('texto', p)) this.chips.push({ tipo: 'texto', valor: p }); });
                            },
                            enviar() {
                                this.textoAChips();
                                this.abierto = false;
                                this.$nextTick(() => this.$root.submit());
                            },
                            quitar(i) {
                                this.chips.splice(i, 1);
                                this.$nextTick(() => this.$root.submit());
                            },
                            enter() {
                                if (this.abierto && this.sugerencias.length) {
                                    this.agregarDistrito(this.sugerencias[this.activo] ?? this.sugerencias[0]);
                                } else {
                                    this.enviar();
                                }
                            },
                            borrar() {
                                if (this.texto === '' && this.chips.length) this.quitar(this.chips.length - 1);
                            },
                        }"
                        class="mb-4 flex shrink-0 items-start gap-3">

                        <div class="relative min-w-0 flex-1" @click.outside="abierto = false">
                            <div class="form-input flex flex-wrap items-center gap-1" @click="$refs.buscador.focus()">
                                <template x-for="(c, i) in chips" :key="c.tipo + c.valor">
                                    <span :class="c.tipo === 'distrito' ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-gray-100 text-gray-700 ring-gray-200'"
                                        class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium ring-1">
                                        <svg x-show="c.tipo === 'distrito'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                        </svg>
                                        <span x-text="c.valor"></span>
                                        <input type="hidden" :name="c.tipo === 'distrito' ? 'distritos[]' : 'terminos[]'" :value="c.valor" />
                                        <button type="button" @click.stop="quitar(i)" class="opacity-60 hover:opacity-100" title="Quitar">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <input type="text" x-ref="buscador" x-model="texto" autocomplete="off"
                                    @input="abierto = true; activo = 0" @focus="abierto = true"
                                    @keydown.enter.prevent="enter()" @keydown.backspace="borrar()"
                                    @keydown.arrow-down.prevent="activo = Math.min(activo + 1, sugerencias.length - 1)"
                                    @keydown.arrow-up.prevent="activo = Math.max(activo - 1, 0)"
                                    @keydown.escape="abierto = false"
                                    :placeholder="chips.length ? 'Agregar otro filtro...' : 'Buscar por N° solicitud, nombre, distrito, categoría...'"
                                    class="min-w-32 flex-1 border-0 bg-transparent p-0 text-sm leading-5 shadow-none focus:outline-none focus:ring-0" />
                            </div>

                            <ul x-show="abierto && sugerencias.length" x-cloak
                                class="absolute left-0 right-0 z-20 mt-1 max-h-60 overflow-y-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-gray-200">
                                <template x-for="(d, i) in sugerencias" :key="d">
                                    <li @mousedown.prevent="agregarDistrito(d)" @mouseenter="activo = i"
                                        :class="i === activo ? 'bg-brand-50 text-brand-700' : 'text-gray-700'"
                                        class="flex cursor-pointer items-center gap-2 px-3 py-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                        </svg>
                                        <span>Distrito: <span class="font-medium" x-text="d"></span></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <button type="submit" class="btn-secondary shrink-0">Buscar</button>
                        <a href="{{ route('employee.technicals.requests.index', $tecnico->id) }}" class="btn-icon shrink-0 self-center" title="Limpiar filtros">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                        <button type="button" @click="assignSelected()" :disabled="selectedAvailable.length === 0 || busyAssignAll" class="btn-brand shrink-0 whitespace-nowrap">
                            <span x-show="!busyAssignAll">Asignar seleccionadas</span>
                            <span x-show="busyAssignAll" x-cloak>Asignando...</span>
                        </button>
                    </form>

                    @include('employee.pages.solicitudesTecnico.partials.tabla-disponibles')
                </div>

                <div class="flex min-w-0 flex-1 flex-col lg:min-h-0">
                    <div class="mb-4 flex shrink-0 items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold text-gray-900">Asignadas</h2>
                        <button type="button" @click="deleteSelected()" :disabled="selectedAssigned.length === 0 || busyDeleteAll"
                            class="btn-secondary text-red-600 hover:bg-red-50">
                            <span x-show="!busyDeleteAll">Eliminar seleccionadas</span>
                            <span x-show="busyDeleteAll" x-cloak>Eliminando...</span>
                        </button>
                    </div>

                    @include('employee.pages.solicitudesTecnico.partials.tabla-asignadas')
                </div>
            </div>
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>

        @include('employee.pages.solicitudesTecnico.ubicacion')
    </div>
@endsection

@push('scripts')
    @vite('resources/js/tecnico-solicitudes.js')
@endpush
