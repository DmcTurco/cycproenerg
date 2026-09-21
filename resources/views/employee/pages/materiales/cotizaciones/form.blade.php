@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;
    $esCorreccion = $cotizacion !== null;

    if ($esCorreccion) {
        $titlePage = 'Estás corrigiendo ' . $cotizacion->numero . ': se reemplazan los ítems y se recalculan los montos, pero el número y el estado NO cambian (igual que "Corregir cotización" en el Excel).';
        $titleVariant = 'warning';
    }

    $materialesData = $materiales->map(fn ($m) => [
        'id' => $m->id,
        'label' => $m->codigo . ' — ' . $m->descripcion,
        'precio_vale' => $m->precioVigente(),
        'precio_cotizacion' => $m->precioVentaSinIgv(),
        'stock_actual' => $m->stockActual(),
        'estado' => $m->estado(),
    ])->values()->all();

    $cuadrillasData = $cuadrillas->map(fn ($c) => [
        'id' => $c->id,
        'tipo' => $c->tipo,
    ])->values()->all();
@endphp

@section('content')
    <div
        x-data='cotizacionForm({
            materiales: @json($materialesData),
            cuadrillas: @json($cuadrillasData),
            cuadrillaId: {{ old('cuadrilla_id', $cotizacion->cuadrilla_id ?? '') ?: 'null' }},
            igvRate: {{ \App\Models\ParametroControlMaterial::actual()->igv }},
            initialItems: @json(old('items', $initialItems)),
        })'
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
            @submit="if (!validarAntesDeEnviar()) { $event.preventDefault(); }"
        >
            @csrf
            @if ($esCorreccion)
                @method('PUT')
            @endif

            <div class="flex min-h-0 flex-1 flex-col gap-4 lg:flex-row">
                <div class="flex min-w-0 flex-1 flex-col lg:min-h-0">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <h3 class="flex-1 text-sm font-semibold text-gray-700">Materiales disponibles</h3>
                        <input type="text" x-model="materialSearch" placeholder="Buscar código o descripción..."
                            class="form-input max-w-[14rem] text-sm" />
                        <button type="button" @click="agregarSeleccionados()" :disabled="seleccionDisponibles.length === 0"
                            class="btn-brand px-3 py-1.5 text-sm disabled:cursor-not-allowed disabled:opacity-50">
                            Agregar seleccionados
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 w-10 bg-white px-4 py-3">
                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300"
                                            title="Seleccionar todos los visibles (con stock)"
                                            :checked="materialesSeleccionables.length > 0 && materialesSeleccionables.every((m) => seleccionDisponibles.includes(m.id))"
                                            @change="seleccionDisponibles = $event.target.checked
                                                ? [...new Set([...seleccionDisponibles, ...materialesSeleccionables.map((m) => m.id)])]
                                                : seleccionDisponibles.filter((id) => !materialesSeleccionables.some((m) => m.id === id))" />
                                    </th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Material</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-24 text-right">Stock</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-24 text-right">Precio</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-24"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="material in materialesDisponibles" :key="material.id">
                                    <tr :class="{ 'opacity-50': material.estado === 'SIN STOCK' }">
                                        <td class="px-4 py-2">
                                            <input type="checkbox" x-model.number="seleccionDisponibles" :value="material.id"
                                                :disabled="material.estado === 'SIN STOCK'" class="h-4 w-4 rounded border-gray-300" />
                                        </td>
                                        <td class="px-4 py-2 text-gray-700" x-text="material.label"></td>
                                        <td class="px-4 py-2 text-right font-medium"
                                            :class="{
                                                'text-red-600': material.estado === 'SIN STOCK',
                                                'text-amber-600': material.estado === 'REPONER',
                                                'text-gray-500': material.estado === 'OK',
                                            }"
                                            x-text="material.stock_actual"></td>
                                        <td class="px-4 py-2 text-right text-gray-500" x-text="precioMaterial(material).toFixed(2)"></td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" @click="agregarUno(material.id)" :disabled="material.estado === 'SIN STOCK'"
                                                class="btn-secondary px-2 py-1 text-xs disabled:cursor-not-allowed disabled:opacity-50" title="Sin stock disponible">
                                                Agregar
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="materialesDisponibles.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">No hay materiales que coincidan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex min-w-0 flex-1 flex-col lg:min-h-0">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-700">Ítems de la cotización</h3>
                        <button type="button" @click="quitarSeleccionados()" :disabled="seleccionItems.length === 0"
                            class="btn-secondary px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50">
                            Quitar seleccionados
                        </button>
                    </div>
                    <div class="min-h-0 flex-1 overflow-auto rounded-lg border border-gray-100">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                    <th class="sticky top-0 z-10 w-10 bg-white px-4 py-3">
                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300"
                                            title="Seleccionar todos"
                                            :checked="items.length > 0 && seleccionItems.length === items.length"
                                            @change="seleccionItems = $event.target.checked ? items.map((_, i) => i) : []" />
                                    </th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3">Material</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-28">Cantidad</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-24 text-right">Total</th>
                                    <th class="sticky top-0 z-10 bg-white px-4 py-3 w-12"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td class="px-4 py-2">
                                            <input type="checkbox" x-model.number="seleccionItems" :value="index" class="h-4 w-4 rounded border-gray-300" />
                                        </td>
                                        <td class="px-4 py-2 text-gray-700" x-text="materialLabel(item)"></td>
                                        <td class="px-4 py-2">
                                            <input type="number" step="1" min="1" x-model.number="item.cantidad" :name="`items[${index}][cantidad]`" class="form-input" />
                                            <input type="hidden" :name="`items[${index}][material_id]`" :value="item.material_id" />
                                        </td>
                                        <td class="px-4 py-2 text-right text-gray-600" x-text="lineTotal(item).toFixed(2)"></td>
                                        <td class="px-4 py-2 text-center">
                                            <button type="button" @click="quitarUno(index)" class="btn-icon text-red-500 hover:bg-red-50" title="Quitar ítem">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-400">Sin ítems todavía. Elige materiales de la izquierda.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="form-error">{{ $errors->first('items') }}</p>

                    <div class="mt-3 grid grid-cols-2 gap-8 border-t border-gray-100 pt-3">
                        <div class="space-y-2">
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
                                <p class="mt-1 min-h-8 text-xs text-gray-400">
                                    <span x-show="tipoSeleccionado && esVale">VALE — a costo, sin IGV, no descuenta stock ahora.</span>
                                    <span x-show="tipoSeleccionado && !esVale">COTIZACIÓN — con IGV, descuenta stock al generarla.</span>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal S/IGV</span>
                                <span x-text="subtotal().toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-gray-600" :class="{ invisible: esVale }">
                                <span>IGV</span>
                                <span x-text="igvMonto().toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
                                <span>Total</span>
                                <span x-text="totalConIgv().toFixed(2)"></span>
                            </div>
                            <p class="text-xs text-gray-400">Referencial: se recalcula al guardar con los precios actuales.</p>
                        </div>
                    </div>

                    <div class="mt-3 flex justify-end gap-2">
                        <a href="{{ route('employee.materiales.cotizaciones.index') }}" class="btn-secondary">Cancelar</a>
                        <button type="submit" class="btn-brand">
                            {{ $esCorreccion ? 'Guardar corrección' : 'Generar documento' }}
                        </button>
                    </div>
                </div>
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
            ? config.initialItems.map((item) => ({ material_id: Number(item.material_id), cantidad: item.cantidad }))
            : [],
        materialSearch: '',
        seleccionDisponibles: [],
        seleccionItems: [],

        get tipoSeleccionado() {
            return this.cuadrillas.find((c) => c.id === this.cuadrillaId) || null;
        },

        get esVale() {
            const cuadrilla = this.tipoSeleccionado;
            return cuadrilla ? cuadrilla.tipo === 'PERSONAL DIRECTO' : false;
        },

        get materialesDisponibles() {
            const usados = new Set(this.items.map((item) => item.material_id));
            const q = this.materialSearch.trim().toLowerCase();

            return this.materiales.filter((material) => {
                if (usados.has(material.id)) return false;
                if (!q) return true;
                return material.label.toLowerCase().includes(q);
            });
        },

        get materialesSeleccionables() {
            return this.materialesDisponibles.filter((material) => material.estado !== 'SIN STOCK');
        },

        materialLabel(item) {
            const material = this.materiales.find((m) => m.id === item.material_id);
            return material ? material.label : '—';
        },

        precioMaterial(material) {
            return this.esVale ? material.precio_vale : material.precio_cotizacion;
        },

        agregarUno(materialId) {
            const material = this.materiales.find((m) => m.id === materialId);
            if (!material || material.estado === 'SIN STOCK') return;
            if (this.items.some((item) => item.material_id === materialId)) return;
            this.items.push({ material_id: materialId, cantidad: 1 });
            this.seleccionDisponibles = this.seleccionDisponibles.filter((id) => id !== materialId);
        },

        agregarSeleccionados() {
            this.seleccionDisponibles.forEach((id) => this.agregarUno(id));
            this.seleccionDisponibles = [];
        },

        quitarUno(index) {
            this.items.splice(index, 1);
            this.seleccionItems = [];
        },

        quitarSeleccionados() {
            this.items = this.items.filter((_, index) => !this.seleccionItems.includes(index));
            this.seleccionItems = [];
        },

        validarAntesDeEnviar() {
            if (!this.cuadrillaId) {
                window.Swal?.fire({ icon: 'error', title: 'Selecciona una cuadrilla' });
                return false;
            }

            if (this.items.length === 0) {
                window.Swal?.fire({ icon: 'error', title: 'Agrega al menos un ítem con material y cantidad' });
                return false;
            }

            const itemInvalido = this.items.some((item) => !item.material_id || !Number.isInteger(Number(item.cantidad)) || Number(item.cantidad) < 1);
            if (itemInvalido) {
                window.Swal?.fire({ icon: 'error', title: 'La cantidad debe ser un número entero, mayor o igual a 1' });
                return false;
            }

            return true;
        },

        precioUnitario(item) {
            const material = this.materiales.find((m) => m.id === item.material_id);
            if (!material) return 0;
            return this.precioMaterial(material);
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
