<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label class="form-label" for="fecha">Fecha</label>
        <input id="fecha" type="date" x-model="form.fecha" class="form-input" />
        <p class="form-error" x-show="errors.fecha" x-text="errors.fecha"></p>
    </div>
    <div>
        <label class="form-label" for="tipo">Movimiento</label>
        <select id="tipo" x-model="form.tipo" class="form-input">
            <option value="ENTREGA">ENTREGA</option>
            <option value="DEVOLUCION">DEVOLUCION</option>
        </select>
        <p class="form-error" x-show="errors.tipo" x-text="errors.tipo"></p>
    </div>
</div>

<div>
    <label class="form-label" for="herramienta_id">Herramienta</label>
    <select id="herramienta_id" x-model="form.herramienta_id" class="form-input">
        <option value="">Selecciona una herramienta...</option>
        @foreach ($herramientas as $herramienta)
            <option value="{{ $herramienta->id }}">{{ $herramienta->codigo }} — {{ $herramienta->descripcion }}</option>
        @endforeach
    </select>
    <p class="form-error" x-show="errors.herramienta_id" x-text="errors.herramienta_id"></p>
</div>

<div>
    <label class="form-label" for="cuadrilla_id">Cuadrilla</label>
    <select id="cuadrilla_id" x-model="form.cuadrilla_id" class="form-input">
        <option value="">Selecciona una cuadrilla...</option>
        @foreach ($cuadrillas as $cuadrilla)
            <option value="{{ $cuadrilla->id }}">{{ $cuadrilla->nombre }}</option>
        @endforeach
    </select>
    <p class="form-error" x-show="errors.cuadrilla_id" x-text="errors.cuadrilla_id"></p>
    <p class="mt-1 text-xs text-gray-400">
        Si es una DEVOLUCIÓN, la herramienta vuelve a figurar en ALMACÉN en el catálogo.
    </p>
</div>

<div>
    <label class="form-label" for="observacion">Observación</label>
    <textarea id="observacion" x-model="form.observacion" class="form-input" rows="2"></textarea>
    <p class="form-error" x-show="errors.observacion" x-text="errors.observacion"></p>
</div>
