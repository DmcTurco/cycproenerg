<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th class="text-center" style="width: 10%">
                    <input type="checkbox" id="selectAll" class="form-check-input">
                </th>
                <th style="width: 12%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    N. solicitud
                </th>
                <th style="width: 33%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Nombre
                </th>
                <th style="width: 15%" class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Dep-Prov-Dist
                </th>
                <th style="width: 13%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Proyecto
                </th>
                <th style="width: 15%"
                    class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                    Estado
                </th>
            </tr>
        </thead>
        <tbody id="solicitudes-pendientes" class="drop-zone">
            @if (count($solicitudesDisponibles ?? []) > 0)
                @foreach ($solicitudesDisponibles as $solicitud)
                    <tr class="draggable" draggable="true" data-id="{{ $solicitud->id }}"
                        data-solicitud="{{ json_encode($solicitud) }}">
                        <td class="text-center">
                            <input type="checkbox" class="form-check-input solicitud-checkbox"
                                data-id="{{ $solicitud->id }}" data-solicitud="{{ json_encode($solicitud) }}">
                        </td>
                        <td style="width: 20%" class="align-middle text-center">
                            <span class="text-xs font-weight-bold">{{ $solicitud->numero_solicitud }}</span>
                        </td>
                        <td style="width: 30%" class="align-middle text-center">
                            <span class="text-xs font-weight-bold">{{ $solicitud->solicitante_nombre }}</span>
                        </td>
                        <td style="width: 35%" class="align-middle text-info">
                            <a href="#" class="ver-ubicacion" data-departamento="{{ $solicitud->departamento }}"
                                data-provincia="{{ $solicitud->provincia }}" data-distrito="{{ $solicitud->distrito }}"
                                data-ubicacion="{{ $solicitud->ubicacion }}">
                                <p class="text-xs font-weight-bold mb-0 text-info">
                                    {{ $solicitud->departamento }}-{{ $solicitud->provincia }}
                                </p>
                                <p class="text-xs text-secondary mb-0">
                                    {{ $solicitud->distrito }}
                                </p>
                            </a>
                        </td>
                        <td class="align-middle text-center">
                            <span class="text-xs font-weight-bold">{{ $solicitud->categoria_proyecto }}</span>
                        </td>
                        <td class="align-middle text-center text-sm">
                            <span class="badge badge-sm {{ $solicitud->estado_badge }} p-2">
                                {{ $solicitud->estado_nombre }}-{{ $solicitud->abreviatura }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr class="empty-row">
                    <td colspan="6" class="text-center py-5" style="height: 500px;">
                        No existen solicitudes registradas
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
    <div class="d-flex justify-content-center mt-3">
        {{ $solicitudesDisponibles->links('pagination::bootstrap-4') }}
    </div>
</div>
