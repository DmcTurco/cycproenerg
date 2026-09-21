<div>
    <label class="form-label" for="h_codigo">Código</label>
    <input id="h_codigo" type="text" x-model="form.codigo" class="form-input" />
    <p class="form-error" x-show="errors.codigo" x-text="errors.codigo"></p>
</div>

<div>
    <label class="form-label" for="h_descripcion">Descripción</label>
    <input id="h_descripcion" type="text" x-model="form.descripcion" class="form-input" />
    <p class="form-error" x-show="errors.descripcion" x-text="errors.descripcion"></p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="h_marca_modelo">Marca / modelo</label>
        <input id="h_marca_modelo" type="text" x-model="form.marca_modelo" class="form-input" />
        <p class="form-error" x-show="errors.marca_modelo" x-text="errors.marca_modelo"></p>
    </div>
    <div>
        <label class="form-label" for="h_numero_serie">N° de serie</label>
        <input id="h_numero_serie" type="text" x-model="form.numero_serie" class="form-input" />
        <p class="form-error" x-show="errors.numero_serie" x-text="errors.numero_serie"></p>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="h_fecha_compra">Fecha de compra</label>
        <input id="h_fecha_compra" type="date" x-model="form.fecha_compra" class="form-input" />
        <p class="form-error" x-show="errors.fecha_compra" x-text="errors.fecha_compra"></p>
    </div>
    <div>
        <label class="form-label" for="h_precio">Precio S/IGV</label>
        <input id="h_precio" type="number" step="0.01" min="0" x-model="form.precio" class="form-input" />
        <p class="form-error" x-show="errors.precio" x-text="errors.precio"></p>
    </div>
</div>

<div>
    <label class="form-label" for="h_estado">Estado</label>
    <select id="h_estado" x-model="form.estado" class="form-input">
        <option value="OPERATIVA">OPERATIVA</option>
        <option value="MALOGRADA">MALOGRADA</option>
        <option value="PERDIDA">PERDIDA</option>
    </select>
    <p class="form-error" x-show="errors.estado" x-text="errors.estado"></p>
</div>

<div>
    <label class="form-label" for="h_observacion">Observación</label>
    <textarea id="h_observacion" x-model="form.observacion" rows="2" class="form-input"></textarea>
    <p class="form-error" x-show="errors.observacion" x-text="errors.observacion"></p>
</div>
