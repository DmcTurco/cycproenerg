/**
 * Validación: la hace SIEMPRE el servidor (los controladores validan con
 * $request->validate / Validator) y los errores se muestran con el estilo
 * del sistema — formularios Alpine con `errors.campo`, formularios normales
 * con <x-form-errors /> o @error. Por eso se apaga la validación nativa del
 * navegador (los globos tipo "Completa este campo"), que además salta antes
 * de que Alpine o el modal de confirmación puedan actuar.
 *
 * Los atributos required/min/max se dejan en el HTML como pista visual y
 * para el teclado numérico; solo dejan de bloquear el envío.
 */
function desactivarValidacionNativa(root = document) {
    if (root instanceof HTMLFormElement) {
        root.noValidate = true;
    }
    root.querySelectorAll?.('form').forEach((form) => {
        form.noValidate = true;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    desactivarValidacionNativa();

    // Formularios que aparecen después (modales con x-if, etc.).
    new MutationObserver((mutations) => {
        for (const m of mutations) {
            m.addedNodes.forEach((node) => {
                if (node.nodeType === 1) desactivarValidacionNativa(node);
            });
        }
    }).observe(document.body, { childList: true, subtree: true });
});
