@extends('company.layouts.user_type.auth')

@section('content')

    <div class="d-flex justify-content-between" style="width:97%; margin:0 auto ">
        <div class="">{{ $tecnico->nombre }}</div>
        <a href="{{ route('company.technicals.index') }}" class="btn btn-info px-3 py-2">
            ATRAS
        </a>
    </div>
    <div class="container-fluid py-4" style="max-height:200vh">
        <div class="row mb-4" style="height: 100%">

            <div class="col-lg-6 col-md-12 mb-md-0 mb-4">

                <div class="card" style="height: 100%">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between mt-3">
                            <div class="">
                                <h6>Solicitudes</h6>
                            </div>
                            <div>
                                <a class="btn btn-info OpenModal py-2 px-3" data-toggle="modal"
                                    data-target="myModal">Registrar</a>
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
                                <tbody>
                                    @if (count($solicitudes ?? []) > 0)
                                        @foreach ($solicitudes as $solicitud)
                                            <tr>
                                                <td class="align-middle text-center text-sm">
                                                    <span
                                                        class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span
                                                        class="text-xs font-weight-bold">{{ $solicitud->numero_documento }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span
                                                        class="text-xs font-weight-bold">{{ $solicitud->tipo_cliente }}</span>
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
                                            <td class="align-middle text-center text-sm" colspan="5">No existen
                                                solicitudes registradas
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <br>
                            <div class="d-flex justify-content-center">
                                {{ $solicitudes->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12 mb-md-0 mb-4">

                <div class="card" style="height: 100%; padding: 0 20px">
                    <form id="form-solicitudes" action="" method="POST" style="height: 100%;">
                        @csrf
                        <div class="card-header pb-0">
                            <div class="mt-3 d-flex justify-content-between">
                                <div class="">
                                    <h6>Información</h6>
                                </div>
                                <div class="">
                                    <label for="" style="opacity: 0">r</label>
                                    <button type="submit" class="btn btn-info px-3 py-2">
                                        Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body px-0 pb-2">
                            <div class="row">
                                <input id="solicitudID" type="hidden" value="" name="solicitudID">
                                <div class="col-md-3">
                                    <label for="numero_documento_identificacion" style="font-size: 12px">N° Documento de
                                        identidad:</label>
                                    <input type="text" class="new-form-control" id="numero_documento_identificacion"
                                        name="numero_documento_identificacion" value="" style="text-align: right">
                                    <div class="invalid-feedback" id="numero_documento_identificacionError"></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="nombre">Nombre del cliente:</label>
                                    <input type="text" class="new-form-control" id="nombre" name="nombre"
                                        value="">
                                    <div class="invalid-feedback" id="nombreError"></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="numero_solicitud">Número de solicitud:</label>
                                    <input type="number" class="new-form-control" id="numero_solicitud"
                                        name="numero_solicitud" value="" min="0" step="1"
                                        style="text-align: right">
                                    <div class="invalid-feedback" id="numero_solicitudError"></div>
                                </div>
                                <div class="col-md-3">
                                    <label for="direccion">Dirección:</label>
                                    <input type="text" class="new-form-control" id="direccion" name="direccion"
                                        value="">
                                    <div class="invalid-feedback" id="direccionError"></div>
                                </div>
                            </div>

                            <br>
                            <div id="resultados" class="mt-3">
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>


        {{-- <form id="form-delete" action="{{ route('company.technicals.requests.destroy', ['technical' => $tecnico->id , 'request' => ':request']) }}" method="POST" class="d-inline"
            style="cursor:pointer">
            @csrf
            @method('DELETE')
        </form> --}}

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

        @php
            $tecnicoId = $tecnico->id;
        @endphp

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
                $('.delete-btn').on('click', function() {
                    var solicitudId = $(this).data('head-id')
                    var newAction = $('#form-delete').attr('action').replace(':request', solicitudId);
                    confirmDelete(function() {
                        $('#form-delete').attr('action', newAction).submit();
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
                $('#form-solicitudes').on('submit', function(event) {
                    console.log("estoy");

                    event.preventDefault();

                    $.ajax({
                        url: '{{ route('company.obtenerRegistros') }}', // Ruta del controlador
                        type: 'POST',
                        data: $(this).serialize(),
                        success: function(response) {
                            $('#resultados').html(
                                response); // Mostrar resultados en el modal
                        },
                        error: function(xhr) {
                            // Manejo de errores
                            let errors = xhr.responseJSON.errors;
                            if (errors) {
                                // Mostrar errores de validación, si los hay
                            }
                        }
                    });
                });

            });
        </script>

    @endsection
