import * as XLSX from 'xlsx';

document.addEventListener('alpine:init', () => {
    Alpine.data('excelUploader', (config) => ({
        status: 'idle', // idle | preview | uploading | processing | success | error
        progress: 0,
        message: '',
        errorMessage: '',
        pollTimer: null,
        dragging: false,
        selectedFile: null,
        previewHeaders: [],
        previewRows: [],
        previewTotalRows: 0,
        total: 0,
        created: 0,
        updated: 0,
        failed: 0,

        pickFile() {
            if (this.status === 'uploading' || this.status === 'processing') return;
            this.$refs.fileInput.click();
        },

        onFileSelected(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (file) {
                this.handleFile(file);
            }
        },

        onFileDropped(event) {
            this.dragging = false;
            if (this.status === 'uploading' || this.status === 'processing') return;
            const file = event.dataTransfer.files[0];
            if (file) {
                this.handleFile(file);
            }
        },

        async handleFile(file) {
            this.reset();
            this.selectedFile = file;

            try {
                const buffer = await file.arrayBuffer();
                const workbook = XLSX.read(buffer, { type: 'array' });
                const sheet = workbook.Sheets[workbook.SheetNames[0]];
                const rows = XLSX.utils.sheet_to_json(sheet, { header: 1, blankrows: false, defval: '' });

                this.previewHeaders = (rows[0] || []).map((h) => (h === '' || h == null ? '—' : String(h)));
                const dataRows = rows.slice(1);
                this.previewTotalRows = dataRows.length;
                this.previewRows = dataRows.slice(0, 8);
                this.status = 'preview';
            } catch (e) {
                this.selectedFile = null;
                window.Swal?.fire({
                    icon: 'error',
                    title: 'No se pudo leer el archivo',
                    text: 'Verifica que sea un Excel válido (.xls, .xlsx).',
                });
            }
        },

        cancelPreview() {
            this.selectedFile = null;
            this.previewHeaders = [];
            this.previewRows = [];
            this.previewTotalRows = 0;
            this.status = 'idle';
        },

        confirmUpload() {
            if (this.selectedFile) {
                this.upload(this.selectedFile);
            }
        },

        reset() {
            clearInterval(this.pollTimer);
            this.status = 'idle';
            this.progress = 0;
            this.message = '';
            this.errorMessage = '';
            this.selectedFile = null;
            this.previewHeaders = [];
            this.previewRows = [];
            this.previewTotalRows = 0;
            this.total = 0;
            this.created = 0;
            this.updated = 0;
            this.failed = 0;
        },

        async upload(file) {
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
                    this.total = data.total ?? 0;
                    this.created = data.created ?? 0;
                    this.updated = data.updated ?? 0;
                    this.failed = data.failed ?? 0;
                    this.message = `Procesadas ${data.processed} de ${data.total} filas (${Math.round(data.progress)}%)`;

                    if (data.completed) {
                        clearInterval(this.pollTimer);
                        this.status = 'success';
                        this.message = data.failed
                            ? `Proceso completado. Se procesaron ${data.total} filas (${data.failed} omitida(s) por error).`
                            : `Proceso completado. Se procesaron ${data.total} filas.`;
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
