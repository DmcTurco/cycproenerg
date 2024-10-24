@extends('company.layouts.user_type.auth')

@section('content')

    <div class="container-fluid ">
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        @include('company.pages.clients.form')
                        @include('company.pages.clients.information')
                        <a class="btn btn-info OpenModal py-2 px-3" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#uploadModal">Abrir Portal de Carga</a>
                        <hr>
                        <div class="row">
                            <form method="GET" action="{{ route('company.client.index') }}"
                                class="d-flex align-items-center justify-content-between">
                                <!-- Número de Solicitud -->
                                <div class=" d-flex align-items-center">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">Numero de Solicitud</label>
                                        <input type="text" name="numero_solicitud" class="form-control"
                                            value="{{ request('numero_solicitud') }}">
                                    </div>
                                </div>

                                <!-- DNI -->
                                <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">DNI</label>
                                        <input type="text" name="dni" class="form-control"
                                            value="{{ request('dni') }}">
                                    </div>
                                </div>

                                <!-- Nombre -->
                                <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">Nombre</label>
                                        <input type="text" name="nombre" class="form-control"
                                            value="{{ request('nombre') }}">
                                    </div>
                                </div>

                                <!-- Número de Suministro -->
                                <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                                    <div class="input-group input-group-outline">
                                        <label class="form-label">Numero De Suministro</label>
                                        <input type="text" name="numero_suministro" class="form-control"
                                            value="{{ request('numero_suministro') }}">
                                    </div>
                                </div>

                                <!-- Estado (Select) -->
                                <div class="ms-md-auto pe-md-1 d-flex align-items-center">
                                    <div class="input-group input-group-outline">
                                        {{-- <label class="form-label">Estado</label> --}}
                                        <select name="estado" class="form-control">
                                            <option value="">Seleccione el estado</option>
                                            @foreach ($estados as $estado)
                                                <option value="{{ $estado->id }}"
                                                    {{ request('estado') == $estado->id ? 'selected' : '' }}>
                                                    {{ $estado->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Botón Buscar -->
                                <div class="ms-md-auto pe-md-3 d-flex align-items-center">
                                    <button type="submit" class="btn btn-primary">Buscar</button>
                                    <a href="{{ route('company.client.index') }}" class="btn btn-secondary">Limpiar</a>
                                </div>
                            </form>
                        </div>
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
                                                    <span class="text-xs font-weight-bold">
                                                        {{ $solicitud->tipo_documento_nombre ?? 'N/A' }}-
                                                        {{ $solicitud->solicitante->numero_documento }}
                                                    </span>
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
                                                        {{ optional($solicitud->estado)->nombre ?? 'N/A' }}
                                                    </span>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <a class="mx-3  OpenModal" data-toggle="modal"
                                                        data-target="#myModalInformation" data-head-id="">
                                                        <i class="fa fa-info-circle fa-lg" aria-hidden="true"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="align-middle text-center text-sm" colspan="7">No hay solicitudes
                                                asociadas</td>
                                        </tr>
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
            initModal('.OpenModal', '/company/technicals/', {
                id: 'head-id',
                titleEdit: "Editar",
                titleCreate: "Registrar",
                submitTextEdit: "Actualizar",
                submitTextCreate: "Guardar",
                modalID: '#myModalInformation',
                dataTransform: function(response) {
                    return response.tecnico;
                }
            });
        });
    </script>
    

@endsection

