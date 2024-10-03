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
    <div class="modal-dialog modal-xl">

            <div class="modal-content" style="max-height: 90vh; overflow:auto">
                <div class="modal-header">
                    <h5 id="title" class="modal-title text-inspinia text-info">Registrar Técnico</h5>
                    <button type="button" class="btn-close text-info" data-bs-dismiss="modal" aria-label="Close" style=" font-size:30px">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="myForm" action="" method="POST">
                        <input id="solicitudID" type="hidden" value="" name="solicitudID">
                        @csrf
                        <div class="row">
                            <div class="col-md-2">
                                <label for="numero_solicitud">Número de solicitud:</label>
                                <input  type="number" class="new-form-control" id="numero_solicitud" name="numero_solicitud" value="" min="0" step="1" style="text-align: right">
                                <div class="invalid-feedback" id="numero_solicitudError"></div>
                            </div>
                            <div class="col-md-2">
                                <label for="numero_documento_identificacion" style="font-size: 12px">N° Documento de identidad:</label>
                                <input type="text" class="new-form-control" id="numero_documento_identificacion" name="numero_documento_identificacion" value="" style="text-align: right">
                                <div class="invalid-feedback" id="numero_documento_identificacionError"></div>
                            </div>
                            <div class="col-md-3">
                                <label for="nombre">Nombre del cliente:</label>
                                <input  type="text" class="new-form-control" id="nombre" name="nombre" value="">
                                <div class="invalid-feedback" id="nombreError"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="direccion">Dirección:</label>
                                <input  type="text" class="new-form-control" id="direccion" name="direccion" value="">
                                <div class="invalid-feedback" id="direccionError"></div>
                            </div>
                            <div class="col-md-1">
                                <label for="" style="opacity: 0">r</label>
                                <button type="submit" class="btn btn-info px-3 py-2">
                                    Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="resultados" class="mt-3"></div>
                    {{-- <div class="d-flex justify-content-center mt-2">
                        <button id="submitBtn" type="submit" class="btn btn-info mx-2 submitButton mt-3 px-3 py-2"></button>
                    </div> --}}
                </div>
            </div>

    </div>
</div>
