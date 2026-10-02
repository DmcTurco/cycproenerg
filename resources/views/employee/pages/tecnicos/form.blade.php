<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="tipo">Tipo</label>
        <select id="tipo" x-model="form.tipo" class="form-input">
            @foreach (\App\Models\PersonaCampo::tipos() as $tipoOpcion)
                <option value="{{ $tipoOpcion }}">{{ $tipoOpcion }}</option>
            @endforeach
        </select>
        <p class="form-error" x-show="errors.tipo" x-text="errors.tipo"></p>
        <p class="mt-1 text-xs text-gray-400"
            x-text="form.tipo === 'CONTRATISTA' ? 'Retira con cotización (con IGV).' : 'Retira con vale a costo y reporta lo ejecutado.'"></p>
    </div>
    <div>
        <label class="form-label" for="estado">Estado</label>
        <select id="estado" x-model="form.estado" class="form-input">
            <option value="ACTIVO">ACTIVO</option>
            <option value="INACTIVO">INACTIVO</option>
        </select>
        <p class="form-error" x-show="errors.estado" x-text="errors.estado"></p>
    </div>
</div>

<div>
    <label class="form-label" for="nombre" x-text="form.tipo === 'CONTRATISTA' ? 'Nombre / razón social' : 'Nombre completo'"></label>
    <input id="nombre" type="text" x-model="form.nombre" class="form-input" />
    <p class="form-error" x-show="errors.nombre" x-text="errors.nombre"></p>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="tipo_documento">Tipo de documento</label>
        <select id="tipo_documento" x-model="form.tipo_documento" class="form-input">
            <option value=""></option>
            @foreach (config('const.tipo_documeto') as $tipoDocumento)
                <option value="{{ $tipoDocumento['id'] }}">{{ $tipoDocumento['name'] }}</option>
            @endforeach
        </select>
        <p class="form-error" x-show="errors.tipo_documento" x-text="errors.tipo_documento"></p>
    </div>
    <div>
        <label class="form-label" for="numero_documento">N° documento</label>
        <input id="numero_documento" type="text" x-model="form.numero_documento" class="form-input" />
        <p class="form-error" x-show="errors.numero_documento" x-text="errors.numero_documento"></p>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div x-show="form.tipo !== 'CONTRATISTA'">
        <label class="form-label" for="fecha_nacimiento">Fecha de nacimiento</label>
        <input id="fecha_nacimiento" type="date" x-model="form.fecha_nacimiento" class="form-input" />
        <p class="form-error" x-show="errors.fecha_nacimiento" x-text="errors.fecha_nacimiento"></p>
    </div>
    <div>
        <label class="form-label" for="celular">N° celular</label>
        <input id="celular" type="text" x-model="form.celular" class="form-input" />
        <p class="form-error" x-show="errors.celular" x-text="errors.celular"></p>
    </div>
</div>

<div>
    <label class="form-label">Empresa(s)</label>
    <div class="flex flex-wrap gap-4 rounded-lg border border-gray-200 px-3 py-2">
        @forelse ($empresas as $empresa)
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" value="{{ $empresa->id }}" x-model="form.empresas" class="rounded border-gray-300" />
                {{ $empresa->codigo ?? $empresa->nombre }}
            </label>
        @empty
            <span class="text-sm text-gray-400">No hay empresas registradas todavía.</span>
        @endforelse
    </div>
    <p class="form-error" x-show="errors.empresas" x-text="errors.empresas"></p>
</div>

<fieldset class="rounded-lg border border-gray-200 p-3">
    <legend class="px-1 text-sm font-medium text-gray-700">Acceso a la app móvil (opcional)</legend>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="form-label" for="email">Correo</label>
            <input id="email" type="email" x-model="form.email" class="form-input" placeholder="Vacío = sin acceso a la app" />
            <p class="form-error" x-show="errors.email" x-text="errors.email"></p>
        </div>
        <div>
            <label class="form-label" for="password">Contraseña</label>
            <input id="password" type="password" x-model="form.password" class="form-input" :disabled="!form.email"
                :placeholder="form.tiene_password ? 'Dejar en blanco para no cambiar' : 'Mínimo 8 caracteres'" />
            <p class="form-error" x-show="errors.password" x-text="errors.password"></p>
        </div>
    </div>
</fieldset>
