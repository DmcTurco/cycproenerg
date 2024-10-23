<style>
    .new-form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem; /* Espaciado interno (padding) */
    font-size: 1rem;
    line-height: 1.5;
    color: #495057; /* Color del texto */
    background-color: #fff; /* Fondo blanco */
    background-clip: padding-box;
    border: 1px solid #ced4da; /* Borde gris claro */
    border-radius: 0.25rem; /* Bordes redondeados */
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; /* Transiciones para foco */
}
</style>


<!-- Modal -->
<div class="modal fade" id="myModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="myForm" action="{{ route('company.technicals.store') }}" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="title" class="modal-title text-inspinia text-info">Registrar Técnico</h5>
                    <button type="button" class="btn-close text-info" data-bs-dismiss="modal" aria-label="Close" style=" font-size:30px">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input id="tecnicoId" type="hidden" value="" name="tecnicoId">
                    <div class="row">
                        <div class="col-md-7">
                            <label for="nombre">Nombre:</label>
                            <input type="text" class="new-form-control" id="nombre" name="nombre" value="">
                            <div class="invalid-feedback" id="nombreError"></div>
                        </div>
                        <div class="col-md-5">
                            <label for="cargo">Rango:</label>
                            <select class="new-form-control" name="cargo" id="cargo">
                                <option value="" ></option>
                                @foreach (config('const.cargo') as $cargo)
                                    <option value="{{ $cargo['id'] }}">{{ $cargo['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="cargoError"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-7">
                            <label for="tipo_documento">Tipo de documento:</label>
                            <select class="new-form-control" name="tipo_documento" id="tipo_documento">
                                <option value="" ></option>
                                @foreach (config('const.tipo_documeto') as $tipo)
                                    <option value="{{ $tipo['id'] }}">{{ $tipo['name'] }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="tipo_documentoError"></div>
                        </div>
                        <div class="col-md-5">
                            <label for="numero_documento_identificacion">N° Documento de indentidad:</label>
                            <input  type="number" class="new-form-control" id="numero_documento_identificacion"
                            name="numero_documento_identificacion" style="text-align: right" min="0" step="1"></input>
                            <div class="invalid-feedback" id="numero_documento_identificacionError"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center mt-2">
                        <button id="submitBtn" type="submit" class="btn btn-info mx-2 submitButton mt-3 px-3 py-2"></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
