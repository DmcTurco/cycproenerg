<div>
    <label class="form-label" for="nombre">Nombre</label>
    <input id="nombre" type="text" x-model="form.nombre" class="form-input" />
    <p class="form-error" x-show="errors.nombre" x-text="errors.nombre"></p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="tipo_documento">Tipo de documento</label>
        <select id="tipo_documento" x-model="form.tipo_documento" class="form-input">
            <option value=""></option>
            @foreach (config('const.tipo_documeto') as $tipo)
                <option value="{{ $tipo['name'] }}">{{ $tipo['name'] }}</option>
            @endforeach
        </select>
        <p class="form-error" x-show="errors.tipo_documento" x-text="errors.tipo_documento"></p>
    </div>
    <div>
        <label class="form-label" for="numero_documento_identificacion">N° Documento</label>
        <input id="numero_documento_identificacion" type="number" min="0" step="1"
            x-model="form.numero_documento_identificacion" class="form-input" />
        <p class="form-error" x-show="errors.numero_documento_identificacion" x-text="errors.numero_documento_identificacion"></p>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="telefono">Teléfono</label>
        <input id="telefono" type="text" x-model="form.telefono" class="form-input" />
        <p class="form-error" x-show="errors.telefono" x-text="errors.telefono"></p>
    </div>
    <div>
        <label class="form-label" for="email">Correo</label>
        <input id="email" type="email" x-model="form.email" class="form-input" />
        <p class="form-error" x-show="errors.email" x-text="errors.email"></p>
    </div>
</div>

<div>
    <label class="form-label" for="direccion">Dirección</label>
    <input id="direccion" type="text" x-model="form.direccion" class="form-input" />
    <p class="form-error" x-show="errors.direccion" x-text="errors.direccion"></p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div>
        <label class="form-label" for="fecha_contratacion">Fecha de contratación</label>
        <input id="fecha_contratacion" type="date" x-model="form.fecha_contratacion" class="form-input" />
        <p class="form-error" x-show="errors.fecha_contratacion" x-text="errors.fecha_contratacion"></p>
    </div>
    <div>
        <label class="form-label" for="comision">Comisión (%)</label>
        <input id="comision" type="number" min="0" max="100" step="0.01" x-model="form.comision" class="form-input" />
        <p class="form-error" x-show="errors.comision" x-text="errors.comision"></p>
    </div>
    <div>
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" x-model="form.estado" class="form-input">
            <option value=""></option>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
        </select>
        <p class="form-error" x-show="errors.estado" x-text="errors.estado"></p>
    </div>
</div>

<div>
    <label class="form-label" for="observaciones">Observaciones</label>
    <textarea id="observaciones" x-model="form.observaciones" rows="3" class="form-input"></textarea>
    <p class="form-error" x-show="errors.observaciones" x-text="errors.observaciones"></p>
</div>
