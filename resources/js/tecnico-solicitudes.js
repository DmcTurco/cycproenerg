document.addEventListener('alpine:init', () => {
    Alpine.data('tecnicoSolicitudes', (config) => ({
        selectedAvailable: [],
        selectedAssigned: [],
        busyAssignAll: false,
        busyDeleteAll: false,
        busyRowId: null,
        ubicacion: {
            open: false,
            hasCoords: false,
            embedUrl: '',
            departamento: '',
            provincia: '',
            distrito: '',
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content;
        },

        toggleAll(list, ids) {
            if (list.length === ids.length) {
                return [];
            }
            return [...ids];
        },

        async handleResponse(response) {
            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                window.Swal?.fire({ icon: 'error', title: data.message || 'Ocurrió un error' });
                return false;
            }

            window.location.reload();
            return true;
        },

        async assignOne(id) {
            this.busyRowId = id;
            try {
                const response = await fetch(config.assignUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ solicitud_id: id }),
                });
                const ok = await this.handleResponse(response);
                if (!ok) this.busyRowId = null;
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'No se pudo asignar la solicitud' });
                this.busyRowId = null;
            }
        },

        async assignSelected() {
            if (this.selectedAvailable.length === 0) return;

            const result = await window.Swal.fire({
                title: `¿Asignar ${this.selectedAvailable.length} solicitud(es) al técnico?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, asignar',
                cancelButtonText: 'Cancelar',
            });
            if (!result.isConfirmed) return;

            this.busyAssignAll = true;
            try {
                const response = await fetch(config.assignUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ solicitudes: this.selectedAvailable }),
                });
                const ok = await this.handleResponse(response);
                if (!ok) this.busyAssignAll = false;
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'No se pudieron asignar las solicitudes' });
                this.busyAssignAll = false;
            }
        },

        async deleteOne(id) {
            const result = await window.Swal.fire({
                title: '¿Quitar esta solicitud del técnico?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
            });
            if (!result.isConfirmed) return;

            this.busyRowId = id;
            try {
                const response = await fetch(`${config.destroyUrlBase}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), Accept: 'application/json' },
                });
                const ok = await this.handleResponse(response);
                if (!ok) this.busyRowId = null;
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'No se pudo quitar la solicitud' });
                this.busyRowId = null;
            }
        },

        async deleteSelected() {
            if (this.selectedAssigned.length === 0) return;

            const result = await window.Swal.fire({
                title: `¿Quitar ${this.selectedAssigned.length} solicitud(es) seleccionada(s)?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc2626',
            });
            if (!result.isConfirmed) return;

            this.busyDeleteAll = true;
            try {
                const response = await fetch(config.bulkDeleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ solicitudes: this.selectedAssigned }),
                });
                const ok = await this.handleResponse(response);
                if (!ok) this.busyDeleteAll = false;
            } catch (e) {
                window.Swal?.fire({ icon: 'error', title: 'No se pudieron quitar las solicitudes' });
                this.busyDeleteAll = false;
            }
        },

        openUbicacion(ubicacionString, departamento, provincia, distrito) {
            this.ubicacion.departamento = departamento || 'No especificado';
            this.ubicacion.provincia = provincia || 'No especificado';
            this.ubicacion.distrito = distrito || 'No especificado';

            const parts = (ubicacionString || '').split(',').map((part) => Number(part.trim()));
            const [lat, lng] = parts;

            if (parts.length === 2 && !Number.isNaN(lat) && !Number.isNaN(lng)) {
                this.ubicacion.hasCoords = true;
                this.ubicacion.embedUrl = `https://www.google.com/maps/embed/v1/place?key=${config.mapsKey}&q=${lat},${lng}`;
            } else {
                this.ubicacion.hasCoords = false;
                this.ubicacion.embedUrl = '';
            }

            this.ubicacion.open = true;
        },

        closeUbicacion() {
            this.ubicacion.open = false;
        },
    }));
});
