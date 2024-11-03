@extends('company.layouts.user_type.auth')

@section('content')
    <div class="d-flex justify-content-between" style="width:100%; margin:0 auto ">
        <div class=""><span><strong>Tecnico Asignado:</strong> </span>{{ $tecnico->nombre }}</div>
        <a href="{{ route('company.technicals.index') }}" class="btn btn-info px-3 py-2">
            ATRAS
        </a>
    </div>
    <div class="row mb-4">
        <!-- Solicitudes Asignadas -->
        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">
            <div class="card" style="min-height: 700px; height: 100%; padding: 0 20px">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between mt-3">
                        <div class="">
                            <h6>Solicitudes asignadas</h6>
                        </div>
                    </div>
                </div>
                <div class="card-body px-0 pb-2 drop-zone" id="solicitudes-asignadas">
                    @include('company.pages.solicitudesTecnico.partials.tabla-asignadas')
                </div>
            </div>
        </div>

        <!-- Solicitudes en Espera -->
        <div class="col-lg-6 col-md-6 mb-md-0 mb-4">
            <div class="card" style="min-height: 700px; height: 100%; padding: 0 20px">
                <div class="card-header pb-0">
                    <form action="{{ route('company.technicals.requests.index', $tecnico->id) }}" method="GET">
                        <div class="row">
                            <div class="col-md-3">
                                <h6>Solicitudes en espera</h6>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline">
                                    {{-- <label class="form-label">Buscar por N° solicitud</label> --}}
                                    <input type="text" class="form-control" name="numero_solicitud" 
                                           value="{{ request('numero_solicitud') }}" placeholder="Buscar por N° solicitud">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group input-group-outline">
                                    {{-- <label class="form-label">Buscar por distrito</label> --}}
                                    <input type="text" class="form-control" name="distrito" 
                                           value="{{ request('distrito') }}" placeholder="Buscar por distrito">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-info btn-sm">
                                    <i class="fas fa-search" style="font-size: 12px;"></i></button>
                                <a href="{{ route('company.technicals.requests.index', $tecnico->id) }}" 
                                    class="btn btn-outline-secondary btn-sm">
                                    <i class="fa fa-trash" style="font-size: 12px;"></i>
                                </a>
                                {{-- @if(request('numero_solicitud') || request('distrito'))
                                    <a href="{{ route('company.technicals.requests.index', $tecnico->id) }}" 
                                       class="btn btn-outline-secondary btn-sm">
                                       <i class="fa fa-trash" aria-hidden="true"></i>
                                    </a>
                                @endif --}}
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body px-0 pb-2">
                    @include('company.pages.solicitudesTecnico.partials.tabla-disponibles')
                </div>
            </div>
        </div>
    </div>



    @include('company.pages.solicitudesTecnico.ubicacion')

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


    {{-- <style>
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
        /* Estilos adicionales para mantener consistencia */
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            /* height: 600px; */
            overflow-y: auto;
            border-radius: 0.5rem;
        }

        .table thead th {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 1;
            border-bottom: 2px solid #dee2e6;
        }

        .table th,
        .table td {
            width: auto;
            white-space: nowrap;
        }

        tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .pagination {
            margin-bottom: 0;
        }

        /* drag and drop */
        .draggable {
            cursor: move;
        }

        .draggable:hover {
            background-color: #f8f9fa;
        }

        .drop-zone {
            min-height: 500px;
            height: 100%;

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
    </style> --}}
    <style>
        
        /* Estilos de Card */
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            min-height: 700px;
            height: 100%;
            padding: 0 20px;
        }

        /* Estilos de Tabla */
        .table-responsive {
            border-radius: 0.5rem;
            overflow-y: auto;
        }

        .table {
            table-layout: fixed;
            width: 100%;
        }

        .table thead th {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 1;
            border-bottom: 2px solid #dee2e6;
        }

        .table th,
        .table td {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        /* Estilos de Paginación */
        .pagination {
            margin-bottom: 0;
        }

        /* Estilos Drag and Drop */
        .draggable {
            cursor: move;
            transition: background-color 0.2s ease;
        }

        .draggable.dragging {
            opacity: 0.5;
            background-color: #e9ecef;
        }

        .draggable:hover {
            background-color: #f8f9fa;
        }

        .drop-zone {
            min-height: 500px;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
        }

        .drop-zone.drag-over {
            background-color: rgba(13, 110, 253, 0.05);
            border: 2px dashed #0d6efd;
        }

        .drop-zone:empty::after {
            content: 'Arrastra aquí las solicitudes para asignarlas';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
        }




        /* Asegurar que el mapa se muestre correctamente en el modal */

        /* Agregar a tus estilos */
        .ver-ubicacion {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .ver-ubicacion:hover {
            color: #0d6efd;
            text-decoration: none;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: none;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tecnicoId = '{{ $tecnico->id }}';
            initDragAndDrop();

            function initDragAndDrop() {
                const solicitudesPendientes = document.querySelectorAll('.draggable');
                const dropZonas = document.querySelectorAll('.drop-zone');

                solicitudesPendientes.forEach(item => {
                    item.addEventListener('dragstart', handleDragStart);
                    item.addEventListener('dragend', handleDragEnd);
                });

                dropZonas.forEach(zona => {
                    zona.addEventListener('dragover', handleDragOver);
                    zona.addEventListener('dragenter', handleDragEnter);
                    zona.addEventListener('dragleave', handleDragLeave);
                    zona.addEventListener('drop', handleDrop);
                });
            }

            function handleDragStart(e) {
                e.target.classList.add('dragging');
                e.dataTransfer.setData('text/plain', e.target.dataset.id);
                e.dataTransfer.setData('application/json', e.target.dataset.solicitud);
            }

            function handleDragEnd(e) {
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

                // Verificar si el drop zone es el card-body de solicitudes asignadas
                if (dropZone && dropZone.id === 'solicitudes-asignadas') {
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
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Añadir aquí el loading
                        Swal.fire({
                            title: 'Asignando solicitud...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
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
                                Swal.close();

                                Swal.fire(
                                    '¡Asignado!',
                                    'La solicitud ha sido asignada correctamente.',
                                    'success'
                                ).then(() => {
                                    location.reload();
                                });
                            },
                            error: function(error) {

                                Swal.close();

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

            // Para el mapa
            $('.ver-ubicacion').on('click', function(e) {
                e.preventDefault();
                const data = $(this).data();

                $('#modal-departamento').text(data.departamento);
                $('#modal-provincia').text(data.provincia);
                $('#modal-distrito').text(data.distrito);

                // Inicializar mapa
                if (data.ubicacion) {
                    const [lat, lng] = data.ubicacion.split(',').map(Number);

                    // Si estás usando Google Maps
                    const map = new google.maps.Map(document.getElementById('mapa'), {
                        center: {
                            lat,
                            lng
                        },
                        zoom: 15
                    });

                    new google.maps.Marker({
                        position: {
                            lat,
                            lng
                        },
                        map,
                        title: `${data.distrito}, ${data.provincia}`
                    });
                }

                $('#ubicacionModal').modal('show');
            });

        });
    </script>
@endsection
