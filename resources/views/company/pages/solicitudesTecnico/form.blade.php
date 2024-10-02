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
<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <form id="myForm" action="{{ route('company.technicals.requests.store', $tecnico->id) }}" method="POST">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="title" class="modal-title text-inspinia text-info">Registrar Técnico</h5>
                    <button type="button" class="btn-close text-info" data-bs-dismiss="modal" aria-label="Close" style=" font-size:30px">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    @csrf
                    <input id="solicitudID" type="hidden" value="" name="solicitudID">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="numero_solicitud">Número de solicitud:</label>
                            <input  type="number" class="new-form-control" id="numero_solicitud" name="numero_solicitud" value="" min="0" step="1" style="text-align: right"></input>
                            <div class="invalid-feedback" id="numero_solicitudError"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="numero_documento">Nombre del cliente:</label>
                            <input type="text" class="new-form-control" id="numero_documento" name="numero_documento" value="" style="text-align: right">
                            <div class="invalid-feedback" id="numero_documentoError"></div>
                        </div>
                        <div class="col-md-3">
                            <label for="numero_solicitud">Número de identificación:</label>
                            <input  type="number" class="new-form-control" id="numero_solicitud" name="numero_solicitud" value="" min="0" step="1" style="text-align: right"></input>
                            <div class="invalid-feedback" id="numero_solicitudError"></div>
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
