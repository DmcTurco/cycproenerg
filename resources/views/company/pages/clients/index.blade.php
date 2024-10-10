@extends('company.layouts.user_type.auth')

@section('content')

    <div class="container-fluid py-4">

        <div class="row mb-4">
            <div class="col-lg-12 col-md-12 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        @include('company.pages.clients.form')
                        <a class="btn btn-info OpenModal py-2 px-3" data-toggle="modal" data-target="#myModal">Registrar</a>

                        <div class="row mt-3">
                            <div class="col-lg-6 col-7">
                                <h6>Solicitudes</h6>
                            </div>
                            <div class="col-lg-6 col-5 my-auto text-end">
                                <div class="dropdown float-lg-end pe-4">
                                    <a class="cursor-pointer" id="dropdownTable" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fa fa-ellipsis-v text-secondary"></i>
                                    </a>
                                    <ul class="dropdown-menu px-2 py-3 ms-sm-n4 ms-n5" aria-labelledby="dropdownTable">
                                        <li><a class="dropdown-item border-radius-md" href="javascript:;">Action</a>
                                        </li>
                                        <li><a class="dropdown-item border-radius-md" href="javascript:;">Another
                                                action</a></li>
                                        <li><a class="dropdown-item border-radius-md" href="javascript:;">Something
                                                else here</a></li>
                                    </ul>
                                </div>
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
                                            Tipo de Documento</th>
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
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if (count($clientesConSolicitudes) > 0)
                                        @foreach ($clientesConSolicitudes as $cliente)
                                            @if ($cliente->solicitudes->isNotEmpty())
                                                @foreach ($cliente->solicitudes as $solicitud)
                                                    <tr>
                                                        <td class="align-middle text-center text-sm">
                                                            <span
                                                                class="text-xs font-weight-bold">{{ $cliente->tipo_documento_identificacion }}</span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span
                                                                class="text-xs font-weight-bold">{{ $cliente->nombre }}</span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span
                                                                class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span
                                                                class="text-xs font-weight-bold">{{ $solicitud->numero_suministro ?? 'Pendiende de Aprobacion' }}</span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span
                                                                class="text-xs font-weight-bold">{{ $solicitud->numero_contrato_suministro ?? 'Sin contrato' }}</span>
                                                        </td>
                                                        <td class="align-middle text-center text-sm">
                                                            <span
                                                                class="text-xs font-weight-bold">{{ $solicitud->numero_contrato_suministro ?? 'Sin contrato' }}</span>
                                                        </td>

                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td>{{ $cliente->tipo_documento_identificacion }}</td>
                                                    <td>{{ $cliente->nombre }}</td>
                                                    <td colspan="3">No hay solicitudes asociadas</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @else
                                        <tr>
                                            <td class="align-middle text-center text-sm" colspan="5">No hay solicitudes
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

        {{-- <input type="file" id="file-input" style="display: none;" accept=".xls,.xlsx" /> --}}



<script>
    
</script>

        {{-- <script>
        // Elementos del DOM
        const uploadTrigger = document.getElementById('upload-trigger');
        const fileInput = document.getElementById('file-input');
        const progressBar = document.getElementById('progress-bar');
        const progressBarContainer = document.getElementById('progress-bar-container');
        const resultIcon = document.getElementById('result-icon');
        const errorMessage = document.getElementById('error-message');
    
        // Evento cuando se hace clic en el ícono de Excel
        uploadTrigger.addEventListener('click', function(event) {
            event.preventDefault(); // Prevenir comportamiento por defecto
            fileInput.click(); // Activar input de archivo
        });
    
        // Evento cuando se selecciona un archivo
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                // Iniciar la carga simulada
                uploadExcel(file);
            }
        });
    
        // Función para simular el progreso de carga del archivo Excel
        function uploadExcel(file) {
            // Resetear la barra y los mensajes
            progressBar.style.width = '0%';
            progressBarContainer.style.display = 'block';
            resultIcon.style.display = 'none';
            errorMessage.style.display = 'none';
            
            // Simular la carga del archivo usando Fetch API (en un entorno real harías una petición real)
            const formData = new FormData();
            formData.append('file', file);
    
            fetch(`/company/change`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
                // Aquí se agrega la opción para rastrear el progreso de carga
            }).then(response => {
                return response.json().then(data => ({ status: response.status, body: data }));
            }).then(result => {
                if (result.status === 200) {
                    // Si la carga fue exitosa
                    progressBar.style.width = '100%';
                    resultIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="green" viewBox="0 0 48 48" width="48px" height="48px"><path d="M 24 4 C 12.972874 4 4 12.972874 4 24 C 4 35.027126 12.972874 44 24 44 C 35.027126 44 44 35.027126 44 24 C 44 12.972874 35.027126 4 24 4 z M 24 6 C 34.010184 6 42 13.989816 42 24 C 42 34.010184 34.010184 42 24 42 C 13.989816 42 6 34.010184 6 24 C 6 13.989816 13.989816 6 24 6 z M 20.792969 31.707031 L 12.792969 23.707031 L 14.207031 22.292969 L 20.792969 28.878906 L 33.792969 15.878906 L 35.207031 17.292969 L 20.792969 31.707031 z"/></svg>';
                    resultIcon.style.display = 'block';
                } else {
                    throw new Error(result.body.error || 'Error al cargar el archivo.');
                }
            }).catch(error => {
                // Si hubo un error
                progressBar.style.width = '100%';
                resultIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="red" viewBox="0 0 48 48" width="48px" height="48px"><path d="M 24 4 C 12.972874 4 4 12.972874 4 24 C 4 35.027126 12.972874 44 24 44 C 35.027126 44 44 35.027126 44 24 C 44 12.972874 35.027126 4 24 4 z M 24 6 C 34.010184 6 42 13.989816 42 24 C 42 34.010184 34.010184 42 24 42 C 13.989816 42 6 34.010184 6 24 C 6 13.989816 13.989816 6 24 6 z M 16.585938 16.585938 L 15.171875 18 L 22.171875 25 L 15.171875 32 L 16.585938 33.414062 L 23.585938 26.414062 L 30.585938 33.414062 L 32 32 L 25 25 L 32 18 L 30.585938 16.585938 L 23.585938 23.585938 L 16.585938 16.585938 z"/></svg>';
                resultIcon.style.display = 'block';
                errorMessage.textContent = error.message;
                errorMessage.style.display = 'block';
            });
        }
    </script> --}}

    @endsection
