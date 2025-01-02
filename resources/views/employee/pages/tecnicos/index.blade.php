@extends('employee.layouts.user_type.auth')

@section('content')
    <style>
        /* Efecto hover para los iconos */
        .btn-link:hover {
            transform: scale(1.1);
            transition: transform 0.2s ease;
        }

        /* Colores específicos para cada tipo de acción */
        .btn-link.text-secondary:hover {
            color: #5a6268 !important;
        }

        .btn-link.text-info:hover {
            color: #17a2b8 !important;
        }

        .btn-link.text-danger:hover {
            color: #dc3545 !important;
        }

        /* Tooltip mejorado */
        [title] {
            position: relative;
            cursor: pointer;
        }

        /* Asegurar que todas las celdas tengan la misma altura */
        .table td {
            height: 60px;
            /* Ajusta este valor según necesites */
            vertical-align: middle;
        }

        /* Estilo para los botones de acción */
        .btn-link {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            /* Ajusta este valor según necesites */
            width: 40px;
            padding: 0;
            margin: 0;
            transition: transform 0.2s ease;
        }

        /* Tamaño consistente para los íconos */
        .fa-lg {
            font-size: 20px !important;
        }
    </style>
    <div class="container-fluid ">
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <a class="btn btn-info OpenModal py-2 px-3" data-bs-toggle="modal"
                            data-bs-target="#myModal">Registrar</a>
                        <div class="row mt-3">
                            <div class="col-lg-6 col-7">
                                <h6>Tecnicos</h6>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">

                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 20%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            N° Documento de indentidad</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Nombre</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Correo</th>
                                        <th style="width: 10%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Cargo</th>
                                        <th style="width: 10%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Historial</th>
                                        <th style="width: 10%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Solicitudes
                                        </th>
                                        <th style="width: 10%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($tecnicos ?? []) > 0)
                                        @foreach ($tecnicos as $tecnico)
                                            <tr>
                                                <td class="align-middle text-center text-sm">
                                                    <p class="text-sm mb-0">
                                                        <span class="badge bg-light text-dark">
                                                            {{ $tecnico->tipo_documento_nombre }}
                                                        </span>
                                                        -
                                                        <span class="text-xs font-weight-bold">
                                                            {{ $tecnico->numero_documento_identificacion }}
                                                        </span>
                                                    </p>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $tecnico->nombre }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $tecnico->email }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="font-weight-bold badge bg-secondary">
                                                        {{ $tecnico->tipo_cargo_name }}
                                                    </span>
                                                </td>
                                                <!-- Para las columnas de historial, solicitudes y acciones -->
                                                <td class="align-middle text-center">
                                                    <div class="d-flex align-items-center justify-content-center"
                                                        title="ver historial de solicitudes" style="height: 100%">
                                                        <a href="{{ route('employee.technicals.record.index', $tecnico->id) }}"
                                                            class="btn btn-link text-secondary p-1">
                                                            <i class="fas fa-history fa-lg"></i>
                                                        </a>
                                                    </div>
                                                </td>

                                                <td class="align-middle text-center">
                                                    <div class="d-flex align-items-center justify-content-center"
                                                        title="Asignar Solicitudes">
                                                        <a href="{{ route('employee.technicals.requests.index', $tecnico->id) }}"
                                                            class="btn btn-link text-info p-2">
                                                            <i class="fas fa-clipboard-list fa-lg"></i>
                                                            <span class="badge bg-light text-dark">
                                                                {{ $tecnico->solicitudes_count }}
                                                            </span>
                                                        </a>
                                                    </div>
                                                </td>

                                                <td class="align-middle text-center">
                                                    <div class="d-flex align-items-center justify-content-center gap-2"
                                                        title="Editar Tecnico" style="height: 100%">
                                                        <a class="btn btn-link text-info p-1 OpenModal" data-toggle="modal"
                                                            data-target="#myModal" data-head-id="{{ $tecnico->id }}">
                                                            <i class="fas fa-pencil-alt fa-lg"></i>
                                                        </a>
                                                        <a class="btn btn-link text-danger p-1 delete-btn"
                                                            title="Eliminar Tecnico" data-head-id="{{ $tecnico->id }}">
                                                            <i class="fas fa-trash fa-lg"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="align-middle text-center text-sm" colspan="7">
                                                <div class="d-flex flex-column align-items-center py-4">
                                                    <i class="fas fa-hard-hat fa-3x text-secondary mb-2"></i>
                                                    <p class="text-secondary mb-0">No existen técnicos registrados</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <br>
                            <div class="d-flex justify-content-center">
                                {{ $tecnicos->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form id="form-delete" action="{{ route('employee.technicals.destroy', '') }}" method="POST" class="d-inline"
            style="cursor:pointer">
            @csrf
            @method('DELETE')
        </form>
    </div>
    @include('employee.pages.tecnicos.form')


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
            initModal('.OpenModal', '/employee/technicals/', {
                id: 'head-id',
                titleEdit: "Editar",
                titleCreate: "Registrar",
                submitTextEdit: "Actualizar",
                submitTextCreate: "Guardar",
                modalID: '#myModal',
                dataTransform: function(response) {
                    return response.tecnico;
                }
            });

            initFormSubmission('#myForm', '#myModal');

            //Delete Tecnico
            $('.delete-btn').on('click', function() {
                var tecnicoId = $(this).data('head-id')
                var action = $('#form-delete').attr('action') + '/' + tecnicoId;
                console.log(action);
                confirmDelete(function() {
                    $('#form-delete').attr('action', action).submit();
                });
            });

            //Edit Tecncio
            $(document).ready(function() {
                $('.edit-form-data').on('click', function() {
                    var tecnicoId = $(this).data('head-id');
                    $('#myModal input#tecnicoId').val(tecnicoId);
                })
            });
        });
    </script>

@endsection
