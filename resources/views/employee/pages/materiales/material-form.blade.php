<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="codigo">Código</label>
        <input id="codigo" type="text" x-model="form.codigo" class="form-input" />
        <p class="form-error" x-show="errors.codigo" x-text="errors.codigo"></p>
    </div>
    <div>
        <label class="form-label" for="unidad">Unidad</label>
        <input id="unidad" type="text" x-model="form.unidad" class="form-input" placeholder="RLL, UNID, MTS..." />
        <p class="form-error" x-show="errors.unidad" x-text="errors.unidad"></p>
    </div>
</div>

<div>
    <label class="form-label" for="descripcion">Descripción</label>
    <input id="descripcion" type="text" x-model="form.descripcion" class="form-input" />
    <p class="form-error" x-show="errors.descripcion" x-text="errors.descripcion"></p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="precio_base">Precio base S/IGV</label>
        <input id="precio_base" type="number" step="0.01" min="0" x-model="form.precio_base" class="form-input" />
        <p class="form-error" x-show="errors.precio_base" x-text="errors.precio_base"></p>
        <p class="mt-1 text-xs text-gray-400">El precio vigente sube solo si un ingreso trae un precio mayor (se ve cuando esté CM-3).</p>
    </div>
    <div>
        <label class="form-label" for="margen_pct">Margen % (por ítem, opcional)</label>
        <input id="margen_pct" type="number" step="0.01" min="0" max="100" x-model="form.margen_pct" class="form-input" placeholder="Vacío = usar el margen general" />
        <p class="form-error" x-show="errors.margen_pct" x-text="errors.margen_pct"></p>
    </div>
</div>

{{-- Vista previa del precio de venta — mismo cálculo que
     Material::precioVentaSinIgv()/ConIgv(); el valor que se guarda lo
     calcula el backend al grabar. --}}
<div
    x-data="{
        margenGeneral: {{ (float) $parametros->margen_general }},
        igv: {{ (float) $parametros->igv }},
        get vigente() {
            const base = parseFloat(form.precio_base) || 0;
            const ultimo = parseFloat(form.ultimo_precio_ingresos);
            return !isNaN(ultimo) && ultimo > base ? ultimo : base;
        },
        get margen() {
            return form.margen_pct === '' || form.margen_pct === null ? this.margenGeneral : (parseFloat(form.margen_pct) || 0) / 100;
        },
        get ventaSinIgv() { return Math.round(this.vigente * (1 + this.margen) * 100) / 100 },
        get ventaConIgv() { return Math.round(this.ventaSinIgv * (1 + this.igv) * 100) / 100 },
    }"
    class="grid grid-cols-1 gap-4 rounded-lg bg-gray-50 p-3 text-sm sm:grid-cols-3"
>
    <div>
        <p class="text-xs text-gray-500">Precio vigente</p>
        <p class="font-medium text-gray-900" x-text="vigente.toFixed(2)"></p>
    </div>
    <div>
        <p class="text-xs text-gray-500">Venta S/IGV <span x-text="'(margen ' + (margen * 100).toFixed(2) + '%)'"></span></p>
        <p class="font-medium text-gray-900" x-text="ventaSinIgv.toFixed(2)"></p>
    </div>
    <div>
        <p class="text-xs text-gray-500">Venta C/IGV</p>
        <p class="font-semibold text-gray-900" x-text="ventaConIgv.toFixed(2)"></p>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div>
        <label class="form-label" for="stock_inicial">Stock inicial</label>
        <input id="stock_inicial" type="number" step="0.01" min="0" x-model="form.stock_inicial" class="form-input" />
        <p class="form-error" x-show="errors.stock_inicial" x-text="errors.stock_inicial"></p>
    </div>
    <div>
        <label class="form-label" for="stock_minimo">Stock mínimo</label>
        <input id="stock_minimo" type="number" step="0.01" min="0" x-model="form.stock_minimo" class="form-input" />
        <p class="form-error" x-show="errors.stock_minimo" x-text="errors.stock_minimo"></p>
    </div>
    <div>
        <label class="form-label" for="factor_metros_por_unidad">Metros por unidad</label>
        <input id="factor_metros_por_unidad" type="number" step="1" min="1" x-model="form.factor_metros_por_unidad" class="form-input" />
        <p class="form-error" x-show="errors.factor_metros_por_unidad" x-text="errors.factor_metros_por_unidad"></p>
        <p class="mt-1 text-xs text-gray-400">Tuberías: 200 o 100 (rollo→metros). El resto: 1.</p>
    </div>
</div>
