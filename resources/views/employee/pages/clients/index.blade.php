@extends('employee.layouts.user_type.auth')

@section('content')

    <div class="container-fluid ">
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        @include('employee.pages.clients.form')
                        @include('employee.pages.clients.information')
                        <form method="GET" action="{{ route('employee.client.index') }}">
                            <div class="row">
                                <div class="col-md-4">
                                    <a class="btn btn-info" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#uploadModal">Abrir Portal de Carga</a>
                                </div>

                                <div class="col-md-4">
                                    <div class="input-group input-group-outline">
                                        <label for="fecha_inicio">Fecha Inicio</label>
                                        <input type="date" class="input-group " id="fecha_inicio" name="fecha_inicio"
                                            value="{{ request('fecha_inicio', $fechas['fecha_inicio']) }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline">
                                        <label for="fecha_fin">Fecha Fin</label>
                                        <input type="date" class="input-group " id="fecha_fin" name="fecha_fin"
                                            value="{{ request('fecha_fin', $fechas['fecha_fin']) }}">
                                    </div>
                                </div>
                            </div>

                            <hr>


                            <!-- Número de Solicitud -->
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">N. de Solicitud</label>
                                        <input type="text" name="numero_solicitud" class="form-control"
                                            value="{{ request('numero_solicitud') }}">
                                    </div>
                                </div>

                                <!-- DNI -->
                                <div class="col-md-2">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">DNI</label>
                                        <input type="text" name="dni" class="form-control"
                                            value="{{ request('dni') }}">
                                    </div>
                                </div>

                                <!-- Nombre -->
                                <div class="col-md-2">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">Nombre</label>
                                        <input type="text" name="nombre" class="form-control"
                                            value="{{ request('nombre') }}">
                                    </div>
                                </div>

                                <!-- Número de Suministro -->
                                <div class="col-md-2">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">N. De Suministro</label>
                                        <input type="text" name="numero_suministro" class="form-control"
                                            value="{{ request('numero_suministro') }}">
                                    </div>
                                </div>

                                <!-- Estado (Select) -->
                                <div class="col-md-2">
                                    <div class="input-group input-group-outline">
                                        {{-- <label class="form-label">Estado</label> --}}
                                        <select name="estado" class="form-control">
                                            <option value="">Seleccione Estado</option>
                                            @foreach ($estados_Portal as $estado)
                                                <option value="{{ $estado->id }}"
                                                    {{ request('estado') == $estado->id ? 'selected' : '' }}>
                                                    {{ $estado->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-info ">
                                        <i class="fas fa-search" style="font-size: 12px;"></i></button>
                                    <a href="{{ route('employee.client.index') }}" class="btn btn-secondary"> <i
                                            class="fa fa-trash" style="font-size: 12px;"></i></a>
                                </div>
                            </div>
                            <!-- Botón Buscar -->
                        </form>

                        <hr>

                        <div class="row mt-3">
                            <div class="col-lg-6 col-7">
                                <h6>Solicitudes</h6>
                                <span class="text-center text-uppercase text-secondary text-xxs ">Total de solicitudes:
                                    <strong>{{ $totalSolicitudes }}</strong></span>
                            </div>
                            <div class="col-lg-6 col-5 my-auto text-end">

                                <span class="text-center text-uppercase text-secondary text-xxs ">Total de solicitudes según
                                    la busqueda: <strong>{{ $totalSolicitudesFiltradas }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body px-0 pb-2">
                        <div class="table-responsive">

                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Tipo y Numero de Documento</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Nombre</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Número de Solicitud</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Número de Suministro</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Número de Contrato de Suministro</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Estado
                                        </th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($clientesConSolicitudes->count() > 0)
                                        @foreach ($clientesConSolicitudes as $solicitud)
                                            <tr>
                                                <td class="align-middle text-center text-sm">
                                                    <p class="text-sm mb-0">
                                                        <span class="badge bg-light text-dark">
                                                            {{ $solicitud->tipo_documento_nombre }}
                                                        </span>
                                                        -
                                                        <span class="text-xs font-weight-bold">
                                                            {{ $solicitud->solicitante->numero_documento }}
                                                        </span>
                                                    </p>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">
                                                        {{ optional($solicitud->solicitante)->nombre ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">
                                                        {{ $solicitud->numero_solicitud }}
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">
                                                        {{ $solicitud->numero_suministro ?? 'Pendiente de Aprobación' }}
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">
                                                        {{ $solicitud->numero_contrato_suministro ?? 'Sin contrato' }}
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">
                                                        {{ optional($solicitud->estadoPortal)->nombre ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <a class="mx-3  OpenModal" data-toggle="modal"
                                                        data-target="#myModalInformation"
                                                        data-head-id="{{ $solicitud->id }}">
                                                        <i class="fa fa-info-circle fa-lg" aria-hidden="true"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <td class="align-middle text-center text-sm" colspan="7">
                                            <div class="d-flex flex-column align-items-center py-4">
                                                <i class="fas fa-file-alt fa-3x text-secondary mb-2"></i>
                                                <p class="text-secondary mb-0">No existen solicitudes registrados</p>
                                            </div>
                                        </td>
                                    @endif
                                </tbody>

                            </table>
                            <br>
                            <div class="d-flex justify-content-center">
                                {{ $clientesConSolicitudes->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Función para manejar las pestañas
            function initTabs() {
                $('.tab-btn').click(function() {
                    // Remover clase active de todos los botones y contenidos
                    $('.tab-btn').removeClass('active');
                    $('.tab-content').removeClass('active');

                    // Agregar clase active al botón clickeado
                    $(this).addClass('active');

                    // Mostrar el contenido correspondiente
                    const tabId = $(this).data('tab');
                    $(`#${tabId}`).addClass('active');
                });
            }

            // Manejador del click en el ícono de información
            $('.OpenModal').click(function() {
                var solicitudId = $(this).data('head-id');

                $('#myModalInformation').modal('show');
                $.ajax({
                    url: `/employee/getFullSolicitudDetails/${solicitudId}`,
                    type: 'GET',
                    dataType: 'json',
                    beforeSend: function() {
                        $('#loader').show();
                        $('#myForm').hide();
                    },
                    success: function(response) {

                        $('#loader').hide();
                        $('#myForm').show();

                        if (response.success) {
                            const solicitud = response.data;

                            // Actualizar el título del modal
                            $('#title').text('Detalles de Solicitud -' + solicitud
                                .numero_solicitud);

                            // Datos de la Solicitud
                            $('#numero_solicitud').val(solicitud.numero_solicitud ||
                                'No especificado');
                            $('#numero_suministro').val(solicitud.numero_suministro ||
                                'No especificado');
                            $('#numero_contrato_suministro').val(solicitud
                                .numero_contrato_suministro || 'No especificado');
                            $('#fecha_aprobacion_contrato').val(solicitud
                                .fecha_aprobacion_contrato || 'No especificado');
                            $('#fecha_registro_portal').val(solicitud.fecha_registro_portal ||
                                'No especificado');
                            $('#estado_solicitud').val(solicitud.estado_nombre ||
                                'No especificado');

                            // Datos del Solicitante
                            $('#solicitante_tipo_documento').val(solicitud
                                .solicitante_tipo_documento_nombre || 'No especificado');
                            $('#solicitante_numero_documento').val(solicitud
                                .solicitante_numero_documento || 'No especificado');
                            $('#solicitante_nombre').val(solicitud.solicitante_nombre ||
                                'No especificado');
                            $('#solicitante_celular').val(solicitud.solicitante_celular ||
                                'No especificado');
                            $('#solicitante_email').val(solicitud.solicitante_email ||
                                'No especificado');
                            $('#solicitante_usuario_fise').val(solicitud
                                .solicitante_usuario_fise || 'No especificado');

                            // Datos de la Empresa
                            $('#empresa_tipo_documento').val(solicitud.empresa_tipo_documento ||
                                'No especificado');
                            $('#empresa_numero_documento').val(solicitud
                                .empresa_numero_documento || 'No especificado');
                            $('#empresa_nombre').val(solicitud.empresa_nombre ||
                                'No especificado');
                            $('#empresa_registro_gas').val(solicitud.empresa_registro_gas ||
                                'No especificado');

                            // Datos de la Concesionaria
                            $('#concesionaria_tipo_documento').val(solicitud
                                .concesionaria_tipo_documento || 'No especificado');
                            $('#concesionaria_numero_documento').val(solicitud
                                .concesionaria_numero_documento || 'No especificado');
                            $('#concesionaria_nombre').val(solicitud.concesionaria_nombre ||
                                'No especificado');

                            // Datos de Instalación
                            $('#tipo_instalacion').val(solicitud.tipo_instalacion ||
                                'No especificado');
                            $('#tipo_acometida').val(solicitud.tipo_acometida ||
                                'No especificado');
                            $('#numero_puntos_instalacion').val(solicitud
                                .numero_puntos_instalacion || 'No especificado');
                            $('#fecha_finalizacion_instalacion_interna').val(solicitud
                                .fecha_finalizacion_instalacion_interna || 'No especificado'
                            );
                            $('#fecha_finalizacion_instalacion_acometida').val(solicitud
                                .fecha_finalizacion_instalacion_acometida ||
                                'No especificado');
                            $('#resultado_instalacion_tc').val(solicitud
                                .resultado_instalacion_tc || 'No especificado');
                            $('#fecha_programacion_habilitacion').val(solicitud
                                .fecha_programacion_habilitacion || 'No especificado');

                            // Datos del Proyecto
                            $('#tipo_proyecto').val(solicitud.tipo_proyecto ||
                                'No especificado');
                            $('#codigo_proyecto').val(solicitud.codigo_proyecto ||
                                'No especificado');
                            $('#categoria_proyecto').val(solicitud.categoria_proyecto ||
                                'No especificado');
                            $('#sub_categoria_proyecto').val(solicitud.sub_categoria_proyecto ||
                                'No especificado');
                            $('#codigo_objeto_conexion').val(solicitud.codigo_objeto_conexion ||
                                'No especificado');

                            // Datos de Ubicación
                            $('#ubicacion').val(solicitud.ubicacion || 'No especificado');
                            $('#codigo_manzana').val(solicitud.codigo_manzana ||
                                'No especificado');
                            $('#codigo_identificacion_interna').val(solicitud
                                .codigo_identificacion_interna || 'No especificado');
                            $('#nombre_malla').val(solicitud.nombre_malla || 'No especificado');
                            $('#direccion').val(solicitud.direccion || 'No especificado');
                            $('#departamento').val(solicitud.departamento || 'No especificado');
                            $('#provincia').val(solicitud.provincia || 'No especificado');
                            $('#distrito').val(solicitud.distrito || 'No especificado');
                            $('#venta_zona_no_gasificada').val(solicitud
                                .venta_zona_no_gasificada || 'No especificado');

                            // Datos del Asesor
                            $('#asesor_nombre').val(solicitud.asesor_nombre ||
                                'No especificado');
                            $('#asesor_tipo_documento').val(solicitud.asesor_tipo_documento ||
                                'No especificado');
                            $('#asesor_numero_documento').val(solicitud
                                .asesor_numero_documento || 'No especificado');
                            $('#asesor_telefono').val(solicitud.asesor_telefono ||
                                'No especificado');
                            $('#asesor_email').val(solicitud.asesor_email || 'No especificado');
                            $('#asesor_direccion').val(solicitud.asesor_direccion ||
                                'No especificado');

                            // Formatear fechas si es necesario
                            formatDates();

                            // Inicializar las pestañas
                            initTabs();

                            // Mostrar la primera pestaña por defecto
                            $('.tab-btn:first').click();

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loader').hide();
                        $('#myForm').show();
                        console.error('Error en la petición:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error al cargar los datos. Por favor, intente nuevamente.'
                        });
                    }
                });
            });

            // Función para formatear fechas
            function formatDates() {
                const dateFields = [
                    'fecha_aprobacion_contrato',
                    'fecha_registro_portal',
                    'fecha_finalizacion_instalacion_interna',
                    'fecha_finalizacion_instalacion_acometida',
                    'fecha_programacion_habilitacion'
                ];

                dateFields.forEach(field => {
                    const value = $(`#${field}`).val();
                    if (value && value !== 'No especificado') {
                        const date = new Date(value);
                        if (!isNaN(date)) {
                            $(`#${field}`).val(date.toLocaleDateString('es-ES'));
                        }
                    }
                });
            }

            // Limpiar el modal cuando se cierre
            $('#myModalInformation').on('hidden.bs.modal', function() {
                $('#myForm')[0].reset();
                $('#title').text('');
                $('.tab-btn:first').click();
            });
        });
    </script>
@endsection
