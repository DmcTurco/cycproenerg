@if($busqueda->count())
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
                @foreach($busqueda as $registro)
                    <tr>
                        <td style="color:rgb(36, 150, 36);cursor:pointer" class="añadir-solicitud">
                            <i class="fas fa-arrow-left"></i>
                        </td>
                        <td>{{ $registro->solicitudes->first()->numero_solicitud }}</td>
                        <td>{{ $registro->numero_documento_identificacion }}</td>
                        <td>{{ $registro->nombre }}</td>
                        <td>{{ $registro->ubicaciones->first()->direccion }}</td>

                        @php
                            $numeroSolicitud = $registro->solicitudes->first()->numero_solicitud;
                            $numDocIdentificacion = $registro->numero_documento_identificacion;
                            $nombre =  $registro->nombre;
                            $direccion =  $registro->ubicaciones->first()->direccion;
                        @endphp
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="pagination">
        {{ $busqueda->links() }}
    </div>

    <script>
        var dnumeroSolicitud = $numeroSolicitud;
         $('#tabla-respuestas').on('click', '.añadir-solicitud', function() {
          
          console.log('okas-okas');
          
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
                  let url = 'company/technicals/$tecnicoId/requests';
                console.log(url);
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        numeroSolicitud: dnumeroSolicitud,
                    },
                    success: 
                        console.log('exito')

                });
              }
          });
      })
    </script>
@else
    <p>No se encontraron registros.</p>
@endif

