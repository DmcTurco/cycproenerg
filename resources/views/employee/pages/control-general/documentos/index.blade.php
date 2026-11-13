@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $backUrl = route('employee.control-general.index', ['etapa' => $cg->etapa]);
    $titlePage = 'Documentos de habilitación';
@endphp

@section('content')
    <div class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
        x-data="docsHabilitacion(@js($js + ['puedeEditar' => $puedeEditar]))">

        {{-- Encabezado: de quién es y la descarga de todo --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5">
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-gray-900">{{ $cliente?->nombre ?? 'Sin cliente' }}</p>
                <p class="text-xs text-gray-500">
                    Suministro {{ $s->numero_suministro ?? '—' }} · Solicitud {{ $s->numero_solicitud }}
                </p>
            </div>
            <a :href="urls.zip" class="btn-secondary px-3 py-1.5 text-sm">Descargar todo (ZIP)</a>
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-[15rem_minmax(0,1fr)_22rem]">

            {{-- Lista de documentos --}}
            <nav class="border-b border-gray-100 p-2 lg:overflow-y-auto lg:border-b-0 lg:border-r">
                <p class="px-2 pb-1 pt-1 text-xs font-semibold uppercase tracking-wide text-gray-400">Documentos</p>
                <template x-for="d in documentos" :key="d.clave">
                    <button type="button" @click="elegir(d.clave)"
                        class="mb-1 block w-full rounded-md px-2.5 py-2 text-left text-sm transition"
                        :class="doc === d.clave ? 'bg-brand-50 font-medium text-brand-700 ring-1 ring-brand-200' : 'text-gray-700 hover:bg-gray-50'">
                        <span x-text="d.titulo"></span>
                    </button>
                </template>
            </nav>

            {{-- Vista previa --}}
            <div class="min-h-[30rem] bg-gray-100 lg:min-h-0">
                <iframe :src="src" title="Vista previa" class="h-full min-h-[30rem] w-full border-0 lg:min-h-0"></iframe>
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

                <h3 class="text-sm font-semibold text-gray-900" x-text="actual.titulo"></h3>
                <p class="mb-3 mt-0.5 text-xs text-gray-500">
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
            </aside>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('docsHabilitacion', (cfg) => ({
                ...cfg,
                doc: cfg.documentos[0].clave,
                ver: Date.now(),
                estado: '',
                mensaje: '',

                get actual() { return this.documentos.find(d => d.clave === this.doc); },
                get src() { return this.urls.pdf.replace('__DOC__', this.doc) + '?v=' + this.ver; },
                get descarga() { return this.urls.pdf.replace('__DOC__', this.doc) + '?descargar=1'; },

                tiene(campo) { return this.actual.campos.includes(campo); },
                elegir(clave) { this.doc = clave; this.mensaje = ''; },

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
