@extends('employee.layouts.user_type.auth')
@push('styles')
    <link href="{{ asset('css/technician-requests.css') }}" rel="stylesheet">
@endpush
@section('content')
    <div class="d-flex justify-content-between" style="width:100%; margin:0 auto ">
        <div class=""><span><strong>Tecnico Asignado:</strong> </span>{{ $tecnico->nombre }}</div>
        <a href="{{ route('employee.technicals.index') }}" class="btn btn-info px-3 py-2">
            ATRAS
        </a>
    </div>
    <div class="row mb-4">
        <!-- Solicitudes Asignadas -->
        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">
            <div class="card" style="min-height: 700px; height: 100%; padding: 0 20px">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between">
                        <div class="row">
                            <div class="col-md-6 ">
                                <h6>Solicitudes Asignadas</h6>
                            </div>
                            <div class="col-md-6 ">
                                <div class="d-flex justify-content-end ">
                                    <button type="button" id="eliminarMultiple" class="btn btn-danger btn-sm" disabled>
                                        <i class="fas fa-trash" style="font-size: 12px;"></i> Eliminar seleccionados
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pb-2 drop-zone" id="solicitudes-asignadas">
                    @include('employee.pages.solicitudesTecnico.partials.tabla-asignadas')
                </div>
            </div>
        </div>

        <!-- Solicitudes en Espera -->
        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">
            <div class="card" style="min-height: 700px; height: 100%; padding: 0 20px">
                <div class="card-header pb-0">

                    <form action="{{ route('employee.technicals.requests.index', $tecnico->id) }}" method="GET">
                        <div class="row">
                            <div class="col-md-4 ">
                                <h6>Solicitudes Disponibles</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="input-group input-group-outline">
                                    <input type="text" class="form-control" name="search"
                                        value="{{ request('search') }}"
                                        placeholder="Buscar por N° solicitud, distrito o categoría...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-search" style="font-size: 12px;"></i>
                                </button>
                                <a href="{{ route('employee.technicals.requests.index', $tecnico->id) }}"
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="fa fa-trash" style="font-size: 12px;"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body px-0 pb-2">
                    @include('employee.pages.solicitudesTecnico.partials.tabla-disponibles')
                </div>
            </div>
        </div>
    </div>

    @include('employee.pages.solicitudesTecnico.ubicacion')

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
@push('scripts')
    <script>
        window.tecnicoId = "{{ $tecnico->id }}";
    </script>
    <script src="{{ asset('js/technician/multiple-selection.js') }}"></script>
    <script src="{{ asset('js/technician/drag-and-drop.js') }}"></script>
    <script src="{{ asset('js/technician/map-handler.js') }}"></script>
    <script src="{{ asset('js/technician/delete-handler.js') }}"></script>
    <script src="{{ asset('js/technician/main.js') }}"></script>
@endpush

