document.addEventListener('alpine:init', () => {
    Alpine.data('solicitudDetail', (config) => ({
        loading: true,
        activeTab: 'solicitud',
        data: {},

        async load() {
            this.loading = true;
            try {
                const response = await fetch(config.detailUrl, {
                    headers: { Accept: 'application/json' },
                });
                const json = await response.json();

                if (json.success) {
                    this.data = json.data;
                } else {
                    window.Swal?.fire({ icon: 'error', title: json.message || 'No se pudo cargar la solicitud' });
                }
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'Error al cargar los datos' });
            } finally {
                this.loading = false;
            }
        },

        field(key) {
            return this.data[key] || 'No especificado';
        },

        tab(key) {
            this.activeTab = key;
        },
    }));
});
