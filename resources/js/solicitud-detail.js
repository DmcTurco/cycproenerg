document.addEventListener('alpine:init', () => {
    Alpine.data('solicitudDetail', (config) => ({
        loading: true,
        loadError: false,
        errorMessage: '',
        activeTab: 'solicitud',
        data: {},

        async load() {
            this.loading = true;
            this.loadError = false;

            try {
                const response = await fetch(config.detailUrl, { headers: { Accept: 'application/json' } });
                const json = await response.json();

                if (json.success) {
                    this.data = json.data;
                } else {
                    this.loadError = true;
                    this.errorMessage = json.message || 'No se pudo cargar la solicitud.';
                }
            } catch (e) {
                this.loadError = true;
                this.errorMessage = 'Error al cargar los datos. Verifica tu conexión.';
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
