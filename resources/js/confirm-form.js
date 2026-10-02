/**
 * Confirmación con el modal del sistema (SweetAlert) en vez del confirm()
 * nativo del navegador. Se usa en formularios normales (no Alpine):
 *
 *   <form method="POST" action="..." data-confirm="¿Eliminar este registro?">
 *
 * Opcionales: data-confirm-text (detalle), data-confirm-button (texto del
 * botón, por defecto "Sí, continuar") y data-confirm-icon (warning, question…).
 */
document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm || form.dataset.confirmed === '1') {
        return;
    }

    event.preventDefault();

    const result = await window.Swal.fire({
        title: form.dataset.confirm,
        text: form.dataset.confirmText || undefined,
        icon: form.dataset.confirmIcon || 'warning',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirmButton || 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        form.dataset.confirmed = '1';
        // requestSubmit respeta el botón que se pulsó y la validación HTML.
        form.requestSubmit ? form.requestSubmit(event.submitter || undefined) : form.submit();
    }
});
