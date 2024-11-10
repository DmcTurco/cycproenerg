function initDragAndDrop(tecnicoId) {
    console.log('Drag and Drop initialized with tecnicoId:', tecnicoId);
    
    const solicitudesPendientes = document.querySelectorAll('.draggable');
    const dropZonas = document.querySelectorAll('.drop-zone');

    // Definir todas las funciones de manejo antes de usarlas
    function handleDragStart(e) {
        const selectedRows = document.querySelectorAll('.solicitud-checkbox:checked');
        
        if (selectedRows.length > 0) {
            const selectedIds = Array.from(selectedRows).map(checkbox => checkbox.dataset.id);
            const selectedData = Array.from(selectedRows).map(checkbox => 
                JSON.parse(checkbox.dataset.solicitud));

            e.dataTransfer.setData('text/plain', JSON.stringify(selectedIds));
            e.dataTransfer.setData('application/json', JSON.stringify(selectedData));

            selectedRows.forEach(checkbox => {
                checkbox.closest('tr').classList.add('dragging');
            });
        } else {
            e.target.classList.add('dragging');
            const solicitudId = e.target.dataset.id;
            const solicitudData = e.target.dataset.solicitud;

            e.dataTransfer.setData('text/plain', JSON.stringify([solicitudId]));
            e.dataTransfer.setData('application/json', JSON.stringify([JSON.parse(solicitudData)]));
        }
    }

    function handleDragEnd(e) {
        document.querySelectorAll('.dragging').forEach(row => {
            row.classList.remove('dragging');
        });
    }

    function handleDragOver(e) {
        e.preventDefault();
    }

    function handleDragEnter(e) {
        e.preventDefault();
        e.target.closest('.drop-zone')?.classList.add('drag-over');
    }

    function handleDragLeave(e) {
        e.target.closest('.drop-zone')?.classList.remove('drag-over');
    }

    function handleDrop(e) {
        e.preventDefault();
        const dropZone = e.target.closest('.drop-zone');
        if (dropZone) {
            dropZone.classList.remove('drag-over');

            const solicitudIds = JSON.parse(e.dataTransfer.getData('text/plain'));
            const solicitudesData = JSON.parse(e.dataTransfer.getData('application/json'));

            if (dropZone.id === 'solicitudes-asignadas') {
                asignarSolicitudes(solicitudIds, solicitudesData);
            }
        }
    }

    function asignarSolicitudes(solicitudIds, solicitudesData) {
        const mensaje = solicitudIds.length > 1 
            ? `¿Deseas asignar las ${solicitudIds.length} solicitudes seleccionadas?`
            : `¿Deseas asignar la solicitud ${solicitudesData[0].numero_solicitud}?`;

        Swal.fire({
            title: '¿Deseas asignar solicitudes?',
            text: mensaje,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, asignar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Asignando solicitudes...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `/employee/technicals/${tecnicoId}/requests`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    data: {
                        solicitudes: solicitudIds
                    },
                    success: function(response) {
                        Swal.close();
                        Swal.fire({
                            title: '¡Asignado!',
                            text: solicitudIds.length > 1 
                                ? 'Las solicitudes han sido asignadas exitosamente.'
                                : 'La solicitud ha sido asignada exitosamente.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(error) {
                        Swal.close();
                        Swal.fire(
                            'Error',
                            'No se pudieron asignar las solicitudes.',
                            'error'
                        );
                    }
                });
            }
        });
    }

    // Agregar los event listeners
    solicitudesPendientes.forEach(item => {
        item.addEventListener('dragstart', handleDragStart);
        item.addEventListener('dragend', handleDragEnd);
    });

    dropZonas.forEach(zona => {
        zona.addEventListener('dragover', handleDragOver);
        zona.addEventListener('dragenter', handleDragEnter);
        zona.addEventListener('dragleave', handleDragLeave);
        zona.addEventListener('drop', handleDrop);
    });
}