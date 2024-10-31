@extends('company.layouts.user_type.auth')

@section('content')

    <div class="d-flex justify-content-between" style="width:100%; margin:0 auto ">
        <div class=""><span><strong>Tecnico Asignado:</strong> </span>{{ $tecnico->nombre }}</div>
        <a href="{{ route('company.technicals.index') }}" class="btn btn-info px-3 py-2">
            ATRAS
        </a>
    </div>
    <div class="row mb-4" style="height: 100%">

        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">

            <div class="card" style="height: 100%; padding: 0 20px">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between mt-3">
                        <div class="">
                            <h6>Solicitudes asignadas</h6>
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
                                        N. SOLICITUD</th>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        NOMBRE</th>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        DEP-PROV-DIST</th>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        ESTADO
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="solicitudes-asignadas" class="drop-zone">
                                @if (count($solicitudesAsignadas ?? []) > 0)
                                    @foreach ($solicitudesAsignadas as $solicitud)
                                        <tr>
                                            <td style="width: 20%" class="align-middle text-center">
                                                <span
                                                    class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                                            </td>
                                            <td style="width: 30%" class="align-middle text-center">
                                                <span
                                                    class="text-xs font-weight-bold">{{ $solicitud->solicitante_nombre }}</span>
                                            </td>
                                            <td style="width: 35%" class="align-middle text-info">
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ $solicitud->departamento }}-{{ $solicitud->provincia }}</p>
                                                <p class="text-xs text-secondary mb-0">{{ $solicitud->distrito }}</p>
                                                {{-- <span class="text-xs font-weight-bold">
                                                    {{ $solicitud->departamento }}-{{ $solicitud->provincia }}-{{ $solicitud->distrito }}
                                                </span> --}}
                                            </td>
                                            <td style="width: 15%" class="align-middle text-center text-sm">
                                                <span class="badge badge-sm {{ $solicitud->estado_badge }} p-2">
                                                    {{ $solicitud->estado_nombre }}
                                                </span>
                                            </td>

                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="empty-row">
                                        <td colspan="4" class="text-center py-5" style="height: 100px;">
                                            Arrastra aquí las solicitudes para asignarlas
                                        </td>
                                    </tr>
                                @endif
                            </tbody>

                        </table>
                        <br>
                        <div class="d-flex justify-content-center">
                            {{-- {{ $solicitudesIndex->links('pagination::bootstrap-4') }} --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!---------------->
        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">

            <div class="card" style="height: 100%; padding: 0 20px">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between mt-3">
                        <div class="">
                            <h6>Solicitudes en espera</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0" id="tabla-solicitudes">
                            <thead>
                                <tr>
                                    <th style="width: 15%"
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        N. solicitud
                                    </th>
                                    <th style="width: 40%"
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Nombre
                                    </th>
                                    <th style="width: 20%"
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Dep-Prov-Dist
                                    </th>
                                    <th style="width: 15%"
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="solicitudes-pendientes" class="drop-zone">
                                @if (count($solicitudesDisponibles ?? []) > 0)
                                    @foreach ($solicitudesDisponibles as $solicitud)
                                        <tr class="draggable" draggable="true" data-id="{{ $solicitud->id }}"
                                            data-solicitud="{{ json_encode($solicitud) }}">
                                            <td style="width: 20%" class="align-middle text-center">
                                                <span
                                                    class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                                            </td>
                                            <td style="width: 30%" class="align-middle text-center">
                                                <span
                                                    class="text-xs font-weight-bold">{{ $solicitud->solicitante_nombre }}</span>
                                            </td>
                                            <td style="width: 35%" class="align-middle text-info">
                                                <p class="text-xs font-weight-bold mb-0">
                                                    {{ $solicitud->departamento }}-{{ $solicitud->provincia }}</p>
                                                <p class="text-xs text-secondary mb-0">{{ $solicitud->distrito }}</p>
                                                {{-- <span class="text-xs font-weight-bold">
                                                    {{ $solicitud->departamento }}-{{ $solicitud->provincia }}-{{ $solicitud->distrito }}
                                                </span> --}}
                                            </td>
                                            <td style="width: 15%" class="align-middle text-center text-sm">
                                                <span class="badge badge-sm {{ $solicitud->estado_badge }} p-2">
                                                    {{ $solicitud->estado_nombre }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="align-middle text-center text-sm font-weight-bold" colspan="4">
                                            No existen solicitudes registradas
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <br>
                        <div class="d-flex justify-content-center">
                            {{ $solicitudesDisponibles->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    </div>

    <form id="form-delete"
        action="{{ route('company.technicals.requests.destroy', ['technical' => $tecnico->id, 'request' => ':request']) }}"
        method="POST" class="d-inline">
        @csrf
        @method('DELETE')
    </form>

    @include('company.pages.solicitudesTecnico.form')

    @if (session('message') || session('error'))
        <script>
            Swal.fire({
                position: "center",
                icon: "{{ session('error') ? 'error' : 'success' }}",
                title: "Información",
                text: "{{ session('error') ?? session('message') }}",
                showConfirmButton: false,
                timer: 1500
            });
        </script>
    @endif


    {{-- <script>
        $(document).ready(function() {

            // Borrar Solicitud
            $('#tabla-solicitudes').on('click', '.delete-btn', function() {
                var solicitudId = $(this).data('head-id');
                var newAction = $('#form-delete').attr('action').replace(':request', solicitudId);
                var _token = $('#form-delete input[name="_token"]').val();

                confirmDelete(function() {
                    // Realizar la eliminación mediante AJAX
                    $.ajax({
                        url: newAction,
                        type: 'DELETE', // Método para eliminar
                        data: {
                            _token: _token
                        },
                        success: function(response) {

                            $.ajax({
                                url: "/company/getDataIndex/{{ $tecnico->id }}",
                                type: 'GET',
                                success: function(response) {

                                    let tbdoy = $('#tbody');
                                    tbdoy.empty();
                                    if (response.solicitudes.data.length >0) {
                                        response.solicitudes.data.forEach(
                                            function(item) {
                                                tbdoy.append(`
                                                    <tr data-id="${item.id}">
                                                        <td class="align-middle text-center text-sm">
                                                            <span
                                                                class="text-xs font-weight-bold">${item.numero_solicitud}</span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span class="text-xs font-weight-bold">
                                                                ${item.solicitante.numero_documento_identificacion}
                                                            </span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span class="text-xs font-weight-bold">
                                                                ${item.proyecto.categoria}
                                                            </span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <a class="mx-3 edit-form-data  OpenModal" data-toggle="modal"
                                                                data-target="#myModal" data-head-id="${item.id}">
                                                                <i class="fa fa-edit fa-lg text-info"></i>
                                                            </a>
                                                            <a class="delete-btn" data-head-id="${item.id}">
                                                                <i class="far fa-trash-alt fa-lg text-danger"></i>
                                                            </a>
                                                        </td>
                                                    </tr>`
                                                );
                                            });
                                    } else {
                                        $(tbdoy).append(
                                            '<p>No existen solicitudes para este técnico.</p>'
                                            );
                                    }
                                },
                                error: function(response) {
                                    console.log('Error al borrar registro');
                                }

                            });

                            //     // Opcional: Actualiza la tabla o elimina el elemento visualmente
                            //     $('#fila-' + solicitudId)
                            // .remove(); // Asegúrate de tener un identificador para la fila
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'No se pudo eliminar el registro',
                            });
                        }
                    });
                });
            });
            //Editar Solicitud
            $(document).ready(function() {
                $('.edit-form-data').on('click', function() {
                    var solicitudId = $(this).data('head-id');
                    $('#myModal input#solicitudID').val(solicitudId);
                })
            });

            //Obtiene resultados de la busqueda
            $('#informacion').on('click', '.page-link', function(e) {

                e.preventDefault();
                let url = $(this).attr('href');

                //Extaemos parametros de la url
                let params = new URLSearchParams(url.split('?')[1]);
                let requesData = {};
                params.forEach((value, key) => {
                    requesData[key] = value;
                });

                let type = 'GET';
                let invalidFeedback = $('#invalid-feedback');
                $.ajax({
                    url: url,
                    type: type,
                    data: requesData,
                    success: function(response) {
                        invalidFeedback.empty();
                        $('#resultados').html(
                            response); // Mostrar resultados en el modal
                    },
                    error: function(response) {
                        if (response.status == 422) {
                            var errors = response.responseJSON.errors;
                            // Maneja errores si es necesario
                        }
                    }
                });

            });

            $('#informacion').on('click', '#submit_formulario_informacion', function() {
                let type = 'POST';
                let url = '{{ route('company.obtenerRegistros') }}';

                $('#form-solicitudes').off('submit').on('submit', function(event) {

                    event.preventDefault();
                    let invalidFeedback = $('#invalid-feedback');

                    $.ajax({
                        url: url,
                        type: type,
                        data: $(this).serialize(),
                        success: function(response) {
                            invalidFeedback.empty();
                            $('#resultados').html(
                                response); // Mostrar resultados en el modal
                        },
                        error: function(response) {

                            if (response.status == 422) {

                                var errors = response.responseJSON.errors;
                                $('#no_hay_registros').remove();
                                invalidFeedback.empty();
                                invalidFeedback.html(
                                    `<p class="text-danger font-weight-bold" style="font-size:14px">${errors.atleast_one}</p>`
                                )
                            }
                        }
                    });
                });

            })


        });
    </script> --}}
    <style>
        #tabla-solicitudes {
            table-layout: fixed;
            /* Esto es clave para mantener los anchos fijos */
            width: 100%;
        }

        #tabla-solicitudes th,
        #tabla-solicitudes td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Si necesitas que el texto largo se muestre en múltiples líneas en vez de truncarse */
        /*
                                #tabla-solicitudes td {
                                    white-space: normal;
                                    word-wrap: break-word;
                                }
                                */


        .draggable {
            cursor: move;
        }

        .draggable:hover {
            background-color: #f8f9fa;
        }

        .drop-zone {
            min-height: 150px !important;
            /* Aumenta la altura mínima */
            border: 2px dashed #eee;
            /* Borde visible */
            transition: all 0.3s ease;
            position: relative;
        }

        /* Opcional: Mensaje cuando está vacío */
        .drop-zone:empty::after {
            content: 'Arrastra aquí las solicitudes para asignarlas';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            color: #999;
            font-size: 14px;
        }

        .drop-zone.drag-over {
            background-color: rgba(0, 123, 255, 0.1);
            border: 2px dashed #0d6efd;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tecnicoId = '{{ $tecnico->id }}'; // Agregar esta línea
            const solicitudesPendientes = document.querySelectorAll('.draggable');
            const dropZonas = document.querySelectorAll('.drop-zone');

            // Configurar los elementos arrastrables
            solicitudesPendientes.forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
                item.addEventListener('dragend', handleDragEnd);
            });

            // Configurar las zonas de soltar
            dropZonas.forEach(zona => {
                zona.addEventListener('dragover', handleDragOver);
                zona.addEventListener('dragenter', handleDragEnter);
                zona.addEventListener('dragleave', handleDragLeave);
                zona.addEventListener('drop', handleDrop);
            });

            function handleDragStart(e) {
                console.log('Drag start'); // Agregar log
                e.target.classList.add('dragging');
                e.dataTransfer.setData('text/plain', e.target.dataset.id);
                e.dataTransfer.setData('application/json', e.target.dataset.solicitud);
            }

            function handleDragEnd(e) {
                console.log('Drag end'); // Agregar log
                e.target.classList.remove('dragging');
            }

            function handleDragOver(e) {
                e.preventDefault();
            }

            function handleDragEnter(e) {
                e.preventDefault();
                e.target.closest('.drop-zone').classList.add('drag-over');
            }

            function handleDragLeave(e) {
                e.target.closest('.drop-zone').classList.remove('drag-over');
            }

            function handleDrop(e) {
                e.preventDefault();
                const dropZone = e.target.closest('.drop-zone');
                dropZone.classList.remove('drag-over');

                const solicitudId = e.dataTransfer.getData('text/plain');
                const solicitudData = JSON.parse(e.dataTransfer.getData('application/json'));

                console.log('Drop en zona:', dropZone.id); // Agregar log
                console.log('Solicitud ID:', solicitudId); // Agregar log

                // Si se suelta en la zona asignada
                if (dropZone.id === 'solicitudes-asignadas') {
                    asignarSolicitud(solicitudId, solicitudData);
                }
            }

            function asignarSolicitud(solicitudId, solicitudData) {
                Swal.fire({
                    title: '¿Deseas asignar esta solicitud?',
                    text: `Asignar solicitud ${solicitudData.numero_solicitud}`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, asignar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/company/technicals/${tecnicoId}/requests`,
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            data: {
                                solicitud_id: solicitudId
                            },
                            success: function(response) {
                                console.log('Asignación exitosa:', response); // Agregar log
                                Swal.fire(
                                    '¡Asignado!',
                                    'La solicitud ha sido asignada correctamente.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            },
                            error: function(error) {
                                console.error('Error en asignación:', error); // Agregar log
                                Swal.fire(
                                    'Error',
                                    'No se pudo asignar la solicitud.',
                                    'error'
                                );
                            }
                        });
                    }
                });
            }
        });
    </script>

@endsection
