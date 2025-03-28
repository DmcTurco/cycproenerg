class ExcelUploader {
    constructor() {
        this.initElements();
        this.initEventListeners();
        this.uploadSuccessful = false;
        this.progressCheckInterval = null;
    }
 
    initElements() {
        this.uploadTrigger = document.getElementById('upload-trigger');
        this.fileInput = document.getElementById('file-input');
        this.progressBar = document.getElementById('progress-bar');
        this.progressContainer = document.getElementById('progress-container');
        this.resultIcon = document.getElementById('result-icon');
        this.statusTitle = document.getElementById('status-title');
        this.statusMessage = document.getElementById('status-message');
        this.errorMessage = document.getElementById('error-message');
        this.modal = document.getElementById('uploadModal');
        this.closeButton = this.modal.querySelector('.btn-close');
        this.closeModalButton = this.modal.querySelector('.btn-secondary');
        this.overlay = document.createElement('div');
        this.setupOverlay();
    }
 
    setupOverlay() {
        this.overlay.className = 'loading-overlay d-none';
        this.overlay.innerHTML = `
            <div class="spinner-border text-light" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        `;
        document.body.appendChild(this.overlay);
    }
 
    initEventListeners() {
        this.uploadTrigger.addEventListener('click', (e) => {
            e.preventDefault();
            this.fileInput.click();
        });
 
        this.fileInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) this.uploadExcel(file);
        });
 
        this.modal.addEventListener('hidden.bs.modal', () => {
            if (this.uploadSuccessful) location.reload();
        });
    }
 
    uploadExcel(file) {
        this.resetUI();
        this.showLoadingState();
 
        const formData = new FormData();
        formData.append('file', file);
 
        fetch('/employee/change', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                this.startProgressCheck(response.processId);
            } else {
                this.handleError(response.message);
            }
        })
        .catch(error => {
            this.handleError('Error al subir el archivo');
            console.error(error);
        });
    }
 
    startProgressCheck(processId) {
        this.progressCheckInterval = setInterval(() => {
            this.checkProgress(processId);
        }, 5000);
    }
 
    checkProgress(processId) {
        let checkAttempts = 0;
        const maxAttempts = 60;
 
        fetch(`/employee/check-progress/${processId}`)
            .then(response => response.json())
            .then(data => {
                if (data.timeout) {
                    clearInterval(this.progressCheckInterval);
                    this.handleError('El proceso ha excedido el tiempo de espera. Por favor, inténtelo de nuevo.');
                    return;
                }
 
                if (data.error) {
                    checkAttempts++;
                    if (checkAttempts >= maxAttempts) {
                        clearInterval(this.progressCheckInterval);
                        this.handleError('No se pudo procesar el archivo. Por favor, inténtelo de nuevo.');
                    }
                    return;
                }
 
                this.updateProgress(data.progress);
                this.updateStatus(
                    'Procesando...',
                    `Procesadas ${data.processed} de ${data.total} filas (${Math.round(data.progress)}%)`
                );
 
                if (data.completed) {
                    clearInterval(this.progressCheckInterval);
                    this.handleSuccess(
                        `Proceso completado. Se procesaron ${data.total} filas. ` +
                        `Se agregaron ${data.created} nuevos registros y ` +
                        `se actualizaron ${data.updated} registros.`
                    );
                    this.uploadSuccessful = true;
                }
 
                const now = Date.now() / 1000;
                if (data.lastUpdate && (now - data.lastUpdate) > 300) {
                    clearInterval(this.progressCheckInterval);
                    this.handleError('El proceso parece estar estancado. Por favor, inténtelo de nuevo.');
                }
            })
            .catch(error => {
                console.error('Error checking progress:', error);
                checkAttempts++;
                if (checkAttempts >= maxAttempts) {
                    clearInterval(this.progressCheckInterval);
                    this.handleError('Error al verificar el progreso. Por favor, inténtelo de nuevo.');
                }
            });
    }
 
    updateProgress(percent) {
        this.progressBar.style.width = `${percent}%`;
        this.progressBar.textContent = `${Math.round(percent)}%`;
    }
 
    showLoadingState() {
        this.overlay.classList.remove('d-none');
        this.closeButton.disabled = true;
        this.closeModalButton.disabled = true;
    }
 
    hideLoadingState() {
        this.overlay.classList.add('d-none');
        this.closeButton.disabled = false;
        this.closeModalButton.disabled = false;
    }
 
    resetUI() {
        if (this.progressCheckInterval) {
            clearInterval(this.progressCheckInterval);
        }
        this.progressBar.style.width = '0%';
        this.progressBar.textContent = '';
        this.progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-secondary';
        this.progressContainer.classList.remove('d-none');
        this.resultIcon.classList.add('d-none');
        this.errorMessage.classList.add('d-none');
    }
 
    updateStatus(title, message) {
        this.statusTitle.textContent = title;
        this.statusMessage.textContent = message;
    }
 
    handleSuccess(message) {
        this.hideLoadingState();
        this.progressBar.classList.remove('bg-secondary');
        this.progressBar.classList.add('bg-success');
        this.resultIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success result-icon"></i>';
        this.resultIcon.classList.remove('d-none');
        this.updateStatus('Datos Cargados', message);
    }
 
    handleError(message) {
        this.hideLoadingState();
        if (this.progressCheckInterval) {
            clearInterval(this.progressCheckInterval);
        }
        this.progressBar.classList.remove('bg-secondary');
        this.progressBar.classList.add('bg-danger');
        this.resultIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger result-icon"></i>';
        this.resultIcon.classList.remove('d-none');
        this.updateStatus('Error en el Proceso', 'Se ha producido un error');
        this.errorMessage.textContent = message;
        this.errorMessage.classList.remove('d-none');
 
        const retryButton = document.createElement('button');
        retryButton.className = 'btn btn-primary mt-3';
        retryButton.textContent = 'Reintentar';
        retryButton.onclick = () => location.reload();
        this.errorMessage.parentNode.appendChild(retryButton);
    }
 }
 
 document.addEventListener('DOMContentLoaded', () => new ExcelUploader());