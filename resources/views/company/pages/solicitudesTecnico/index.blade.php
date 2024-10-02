@extends('company.layouts.user_type.auth')

@section('content')

<div class="mx-4">{{ $tecnico->nombre }}</div>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                
                <div class="card">
                    <div class="card-header pb-0">
                        <a class="btn btn-info OpenModal py-2 px-3" data-toggle="modal" data-target="myModal">Registrar</a>
                        <div class="row mt-3">
                            <div class="col-lg-6 col-7">
                                <h6>Solicitudes</h6>
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

                                        </th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($tecnicos ?? []) > 0)
                                        @foreach ($solicitudes as $solicitud)
                                            <tr>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $solicitud->numero_documento }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $solicitud->tipo_cliente }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold"></span>
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
                                            <td class="align-middle text-center text-sm" colspan="5">No existen solicitudes registradas
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
        </div>
        <div class="d-flex justify-content-end">
            <a href="{{ route('company.technicals.index') }}" class="btn btn-info px-3 py-2">
                ATRAS
            </a>
        </div>
{{-- 
        <form id="form-delete" action="{{ route('company.technicals.destroy', '') }}" method="POST" class="d-inline"
            style="cursor:pointer">
            @csrf
            @method('DELETE')
        </form> --}}


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
                initModal('.OpenModal', '/company/technicals/', {
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
