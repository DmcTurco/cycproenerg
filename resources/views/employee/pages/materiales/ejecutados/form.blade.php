@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
@endphp

@section('content')
    <div
        x-data="ejecutadoForm({
            materiales: @json($materiales->map(fn ($m) => [
                'id' => $m->id,
                'label' => $m->codigo . ' — ' . $m->descripcion,
                'precio_costo' => round($m->precioVigente() / max(1, $m->factor_metros_por_unidad), 4),
                'unidad_reporte' => $m->factor_metros_por_unidad > 1 ? 'MTS' : $m->unidad,
            ])),
            saldos: @json($saldos),
        })"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Nuevo registro rápido (Ejecutado)</h2>
            <a href="{{ route('employee.materiales.ejecutados.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <div class="flex flex-1 flex-col overflow-y-auto p-3 sm:p-4 lg:p-4">
            <p class="mb-4 max-w-2xl text-sm text-gray-500">
                Elige la cuadrilla primero: la columna "en su poder" se actualiza sola con lo que le queda de sus
                vales, menos lo ya ejecutado o devuelto. Solo PERSONAL DIRECTO reporta acá.
            </p>

            <form method="GET" action="{{ route('employee.materiales.ejecutados.create') }}" class="mb-4 max-w-xs">
                <label class="form-label" for="cuadrilla_selector">Cuadrilla</label>
                <select id="cuadrilla_selector" name="cuadrilla_id" class="form-input" onchange="this.form.submit()">
                    <option value="">Selecciona...</option>
                    @foreach ($cuadrillas as $cuadrilla)
                        <option value="{{ $cuadrilla->id }}" @selected($cuadrillaSeleccionada && $cuadrillaSeleccionada->id === $cuadrilla->id)>{{ $cuadrilla->nombre }}</option>
                    @endforeach
                </select>
                @if ($cuadrillas->isEmpty())
                    <p class="mt-1 text-xs text-red-500">No hay cuadrillas ACTIVAS de tipo PERSONAL DIRECTO registradas.</p>
                @endif
            </form>

            @if ($cuadrillaSeleccionada)
                <form method="POST" action="{{ route('employee.materiales.ejecutados.store') }}">
                    @csrf
                    <input type="hidden" name="cuadrilla_id" value="{{ $cuadrillaSeleccionada->id }}" />

                    <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 sm:max-w-4xl">
                        <div>
                            <label class="form-label" for="fecha">Fecha</label>
                            <input id="fecha" name="fecha" type="date" value="{{ old('fecha', now()->format('Y-m-d')) }}" class="form-input" />
                            <p class="form-error">{{ $errors->first('fecha') }}</p>
                        </div>
                        <div>
                            <label class="form-label" for="movimiento">Movimiento</label>
                            <select id="movimiento" name="movimiento" class="form-input" x-model="movimiento">
                                <option value="{{ \App\Models\Ejecutado::MOVIMIENTO_SALIDA }}">SALIDA</option>
                                <option value="{{ \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION }}">DEVOLUCION</option>
                            </select>
                            <p class="form-error">{{ $errors->first('movimiento') }}</p>
                        </div>
                        <div>
                            <label class="form-label" for="tipo_trabajo">Tipo de trabajo</label>
                            <select id="tipo_trabajo" name="tipo_trabajo" class="form-input">
                                <option value="">— (solo obligatorio en SALIDA)</option>
                                <option value="INSTALACION NUEVA" @selected(old('tipo_trabajo') === 'INSTALACION NUEVA')>INSTALACION NUEVA</option>
                                <option value="HABILITACION" @selected(old('tipo_trabajo') === 'HABILITACION')>HABILITACION</option>
                                <option value="SUBSANACION" @selected(old('tipo_trabajo') === 'SUBSANACION')>SUBSANACION</option>
                            </select>
                            <p class="form-error">{{ $errors->first('tipo_trabajo') }}</p>
                        </div>
                        <div>
                            <label class="form-label" for="n_suministro">N° suministro</label>
                            <input id="n_suministro" name="n_suministro" type="text" value="{{ old('n_suministro') }}" class="form-input" />
                            <p class="form-error">{{ $errors->first('n_suministro') }}</p>
                        </div>
                    </div>

                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700">Ítems — {{ $cuadrillaSeleccionada->nombre }}</h3>
                        <button type="button" @click="agregarItem()" class="btn-secondary px-3 py-1 text-sm">+ Agregar ítem</button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Material</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-36 text-right">En su poder</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-32">Cantidad</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-32 text-right">Total S/IGV</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <select x-model.number="item.material_id" class="form-input">
                                                <option value="">Selecciona...</option>
                                                <template x-for="material in materiales" :key="material.id">
                                                    <option :value="material.id" x-text="material.label"></option>
                                                </template>
                                            </select>
                                            <input type="hidden" :name="`items[${index}][material_id]`" :value="item.material_id" />
                                        </td>
                                        <td class="px-4 py-2 text-right text-gray-500" x-text="enSuPoder(item).toFixed(2)"></td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="0.01" min="0.01" x-model.number="item.cantidad" class="form-input" />
                                            <input type="hidden" :name="`items[${index}][cantidad]`" :value="item.cantidad" />
                                        </td>
                                        <td class="px-4 py-2 text-right text-gray-600" x-text="lineTotal(item).toFixed(2)"></td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" @click="items.splice(index, 1)" class="btn-icon text-red-500 hover:bg-red-50" title="Quitar ítem">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">Sin ítems todavía. Pulsa "+ Agregar ítem".</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="form-error">{{ $errors->first('items') }}</p>

                    <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('employee.materiales.ejecutados.index') }}" class="btn-secondary">Cancelar</a>
                        <button type="submit" class="btn-brand">Guardar registro</button>
                    </div>
                </form>
            @else
                <p class="text-sm text-gray-400">Elige una cuadrilla para empezar a reportar.</p>
            @endif
        </div>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('ejecutadoForm', (config) => ({
        materiales: config.materiales,
        saldos: config.saldos,
        movimiento: '{{ \App\Models\Ejecutado::MOVIMIENTO_SALIDA }}',
        items: [{ material_id: '', cantidad: '' }],

        agregarItem() {
            this.items.push({ material_id: '', cantidad: '' });
        },

        enSuPoder(item) {
            if (!item.material_id) return 0;
            return Number(this.saldos[item.material_id] ?? 0);
        },

        precioCosto(item) {
            const material = this.materiales.find((m) => m.id === item.material_id);
            return material ? material.precio_costo : 0;
        },

        lineTotal(item) {
            const cantidad = Number(item.cantidad) || 0;
            const total = Math.round(cantidad * this.precioCosto(item) * 100) / 100;
            return this.movimiento === '{{ \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION }}' ? -total : total;
        },
    }));
});
</script>
@endpush
