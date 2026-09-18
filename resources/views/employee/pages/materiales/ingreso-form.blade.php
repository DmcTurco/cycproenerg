<div>
    <label class="form-label" for="material_id">Material</label>
    <select id="material_id" x-model="form.material_id" class="form-input">
        <option value="">Selecciona un material...</option>
        @foreach ($materiales as $material)
            <option value="{{ $material->id }}">{{ $material->codigo }} — {{ $material->descripcion }}</option>
        @endforeach
    </select>
    <p class="form-error" x-show="errors.material_id" x-text="errors.material_id"></p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="fecha">Fecha</label>
        <input id="fecha" type="date" x-model="form.fecha" class="form-input" />
        <p class="form-error" x-show="errors.fecha" x-text="errors.fecha"></p>
    </div>
    <div>
        <label class="form-label" for="cantidad">Cantidad</label>
        <input id="cantidad" type="number" step="0.01" min="0.01" x-model="form.cantidad" class="form-input" />
        <p class="form-error" x-show="errors.cantidad" x-text="errors.cantidad"></p>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="proveedor">Proveedor</label>
        <input id="proveedor" type="text" x-model="form.proveedor" class="form-input" />
        <p class="form-error" x-show="errors.proveedor" x-text="errors.proveedor"></p>
    </div>
    <div>
        <label class="form-label" for="guia_factura">N° guía / factura</label>
        <input id="guia_factura" type="text" x-model="form.guia_factura" class="form-input" />
        <p class="form-error" x-show="errors.guia_factura" x-text="errors.guia_factura"></p>
    </div>
</div>

<div>
    <label class="form-label" for="precio_compra">Precio compra S/IGV (opcional)</label>
    <input id="precio_compra" type="number" step="0.01" min="0" x-model="form.precio_compra" class="form-input" placeholder="Anótalo solo si quieres control de precio" />
    <p class="form-error" x-show="errors.precio_compra" x-text="errors.precio_compra"></p>
    <p class="mt-1 text-xs text-gray-400">Si lo llenas, se compara contra el precio base del material y puede disparar una alerta.</p>
</div>

<div>
    <label class="form-label" for="observacion">Observación</label>
    <textarea id="observacion" x-model="form.observacion" class="form-input" rows="2"></textarea>
    <p class="form-error" x-show="errors.observacion" x-text="errors.observacion"></p>
</div>
