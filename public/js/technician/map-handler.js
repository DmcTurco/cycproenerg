function initMapHandler() {
    $('.ver-ubicacion').on('click', function(e) {
        e.preventDefault();
        const data = $(this).data();

        $('#modal-departamento').text(data.departamento);
        $('#modal-provincia').text(data.provincia);
        $('#modal-distrito').text(data.distrito);

        const mapaContainer = document.getElementById('mapa');

        if (data.ubicacion && data.ubicacion.trim()) {
            mapaContainer.innerHTML = '';
            const [lat, lng] = data.ubicacion.split(',').map(Number);

            const map = new google.maps.Map(mapaContainer, {
                center: { lat, lng },
                zoom: 15
            });

            new google.maps.Marker({
                position: { lat, lng },
                map,
                title: `${data.distrito}, ${data.provincia}`
            });
        } else {
            mapaContainer.innerHTML = `
                <div class="no-location-container">
                    <div class="map-error-animation">
                        <i class="fas fa-map-marker-alt map-marker"></i>
                        <div class="radar-circle"></div>
                    </div>
                    <div class="error-message">
                        <h6>No hay coordenadas disponibles</h6>
                        <p>No se encontraron datos de ubicación para mostrar en el mapa</p>
                    </div>
                </div>
            `;
        }

        $('#ubicacionModal').modal('show');
    });
}