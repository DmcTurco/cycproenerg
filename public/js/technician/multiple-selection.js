function initMultipleSelection(tecnicoId) {
    console.log('Multiple Selection initialized with tecnicoId:', tecnicoId);
    const selectAllCheckbox = document.getElementById('selectAll');
    const solicitudCheckboxes = document.querySelectorAll('.solicitud-checkbox');
    const asignarMultipleBtn = document.getElementById('asignarMultiple');

    const selectAllAsignadasCheckbox = document.getElementById('selectAllAsignadas');
    const solicitudAsignadasCheckboxes = document.querySelectorAll('.solicitud-asignada-checkbox');
    const eliminarMultipleBtn = document.getElementById('eliminarMultiple');

    // Manejar clics en las filas de ambas tablas
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function (e) {
            // Si el clic fue en un elemento que no debe disparar la selección
            if (e.target.type === 'checkbox' || e.target.tagName === 'A' ||
                e.target.closest('a') || e.target.closest('.actions') ||
                e.target.closest('button')) {
                return;
            }

            // Buscar checkbox en la fila (tanto para disponibles como asignadas)
            const checkbox = this.querySelector('.solicitud-checkbox') ||
                this.querySelector('.solicitud-asignada-checkbox');

            if (checkbox && !checkbox.disabled) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });


    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.solicitud-checkbox:not(:disabled)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                updateRowState(checkbox);
            });
            updateAsignarButton();
        });
    }

    // Manejador para "Seleccionar todos" en tabla de asignadas
    if (selectAllAsignadasCheckbox) {
        selectAllAsignadasCheckbox.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.solicitud-asignada-checkbox:not(:disabled)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                updateRowState(checkbox);
            });
            updateEliminarButton();
        });
    }


    // Event listeners para checkboxes individuales en tabla de disponibles
    solicitudCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateRowState(this);
            updateSelectAllState(selectAllCheckbox, '.solicitud-checkbox');
            updateAsignarButton();
        });
    })
    // Event listeners para checkboxes individuales en tabla de asignadas
    solicitudAsignadasCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            updateRowState(this);
            updateSelectAllState(selectAllAsignadasCheckbox, '.solicitud-asignada-checkbox');
            updateEliminarButton();
        });
    });


    // Funciones de actualización de estado
    function updateRowState(checkbox) {
        const row = checkbox.closest('tr');
        if (row) {
            row.classList.toggle('selected-row', checkbox.checked);
            row.setAttribute('draggable', 'true');
        }
    }


    function updateSelectAllState(selectAllCheckbox, checkboxClass) {
        if (!selectAllCheckbox) return;
        const totalCheckboxes = document.querySelectorAll(`${checkboxClass}:not(:disabled)`).length;
        const checkedCheckboxes = document.querySelectorAll(`${checkboxClass}:checked`).length;

        if (checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes) {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        } else {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0;
        }
    }

    function updateAsignarButton() {
        if (!asignarMultipleBtn) return;
        const checkedCheckboxes = document.querySelectorAll('.solicitud-checkbox:checked').length;
        asignarMultipleBtn.disabled = checkedCheckboxes === 0;
    }

    function updateEliminarButton() {
        if (!eliminarMultipleBtn) return;
        const checkedCheckboxes = document.querySelectorAll('.solicitud-asignada-checkbox:checked').length;
        eliminarMultipleBtn.disabled = checkedCheckboxes === 0;
    }
}