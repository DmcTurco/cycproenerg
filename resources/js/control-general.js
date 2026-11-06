/**
 * Control General (Internas · TC · Habilitación): edición en la misma tabla,
 * como en el Excel. Cada celda se guarda sola al cambiarla; si la marca mueve
 * la fila de etapa (Construida = Sí, TC Concluida, Habilitado = Sí,
 * Anular/Reingresar), la fila desaparece de la pestaña y se avisa a dónde fue.
 */
document.addEventListener('alpine:init', () => {
    const aviso = (titulo, icono = 'success') => {
        if (!window.Swal) return;
        window.Swal.fire({ toast: true, position: 'top-end', icon: icono, title: titulo, timer: 2500, showConfirmButton: false });
    };

    Alpine.data('cgFila', (cfg) => ({
        f: { ...cfg.valores },
        situacion: cfg.situacion,
        estado: '',
        movida: false,

        // Modal de Asesor / Cuadrilla / Observaciones: se editan juntos y se
        // guardan de una sola vez (ver guardarModal). Qué campos trae cada
        // fila lo decide la vista según la etapa (camposModal).
        modal: { abierto: false, asesor: '', cuadrilla: '', observacion: '' },

        abrirModal() {
            this.modal.asesor = this.f.asesor || '';
            this.modal.cuadrilla = this.f.cuadrilla || '';
            this.modal.observacion = this.f.observacion || '';
            this.modal.abierto = true;
        },

        cerrarModal() {
            this.modal.abierto = false;
        },

        async guardarModal(campos) {
            const cambios = {};
            for (const campo of campos) {
                const nuevo = (this.modal[campo] ?? '').toString().trim();
                const anterior = (this.f[campo] ?? '').toString().trim();
                if (nuevo !== anterior) cambios[campo] = nuevo;
            }
            if (!Object.keys(cambios).length) {
                this.modal.abierto = false;
                return;
            }

            this.estado = 'guardando';
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const resp = await fetch(cfg.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ campos: cambios }),
                });
                const data = await resp.json().catch(() => ({}));
                if (!resp.ok) throw new Error(data.message || 'No se pudo guardar el cambio.');

                Object.assign(this.f, cambios);
                this.situacion = data.situacion ?? this.situacion;
                if (data.fecha_observacion !== undefined) this.f.fecha_observacion = data.fecha_observacion;

                this.modal.abierto = false;
                aviso(data.mensaje || 'Guardado');
                this.estado = 'ok';
                setTimeout(() => { if (this.estado === 'ok') this.estado = ''; }, 1500);
            } catch (e) {
                this.estado = 'error';
                aviso(e.message, 'error');
            }
        },

        async cambiar(campo, evento, confirmar = null) {
            const input = evento.target;
            const valor = input.value;
            const anterior = this.f[campo] ?? '';

            if ((valor ?? '') === (anterior ?? '')) return;

            if (confirmar && confirmar.valores.includes(valor) && window.Swal) {
                const r = await window.Swal.fire({
                    title: '¿Confirmas el cambio?',
                    text: confirmar.mensaje,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar',
                });
                if (!r.isConfirmed) {
                    input.value = anterior;
                    return;
                }
            }

            this.f[campo] = valor;
            const ok = await this.guardar(campo, valor);
            if (!ok) {
                this.f[campo] = anterior;
                input.value = anterior;
            }
        },

        async guardar(campo, valor) {
            this.estado = 'guardando';
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            try {
                const resp = await fetch(cfg.url, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ campo, valor: valor === '' ? null : valor }),
                });
                const data = await resp.json().catch(() => ({}));

                if (!resp.ok) {
                    throw new Error(data.message || 'No se pudo guardar el cambio.');
                }

                this.situacion = data.situacion ?? this.situacion;
                if (data.fecha_observacion !== undefined) this.f.fecha_observacion = data.fecha_observacion;

                if (data.movida) {
                    aviso(data.mensaje);
                    window.dispatchEvent(new CustomEvent('cg-movida', { detail: { de: cfg.etapa, a: data.etapa } }));
                    this.movida = true;
                } else {
                    this.estado = 'ok';
                    setTimeout(() => { if (this.estado === 'ok') this.estado = ''; }, 1500);
                    return true;
                }

                this.estado = '';
                return true;
            } catch (e) {
                this.estado = 'error';
                aviso(e.message, 'error');
                return false;
            }
        },

        formatoFecha(iso) {
            if (!iso) return '—';
            const [a, m, d] = iso.split('-');
            return `${d}/${m}/${a}`;
        },

        colorSituacion(s) {
            return {
                'Por programar': 'bg-gray-100 text-gray-700',
                Programado: 'bg-sky-100 text-sky-700',
                Observado: 'bg-amber-100 text-amber-700',
                Habilitado: 'bg-green-100 text-green-700',
            }[s] || 'bg-gray-100 text-gray-700';
        },
    }));
});
