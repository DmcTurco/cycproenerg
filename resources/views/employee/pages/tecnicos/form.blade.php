<div>
    <label class="form-label" for="nombre">Nombre</label>
    <input id="nombre" type="text" x-model="form.nombre" class="form-input" />
    <p class="form-error" x-show="errors.nombre" x-text="errors.nombre"></p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div>
        <label class="form-label" for="cargo">Rango</label>
        <select id="cargo" x-model="form.cargo" class="form-input">
            <option value=""></option>
            @foreach (config('const.cargo') as $cargo)
                <option value="{{ $cargo['id'] }}">{{ $cargo['name'] }}</option>
            @endforeach
        </select>
        <p class="form-error" x-show="errors.cargo" x-text="errors.cargo"></p>
    </div>
    <div>
        <label class="form-label" for="tipo_documento">Tipo de documento</label>
        <select id="tipo_documento" x-model="form.tipo_documento" class="form-input">
            <option value=""></option>
            @foreach (config('const.tipo_documeto') as $tipo)
                <option value="{{ $tipo['id'] }}">{{ $tipo['name'] }}</option>
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
        <label class="form-label" for="email">Correo</label>
        <input id="email" type="email" x-model="form.email" class="form-input" :readonly="mode === 'edit'" />
        <p class="form-error" x-show="errors.email" x-text="errors.email"></p>
    </div>
    <div>
        <label class="form-label" for="password">Contraseña</label>
        <input id="password" type="password" x-model="form.password" class="form-input"
            placeholder="Dejar en blanco para no cambiar" />
        <p class="form-error" x-show="errors.password" x-text="errors.password"></p>
    </div>
</div>
