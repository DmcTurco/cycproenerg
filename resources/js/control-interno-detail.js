document.addEventListener('alpine:init', () => {
    Alpine.data('controlInternoDetail', (config) => ({
        loading: true,
        loadError: false,
        errorMessage: '',
        saving: false,
        errors: {},

        ci: {
            fase: '',
            fecha_ingreso_general: '',
            fecha_construccion_control: '',
            observacion_control: '',
            marcado_para_anular: false,
            indicadores: {},
        },

        async load() {
            this.loading = true;
            this.loadError = false;

            try {
                const response = await fetch(config.controlInternoUrl, { headers: { Accept: 'application/json' } });
                if (!response.ok) {
                    throw new Error('load failed');
                }
                this.ci = { ...this.ci, ...(await response.json()) };
            } catch (e) {
                this.loadError = true;
                this.errorMessage = 'No se pudo cargar Control Interno de esta solicitud.';
            } finally {
                this.loading = false;
            }
        },

        async save() {
            this.saving = true;
            this.errors = {};

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            try {
                const response = await fetch(config.controlInternoUrl, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token || '',
                    },
                    body: JSON.stringify({
                        fecha_construccion_control: this.ci.fecha_construccion_control || null,
                        observacion_control: this.ci.observacion_control || null,
                        marcado_para_anular: !!this.ci.marcado_para_anular,
                    }),
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
                    throw new Error('save failed');
                }

                const data = await response.json();
                if (data.indicadores) {
                    // El guardado puede haber reclasificado la fase (CI-4) al
                    // toque: reflejamos fase + indicadores actualizados sin
                    // tener que recargar toda la pantalla.
                    this.ci.indicadores = data.indicadores;
                    this.ci.fase = data.indicadores.fase;
                }

                window.Swal?.fire({ icon: 'success', title: 'Control Interno actualizado', timer: 1500, showConfirmButton: false });
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'No se pudo guardar Control Interno' });
            } finally {
                this.saving = false;
            }
        },

        // CI-5: clases Tailwind para el badge de SEMÁFORO según su valor.
        semaforoClase() {
            const colores = {
                VERDE: 'bg-green-100 text-green-700',
                AMBAR: 'bg-amber-100 text-amber-700',
                ROJO: 'bg-red-100 text-red-700',
                TC: 'bg-blue-100 text-blue-700',
                ANULAR: 'bg-red-100 text-red-700',
                'SIN RED': 'bg-gray-100 text-gray-600',
            };
            return colores[this.ci.indicadores?.semaforo] || 'bg-gray-100 text-gray-600';
        },
    }));
});
