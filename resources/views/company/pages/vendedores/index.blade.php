@extends('company.layouts.user_type.auth')

@section('content')

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <a class="btn btn-info OpenModal py-2 px-3" data-toggle="modal" data-target="myModal">Registrar</a>
                        <div class="row mt-3">
                            <div class="col-lg-6 col-7">
                                <h6>Vendedores</h6>
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
                                            Numero de solicitud</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            N° Documento de indentidad</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Cargo</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Tipo de cliente
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($vendedores ?? []) > 0)
                                        @foreach ($vendedores as $vendedor)
                                            <tr>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $vendedor->nombre }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $vendedor->tipo_documento }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $vendedor->numero_documento_identificacion }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">{{ $vendedor->cargo }}</span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <span class="text-xs font-weight-bold">
                                                        {{-- {{ $vendedor->numeroSolicitudes() }} --}}
                                                        <a href="{{ route('company.technicals.requests.index' , $vendedor->id) }}">
                                                            <i class="fas fa-plus-circle text-info" style="font-size: 15px"></i>
                                                        </a>
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <a class="mx-3 edit-form-data  OpenModal" data-toggle="modal"
                                                        data-target="#myModal" data-head-id="{{ $vendedor->id }}">
                                                        <i class="fa fa-edit fa-lg text-info"></i>
                                                    </a>
                                                    <a class="delete-btn" data-head-id="{{ $vendedor->id }}">
                                                        <i class="far fa-trash-alt fa-lg text-danger"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="align-middle text-center text-sm" colspan="5">No existen Vendedores
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <br>
                            <div class="d-flex justify-content-center">
                                {{ $vendedores->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- <form id="form-delete" action="{{ route('company.technicals.destroy', '') }}" method="POST" class="d-inline"
            style="cursor:pointer">
            @csrf
            @method('DELETE')
        </form> --}}


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


    @endsection
