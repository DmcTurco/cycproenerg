@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $esCorreccion = $cotizacion !== null;
@endphp

@section('content')
    <div
        x-data="cotizacionForm({
            materiales: @json($materiales->map(fn ($m) => [
                'id' => $m->id,
                'label' => $m->codigo . ' — ' . $m->descripcion,
                'precio_vale' => $m->precioVigente(),
                'precio_cotizacion' => $m->precioVentaSinIgv(),
            ])),
            cuadrillas: @json($cuadrillas->map(fn ($c) => ['id' => $c->id, 'tipo' => $c->tipo])),
            cuadrillaId: {{ old('cuadrilla_id', $cotizacion->cuadrilla_id ?? '') ?: 'null' }},
            igvRate: {{ \App\Models\ParametroControlMaterial::actual()->igv }},
            initialItems: @json(old('items', $initialItems)),
        })"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">
                {{ $esCorreccion ? 'Corregir documento — ' . $cotizacion->numero : 'Nueva cotización / vale' }}
            </h2>
            <a href="{{ route('employee.materiales.cotizaciones.index') }}"
                class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                Salir
            </a>
        </div>

        <form method="POST"
            action="{{ $esCorreccion ? route('employee.materiales.cotizaciones.update', $cotizacion) : route('employee.materiales.cotizaciones.store') }}"
            class="flex flex-1 flex-col overflow-y-auto p-3 sm:p-4 lg:p-4"
        >
            @csrf
            @if ($esCorreccion)
                @method('PUT')
                <p class="mb-4 max-w-2xl rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    Estás corrigiendo <strong>{{ $cotizacion->numero }}</strong>: se reemplazan los ítems y se
                    recalculan los montos, pero el número y el estado NO cambian (igual que "Corregir cotización" en
                    el Excel).
                </p>
            @endif

            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:max-w-xl">
                <div>
                    <label class="form-label" for="fecha">Fecha</label>
                    <input id="fecha" name="fecha" type="date" value="{{ old('fecha', optional($cotizacion->fecha ?? now())->format('Y-m-d')) }}" class="form-input" />
                    <p class="form-error">{{ $errors->first('fecha') }}</p>
                </div>
                <div>
                    <label class="form-label" for="cuadrilla_id">Cuadrilla</label>
                    <select id="cuadrilla_id" name="cuadrilla_id" class="form-input" x-model.number="cuadrillaId">
                        <option value="">Selecciona...</option>
                        @foreach ($cuadrillas as $cuadrilla)
                            <option value="{{ $cuadrilla->id }}">{{ $cuadrilla->nombre }} ({{ $cuadrilla->tipo }})</option>
                        @endforeach
                    </select>
                    <p class="form-error">{{ $errors->first('cuadrilla_id') }}</p>
                    <p class="mt-1 text-xs text-gray-400" x-show="tipoSeleccionado">
                        <span x-show="esVale">VALE DE ENTREGA — a precio costo, sin IGV. No descuenta stock ahora (se descuenta cuando se reporte lo ejecutado).</span>
                        <span x-show="!esVale">COTIZACIÓN — con IGV. Descuenta stock al generarla.</span>
                    </p>
                </div>
            </div>

            <div class="mb-2 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Ítems</h3>
                <button type="button" @click="agregarItem()" class="btn-secondary px-3 py-1 text-sm">+ Agregar ítem</button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Material</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 w-32">Cantidad</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 w-32 text-right">Precio unit.</th>
                            <th class="sticky top-0 z-10 bg-white px-4 py-3 w-32 text-right">Total</th>
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
                                <td class="px-4 py-2">
                                    <input type="number" step="0.01" min="0.01" x-model.number="item.cantidad" class="form-input" />
                                    <input type="hidden" :name="`items[${index}][cantidad]`" :value="item.cantidad" />
                                </td>
                                <td class="px-4 py-2 text-right text-gray-600" x-text="precioUnitario(item).toFixed(2)"></td>
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

            <div class="mt-4 flex justify-end">
                <div class="w-64 space-y-1 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal S/IGV</span>
                        <span x-text="subtotal().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-gray-600" x-show="!esVale">
                        <span>IGV</span>
                        <span x-text="igvMonto().toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
                        <span>Total</span>
                        <span x-text="totalConIgv().toFixed(2)"></span>
                    </div>
                    <p class="text-xs text-gray-400">Referencial: el sistema recalcula todo al guardar con los precios actuales del catálogo.</p>
                </div>
            </div>

            <div class="mt-4 flex justify-end gap-2 border-t border-gray-100 pt-4">
                <a href="{{ route('employee.materiales.cotizaciones.index') }}" class="btn-secondary">Cancelar</a>
                <button type="submit" class="btn-brand">
                    {{ $esCorreccion ? 'Guardar corrección' : 'Generar documento' }}
                </button>
            </div>
        </form>

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cotizacionForm', (config) => ({
        materiales: config.materiales,
        cuadrillas: config.cuadrillas,
        cuadrillaId: config.cuadrillaId,
        igvRate: config.igvRate,
        items: (config.initialItems && config.initialItems.length)
            ? config.initialItems.map((item) => ({ material_id: item.material_id, cantidad: item.cantidad }))
            : [{ material_id: '', cantidad: '' }],

        get tipoSeleccionado() {
            return this.cuadrillas.find((c) => c.id === this.cuadrillaId) || null;
        },

        get esVale() {
            const cuadrilla = this.tipoSeleccionado;
            return cuadrilla ? cuadrilla.tipo === 'PERSONAL DIRECTO' : false;
        },

        agregarItem() {
            this.items.push({ material_id: '', cantidad: '' });
        },

        precioUnitario(item) {
            const material = this.materiales.find((m) => m.id === item.material_id);
            if (!material) return 0;
            return this.esVale ? material.precio_vale : material.precio_cotizacion;
        },

        lineTotal(item) {
            const cantidad = Number(item.cantidad) || 0;
            return Math.round(cantidad * this.precioUnitario(item) * 100) / 100;
        },

        subtotal() {
            return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },

        igvMonto() {
            return this.esVale ? 0 : Math.round(this.subtotal() * this.igvRate * 100) / 100;
        },

        totalConIgv() {
            return this.subtotal() + this.igvMonto();
        },
    }));
});
</script>
@endpush
