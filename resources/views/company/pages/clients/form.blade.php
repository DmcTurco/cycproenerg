<!-- Modal -->
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
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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

<div class="modal fade" id="uploadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-primary" id="uploadModalLabel">Portal de Carga de Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <div class="upload-icon" id="upload-trigger">
                        <i class="bi bi-file-earmark-excel text-success" style="font-size: 48px;"></i>
                    </div>
                    <h4 id="status-title" class="mt-3">Cargar Datos</h4>
                    <p id="status-message" class="text-muted">Haga clic en el icono para seleccionar un archivo</p>
                </div>
                <div class="progress d-none" id="progress-container">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-secondary" role="progressbar" style="width: 0%;" id="progress-bar"></div>
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

        uploadTrigger.addEventListener('click', () => fileInput.click());

        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) uploadExcel(file);
        });

        function uploadExcel(file) {
            resetUI();
            updateStatus('Cargando...', 'Procesando el archivo...');
            
            const xhr = new XMLHttpRequest();
            const formData = new FormData();
            formData.append('file', file);

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    const percentComplete = (event.loaded / event.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });

            xhr.addEventListener('load', () => {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && response.success) {
                        handleSuccess(response.message);
                    } else {
                        handleError(response.message || 'Error al procesar el archivo.');
                    }
                } catch (e) {
                    handleError('Error al procesar la respuesta del servidor.');
                }
            });

            xhr.addEventListener('error', () => handleError('Error de conexión al servidor.'));
            xhr.addEventListener('timeout', () => handleError('La solicitud ha excedido el tiempo de espera.'));

            xhr.open('POST', '/company/change');
            xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
            xhr.send(formData);
        }

        function resetUI() {
            progressBar.style.width = '0%';
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
            resultIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success result-icon"></i>';
            resultIcon.classList.remove('d-none');
            updateStatus('Datos Cargados', message);
        }

        function handleError(message) {
            progressBar.classList.remove('bg-secondary');
            progressBar.classList.add('bg-danger');
            resultIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger result-icon"></i>';
            resultIcon.classList.remove('d-none');
            updateStatus('Error al Cargar Datos', 'Se ha producido un error');
            errorMessage.textContent = message;
            errorMessage.classList.remove('d-none');
        }
    });
</script>
