document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');

    // Obtener el tecnicoId de la variable global
    const tecnicoId = window.tecnicoId;
    console.log('Tecnico ID:', tecnicoId);

    try {
        console.log('Initializing Multiple Selection...');
        initMultipleSelection(tecnicoId);

        console.log('Initializing Drag and Drop...');
        initDragAndDrop(tecnicoId);

        console.log('Initializing Map Handler...');
        initMapHandler();

        console.log('Initializing Delete Handler...');
        initDeleteHandler(tecnicoId);

        console.log('All modules initialized successfully');
    } catch (error) {
        console.error('Error initializing modules:', error);
    }
});