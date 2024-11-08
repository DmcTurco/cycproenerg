{{-- Modal de Ubicación --}}
<div class="modal fade" id="ubicacionModal" tabindex="-1" aria-labelledby="ubicacionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ubicacionModalLabel">Detalles de Ubicación</h5>
                <button type="button" class="btn-close btn-info" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Departamento:</strong>
                        <p id="modal-departamento"></p>
                    </div>
                    <div class="col-md-4">
                        <strong>Provincia:</strong>
                        <p id="modal-provincia"></p>
                    </div>
                    <div class="col-md-4">
                        <strong>Distrito:</strong>
                        <p id="modal-distrito"></p>
                    </div>
                </div>
                <div id="mapa" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>