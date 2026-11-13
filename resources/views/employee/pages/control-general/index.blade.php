@extends('employee.layouts.user_type.auth')
@use('App\Models\ControlGeneral')

@php
    $fullBleed = true;

    $q = request()->except(['page']);
    $urlCon = fn (array $cambios) => route('employee.control-general.index', array_filter(array_merge($q, $cambios), fn ($v) => !is_null($v) && $v !== ''));

    $coloresPlazo = [
        ControlGeneral::PLAZO_EN_PLAZO => 'bg-green-100 text-green-700',
        ControlGeneral::PLAZO_DESFASE => 'bg-red-100 text-red-700',
        ControlGeneral::PLAZO_SIN_FECHA => 'bg-gray-100 text-gray-600',
        ControlGeneral::PLAZO_AVANZO => 'bg-amber-100 text-amber-700',
    ];
    $coloresSituacion = [
        'Por programar' => 'bg-gray-100 text-gray-700',
        'Programado' => 'bg-sky-100 text-sky-700',
        'Observado' => 'bg-amber-100 text-amber-700',
        'Habilitado' => 'bg-green-100 text-green-700',
    ];
    $coloresTc = [
        'Concluida' => 'bg-green-100 text-green-700',
        'Rechazada' => 'bg-red-100 text-red-700',
        'Pendiente' => 'bg-amber-100 text-amber-700',
    ];

    $puedeEditar = \App\Support\Permisos::puedeRuta('employee.control-general.update');
    $editable = fn (string $campo) => $puedeEditar && in_array($campo, $editables, true);

    // Qué campos trae el modal de Asesor/Cuadrilla/Observaciones en cada
    // etapa (coincide con lo que antes se podía editar en la fila).
    $camposModal = match ($etapa) {
        ControlGeneral::APROBADA => ['asesor_id', 'cuadrilla_id', 'observacion'],
        ControlGeneral::TC_CONSTRUIDA => ['cuadrilla_id', 'observacion'],
        ControlGeneral::HABILITACION => ['asesor_id', 'cuadrilla_id', 'observacion'],
        default => ['observacion'],
    };

    // Celdas que llena la usuaria: fondo amarillo suave, como en su Excel.
    $celdaManual = 'bg-amber-50/60';
    $input = 'w-full min-w-[7rem] rounded border border-gray-300 bg-white px-1.5 py-1 text-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500';
    $select = 'rounded border border-gray-300 bg-white py-1 pl-1.5 pr-6 text-xs focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500';

    $titlePage = 'Control General';
    $navSubtitle = $ultimaCarga
        ? 'Internas, TC y Habilitación · Última carga del portal: ' . $ultimaCarga->created_at->format('d/m/Y H:i')
        : 'Internas, TC y Habilitación · Todavía no se cargó ningún Excel del portal';
@endphp

@section('nav_actions')
    <a href="{{ route('employee.control-general.resumen') }}" class="btn-secondary px-3 py-1.5 text-sm">Resumen</a>
@endsection

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
        x-data="{ conteos: @js((object) $conteos->all()), masDatos: (() => { try { return localStorage.getItem('cg-mas-datos') === '1' } catch (e) { return false } })() }"
        x-init="$watch('masDatos', v => { try { localStorage.setItem('cg-mas-datos', v ? '1' : '0') } catch (e) {} })"
        @cg-movida.window="conteos[$event.detail.de] = Math.max(0, (conteos[$event.detail.de] || 0) - 1); conteos[$event.detail.a] = (conteos[$event.detail.a] || 0) + 1">

        {{-- Una pestaña por hoja del Excel, en la barra azul (el título va arriba, en la barra principal) --}}
        <div class="flex flex-wrap items-center gap-1.5 bg-brand-600 px-3 py-2 sm:px-4">
            @foreach (ControlGeneral::ETAPAS as $clave => $nombre)
                <a href="{{ $urlCon(['etapa' => $clave, 'filtro' => null]) }}"
                    class="rounded-full px-3 py-1 text-sm font-medium transition {{ $etapa === $clave ? 'bg-white text-brand-700 shadow-sm' : 'text-white/90 hover:bg-white/15 hover:text-white' }}">
                    {{ $nombre }}
                    <span class="ml-0.5 opacity-75">(<span x-text="conteos['{{ $clave }}'] ?? 0">{{ $conteos->get($clave, 0) }}</span>)</span>
                </a>
            @endforeach
        </div>

        <div class="flex flex-1 flex-col p-3 sm:p-4 lg:min-h-0">

            {{-- Empresa + búsqueda --}}
            <div class="flex flex-wrap items-center gap-3">
                <div title="Empresa">
                    <div class="inline-flex h-[2.75rem] overflow-hidden rounded-lg border border-gray-300 text-sm">
                        @foreach ([null => 'Todas', 'CLB' => 'CLB', 'CYC' => 'C&C'] as $codigo => $texto)
                            <a href="{{ $urlCon(['empresa' => $codigo ?: null]) }}"
                                class="flex items-center px-3 font-medium {{ $empresa === ($codigo ?: null) ? 'bg-brand-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }} {{ !$loop->first ? 'border-l border-gray-300' : '' }}">
                                {{ $texto }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Buscador con recuadros (como en Asignar solicitudes): se pueden
                     escribir o pegar varios suministros / N° de solicitud (por ejemplo
                     una columna copiada del Excel) y se buscan todos a la vez. Al
                     escribir un distrito aparece en la lista y queda como un solo
                     recuadro (aunque tenga espacios, como LA VICTORIA). --}}
                <form method="GET" action="{{ route('employee.control-general.index') }}" class="flex min-w-[20rem] flex-1 items-center gap-2"
                    x-ref="formBuscar" @submit.prevent="enviar()"
                    x-data="{
                        todos: @js($distritosDisponibles),
                        chips: [
                            ...@js($distritos).map(v => ({ tipo: 'distrito', valor: v })),
                            ...@js($terminos).map(v => ({ tipo: 'texto', valor: v })),
                        ],
                        texto: '',
                        abierto: false,
                        activo: 0,
                        get sugerencias() {
                            const t = this.texto.trim().toUpperCase();
                            if (t.length < 2 || /^[0-9\s,;]+$/.test(t)) return [];
                            const ya = this.chips.filter(c => c.tipo === 'distrito').map(c => c.valor);
                            return this.todos.filter(d => d.toUpperCase().includes(t) && !ya.includes(d)).slice(0, 8);
                        },
                        existe(tipo, valor) {
                            return this.chips.some(c => c.tipo === tipo && c.valor.toUpperCase() === valor.toUpperCase());
                        },
                        distritoExacto(t) {
                            return this.todos.find(d => d.toUpperCase() === t.trim().toUpperCase());
                        },
                        {{-- lo escrito o pegado: un distrito exacto queda entero; lo demás se separa por espacios, saltos de línea, comas o ; --}}
                        agregar(valor) {
                            const t = (valor || '').trim();
                            if (!t) return;
                            const d = this.distritoExacto(t);
                            if (d) { if (!this.existe('distrito', d)) this.chips.push({ tipo: 'distrito', valor: d }); return; }
                            t.split(/[\s,;]+/).map(v => v.trim()).filter(v => v !== '')
                                .forEach(v => { if (!this.existe('texto', v)) this.chips.push({ tipo: 'texto', valor: v }); });
                        },
                        elegirDistrito(d) {
                            if (!this.existe('distrito', d)) this.chips.push({ tipo: 'distrito', valor: d });
                            this.texto = '';
                            this.abierto = false;
                            this.$nextTick(() => this.$refs.formBuscar.submit());
                        },
                        pegar(e) {
                            const t = (e.clipboardData || window.clipboardData).getData('text');
                            if (/[\s,;]/.test(t.trim()) || this.distritoExacto(t)) { e.preventDefault(); this.agregar(t); }
                        },
                        {{-- espacio o coma cierran el recuadro solo si se está escribiendo un número; si no, el espacio es parte del texto (LA VICTORIA) --}}
                        separar(e) {
                            if (/^[0-9]+$/.test(this.texto.trim())) { e.preventDefault(); this.agregar(this.texto); this.texto = ''; }
                        },
                        enter() {
                            if (this.abierto && this.sugerencias.length) {
                                this.elegirDistrito(this.sugerencias[this.activo] ?? this.sugerencias[0]);
                            } else {
                                this.enviar();
                            }
                        },
                        enviar() {
                            this.agregar(this.texto);
                            this.texto = '';
                            this.abierto = false;
                            this.$nextTick(() => this.$refs.formBuscar.submit());
                        },
                        quitar(i) {
                            this.chips.splice(i, 1);
                            this.$nextTick(() => this.$refs.formBuscar.submit());
                        },
                        borrar() {
                            if (this.texto === '' && this.chips.length) this.quitar(this.chips.length - 1);
                        },
                    }">
                    <input type="hidden" name="etapa" value="{{ $etapa }}" />
                    @if ($empresa)
                        <input type="hidden" name="empresa" value="{{ $empresa }}" />
                    @endif
                    <div class="relative min-w-0 flex-1" @click.outside="abierto = false">
                        <div class="flex max-h-32 min-h-[2.75rem] flex-wrap items-center gap-1.5 overflow-y-auto rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 focus-within:border-brand-500 focus-within:ring-1 focus-within:ring-brand-500"
                            @click="$refs.buscador.focus()">
                            <template x-for="(c, i) in chips" :key="c.tipo + c.valor">
                                <span :class="c.tipo === 'distrito' ? 'bg-brand-50 text-brand-700 ring-brand-200' : 'bg-gray-100 text-gray-700 ring-gray-200'"
                                    class="inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-medium ring-1">
                                    <svg x-show="c.tipo === 'distrito'" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                    <span x-text="c.valor"></span>
                                    <input type="hidden" :name="c.tipo === 'distrito' ? 'distritos[]' : 'terminos[]'" :value="c.valor" />
                                    <button type="button" @click.stop="quitar(i)" class="opacity-60 hover:opacity-100" title="Quitar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </span>
                            </template>
                            <input type="text" x-ref="buscador" x-model="texto" autocomplete="off"
                                @input="abierto = true; activo = 0" @focus="abierto = true"
                                @paste="pegar($event)" @keydown.enter.prevent="enter()" @keydown.backspace="borrar()"
                                @keydown.space="separar($event)" @keydown.comma="separar($event)"
                                @keydown.arrow-down.prevent="activo = Math.min(activo + 1, sugerencias.length - 1)"
                                @keydown.arrow-up.prevent="activo = Math.max(activo - 1, 0)"
                                @keydown.escape="abierto = false"
                                :placeholder="chips.length ? 'Agregar otro…' : 'Buscar: suministro, N° solicitud, cliente, DNI o distrito (puedes pegar varios)'"
                                class="min-w-32 flex-1 border-0 bg-transparent p-0 text-sm leading-6 shadow-none focus:outline-none focus:ring-0" />
                        </div>

                        <ul x-show="abierto && sugerencias.length" x-cloak
                            class="absolute left-0 right-0 z-30 mt-1 max-h-60 overflow-y-auto rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-gray-200">
                            <template x-for="(d, i) in sugerencias" :key="d">
                                <li @mousedown.prevent="elegirDistrito(d)" @mouseenter="activo = i"
                                    :class="i === activo ? 'bg-brand-50 text-brand-700' : 'text-gray-700'"
                                    class="flex cursor-pointer items-center gap-2 px-3 py-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                    <span>Distrito: <span class="font-medium" x-text="d"></span></span>
                                </li>
                            </template>
                        </ul>
                    </div>
                    <button type="submit" class="btn-brand h-[2.75rem] px-4 text-sm">Buscar</button>
                    @if ($terminos || $distritos)
                        <a href="{{ $urlCon(['terminos' => null, 'distritos' => null, 'search' => null]) }}" class="btn-secondary h-[2.75rem] px-4 text-sm" title="Quitar la búsqueda">Limpiar</a>
                    @endif
                </form>

                <label class="flex cursor-pointer items-center gap-2 whitespace-nowrap text-sm text-gray-600">
                    <input type="checkbox" x-model="masDatos" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                    Más datos del cliente
                </label>
            </div>

            {{-- Filtros rápidos de la pestaña (con contador) --}}
            @if ($subconteos)
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-gray-500">
                        {{ $etapa === ControlGeneral::APROBADA ? "Control de plazo ({$diasLimite} días):" : 'Filtrar:' }}
                    </span>
                    <a href="{{ $urlCon(['filtro' => null]) }}"
                        class="rounded-full px-2.5 py-1 text-xs font-medium {{ $filtro ? 'bg-gray-100 text-gray-600 hover:bg-gray-200' : 'bg-gray-800 text-white' }}">
                        Todas
                    </a>
                    @foreach ($subconteos as $opcion => $total)
                        <a href="{{ $urlCon(['filtro' => $opcion]) }}"
                            class="rounded-full px-2.5 py-1 text-xs font-medium {{ $filtro === $opcion ? 'bg-gray-800 text-white' : (($coloresPlazo[$opcion] ?? $coloresSituacion[$opcion] ?? $coloresTc[$opcion] ?? 'bg-gray-100 text-gray-600') . ' hover:opacity-80') }}">
                            {{ $opcion }} <span class="opacity-75">({{ $total }})</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($noEncontrados)
                <div class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 ring-1 ring-amber-200">
                    <strong>{{ count($noEncontrados) }} de {{ collect($terminos)->filter(fn ($t) => ctype_digit($t))->count() }}</strong>
                    no están en Control General (no cargados del portal, de otra provincia o anulados en el portal):
                    <span class="font-mono">{{ implode(', ', $noEncontrados) }}</span>
                </div>
            @endif

            <div class="mt-3 mb-2 flex shrink-0 items-center justify-between text-xs text-gray-500">
                <span>
                    @if ($puedeEditar)
                        Las celdas <span class="rounded bg-amber-100 px-1 text-amber-800">amarillas</span> las llenas tú: se guardan solas al cambiarlas.
                    @endif
                </span>
                <span>Total: <strong>{{ $registros->total() }}</strong></span>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead>
                        <tr class="text-left font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Empresa</th>
                            <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Suministro</th>
                            <th class="sticky top-0 z-10 bg-white px-2 py-2.5">N° Solicitud</th>
                            <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Cliente</th>
                            <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Distrito</th>
                            <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">DNI / RUC</th>
                            <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">Celular</th>
                            <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">Dirección</th>
                            <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">Tipo</th>
                            <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">FISE</th>
                            <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">Puntos</th>
                            <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">Estado portal</th>

                            @switch($etapa)
                                @case(ControlGeneral::APROBADA)
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. aprobación</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5 text-right">Días</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Control de plazo</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">¿Construida?</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Asesor</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Cuadrilla</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Reingresar / Anular</th>
                                    @break
                                @case(ControlGeneral::TC_CONSTRUIDA)
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. aprobación</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Resultado TC</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. obs.</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Contratista</th>
                                    <th x-show="masDatos" class="sticky top-0 z-10 bg-white px-2 py-2.5">Asesor</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Cuadrilla</th>
                                    @break
                                @case(ControlGeneral::HABILITACION)
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. programación</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Técnico (habilita)</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">¿Observado?</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Asesor / Cuadrilla</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">¿Habilitado?</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Situación</th>
                                    @break
                                @case(ControlGeneral::ANULACION)
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Acción</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. marca</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Estado del trámite</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Asesor</th>
                                    @break
                                @case(ControlGeneral::HABILITADA)
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. programación</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Técnico</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. habilitación</th>
                                    <th class="sticky top-0 z-10 bg-white px-2 py-2.5">¿Habilitado?</th>
                                    @break
                            @endswitch

                            <th class="sticky top-0 z-10 bg-white px-2 py-2.5">Observaciones</th>
                            @if ($etapa === ControlGeneral::HABILITACION)
                                <th class="sticky top-0 z-10 bg-white px-2 py-2.5">F. última visita</th>
                            @endif
                            <th class="sticky top-0 z-10 bg-white px-2 py-2.5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($registros as $cg)
                            @php
                                $s = $cg->solicitud;
                                $cliente = $s?->solicitante;
                                $ubi = $s?->ubicacion;
                                $coords = trim((string) $ubi?->ubicacion);
                                $celular = trim((string) $cliente?->celular);
                                $codigoEstado = $s?->estadoPortal?->codigo;
                                $plazo = $cg->controlPlazo($diasLimite, $codigoEstado);
                                $fila = [
                                    'url' => route('employee.control-general.update', $cg),
                                    'etapa' => $cg->etapa,
                                    'situacion' => $cg->situacion(),
                                    'valores' => [
                                        'construida' => $cg->construida ? '1' : '0',
                                        'asesor_id' => $cg->asesor_id,
                                        'asesor' => $cg->asesor?->nombre,
                                        'cuadrilla_id' => $cg->cuadrilla_id,
                                        'cuadrilla' => $cg->cuadrilla?->nombre,
                                        'contratista_id' => $cg->contratista_id,
                                        'contratista' => $cg->contratista?->nombre,
                                        'accion' => $cg->accion,
                                        'observacion' => $cg->observacion,
                                        'fecha_observacion' => $cg->fecha_observacion?->toDateString(),
                                        'resultado_tc' => $cg->resultado_tc,
                                        'estado_tramite' => $cg->estado_tramite,
                                        'fecha_programacion' => $cg->fecha_programacion?->toDateString(),
                                        'tecnico_id' => $cg->tecnico_id,
                                        'tecnico' => $cg->tecnico?->nombre,
                                        'fecha_ultima_visita' => $cg->fecha_ultima_visita?->toDateString(),
                                        'observado' => $cg->observado ? '1' : '0',
                                        'habilitado' => $cg->habilitado ? '1' : '0',
                                        'fecha_habilitacion' => $cg->fecha_habilitacion?->toDateString(),
                                    ],
                                ];
                            @endphp
                            <tr x-data="cgFila(@js($fila))" x-show="!movida"
                                x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                                :class="{ 'opacity-60': estado === 'guardando' }" class="align-middle hover:bg-gray-50/60">
                                <td class="whitespace-nowrap px-2 py-1.5 font-medium text-gray-700">{{ ControlGeneral::etiquetaEmpresa($s?->empresa) }}</td>
                                <td class="whitespace-nowrap px-2 py-1.5 text-gray-900">
                                    {{ $s?->numero_suministro ?? '—' }}
                                    @if ($etapa === ControlGeneral::HABILITACION && in_array($s?->numero_suministro, $duplicados, true))
                                        <span class="badge bg-red-100 text-red-700" title="Este suministro aparece más de una vez en Habilitación">DUPLICADO</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-2 py-1.5 text-gray-900">{{ $s?->numero_solicitud }}</td>
                                <td class="max-w-[14rem] truncate px-2 py-1.5 text-gray-700" title="{{ $cliente?->nombre }}">{{ $cliente?->nombre ?? '—' }}</td>
                                <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $ubi?->distrito ?? '—' }}</td>
                                <td x-show="masDatos" class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cliente?->numero_documento }}</td>
                                <td x-show="masDatos" class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $celular ?: '—' }}</td>
                                <td x-show="masDatos" class="max-w-[16rem] truncate px-2 py-1.5 text-gray-600" title="{{ $ubi?->direccion }}">{{ $ubi?->direccion ?? '—' }}</td>
                                <td x-show="masDatos" class="whitespace-nowrap px-2 py-1.5 text-gray-600" title="{{ $s?->proyecto?->sub_categoria_proyecto }}">{{ $s?->proyecto?->categoria_proyecto ?? '—' }}</td>
                                <td x-show="masDatos" class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cliente?->usuario_fise ?: '—' }}</td>
                                <td x-show="masDatos" class="whitespace-nowrap px-2 py-1.5 text-right text-gray-600">{{ $s?->instalacion?->numero_puntos_instalacion ?? '—' }}</td>
                                <td x-show="masDatos" class="max-w-[12rem] truncate px-2 py-1.5 text-gray-600" title="{{ $codigoEstado }} {{ $s?->estadoPortal?->nombre }}">{{ $codigoEstado ?? '—' }}</td>

                                @switch($etapa)
                                    @case(ControlGeneral::APROBADA)
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->fecha_aprobacion?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-right font-medium {{ $plazo === ControlGeneral::PLAZO_DESFASE ? 'text-red-600' : 'text-gray-700' }}">{{ $cg->diasDesdeAprobacion() ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5"><span class="badge {{ $coloresPlazo[$plazo] }}">{{ $plazo }}</span></td>
                                        @include('employee.pages.control-general.partials.celda-si-no', ['campo' => 'construida', 'confirmarEn' => ['1'], 'confirmar' => 'La solicitud pasará a 2. TC Construidas.'])
                                        @include('employee.pages.control-general.partials.celda-dato-modal', ['campo' => 'asesor'])
                                        @include('employee.pages.control-general.partials.celda-dato-modal', ['campo' => 'cuadrilla'])
                                        @include('employee.pages.control-general.partials.celda-select', ['campo' => 'accion', 'opciones' => ControlGeneral::ACCIONES, 'vacio' => '—', 'confirmarEn' => ControlGeneral::ACCIONES, 'confirmar' => 'La solicitud pasará a 4. Anulaciones y reingresos.'])
                                        @break

                                    @case(ControlGeneral::TC_CONSTRUIDA)
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->fecha_aprobacion?->format('d/m/Y') ?? '—' }}</td>
                                        @include('employee.pages.control-general.partials.celda-select', ['campo' => 'resultado_tc', 'opciones' => ControlGeneral::RESULTADOS_TC, 'vacio' => '—', 'confirmarEn' => ['Concluida'], 'confirmar' => 'Con TC Concluida la solicitud pasa a 3. Habilitación.'])
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600" x-text="formatoFecha(f.fecha_observacion)">{{ $cg->fecha_observacion?->format('d/m/Y') ?? '—' }}</td>
                                        @include('employee.pages.control-general.partials.celda-elegir', ['campo' => 'contratista_id'])
                                        <td x-show="masDatos" class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->asesor?->nombre ?? '—' }}</td>
                                        @include('employee.pages.control-general.partials.celda-dato-modal', ['campo' => 'cuadrilla'])
                                        @break

                                    @case(ControlGeneral::HABILITACION)
                                        @include('employee.pages.control-general.partials.celda-fecha', ['campo' => 'fecha_programacion'])
                                        @include('employee.pages.control-general.partials.celda-elegir', ['campo' => 'tecnico_id'])
                                        @include('employee.pages.control-general.partials.celda-si-no', ['campo' => 'observado'])
                                        <td class="px-2 py-1.5 {{ $celdaManual }}">
                                            <div class="flex items-center gap-1.5">
                                                <span class="max-w-[12rem] flex-1 truncate text-gray-700"
                                                    :title="(f.asesor || '—') + ' / ' + (f.cuadrilla || '—')"
                                                    x-text="(f.asesor || '—') + ' / ' + (f.cuadrilla || '—')"></span>
                                                @if ($editable('asesor_id'))
                                                    <button type="button" @click="abrirModal()" class="btn-icon shrink-0" title="Seleccionar asesor y cuadrilla">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                        @include('employee.pages.control-general.partials.celda-si-no', ['campo' => 'habilitado', 'confirmarEn' => ['1'], 'confirmar' => 'La cuenta pasará a 5. Habilitadas con fecha de hoy.'])
                                        <td class="whitespace-nowrap px-2 py-1.5">
                                            <span class="badge" :class="colorSituacion(situacion)" x-text="situacion">{{ $cg->situacion() }}</span>
                                        </td>
                                        @break

                                    @case(ControlGeneral::ANULACION)
                                        @include('employee.pages.control-general.partials.celda-select', ['campo' => 'accion', 'opciones' => ControlGeneral::ACCIONES, 'vacio' => 'Quitar (vuelve a Aprobadas)', 'confirmarEn' => [''], 'confirmar' => 'Sin acción, la solicitud vuelve a 1. Aprobadas.'])
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->fecha_marca?->format('d/m/Y') ?? '—' }}</td>
                                        @include('employee.pages.control-general.partials.celda-select', ['campo' => 'estado_tramite', 'opciones' => ControlGeneral::ESTADOS_TRAMITE, 'vacio' => '—'])
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->asesor?->nombre ?? '—' }}</td>
                                        @break

                                    @case(ControlGeneral::HABILITADA)
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->fecha_programacion?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-2 py-1.5 text-gray-600">{{ $cg->tecnico?->nombre ?? '—' }}</td>
                                        @include('employee.pages.control-general.partials.celda-fecha', ['campo' => 'fecha_habilitacion'])
                                        @include('employee.pages.control-general.partials.celda-si-no', ['campo' => 'habilitado', 'confirmarEn' => ['0'], 'confirmar' => 'La cuenta volverá a 3. Habilitación.'])
                                        @break
                                @endswitch

                                @include('employee.pages.control-general.partials.celda-observacion-modal')

                                @if ($etapa === ControlGeneral::HABILITACION)
                                    @include('employee.pages.control-general.partials.celda-fecha', ['campo' => 'fecha_ultima_visita'])
                                @endif

                                <td class="whitespace-nowrap px-2 py-1.5">
                                    <div class="flex items-center justify-center gap-0.5">
                                        <span class="w-4 text-center">
                                            <span x-show="estado === 'ok'" class="text-green-600" title="Guardado">✓</span>
                                            <span x-show="estado === 'error'" class="text-red-600" title="No se guardó">!</span>
                                        </span>
                                        @if ($celular)
                                            <a href="tel:{{ $celular }}" class="btn-icon" title="Llamar {{ $celular }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                                            </a>
                                        @endif
                                        @if ($coords)
                                            <a href="https://www.google.com/maps?q={{ urlencode($coords) }}" target="_blank" rel="noopener" class="btn-icon" title="Ver mapa">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                            </a>
                                        @endif
                                        @if ($s)
                                            <a href="{{ route('employee.solicitudes.detalle', $s->id) }}" class="btn-icon" title="Ver detalle de la solicitud">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-empty-state colspan="30" message="No hay solicitudes en esta etapa con estos filtros." />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 shrink-0">
                {{ $registros->links('pagination::tailwind') }}
            </div>
        </div>
    </div>
@endsection
