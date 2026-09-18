document.addEventListener('alpine:init', () => {
    // CI-3/CI-5: pantalla dedicada de Control Interno de una solicitud
    // (`employee/solicitudes/{id}/control-interno`), separada del Detalle de
    // Solicitud general (`solicitudDetail`, en solicitud-detail.js). Muestra
    // fase + indicadores calculados (CI-5) y permite editar las 3 columnas
    // manuales (CI-3): F. CONSTRUCCIÓN control, OBSERVACIÓN, ANULAR.
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
            portal_alerta: { rechazada: false, anulada: false, motivo: null },
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
                this.errorMessage = 'Error al cargar Control Interno. Verifica tu conexión.';
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
                    // toque — igual que el botón "Aplicar movimientos" del
                    // VBA, pero automático al guardar. Reflejamos fase +
                    // indicadores actualizados sin recargar toda la pantalla.
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
