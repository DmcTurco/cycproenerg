@extends('employee.layouts.user_type.auth')

@php
    $fullBleed = true;

    $materialesData = $materiales->map(fn ($m) => [
        'id' => $m->id,
        'label' => $m->codigo . ' — ' . $m->descripcion,
        'precio_costo' => round($m->precioVigente() / max(1, $m->factor_metros_por_unidad), 4),
        'unidad_reporte' => $m->factor_metros_por_unidad > 1 ? 'MTS' : $m->unidad,
    ])->values()->all();
@endphp

@section('content')
    <div
        x-data='ejecutadoForm({
            materiales: @json($materialesData),
            saldos: @json($saldos),
        })'
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between gap-4 bg-brand-600 px-4 py-2.5 sm:px-6">
            <h2 class="text-base font-semibold text-white">Nuevo registro rápido (Ejecutado)</h2>
            <div class="flex shrink-0 items-center gap-2">
                <form method="GET" action="{{ route('employee.materiales.ejecutados.create') }}">
                    <select name="cuadrilla_id"
                        class="rounded-lg border-0 bg-white/90 px-2.5 py-1.5 text-sm font-medium text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-white"
                        onchange="this.form.submit()">
                        <option value="">Selecciona cuadrilla...</option>
                        @foreach ($cuadrillas as $cuadrilla)
                            <option value="{{ $cuadrilla->id }}" @selected($cuadrillaSeleccionada && $cuadrillaSeleccionada->id === $cuadrilla->id)>{{ $cuadrilla->nombre }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('employee.materiales.ejecutados.index') }}"
                    class="rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">
                    Salir
                </a>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-y-auto p-3 sm:p-4 lg:p-4">
            @if ($cuadrillas->isEmpty())
                <p class="mb-4 text-xs text-red-500">No hay cuadrillas ACTIVAS de tipo PERSONAL DIRECTO registradas.</p>
            @endif

            @if ($cuadrillaSeleccionada)
                <form method="POST" action="{{ route('employee.materiales.ejecutados.store') }}" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <input type="hidden" name="cuadrilla_id" value="{{ $cuadrillaSeleccionada->id }}" />

                    <h3 class="mb-3 text-sm font-semibold text-gray-700">Ítems — {{ $cuadrillaSeleccionada->nombre }}</h3>

                    <div class="flex min-h-0 flex-1 flex-col gap-4 lg:flex-row">
                        <div class="flex min-w-0 flex-1 flex-col lg:min-h-0">
                            <div class="mb-2 flex flex-wrap items-center gap-2">
                                <h4 class="flex-1 text-sm font-semibold text-gray-700">Materiales disponibles</h4>
                                <input type="text" x-model="materialSearch" placeholder="Buscar código o descripción..."
                                    class="form-input max-w-56 text-sm" />
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
                                                    title="Seleccionar todos los visibles"
                                                    :checked="materialesDisponibles.length > 0 && materialesDisponibles.every((m) => seleccionDisponibles.includes(m.id))"
                                                    @change="seleccionDisponibles = $event.target.checked
                                                        ? [...new Set([...seleccionDisponibles, ...materialesDisponibles.map((m) => m.id)])]
                                                        : seleccionDisponibles.filter((id) => !materialesDisponibles.some((m) => m.id === id))" />
                                            </th>
                                            <th class="sticky top-0 z-10 bg-white px-4 py-3">Material</th>
                                            <th class="sticky top-0 z-10 bg-white px-4 py-3 w-28 text-right">En su poder</th>
                                            <th class="sticky top-0 z-10 bg-white px-4 py-3 w-24 text-right">Precio</th>
                                            <th class="sticky top-0 z-10 bg-white px-4 py-3 w-24"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="material in materialesDisponibles" :key="material.id">
                                            <tr>
                                                <td class="px-4 py-2">
                                                    <input type="checkbox" x-model.number="seleccionDisponibles" :value="material.id" class="h-4 w-4 rounded border-gray-300" />
                                                </td>
                                                <td class="px-4 py-2 text-gray-700" x-text="material.label"></td>
                                                <td class="px-4 py-2 text-right text-gray-500" x-text="enSuPoderMaterial(material.id).toFixed(2)"></td>
                                                <td class="px-4 py-2 text-right text-gray-500" x-text="material.precio_costo.toFixed(2)"></td>
                                                <td class="px-4 py-2 text-center">
                                                    <button type="button" @click="agregarUno(material.id)" class="btn-secondary px-2 py-1 text-xs">Agregar</button>
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
                                <h4 class="text-sm font-semibold text-gray-700">Ítems reportados</h4>
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
                                            <th class="sticky top-0 z-10 bg-white px-4 py-3 w-24 text-right">Total S/IGV</th>
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
                                                    <input type="number" step="0.01" min="0.01" x-model.number="item.cantidad" :name="`items[${index}][cantidad]`" class="form-input" />
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
                                        <input id="fecha" name="fecha" type="date" value="{{ old('fecha', now()->format('Y-m-d')) }}" class="form-input" />
                                        <p class="form-error">{{ $errors->first('fecha') }}</p>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="form-label" for="movimiento">Movimiento</label>
                                            <select id="movimiento" name="movimiento" class="form-input" x-model="movimiento">
                                                <option value="{{ \App\Models\Ejecutado::MOVIMIENTO_SALIDA }}">SALIDA</option>
                                                <option value="{{ \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION }}">DEVOLUCION</option>
                                            </select>
                                            <p class="form-error">{{ $errors->first('movimiento') }}</p>
                                        </div>
                                        <div>
                                            <label class="form-label" for="n_suministro">N° suministro</label>
                                            <input id="n_suministro" name="n_suministro" type="text" value="{{ old('n_suministro') }}" class="form-input" />
                                            <p class="form-error">{{ $errors->first('n_suministro') }}</p>
                                        </div>
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
                                </div>

                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between border-t border-gray-200 pt-1 font-semibold text-gray-900">
                                        <span>Total S/IGV</span>
                                        <span x-text="totalGeneral().toFixed(2)"></span>
                                    </div>
                                    <p class="text-xs text-gray-400">Referencial: el sistema recalcula todo al guardar con los precios actuales del catálogo.</p>
                                </div>
                            </div>

                            <div class="mt-3 flex justify-end gap-2">
                                <a href="{{ route('employee.materiales.ejecutados.index') }}" class="btn-secondary">Cancelar</a>
                                <button type="submit" class="btn-brand">Guardar registro</button>
                            </div>
                        </div>
                    </div>
                </form>
            @else
                <div class="flex flex-1 flex-col items-center justify-center gap-2 px-6 text-center">
                    <h1 class="text-lg font-semibold text-gray-700">Elige una cuadrilla para empezar a reportar</h1>
                    <p class="max-w-md text-sm text-gray-400">
                        Lo que el personal directo realmente usó (SALIDA) o devolvió (DEVOLUCION) — se valoriza a costo
                        con el precio vigente actual del material (no una foto: si el precio cambia, el total mostrado
                        acá cambia también, igual que en el Excel). Los contratistas no reportan acá.
                    </p>
                </div>
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
        items: [],
        materialSearch: '',
        seleccionDisponibles: [],
        seleccionItems: [],

        get materialesDisponibles() {
            const usados = new Set(this.items.map((item) => item.material_id));
            const q = this.materialSearch.trim().toLowerCase();

            return this.materiales.filter((material) => {
                if (usados.has(material.id)) return false;
                if (!q) return true;
                return material.label.toLowerCase().includes(q);
            });
        },

        materialLabel(item) {
            const material = this.materiales.find((m) => m.id === item.material_id);
            return material ? material.label : '—';
        },

        enSuPoderMaterial(materialId) {
            return Number(this.saldos[materialId] ?? 0);
        },

        agregarUno(materialId) {
            if (this.items.some((item) => item.material_id === materialId)) return;
            this.items.push({ material_id: materialId, cantidad: '' });
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

        precioCosto(item) {
            const material = this.materiales.find((m) => m.id === item.material_id);
            return material ? material.precio_costo : 0;
        },

        lineTotal(item) {
            const cantidad = Number(item.cantidad) || 0;
            const total = Math.round(cantidad * this.precioCosto(item) * 100) / 100;
            return this.movimiento === '{{ \App\Models\Ejecutado::MOVIMIENTO_DEVOLUCION }}' ? -total : total;
        },

        totalGeneral() {
            return this.items.reduce((sum, item) => sum + this.lineTotal(item), 0);
        },
    }));
});
</script>
@endpush
