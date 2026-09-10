document.addEventListener('alpine:init', () => {
    Alpine.data('crudModal', (config) => ({
        open: false,
        mode: 'create',
        submitting: false,
        recordId: null,
        form: { ...(config.defaults || {}) },
        errors: {},

        openCreate() {
            this.mode = 'create';
            this.recordId = null;
            this.form = { ...(config.defaults || {}) };
            this.errors = {};
            this.open = true;
        },

        async openEdit(id) {
            this.mode = 'edit';
            this.recordId = id;
            this.errors = {};
            this.open = true;

            try {
                const response = await fetch(`${config.baseUrl}/${id}/edit`, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error('load failed');
                }

                const data = await response.json();
                const record = config.unwrap ? data[config.unwrap] : data;
                this.form = { ...(config.defaults || {}), ...record };
            } catch (e) {
                this.open = false;
                window.Swal?.fire({ icon: 'error', title: 'No se pudo cargar el registro' });
            }
        },

        async submit() {
            this.submitting = true;
            this.errors = {};

            const formData = new FormData();
            Object.entries(this.form).forEach(([key, value]) => {
                formData.append(key, value ?? '');
            });

            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                formData.append('_token', token);
            }

            // El backend expone un único endpoint de "store" que crea o
            // actualiza según si el campo id viene presente en el request
            // (no sigue la convención REST update/PUT).
            const url = config.baseUrl;

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: formData,
                });

                if (response.status === 422) {
                    const data = await response.json();
                    const flat = {};
                    Object.entries(data.errors || {}).forEach(([field, messages]) => {
                        flat[field] = Array.isArray(messages) ? messages[0] : messages;
                    });
                    this.errors = flat;
                    return;
                }

                if (!response.ok) {
                    throw new Error('submit failed');
                }

                const data = await response.json().catch(() => ({}));
                this.open = false;
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.reload();
                }
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'Ocurrió un error al guardar' });
            } finally {
                this.submitting = false;
            }
        },

        async remove(id) {
            const result = await window.Swal.fire({
                title: '¿Eliminar este registro?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
            });

            if (!result.isConfirmed) {
                return;
            }

            const formData = new FormData();
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                formData.append('_token', token);
            }
            formData.append('_method', 'DELETE');

            try {
                const response = await fetch(`${config.baseUrl}/${id}`, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: formData,
                });

                if (!response.ok) {
                    throw new Error('delete failed');
                }

                window.location.reload();
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'No se pudo eliminar el registro' });
            }
        },
    }));
});
