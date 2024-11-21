@extends('employee.layouts.user_type.auth')

@section('content')

    <div class="container-fluid ">
        <div class="d-flex justify-content-between" style="width:100%; margin:0 auto ">
            <div class=""><span><strong>Tecnico:</strong> </span>{{ $tecnico->nombre }}</div>
            <a href="{{ route('employee.technicals.index') }}" class="btn btn-info px-3 py-2">
                ATRAS
            </a>
        </div>
        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <form action="{{ route('employee.technicals.record.index', $tecnico->id) }}" method="GET">
                            <div class="row">
                                <div class="col-md-6 ">
                                    <h6>Historial</h6>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group input-group-outline">
                                        <input type="text" class="form-control" name="search"
                                            value="{{ request('search') }}"
                                            placeholder="Buscar por N° solicitud">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-info btn-sm">
                                        <i class="fas fa-search" style="font-size: 12px;"></i>
                                    </button>
                                    <a href="{{ route('employee.technicals.record.index', $tecnico->id) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        <i class="fa fa-trash" style="font-size: 12px;"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 5%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            N. solicitud
                                        </th>
                                        <th style="width: 5%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Estado
                                        </th>
                                        <th style="width: 30%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Descripcion
                                        </th>
                                        <th style="width: 30%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Registrado
                                        </th>
                                        <th style="width: 13%"
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Fecha
                                        </th>
                                    </tr>
                                </thead>
                                <tbody id="solicitudes-pendientes" class="drop-zone">
                                    @if (count($historial) > 0)
                                        @foreach ($historial as $item)
                                            <tr>
                                                <td style="width: 10%" class="align-middle text-center">
                                                    <span class="text-xs font-weight-bold">{{ $item->numero_solicitud }}</span>
                                                </td>
                                                <td style="width: 10%" class="align-middle text-center">
                                                    <span class="badge badge-sm {{ $item->estado_badge }} p-2">
                                                        {{ $item->estado_nombre }}-{{ $item->abreviatura }}
                                                    </span>
                                                </td>
                                                <td style="width: 30%" class="align-middle text-center">
                                                    <span class="text-xs font-weight-bold">{{ $item->descripcion }}</span>
                                                </td>
                                                <td style="width: 30%" class="align-middle text-center">
                                                    <span class="text-xs font-weight-bold">Registrado por: {{ $item->name }}</span>
                                                </td>
                                                <td style="width: 30%" class="align-middle text-center">
                                                    <span class="text-xs font-weight-bold">{{ $item->created_at }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr class="empty-row">
                                            <td colspan="6" class="text-center py-5" style="height: 500px;">
                                                No existen solicitudes registradas
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $historial->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
