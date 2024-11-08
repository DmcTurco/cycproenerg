function initMultipleSelection(tecnicoId) {
    console.log('Multiple Selection initialized with tecnicoId:', tecnicoId);
    const selectAllCheckbox = document.getElementById('selectAll');
    const solicitudCheckboxes = document.querySelectorAll('.solicitud-checkbox');
    const asignarMultipleBtn = document.getElementById('asignarMultiple');

    // Manejar clics en las filas
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.type === 'checkbox' || e.target.tagName === 'A' ||
                e.target.closest('a') || e.target.closest('.actions')) {
                return;
            }

            const checkbox = this.querySelector('.solicitud-checkbox');
            if (checkbox && !checkbox.disabled) {
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    });

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.solicitud-checkbox:not(:disabled)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                const row = checkbox.closest('tr');
                if (row) {
                    row.classList.toggle('selected-row', this.checked);
                    row.setAttribute('draggable', 'true');
                }
            });
            updateAsignarButton();
        });
    }

    solicitudCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRowState(this);
            updateSelectAllCheckbox();
            updateAsignarButton();
        });
    });

    function updateRowState(checkbox) {
        const row = checkbox.closest('tr');
        if (row) {
            row.classList.toggle('selected-row', checkbox.checked);
            row.setAttribute('draggable', 'true');
        }
    }

    function updateSelectAllCheckbox() {
        if (!selectAllCheckbox) return;
        const totalCheckboxes = document.querySelectorAll('.solicitud-checkbox:not(:disabled)').length;
        const checkedCheckboxes = document.querySelectorAll('.solicitud-checkbox:checked').length;

        if (checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes) {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        } else {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0;
        }
    }

    function updateAsignarButton() {
        const checkedCheckboxes = document.querySelectorAll('.solicitud-checkbox:checked').length;
        asignarMultipleBtn.disabled = checkedCheckboxes === 0;
    }
}