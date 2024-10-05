@if ($registros->count() > 0)
    <div class="table-responsive" id="tabla-respuestas">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-center">N° Solicitud</th>
                    <th class="text-center">N° Documento</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($registros as $registro)
                    <tr>
                        <td style="color:rgb(36, 150, 36);cursor:pointer" class="añadir-solicitud"
                            data-num-solicitud = "{{ $registro->solicitudes->first()->numero_solicitud }}"
                            data-num-doc-identificacion = "{{ $registro->numero_documento_identificacion }}"
                            data-nombre = "{{ $registro->nombre }}"
                            data-direccion = "{{ $registro->ubicaciones->first()->direccion }}"
                            data-categoria = "{{ $registro->proyectos->first()->categoria }}"
                            data-solicitante-id = "{{ $registro->id }}">
                            <i class="fas fa-arrow-left"></i>
                        </td>
                        <td>{{ $registro->solicitudes->first()->numero_solicitud }}</td>
                        <td>{{ $registro->numero_documento_identificacion }}</td>
                        <td>{{ $registro->nombre }}</td>
                        <td>{{ $registro->ubicaciones->first()->direccion }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <form action="{{ route('company.technicals.requests.store', $tecnicoID) }}" method="POST"
            id="form-agregar-solicitud">
            @csrf
        </form>
    </div>
    <div class="pagination">
        {{ $registros->links() }}
    </div>

    <script>
        $(document).ready(function() {

            $('#tabla-respuestas').on('click', '.añadir-solicitud', function() {

                let solicitanteID = $(this).data('solicitante-id');
                let categoria = $(this).data('categoria');
                let numSolicitud = $(this).data('num-solicitud');
                let numDocIdentificacion = $(this).data('num-doc-identificacion');
                let nombre = $(this).data('nombre');
                let direccion = $(this).data('direccion');
                let _token = $('#form-agregar-solicitud input[name="_token"]').val();

                let url = $('form#form-agregar-solicitud').attr('action');
                Swal.fire({
                    position: 'center',
                    icon: 'warning',
                    title: '¿Quieres añadir el registro?',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Aceptar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _token: _token,
                                solicitanteID: solicitanteID,
                                categoria: categoria,
                                numSolicitud: numSolicitud,
                                numDocIdentificacion: numDocIdentificacion,
                                nombre: nombre,
                                direccion: direccion
                            },
                            success: function(response) {
                                console.log('Registro añadido con éxito');

                            },
                        });
                    }
                });
            });
        });
    </script>
@else
    <p>No se encontraron registros.</p>
@endif
