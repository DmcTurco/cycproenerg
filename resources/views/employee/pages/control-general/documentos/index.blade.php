@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    // Vuelve a la tabla tal como estaba (pestaña, filtros, página) si viene con ?volver=.
    $volver = (string) request('volver');
    $backUrl = $volver !== '' && str_starts_with($volver, url('/') . '/')
        ? $volver
        : route('employee.control-general.index', ['etapa' => $cg->etapa]);
    $titlePage = 'Documentos de habilitación';
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-y-auto rounded-xl bg-gray-50 ring-1 ring-gray-200"
        x-data="docsHabilitacion(@js($js + ['puedeEditar' => $puedeEditar]))">

        {{-- Encabezado: de quién es y la descarga de todo --}}
        <div class="border-b border-gray-200 bg-white px-5 py-4">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-4">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-base font-semibold text-gray-900">{{ $cliente?->nombre ?? 'Sin cliente' }}</p>
                        <div class="mt-1 flex flex-wrap gap-1.5 text-xs">
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-600">Suministro {{ $s->numero_suministro ?? '—' }}</span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-gray-600">Solicitud {{ $s->numero_solicitud }}</span>
                        </div>
                    </div>
                </div>
                <a :href="urls.zip" class="btn-secondary gap-2 px-3.5 py-2 text-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12L12 16.5m0 0L16.5 12M12 16.5V3"/></svg>
                    Descargar todo (ZIP)
                </a>
            </div>
        </div>

        <div class="mx-auto grid w-full max-w-6xl items-start gap-6 p-5 lg:grid-cols-2">

            {{-- Documentos que el sistema genera --}}
            <section>
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Documentos de habilitación</h2>
                    <span class="text-xs text-gray-500" x-text="documentos.length + ' documentos'"></span>
                </div>
                <div class="grid gap-3">
                    <template x-for="(d, i) in documentos" :key="d.clave">
                        <div class="flex items-center gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-200 transition hover:shadow-md hover:ring-brand-300">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                            </span>
                            <p class="min-w-0 flex-1 text-sm font-medium leading-snug text-gray-800" x-text="d.titulo"></p>
                            <button type="button" @click="elegir(d.clave)" class="btn-secondary shrink-0 px-3 py-1.5 text-sm">Ver</button>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Documentos subidos a mano --}}
            <section>
                <div class="mb-3 flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Documentos subidos</h2>
                    <span class="text-xs text-gray-500" x-text="archivos.length + (archivos.length === 1 ? ' archivo' : ' archivos')"></span>
                </div>

                <input type="file" x-ref="archivo" class="hidden" @change="subir($refs.archivo.files[0])"
                    accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
                <p class="mb-3 text-xs text-red-600" x-show="errorSubida" x-text="errorSubida"></p>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <template x-for="a in archivos" :key="a.id">
                        <div class="flex flex-col items-center rounded-xl bg-brand-50 p-4 text-center shadow-sm ring-1 ring-brand-200 transition hover:shadow-md">
                            <span class="mb-2 flex h-14 w-14 shrink-0 items-center justify-center rounded-xl text-xs font-bold uppercase"
                                :class="colorExt(a)" x-text="ext(a)"></span>
                            <div class="w-full min-w-0">
                                <p class="truncate text-sm font-medium text-gray-800" x-text="a.nombre" :title="a.nombre"></p>
                                <p class="truncate text-xs text-gray-500" x-text="tamano(a.tamano) + ' · ' + a.fecha"></p>
                            </div>
                            <div class="mt-3 flex shrink-0 items-center gap-1">
                                <button type="button" x-show="a.visible" @click="verArchivo(a)" class="btn-secondary px-2.5 py-1 text-xs">Ver</button>
                                <a :href="a.ver + '?descargar=1'" class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Descargar">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12L12 16.5m0 0L16.5 12M12 16.5V3"/></svg>
                                </a>
                                <button type="button" x-show="puedeEditar" @click="eliminar(a)" class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600" title="Eliminar">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.35 9m-4.78 0L9.26 9m9.97-3.21c.34.05.68.11 1.02.17m-1.02-.17L18.16 19.67a2.25 2.25 0 01-2.24 2.08H8.08a2.25 2.25 0 01-2.24-2.08L4.77 5.79m14.46 0a48 48 0 00-3.48-.4m-12 .57c.34-.06.68-.12 1.02-.17m0 0a48 48 0 013.48-.4m7.5 0v-.92c0-1.18-.91-2.16-2.09-2.2a51 51 0 00-3.32 0c-1.18.04-2.09 1.02-2.09 2.2v.92m7.5 0a48 48 0 00-7.5 0"/></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Cuadro vacío: elige (o arrastra) un archivo y se sube; luego aparece otro vacío --}}
                    <button type="button" x-show="puedeEditar" @click="$refs.archivo.click()" :disabled="subiendo"
                        @dragover.prevent="arrastrando = true" @dragleave.prevent="arrastrando = false" @drop.prevent="soltar($event)"
                        class="flex min-h-[10.5rem] flex-col items-center justify-center rounded-xl border-2 border-dashed bg-white p-4 text-center transition hover:border-brand-400 hover:bg-brand-50"
                        :class="arrastrando ? 'border-brand-400 bg-brand-50' : 'border-gray-300'">
                        <span class="mb-2 flex h-12 w-12 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                            <svg x-show="!subiendo" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <svg x-show="subiendo" class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                        </span>
                        <span class="text-sm font-medium text-gray-700" x-text="subiendo ? 'Subiendo…' : 'Subir documento'"></span>
                        <span class="mt-0.5 text-xs text-gray-400">PDF, imagen, Word o Excel</span>
                    </button>
                </div>
            </section>
        </div>

        {{-- Modal: vista previa de un documento subido --}}
        <template x-teleport="body">
            <div x-show="visto" x-cloak @keydown.escape.window="visto = null"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
                <div class="flex h-full max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                        <h2 class="truncate text-sm font-semibold text-gray-900" x-text="visto?.nombre"></h2>
                        <button type="button" @click="visto = null" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Cerrar">✕</button>
                    </div>
                    <div class="min-h-0 flex-1 bg-gray-100">
                        <template x-if="visto"><iframe :src="visto.ver" title="Documento" class="h-full w-full border-0"></iframe></template>
                    </div>
                </div>
            </div>
        </template>

        {{-- Modal: vista previa + formulario del documento elegido --}}
        <template x-teleport="body">
        <div x-show="doc" x-cloak @keydown.escape.window="cerrar()"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
            <div class="flex h-full max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-gray-900" x-text="actual.titulo"></h2>
                    <button type="button" @click="cerrar()" class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Cerrar">✕</button>
                </div>
                <div class="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-[minmax(0,1fr)_22rem]">

            {{-- Vista previa --}}
            <div class="min-h-[20rem] bg-gray-100 lg:min-h-0">
                <template x-if="doc">
                    <iframe :src="src" title="Vista previa" class="h-full min-h-[20rem] w-full border-0 lg:min-h-0"></iframe>
                </template>
            </div>

            {{-- Formulario: solo lo que el sistema todavía no tiene --}}
            <aside class="border-t border-gray-100 p-4 lg:overflow-y-auto lg:border-l lg:border-t-0">

                @if ($faltantes)
                    <div class="mb-4 rounded-md bg-amber-50 p-3 text-xs text-amber-800 ring-1 ring-amber-200">
                        <p class="mb-1 font-semibold">Falta en el sistema</p>
                        <ul class="list-disc space-y-0.5 pl-4">
                            @foreach ($faltantes as $falta)
                                <li>{{ $falta }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div x-show="doc">
                <p class="mb-3 text-xs text-gray-500">
                    Lo demás sale solo de la solicitud. Estos datos se guardan para esta solicitud.
                </p>

                <div class="space-y-3">

                    <div x-show="tiene('n_instalacion')">
                        <label class="form-label">N° de instalación</label>
                        <input type="text" x-model="datos.n_instalacion" :disabled="!puedeEditar" class="form-input w-full" />
                    </div>

                    <div x-show="tiene('n_certificado')">
                        <label class="form-label">N° de certificado</label>
                        <input type="text" x-model="datos.n_certificado" :disabled="!puedeEditar" class="form-input w-full" />
                    </div>

                    <div x-show="tiene('fecha')">
                        <label class="form-label">Fecha</label>
                        <x-date-input x-model="datos.fecha" />
                    </div>

                    <div x-show="tiene('lugar')">
                        <label class="form-label">Lugar</label>
                        <input type="text" x-model="datos.lugar" :disabled="!puedeEditar" class="form-input w-full" />
                    </div>

                    <div class="grid grid-cols-2 gap-3" x-show="tiene('material') || tiene('tipo') || tiene('potencia_nominal')">
                        <div x-show="tiene('material')">
                            <label class="form-label">Material</label>
                            <select x-model="datos.material" :disabled="!puedeEditar" class="form-input w-full">
                                <template x-for="m in materiales" :key="m"><option :value="m" x-text="m"></option></template>
                            </select>
                        </div>
                        <div x-show="tiene('tipo')">
                            <label class="form-label">Tipo</label>
                            <select x-model="datos.tipo" :disabled="!puedeEditar" class="form-input w-full">
                                <template x-for="t in tipos" :key="t"><option :value="t" x-text="t"></option></template>
                            </select>
                        </div>
                        <div x-show="tiene('potencia_nominal')" class="col-span-2">
                            <label class="form-label">Potencia nominal</label>
                            <input type="text" x-model="datos.potencia_nominal" :disabled="!puedeEditar" placeholder="Ej. 11KW" class="form-input w-full" />
                        </div>
                    </div>

                    {{-- Gasodomésticos instalados (certificado) --}}
                    <div x-show="tiene('items')">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="form-label">Gasodomésticos instalados</span>
                            <button type="button" x-show="puedeEditar && datos.items.length < filasItems" @click="agregarItem()"
                                class="text-xs font-medium text-brand-600 hover:text-brand-700">+ Agregar</button>
                        </div>
                        <template x-for="(it, i) in datos.items" :key="i">
                            <div class="mb-2 grid grid-cols-[1.6fr_0.7fr_1fr_auto] items-center gap-1.5">
                                <select x-model="it.inst" :disabled="!puedeEditar" class="form-input w-full px-1.5 text-xs">
                                    <option value="">—</option>
                                    <template x-for="t in tiposInstalacion" :key="t"><option :value="t" x-text="t"></option></template>
                                </select>
                                <select x-model="it.tipo" :disabled="!puedeEditar" class="form-input w-full px-1.5 text-xs">
                                    <template x-for="t in tipos" :key="t"><option :value="t" x-text="t"></option></template>
                                </select>
                                <input type="text" x-model="it.potencia" :disabled="!puedeEditar" placeholder="kW" class="form-input w-full px-1.5 text-xs" />
                                <button type="button" x-show="puedeEditar" @click="datos.items.splice(i, 1)" class="text-gray-400 hover:text-red-600" title="Quitar">✕</button>
                            </div>
                        </template>
                        <p x-show="!datos.items.length" class="text-xs text-gray-400">Sin gasodomésticos.</p>
                    </div>

                    {{-- Puntos de consumo (acta) --}}
                    <div x-show="tiene('puntos')">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="form-label">Puntos de consumo</span>
                            <button type="button" x-show="puedeEditar && datos.puntos.length < filasPuntos" @click="agregarPunto()"
                                class="text-xs font-medium text-brand-600 hover:text-brand-700">+ Agregar</button>
                        </div>
                        <template x-for="(p, i) in datos.puntos" :key="i">
                            <div class="mb-2 rounded-md border border-gray-200 p-2">
                                <div class="mb-1.5 flex items-center justify-between">
                                    <span class="text-xs font-medium text-gray-500" x-text="'Punto ' + (i + 1)"></span>
                                    <button type="button" x-show="puedeEditar" @click="datos.puntos.splice(i, 1)" class="text-gray-400 hover:text-red-600" title="Quitar">✕</button>
                                </div>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <input type="text" x-model="p.artefacto" :disabled="!puedeEditar" placeholder="Artefacto" class="form-input col-span-2 w-full px-1.5 text-xs" />
                                    <select x-model="p.estado" :disabled="!puedeEditar" class="form-input w-full px-1.5 text-xs">
                                        <option value="">Estado</option><option>Conectado</option><option>Pendiente</option>
                                    </select>
                                    <input type="text" x-model="p.potencia" :disabled="!puedeEditar" placeholder="Potencia kW" class="form-input w-full px-1.5 text-xs" />
                                    <input type="text" x-model="p.ambiente" :disabled="!puedeEditar" placeholder="Ambiente" class="form-input w-full px-1.5 text-xs" />
                                    <input type="text" x-model="p.volumen" :disabled="!puedeEditar" placeholder="Volumen m3" class="form-input w-full px-1.5 text-xs" />
                                    <select x-model="p.confinado" :disabled="!puedeEditar" class="form-input w-full px-1.5 text-xs">
                                        <option value="">Confinado</option><option>SI</option><option>NO</option>
                                    </select>
                                    <span></span>
                                    <input type="text" x-model="p.rejilla_sup" :disabled="!puedeEditar" placeholder="Rejilla sup. cm2" class="form-input w-full px-1.5 text-xs" />
                                    <input type="text" x-model="p.rejilla_inf" :disabled="!puedeEditar" placeholder="Rejilla inf. cm2" class="form-input w-full px-1.5 text-xs" />
                                </div>
                            </div>
                        </template>
                        <p x-show="!datos.puntos.length" class="text-xs text-gray-400">Los ítems salen numerados según los puntos de la solicitud; aquí puedes detallar cada artefacto.</p>
                    </div>

                    {{-- Diseño típico (anexo) --}}
                    <div x-show="tiene('anexos')">
                        <span class="form-label">Diseños típicos a incluir</span>
                        <template x-for="a in anexos" :key="a.clave">
                            <label class="mt-1.5 flex items-start gap-2 text-sm text-gray-700">
                                <input type="checkbox" :value="a.clave" x-model="datos.anexos" :disabled="!puedeEditar"
                                    class="mt-0.5 rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                                <span x-text="a.titulo"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <p class="mt-3 text-xs" :class="estado === 'error' ? 'text-red-600' : 'text-green-600'" x-text="mensaje" x-show="mensaje"></p>

                <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                    <button type="button" x-show="puedeEditar" @click="guardar()" :disabled="estado === 'guardando'" class="btn-brand px-3 py-1.5 text-sm">
                        <span x-text="estado === 'guardando' ? 'Guardando…' : 'Guardar'"></span>
                    </button>
                    <a :href="descarga" class="btn-secondary px-3 py-1.5 text-sm">Descargar este documento</a>
                </div>
                </div>
            </aside>
                </div>
            </div>
        </div>
        </template>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('docsHabilitacion', (cfg) => ({
                ...cfg,
                doc: null,
                ver: Date.now(),
                estado: '',
                mensaje: '',

                get actual() { return this.documentos.find(d => d.clave === this.doc) || { titulo: '', campos: [] }; },
                get src() { return this.urls.pdf.replace('__DOC__', this.doc) + '?v=' + this.ver; },
                get descarga() { return this.urls.pdf.replace('__DOC__', this.doc) + '?descargar=1'; },

                tiene(campo) { return this.actual.campos.includes(campo); },
                elegir(clave) { this.doc = clave; this.mensaje = ''; this.ver = Date.now(); },
                cerrar() { this.doc = null; },

                // Documentos subidos a mano
                visto: null,
                subiendo: false,
                errorSubida: '',
                nombreNuevo: '',
                token() { return document.querySelector('meta[name="csrf-token"]')?.content || ''; },
                tamano(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; },
                verArchivo(a) { this.visto = a; },
                arrastrando: false,
                elegido: '',
                ext(a) { return (a.original.split('.').pop() || '').slice(0, 4); },
                colorExt(a) {
                    const e = this.ext(a).toLowerCase();
                    if (e === 'pdf') return 'bg-red-50 text-red-600';
                    if (['doc', 'docx'].includes(e)) return 'bg-blue-50 text-blue-600';
                    if (['xls', 'xlsx'].includes(e)) return 'bg-green-50 text-green-600';
                    return 'bg-amber-50 text-amber-600';
                },
                soltar(e) {
                    this.arrastrando = false;
                    if (this.puedeEditar && e.dataTransfer.files.length) this.subir(e.dataTransfer.files[0]);
                },

                // Se sube apenas se elige el archivo; el nombre es el del archivo.
                async subir(file) {
                    if (!file || this.subiendo) return;
                    this.subiendo = true;
                    this.errorSubida = '';
                    const fd = new FormData();
                    fd.append('archivo', file);
                    try {
                        const resp = await fetch(this.urlSubir, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.token() }, body: fd });
                        const data = await resp.json().catch(() => ({}));
                        if (!resp.ok) {
                            throw new Error((data.errors ? Object.values(data.errors)[0][0] : null) || data.message || 'No se pudo subir.');
                        }
                        this.archivos = data.archivos;
                    } catch (err) {
                        this.errorSubida = err.message;
                    } finally {
                        this.subiendo = false;
                        this.$refs.archivo.value = '';
                    }
                },

                async eliminar(a) {
                    const r = await window.Swal.fire({
                        title: '¿Eliminar este documento?',
                        text: a.nombre + ' se borrará y no se podrá recuperar.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626',
                    });
                    if (!r.isConfirmed) return;
                    const resp = await fetch(this.urlArchivo.replace('__ID__', a.id), { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': this.token() } });
                    if (resp.ok) {
                        this.archivos = (await resp.json()).archivos;
                    } else {
                        window.Swal.fire({ icon: 'error', title: 'No se pudo eliminar el documento' });
                    }
                },

                agregarItem() { this.datos.items.push({ inst: '', tipo: 'A', potencia: '' }); },
                agregarPunto() {
                    this.datos.puntos.push({ artefacto: '', estado: '', potencia: '', ambiente: '', volumen: '', confinado: '', rejilla_sup: '', rejilla_inf: '' });
                },

                async guardar() {
                    this.estado = 'guardando';
                    this.mensaje = '';
                    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    try {
                        const resp = await fetch(this.urls.guardar, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                            body: JSON.stringify(this.datos),
                        });
                        const data = await resp.json().catch(() => ({}));
                        if (!resp.ok) {
                            const primero = data.errors ? Object.values(data.errors)[0][0] : null;
                            throw new Error(primero || data.message || 'No se pudo guardar.');
                        }
                        this.datos = data.datos;
                        this.ver = Date.now(); // recarga la vista previa
                        this.estado = 'ok';
                        this.mensaje = 'Guardado';
                    } catch (e) {
                        this.estado = 'error';
                        this.mensaje = e.message;
                    }
                },
            }));
        });
    </script>
@endsection
