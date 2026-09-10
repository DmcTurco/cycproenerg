document.addEventListener('alpine:init', () => {
    Alpine.data('excelUploader', (config) => ({
        status: 'idle', // idle | uploading | processing | success | error
        progress: 0,
        message: '',
        errorMessage: '',
        pollTimer: null,

        pickFile() {
            if (this.status === 'uploading' || this.status === 'processing') return;
            this.$refs.fileInput.click();
        },

        onFileSelected(event) {
            const file = event.target.files[0];
            if (file) {
                this.upload(file);
            }
            event.target.value = '';
        },

        reset() {
            clearInterval(this.pollTimer);
            this.status = 'idle';
            this.progress = 0;
            this.message = '';
            this.errorMessage = '';
        },

        async upload(file) {
            this.reset();
            this.status = 'uploading';
            this.message = 'Subiendo archivo...';

            const formData = new FormData();
            formData.append('file', file);

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch(config.uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
                    body: formData,
                });
                const data = await response.json();

                if (!data.success) {
                    this.fail(data.message || 'No se pudo subir el archivo.');
                    return;
                }

                this.status = 'processing';
                this.startPolling(data.processId);
            } catch (e) {
                this.fail('Error al subir el archivo.');
            }
        },

        startPolling(processId) {
            let attempts = 0;
            const maxAttempts = 60;

            this.pollTimer = setInterval(async () => {
                try {
                    const response = await fetch(config.progressUrl.replace(':id', processId));
                    const data = await response.json();

                    if (data.timeout) {
                        this.fail('El proceso ha excedido el tiempo de espera. Inténtalo de nuevo.');
                        return;
                    }

                    if (data.error) {
                        attempts++;
                        if (attempts >= maxAttempts) {
                            this.fail('No se pudo procesar el archivo. Inténtalo de nuevo.');
                        }
                        return;
                    }

                    this.progress = data.progress;
                    this.message = `Procesadas ${data.processed} de ${data.total} filas (${Math.round(data.progress)}%)`;

                    if (data.completed) {
                        clearInterval(this.pollTimer);
                        this.status = 'success';
                        this.message = `Proceso completado. Se procesaron ${data.total} filas: ${data.created} nuevas, ${data.updated} actualizadas.`;
                    }
                } catch (e) {
                    attempts++;
                    if (attempts >= maxAttempts) {
                        this.fail('Error al verificar el progreso.');
                    }
                }
            }, 4000);
        },

        fail(message) {
            clearInterval(this.pollTimer);
            this.status = 'error';
            this.errorMessage = message;
        },
    }));
});
