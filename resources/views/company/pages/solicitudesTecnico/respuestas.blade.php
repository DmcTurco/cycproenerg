@if ($registros->count() > 0)
    <div class="table-responsive" id="tabla-respuestas">
        <table class="table align-items-center mb-0">
            <thead>
                <tr>
                    <th></th>
                    <th  class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">N° Solicitud</th>
                    <th  class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">N° Documento</th>
                    <th  class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Nombre</th>
                    <th  class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Dirección</th>

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
                        <td class="text-center text-uppercase text-sm font-weight-bold">{{ $registro->solicitudes->first()->numero_solicitud }}</td>
                        <td class="text-center text-uppercase text-sm font-weight-bold">{{ $registro->numero_documento_identificacion }}</td>
                        <td class="text-center text-uppercase text-sm font-weight-bold">{{ $registro->nombre }}</td>
                        <td class="text-center text-uppercase text-sm font-weight-bold">{{ $registro->ubicaciones->first()->direccion }}</td>
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
