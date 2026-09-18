<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="nombre">Nombre completo</label>
        <input id="nombre" type="text" x-model="form.nombre" class="form-input" />
        <p class="form-error" x-show="errors.nombre" x-text="errors.nombre"></p>
    </div>
    <div>
        <label class="form-label" for="dni">DNI</label>
        <input id="dni" type="text" x-model="form.dni" class="form-input" />
        <p class="form-error" x-show="errors.dni" x-text="errors.dni"></p>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="tipo">Tipo</label>
        <select id="tipo" x-model="form.tipo" class="form-input">
            <option value="CONTRATISTA">CONTRATISTA</option>
            <option value="PERSONAL DIRECTO">PERSONAL DIRECTO</option>
        </select>
        <p class="form-error" x-show="errors.tipo" x-text="errors.tipo"></p>
        <p class="mt-1 text-xs text-gray-400">Define si retira con cotización (con IGV) o con vale a costo.</p>
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

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
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
    <p class="mt-1 text-xs text-gray-400">Una cuadrilla puede estar ligada a una o ambas empresas (ej. "CLB/C&C" en el Excel).</p>
</div>
