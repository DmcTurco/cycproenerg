<div class="modal fade" id="myModalInformation" tabindex="-1" role="dialog" aria-labelledby="informationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="title">Detalles de la Solicitud</h5>
                <button type="button" class="btn-close text-info" data-bs-dismiss="modal" aria-label="Close"
                    style=" font-size:30px">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Div específico para el loader -->
                <div class="custom-tabs mb-3">
                    <button type="button" class="tab-btn active" data-tab="solicitud">Solicitud</button>
                    <button type="button" class="tab-btn" data-tab="solicitante">Solicitante</button>
                    <button type="button" class="tab-btn" data-tab="ubicacionTab">Ubicación</button>
                    <button type="button" class="tab-btn" data-tab="instalacion">Instalación</button>
                    <button type="button" class="tab-btn" data-tab="proyecto">Proyecto</button>
                    <button type="button" class="tab-btn" data-tab="asesor">Asesor</button>
                </div>
                <form id="myForm" class="needs-validation" novalidate>
                    <!-- Datos de la Solicitud -->
                    <div id="solicitud" class="tab-content active">
                        <div class="section-wrapper bg-white rounded p-4 mb-4">
                            <h6 class="section-title text-muted mb-4">Datos de la Solicitud</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Número de Solicitud</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="numero_solicitud" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Número de Suministro</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="numero_suministro" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Número de Contrato</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="numero_contrato_suministro" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Estado</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="estado_solicitud" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Fecha Aprobación</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="fecha_aprobacion_contrato" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Fecha Registro</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="fecha_registro_portal" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Solicitante -->
                    <div id="solicitante" class="tab-content">
                        <div class="section-wrapper bg-white rounded p-4 mb-4">
                            <h6 class="section-title text-muted mb-4">Datos del Solicitante</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="solicitante_nombre" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Tipo Documento</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="solicitante_tipo_documento" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Número Documento</label>
                                    <input type="text" class="form-control text-center text-center"
                                        id="solicitante_numero_documento" readonly>
                                </div>

                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control text-center" id="solicitante_email"
                                        readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Celular</label>
                                    <input type="text" class="form-control text-center " id="solicitante_celular"
                                        readonly>
                                </div>

                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Usuario FISE</label>
                                    <input type="text" class="form-control text-center"
                                        id="solicitante_usuario_fise" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de Ubicación -->
                    <div id="ubicacionTab" class="tab-content">
                        <div class="section-wrapper bg-white rounded p-4 mb-4">
                            <h6 class="section-title text-muted mb-4">Datos de Ubicación</h6>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control text-center" id="direccion" readonly>
                                </div>
                            </div>
                            <div class="row">
                                
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Ubicación</label>
                                    <input type="text" class="form-control text-center" id="ubicacion" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Departamento</label>
                                    <input type="text" class="form-control text-center" id="departamento"
                                        readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Provincia</label>
                                    <input type="text" class="form-control text-center" id="provincia" readonly>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Distrito</label>
                                    <input type="text" class="form-control text-center" id="distrito" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">codigo Mz</label>
                                    <input type="text" class="form-control text-center" id="codigo_manzana" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Nombre Malla</label>
                                    <input type="text" class="form-control text-center" id="nombre_malla"
                                        readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">zona no gasificada</label>
                                    <input type="text" class="form-control text-center" id="venta_zona_no_gasificada" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos de Instalación -->
                    <div id="instalacion" class="tab-content">
                        <div class="section-wrapper bg-white rounded p-4 mb-4">
                            <h6 class="section-title text-muted mb-4">Datos de Instalación</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tipo Instalación</label>
                                    <input type="text" class="form-control text-center" id="tipo_instalacion"
                                        readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tipo Acometida</label>
                                    <input type="text" class="form-control text-center" id="tipo_acometida"
                                        readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Número de Puntos</label>
                                    <input type="text" class="form-control text-center"
                                        id="numero_puntos_instalacion" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Proyecto -->
                    <div id="proyecto" class="tab-content">
                        <div class="section-wrapper bg-white rounded p-4 mb-4">
                            <h6 class="section-title text-muted mb-4">Datos del Proyecto</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Tipo Proyecto</label>
                                    <input type="text" class="form-control text-center" id="tipo_proyecto"
                                        readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Código Proyecto</label>
                                    <input type="text" class="form-control text-center" id="codigo_proyecto"
                                        readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Categoría</label>
                                    <input type="text" class="form-control text-center" id="categoria_proyecto"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Datos del Asesor -->
                    <div id="asesor" class="tab-content">
                        <div class="section-wrapper bg-white rounded p-4 mb-4">
                            <h6 class="section-title text-muted mb-4">Datos del Asesor</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control text-center" id="asesor_nombre"
                                        readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control text-center" id="asesor_email"
                                        readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control text-center" id="asesor_telefono"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<style>
    /* Estilo para los inputs readonly */
    .form-control [readonly] {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        cursor: default;
    }

    /* Estilo para las etiquetas */
    .form-label {
        font-weight: 500;
        text-align: center;
        display: block;
    }

    /* Estilo para los títulos de sección */
    .section-title {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
    }

    /* Estilo para las secciones */
    .section-wrapper {
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
    }

    /* Estilo para el modal */
    .modal-dialog {
        max-width: 800px;
    }

    .modal-content {
        border-radius: 12px;
    }

    .modal-header {
        background-color: #f8f9fa;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    /* Estilo para hacer el scroll más suave */
    .modal-body {
        height: 500px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
    }

    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 3px;
    }

    .custom-tabs {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 20px;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 10px;
    }

    .tab-btn {
        padding: 8px 16px;
        border: none;
        background: none;
        color: #6c757d;
        cursor: pointer;
        position: relative;
        transition: all 0.3s ease;
    }

    .tab-btn:hover {
        color: #007bff;
    }

    .tab-btn.active {
        color: #007bff;
        font-weight: 500;
    }

    .tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -12px;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #007bff;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
