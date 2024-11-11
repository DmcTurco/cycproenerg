@extends('employee.layouts.user_type.auth')

@section('content')

    <div class="container-fluid ">
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <a class="btn btn-info OpenModal py-2 px-3" data-bs-toggle="modal" data-bs-target="#myModal">Registrar</a>
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
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Nombre</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Tipo de documento</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            N° Documento de indentidad</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Cargo</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Solicitudes
                                        </th>
                                        <th
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
                                                    <span class="text-xs font-weight-bold">{{ $tecnico->nombre }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span
                                                        class="text-xs font-weight-bold">{{ $tecnico->tipo_documento }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span
                                                        class="text-xs font-weight-bold">{{ $tecnico->numero_documento_identificacion }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $tecnico->cargo }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">
                                                        {{-- {{ $tecnico->numeroSolicitudes() }} --}}
                                                        <a
                                                            href="{{ route('employee.technicals.requests.index', $tecnico->id) }}">
                                                            <i class="fas fa-plus-circle text-info"
                                                                style="font-size: 15px"></i>
                                                        </a>
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <a class="mx-3 edit-form-data  OpenModal" data-toggle="modal"
                                                        data-target="#myModal" data-head-id="{{ $tecnico->id }}">
                                                        <i class="fa fa-edit fa-lg text-info"></i>
                                                    </a>
                                                    <a class="delete-btn" data-head-id="{{ $tecnico->id }}">
                                                        <i class="far fa-trash-alt fa-lg text-danger"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="align-middle text-center text-sm" colspan="6">No existen técnicos
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

        @include('employee.pages.tecnicos.form')

        {{-- <input type="file" id="file-input" style="display: none;" accept=".xls,.xlsx" /> --}}

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
