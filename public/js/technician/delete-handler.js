function initDeleteHandler(tecnicoId) {
    // Inicializar elementos UI
    const eliminarMultipleBtn = document.getElementById('eliminarMultiple');
    const selectAllAsignadas = document.getElementById('selectAllAsignadas');

    // Manejador para eliminación individual (tu código existente)
    $('.delete-solicitud').on('click', function() {
        const solicitudId = $(this).data('id');
        const numeroSolicitud = $(this).data('numero');
        const row = $(this).closest('tr');

        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar la solicitud ${numeroSolicitud}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
        }).then((result) => {
            if (result.isConfirmed) {
                handleDelete(solicitudId, row, tecnicoId);
            }
        });
    });

    // Manejar selección de todas las solicitudes
    if (selectAllAsignadas) {
        selectAllAsignadas.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.solicitud-asignada-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                checkbox.closest('tr').classList.toggle('selected-row', this.checked);
            });
            updateEliminarButton();
        });
    }

    // Manejar checkboxes individuales
    document.querySelectorAll('.solicitud-asignada-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            this.closest('tr').classList.toggle('selected-row', this.checked);
            updateSelectAllState();
            updateEliminarButton();
        });
    });

    // Manejar eliminación múltiple
    if (eliminarMultipleBtn) {
        eliminarMultipleBtn.addEventListener('click', function() {
            const selectedSolicitudes = Array.from(document.querySelectorAll('.solicitud-asignada-checkbox:checked'))
                .map(checkbox => ({
                    id: checkbox.dataset.id,
                    numero: checkbox.dataset.numero
                }));

            if (selectedSolicitudes.length === 0) return;

            Swal.fire({
                title: '¿Estás seguro?',
                text: `¿Deseas eliminar ${selectedSolicitudes.length} solicitudes seleccionadas?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
            }).then((result) => {
                if (result.isConfirmed) {
                    handleMultipleDelete(selectedSolicitudes, tecnicoId);
                }
            });
        });
    }
}

// Tu función handleDelete existente
function handleDelete(solicitudId, row, tecnicoId) {
    row.addClass('fade-out-row');
    row.animate({
        opacity: 0,
        right: '-100%'
    }, 500, function() {
        $.ajax({
            url: `/company/technicals/${tecnicoId}/requests/${solicitudId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            success: function(response) {
                row.slideUp(300, function() {
                    row.remove();
                });

                Swal.fire({
                    title: '¡Eliminado!',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            },
            error: function() {
                row.removeClass('fade-out-row');
                row.css({
                    opacity: 1,
                    right: 0
                });

                Swal.fire(
                    'Error',
                    'No se pudo eliminar la solicitud.',
                    'error'
                );
            }
        });
    });
}

// Nueva función para eliminación múltiple
function handleMultipleDelete(solicitudes, tecnicoId) {
    // Añadir efecto visual a todas las filas seleccionadas
    const selectedRows = solicitudes.map(s => 
        document.querySelector(`.solicitud-asignada-checkbox[data-id="${s.id}"]`).closest('tr')
    );

    selectedRows.forEach(row => {
        $(row).addClass('fade-out-row').animate({
            opacity: 0,
            right: '-100%'
        }, 500);
    });

    // Realizar la petición de eliminación múltiple
    $.ajax({
        url: `/company/technicals/${tecnicoId}/requests/bulk-delete`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        data: {
            solicitudes: solicitudes.map(s => s.id)
        },
        success: function(response) {
            selectedRows.forEach(row => {
                $(row).slideUp(300, function() {
                    $(row).remove();
                });
            });

            Swal.fire({
                title: '¡Eliminado!',
                text: response.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        },
        error: function() {
            selectedRows.forEach(row => {
                $(row).removeClass('fade-out-row').css({
                    opacity: 1,
                    right: 0
                });
            });

            Swal.fire(
                'Error',
                'No se pudieron eliminar las solicitudes.',
                'error'
            );
        }
    });
}

// Funciones auxiliares para el estado de los checkboxes
function updateEliminarButton() {
    const checkedCheckboxes = document.querySelectorAll('.solicitud-asignada-checkbox:checked').length;
    const eliminarMultipleBtn = document.getElementById('eliminarMultiple');
    if (eliminarMultipleBtn) {
        eliminarMultipleBtn.disabled = checkedCheckboxes === 0;
    }
}

function updateSelectAllState() {
    const selectAllAsignadas = document.getElementById('selectAllAsignadas');
    if (!selectAllAsignadas) return;
    
    const totalCheckboxes = document.querySelectorAll('.solicitud-asignada-checkbox').length;
    const checkedCheckboxes = document.querySelectorAll('.solicitud-asignada-checkbox:checked').length;

    if (checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes) {
        selectAllAsignadas.indeterminate = true;
        selectAllAsignadas.checked = false;
    } else {
        selectAllAsignadas.indeterminate = false;
        selectAllAsignadas.checked = checkedCheckboxes === totalCheckboxes;
    }
}