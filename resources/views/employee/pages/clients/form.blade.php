<div class="modal fade" id="uploadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-inspinia text-info" id="uploadModalLabel">Portal de Carga de Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <a href="#" id="upload-trigger">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="48px" height="48px">
                            <rect width="16" height="9" x="28" y="15" fill="#21a366" />
                            <path fill="#185c37" d="M44,24H12v16c0,1.105,0.895,2,2,2h28c1.105,0,2-0.895,2-2V24z" />
                            <rect width="16" height="9" x="28" y="24" fill="#107c42" />
                            <rect width="16" height="9" x="12" y="15" fill="#3fa071" />
                            <path fill="#33c481" d="M42,6H28v9h16V8C44,6.895,43.105,6,42,6z" />
                            <path fill="#21a366" d="M14,6h14v9H12V8C12,6.895,12.895,6,14,6z" />
                            <path
                                d="M22.319,13H12v24h10.319C24.352,37,26,35.352,26,33.319V16.681C26,14.648,24.352,13,22.319,13z"
                                opacity=".05" />
                            <path
                                d="M22.213,36H12V13.333h10.213c1.724,0,3.121,1.397,3.121,3.121v16.425	C25.333,34.603,23.936,36,22.213,36z"
                                opacity=".07" />
                            <path
                                d="M22.106,35H12V13.667h10.106c1.414,0,2.56,1.146,2.56,2.56V32.44C24.667,33.854,23.52,35,22.106,35z"
                                opacity=".09" />
                            <linearGradient id="flEJnwg7q~uKUdkX0KCyBa" x1="4.725" x2="23.055" y1="14.725"
                                y2="33.055" gradientUnits="userSpaceOnUse">
                                <stop offset="0" stop-color="#18884f" />
                                <stop offset="1" stop-color="#0b6731" />
                            </linearGradient>
                            <path fill="url(#flEJnwg7q~uKUdkX0KCyBa)"
                                d="M22,34H6c-1.105,0-2-0.895-2-2V16c0-1.105,0.895-2,2-2h16c1.105,0,2,0.895,2,2v16	C24,33.105,23.105,34,22,34z" />
                            <path fill="#fff"
                                d="M9.807,19h2.386l1.936,3.754L16.175,19h2.229l-3.071,5l3.141,5h-2.351l-2.11-3.93L11.912,29H9.526	l3.193-5.018L9.807,19z" />
                        </svg>
                    </a>
                    <h4 id="status-title" class="mt-3">Cargar Datos</h4>
                    <p id="status-message" class="text-muted">Haga clic en el icono para seleccionar un archivo</p>
                </div>
                <div class="progress d-none" id="progress-container">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar"
                        style="width: 0%;" id="progress-bar"></div>
                </div>
                <div id="result-icon" class="text-center mt-3 d-none">
                    <!-- El icono de éxito/error se insertará aquí -->
                </div>
                <div id="error-message" class="text-danger mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<input type="file" id="file-input" style="display: none;" accept=".xls,.xlsx" />

<style>
    .upload-icon {
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .upload-icon:hover {
        transform: scale(1.1);
    }

    .modal-content {
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
        background-color: #f8f9fa;
        border-bottom: none;
    }

    .modal-footer {
        border-top: none;
    }

    .progress {
        height: 25px;
        border-radius: 5px;
    }

    .result-icon {
        font-size: 48px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadTrigger = document.getElementById('upload-trigger');
        const fileInput = document.getElementById('file-input');
        const progressBar = document.getElementById('progress-bar');
        const progressContainer = document.getElementById('progress-container');
        const resultIcon = document.getElementById('result-icon');
        const statusTitle = document.getElementById('status-title');
        const statusMessage = document.getElementById('status-message');
        const errorMessage = document.getElementById('error-message');
        const modal = document.getElementById('uploadModal');
        let uploadSuccessful = false;
        let processingCheckInterval;

        uploadTrigger.addEventListener('click', function(event) {
            event.preventDefault(); // Prevenir el comportamiento por defecto del enlace
            event.stopPropagation(); // Detener la propagación del evento
            fileInput.click();
        });

        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) uploadExcel(file);
        });

        function getCsrfToken() {
            // Intenta obtener el token del meta tag
            let token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                return token.getAttribute('content');
            }

            // Si no está en un meta tag, intenta obtenerlo de un input hidden
            token = document.querySelector('input[name="_token"]');
            if (token) {
                return token.value;
            }

            // Si aún no lo encuentra, busca en las cookies
            const name = 'XSRF-TOKEN=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const cookieArray = decodedCookie.split(';');
            for (let i = 0; i < cookieArray.length; i++) {
                let cookie = cookieArray[i].trim();
                if (cookie.indexOf(name) == 0) {
                    return cookie.substring(name.length, cookie.length);
                }
            }

            // Si no se encuentra el token, lanza un error
            console.error('No se pudo encontrar el token CSRF');
            return null;
        }

        function updateProgress(percent) {
            progressBar.style.width = percent + '%';
            progressBar.textContent = percent.toFixed(1) + '%';
        }

        function handleUploadSuccess(response) {
            updateStatus('Procesando...', 'El archivo se está procesando en segundo plano');
            updateProgress(90);
        }

        function uploadExcel(file) {
            resetUI();
            updateStatus('Cargando...', 'Procesando el archivo...');
            const formData = new FormData();
            formData.append('file', file);
            const xhr = new XMLHttpRequest();

            // Evento de progreso de subida
            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    updateProgress(Math.min(percentComplete,
                        90)); // Máximo 90% hasta que termine el procesamiento
                }
            });

            // Evento de respuesta del servidor
            xhr.addEventListener('load', () => {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && response.success) {
                        // Archivo subido exitosamente, comenzar a verificar el progreso
                        handleUploadSuccess(response);
                        startProgressChecking(response.file_id);
                    } else {
                        handleError(response.message || 'Error al procesar el archivo.');
                    }
                } catch (e) {
                    handleError('Error al procesar la respuesta del servidor.');
                }
            });
            xhr.addEventListener('error', () => {
                handleError('Error de conexión al servidor.');
                uploadSuccessful = false;
            });
            xhr.addEventListener('timeout', () => {
                handleError('La solicitud ha excedido el tiempo de espera.');
                uploadSuccessful = false;
            });
            xhr.open('POST', '/employee/change');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            xhr.send(formData);
        }

        function startProgressChecking(fileId) {
            // Verificar el progreso cada 2 segundos
            processingCheckInterval = setInterval(() => {
                checkProcessingProgress(fileId);
            }, 2000);
        }

        function checkProcessingProgress(fileId) {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `/employee/check-progress/${fileId}`);
            xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.addEventListener('load', () => {
                try {
                    const response = JSON.parse(xhr.responseText);

                    if (response.error === "No se encontró el proceso de carga") {
                        // Si no se encuentra el proceso, asumimos que terminó exitosamente
                        clearInterval(processingCheckInterval);
                        handleSuccess("El archivo se procesó correctamente");
                        uploadSuccessful = true;
                    } else if (response.completed) {
                        clearInterval(processingCheckInterval);
                        handleSuccess(response.message);
                        uploadSuccessful = true;
                    } else if (response.error) {
                        clearInterval(processingCheckInterval);
                        handleError(response.error);
                    } else {
                        // Actualizar el progreso
                        updateProgress(90 + (response.progress * 10)); // 90-100%
                        updateStatus('Procesando...',
                            `Procesadas ${response.processed} de ${response.total} filas`);
                    }
                } catch (e) {
                    console.error('Error checking progress:', e);
                }
            });

            xhr.send();
        }

        function resetUI() {
            if (processingCheckInterval) {
                clearInterval(processingCheckInterval);
            }
            progressBar.style.width = '0%';
            progressBar.textContent = '';
            progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-secondary';
            progressContainer.classList.remove('d-none');
            resultIcon.classList.add('d-none');
            errorMessage.classList.add('d-none');
        }

        function updateStatus(title, message) {
            statusTitle.textContent = title;
            statusMessage.textContent = message;
        }

        function handleSuccess(message) {
            progressBar.classList.remove('bg-secondary');
            progressBar.classList.add('bg-success');
            progressBar.style.width = '100%';
            progressBar.textContent = '100%';
            resultIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success result-icon"></i>';
            resultIcon.classList.remove('d-none');
            updateStatus('Datos Cargados', message);
        }

        function handleError(message) {
            if (processingCheckInterval) {
                clearInterval(processingCheckInterval);
            }
            progressBar.classList.remove('bg-secondary');
            progressBar.classList.add('bg-danger');
            resultIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger result-icon"></i>';
            resultIcon.classList.remove('d-none');
            updateStatus('Error al Cargar Datos', 'Se ha producido un error');
            errorMessage.textContent = message;
            errorMessage.classList.remove('d-none');
        }


        modal.addEventListener('hidden.bs.modal', function() {
            if (uploadSuccessful) {
                location.reload();
            }
        });
    });
</script>
