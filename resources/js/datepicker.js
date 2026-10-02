import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Calendario del componente <x-date-input> (resources/views/components/
 * date-input.blade.php). Se aplica a los inputs con data-datepicker:
 * se muestra dd/mm/aaaa en español, pero el input original sigue
 * guardando aaaa-mm-dd (lo que esperan los controladores y x-model).
 */
flatpickr.localize(Spanish);

const nativeValue = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

function enhance(input) {
    if (input._flatpickr) return;

    const fp = flatpickr(input, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: true,
        disableMobile: true,
        monthSelectorType: 'static',
        minDate: input.min || null,
        maxDate: input.max || null,
        onReady(_, __, instance) {
            // El campo visible hereda el estilo del original.
            instance.altInput.className = input.className;
            instance.altInput.placeholder = input.placeholder || 'dd/mm/aaaa';
        },
        onChange() {
            // Alpine (x-model) escucha "input": se avisa al cambiar la fecha.
            input.dispatchEvent(new Event('input', { bubbles: true }));
        },
    });

    // Si Alpine u otro script cambia el valor por código (p. ej. al abrir un
    // modal de edición), el calendario visible se actualiza solo. `syncing`
    // evita el bucle: flatpickr también escribe input.value al actualizarse.
    let syncing = false;
    Object.defineProperty(input, 'value', {
        configurable: true,
        get() {
            return nativeValue.get.call(this);
        },
        set(v) {
            nativeValue.set.call(this, v);
            if (syncing) return;
            const actual = fp.selectedDates[0] ? fp.formatDate(fp.selectedDates[0], 'Y-m-d') : '';
            if (actual === (v || '')) return;
            syncing = true;
            try {
                fp.setDate(v || null, false);
            } finally {
                syncing = false;
            }
        },
    });
}

function enhanceAll(root = document) {
    root.querySelectorAll?.('input[data-datepicker]').forEach(enhance);
}

document.addEventListener('DOMContentLoaded', () => {
    enhanceAll();

    // Inputs que aparecen después (modales con x-if, filas nuevas, etc.).
    new MutationObserver((mutations) => {
        for (const m of mutations) {
            m.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                if (node.matches?.('input[data-datepicker]')) enhance(node);
                else enhanceAll(node);
            });
        }
    }).observe(document.body, { childList: true, subtree: true });
});
