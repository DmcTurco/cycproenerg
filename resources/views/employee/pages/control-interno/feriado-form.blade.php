<div>
    <label class="form-label" for="fecha">Fecha</label>
    <input id="fecha" type="date" x-model="form.fecha" class="form-input" />
    <p class="form-error" x-show="errors.fecha" x-text="errors.fecha"></p>
</div>

<div>
    <label class="form-label" for="descripcion">Descripción</label>
    <input id="descripcion" type="text" x-model="form.descripcion" class="form-input" placeholder="Opcional" />
    <p class="form-error" x-show="errors.descripcion" x-text="errors.descripcion"></p>
</div>
