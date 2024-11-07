<div class="table-responsive">
    <table class="table  mb-0">
        <thead>
            <tr>
                <th style="width: 15%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    N. solicitud
                </th>
                <th style="width: 35%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Nombre
                </th>
                <th style="width: 20%" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Dep-Prov-Dist
                </th>
                <th style="width: 15%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Proyecto
                </th>
                <th style="width: 15%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Estado
                </th>
            </tr>
        </thead>
        <tbody id="solicitudes-asignadas">
            @if (count($solicitudesAsignadas ?? []) > 0)
                @foreach ($solicitudesAsignadas as $solicitud)
                    <tr>
                        <td style="width: 20%" class="align-middle text-center">
                            <span class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                        </td>
                        <td style="width: 30%" class="align-middle text-center">
                            <span class="text-xs font-weight-bold">{{ $solicitud->solicitante_nombre }}</span>
                        </td>
                        <td style="width: 35%" class="align-middle text-info">
                            <p class="text-xs font-weight-bold mb-0">
                                {{ $solicitud->departamento }}-{{ $solicitud->provincia }}</p>
                            <p class="text-xs text-secondary mb-0">{{ $solicitud->distrito }}</p>
                        </td>
                        <td style="width: 30%" class="align-middle text-center">
                            <span class="text-xs font-weight-bold">{{ $solicitud->categoria_proyecto }}</span>
                        </td>
                        <td style="width: 15%" class="align-middle text-center text-sm">
                            <button class="badge badge-sm {{ $solicitud->estado_badge }} p-2 delete-solicitud"
                                data-id="{{ $solicitud->id }}" data-numero = "{{ $solicitud->numero_solicitud }}">
                                {{ $solicitud->estado_nombre }}-{{ $solicitud->abreviatura }}
                            </button>
                        </td>

                    </tr>
                @endforeach
            @else
                <tr class="empty-row">
                    <td colspan="5" class="text-center py-5" style="height: 500px;">
                        Arrastra aquí las solicitudes para asignarlas
                    </td>
                </tr>
            @endif
        </tbody>

    </table>
    <div class="d-flex justify-content-center mt-3">
        {{ $solicitudesAsignadas->links('pagination::bootstrap-4') }}
    </div>
</div>
