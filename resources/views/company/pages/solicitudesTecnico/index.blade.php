@extends('company.layouts.user_type.auth')

@section('content')

    <div class="d-flex justify-content-between" style="width:100%; margin:0 auto ">
        <div class="">{{ $tecnico->nombre }}</div>
        <a href="{{ route('company.technicals.index') }}" class="btn btn-info px-3 py-2">
            ATRAS
        </a>
    </div>
    <div class="row mb-4" style="height: 100%">

        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">

            <div class="card" style="height: 100%">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between mt-3">
                        <div class="">
                            <h6>Solicitudes</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0" id="tabla-solicitudes">
                            <thead>
                                <tr>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Número de solicitud</th>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Número de documento</th>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Tipo de cliente</th>
                                    <th
                                        class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            {{-- <tbody id="tbody">

                                @if (!empty($solicitudesIndex))
                                ok

                                @endif
                                <!--dynamic data-->

                            </tbody> --}}
                            <tbody id="tbody">
                                @if (count($solicitudesIndex ?? []) > 0)
                                    @foreach ($solicitudesIndex as $solicitud)
                                        <tr>
                                            <td class="align-middle text-center text-sm">
                                                <span
                                                    class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span
                                                    class="text-xs font-weight-bold">{{ $solicitud->solicitante->numero_documento_identificacion }}</span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span
                                                    class="text-xs font-weight-bold">{{ $solicitud->pivot->categoria }}</span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <a class="mx-3 edit-form-data  OpenModal" data-toggle="modal"
                                                    data-target="#myModal" data-head-id="{{ $solicitud->id }}">
                                                    <i class="fa fa-edit fa-lg text-info"></i>
                                                </a>
                                                <a class="delete-btn" data-head-id="{{ $solicitud->id }}">
                                                    <i class="far fa-trash-alt fa-lg text-danger"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="align-middle text-center text-sm font-weight-bold" colspan="5">No
                                            existen
                                            solicitudes registradas
                                    </tr>
                                @endif
                            </tbody>

                        </table>
                        <br>
                        <div class="d-flex justify-content-center">
                            {{ $solicitudesIndex->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!---------------->
        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">

            <div class="card" style="height: 100%; padding: 0 20px" id="informacion">
                <form id="form-solicitudes" action="" method="POST" style="height: 100%;">
                    @csrf
                    <input type="hidden" id="tecnicoID" name="tecnicoID" value="{{ $tecnico->id }}">
                    <div class="card-header pb-0">
                        <div class="mt-3 d-flex justify-content-between">
                            <div class="">
                                <h6>Información</h6>
                            </div>
                            <div class="">
                                <label for="" style="opacity: 0">r</label>
                                <button type="submit" id="getInfo" class="btn btn-info px-3 py-2">
                                    Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0" style="margin-top: -23px">
                        <div class="row">
                            <input id="solicitudID" type="hidden" value="" name="solicitudID">
                            <div class="col-md-3 d-flex flex-column justify-content-end" style="height:80px">
                                <label for="numero_documento_identificacion" style="font-size: 12px"
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    N° Documento de identidad:</label>
                                <input type="text" class="new-form-control" id="numero_documento_identificacion"
                                    name="numero_documento_identificacion" value="" style="text-align: right">
                                <div class="invalid-feedback" id="numero_documento_identificacionError"></div>
                            </div>
                            <div class="col-md-3 d-flex flex-column justify-content-end" style="height:80px">
                                <label for="nombre"
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    Nombre del cliente:</label>
                                <input type="text" class="new-form-control" id="nombre" name="nombre" value="">
                                <div class="invalid-feedback" id="nombreError"></div>
                            </div>
                            <div class="col-md-3 d-flex flex-column justify-content-end" style="height:80px">
                                <label for="numero_solicitud"
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    Número de solicitud:</label>
                                <input type="number" class="new-form-control" id="numero_solicitud" name="numero_solicitud"
                                    value="" min="0" step="1" style="text-align: right">
                                <div class="invalid-feedback" id="numero_solicitudError"></div>
                            </div>
                            <div class="col-md-3 d-flex flex-column justify-content-end" style="height:80px">
                                <label for="direccion"
                                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    Dirección:</label>
                                <input type="text" class="new-form-control" id="direccion" name="direccion"
                                    value="">
                                <div class="invalid-feedback" id="direccionError"></div>
                            </div>

                        </div>

                        <br>
                    </div>
                    <div id="invalid-feedback" class="d-flex justify-content-center">
                </form>
            </div>
            <div id="resultados" class="mt-3">
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


    <script>
        $(document).ready(function() {
            initModal('.OpenModal', '/company/technicals/{{ $tecnico->id }}/requests/', {
                id: 'head-id',
                titleEdit: "Editar",
                titleCreate: "Registrar",
                submitTextEdit: "Actualizar",
                submitTextCreate: "Guardar",
                modalID: '#myModal',
                dataTransform: function(response) {
                    return response.solicitud;
                }
            });

            initFormSubmission('#myForm', '#myModal');

            //Borrar Solicitud
            $('#tabla-solicitudes').on('click', '.delete-btn', function() {
                var solicitudId = $(this).data('head-id')
                var newAction = $('#form-delete').attr('action').replace(':request', solicitudId);
                confirmDelete(function() {
                    $('#form-delete').attr('action', newAction).submit();
                });
            });

            // Borrar Solicitud
            $('#tabla-solicitudes').on('click', '.delete-btn', function() {
                var solicitudId = $(this).data('head-id');
                var newAction = $('#form-delete').attr('action').replace(':request', solicitudId);
                var _token = $('#form-delete input[name="_token"]').val();
                console.log(newAction, _token);

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
                                url: "/company/technicals/{{ $tecnico->id }}/requests",
                                type: 'GET',
                                success: function(response) {
                                    console.log(
                                        'Indice recuperado correctamente'
                                    );
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

            $('#informacion').on('click', '#getInfo', function() {
                let type = 'POST';
                let url = '{{ route('company.obtenerRegistros') }}';
                obtenerRegistros(url, type);
            })

            function obtenerRegistros(url, type, newdata = null) {
                $('#form-solicitudes').off('submit').on('submit', function(event) {
                    console.log('estoy en el form');

                    event.preventDefault();
                    let invalidFeedback = $('#invalid-feedback');

                    $.ajax({
                        url: url,
                        type: type,
                        data: newdata ? newdata : $(this).serialize(),
                        success: function(response) {
                            invalidFeedback.empty();
                            $('#resultados').html(
                                response); // Mostrar resultados en el modal
                        },
                        error: function(response) {

                            if (response.status == 422) {

                                var errors = response.responseJSON.errors;
                                console.log(errors.atleast_one);

                                invalidFeedback.empty();
                                invalidFeedback.html(
                                    `<p class="text-danger font-weight-bold" style="font-size:13px">${errors.atleast_one}</p>`
                                )
                            }
                        }
                    });
                });
            }

        });
    </script>

@endsection
