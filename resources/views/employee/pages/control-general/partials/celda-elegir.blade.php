{{--
    Celda para ELEGIR de una lista registrada (técnico, contratista): no se
    escribe un nombre nuevo. Se hace clic, se filtra escribiendo y se elige;
    se guarda el id al instante. Cada nombre lleva su etiqueta Personal
    directo / Contratista. Variable: $campo ('tecnico_id' | 'contratista_id').
--}}
@php $nombre = \Illuminate\Support\Str::beforeLast($campo, '_id'); @endphp
@if ($editable($campo))
    <td class="px-2 py-1 {{ $celdaManual }}">
        <div x-data="{
                todas: @js($opciones[$campo] ?? []),
                abierto: false, texto: '', pos: { top: 0, left: 0, width: 0 },
                abrir() {
                    const r = this.$refs.entrada.getBoundingClientRect();
                    this.pos = { top: r.bottom + 4, left: r.left, width: Math.max(r.width, 240) };
                    this.texto = '';
                    this.abierto = true;
                },
                get filtradas() {
                    const t = this.texto.trim().toUpperCase();
                    return this.todas.filter(o => o.nombre.toUpperCase().includes(t)).slice(0, 30);
                },
                async elegir(op) {
                    this.abierto = false;
                    this.$refs.entrada.blur();
                    await elegirPersona('{{ $campo }}', '{{ $nombre }}', op);
                },
            }" @click.outside="abierto = false" @scroll.window="abierto = false">
            <div class="relative">
                <input type="text" x-ref="entrada" autocomplete="off" class="{{ $input }} pr-6"
                    :value="abierto ? texto : (f.{{ $nombre }} ?? '')" placeholder="Seleccionar…"
                    @focus="abrir()" @input="texto = $event.target.value"
                    @keydown.escape="abierto = false; $event.target.blur()" />
                <button type="button" x-show="f.{{ $campo }} && !abierto" x-cloak @click="elegir(null)"
                    class="absolute right-1.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600" title="Quitar">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <template x-teleport="body">
                <ul x-show="abierto" x-cloak :style="`top:${pos.top}px;left:${pos.left}px;width:${pos.width}px`"
                    class="fixed z-50 max-h-44 overflow-y-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-gray-200">
                    <template x-for="op in filtradas" :key="op.id">
                        <li @mousedown.prevent="elegir(op)"
                            class="flex cursor-pointer items-center justify-between gap-2 px-3 py-1.5 text-gray-700 hover:bg-brand-50 hover:text-brand-700">
                            <span x-text="op.nombre"></span>
                            <span x-show="op.tipo" x-text="op.tipo === 'CONTRATISTA' ? 'Contratista' : 'Personal directo'"
                                class="badge shrink-0" :class="op.tipo === 'CONTRATISTA' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'"></span>
                        </li>
                    </template>
                    <li x-show="!filtradas.length" class="px-3 py-1.5 text-xs text-gray-400">Sin coincidencias.</li>
                </ul>
            </template>
        </div>
    </td>
@else
    <td class="max-w-[16rem] truncate px-2 py-1.5 text-gray-600" title="{{ $cg->{$nombre}?->nombre }}">{{ $cg->{$nombre}?->nombre ?? '—' }}</td>
@endif
