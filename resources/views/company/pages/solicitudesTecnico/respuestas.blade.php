@if($busqueda->count())
    <table class="table">
        <thead>
            <tr>
                <th>N° Solicitud</th>
                <th>N° Documento</th>
                <th>Nombre</th>
                <th>Dirección</th>
            </tr>
        </thead>
        <tbody>
            @foreach($busqueda as $registro)
                <tr>
                    <td>{{ $registro->numero_solicitud }}</td>
                    <td>{{ $registro->numero_documento_identificacion }}</td>
                    <td>{{ $registro->nombre }}</td>
                    <td>{{ $registro->direccion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Paginador -->
    <div class="pagination">
        {{ $busqueda->links() }} <!-- Esto genera el paginador -->
    </div>
@else
    <p>No se encontraron registros.</p>
@endif
