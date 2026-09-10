@extends('employee.layouts.user_type.auth')

@section('content')
    <div
        x-data="solicitudDetail({ detailUrl: '{{ route('employee.getFullSolicitudDetails', $id) }}' })"
        x-init="load()"
    >
        <div class="mb-4 flex items-center justify-between">
            <a href="{{ route('employee.client.index') }}" class="btn-secondary">Atrás</a>
            <h2 class="text-lg font-semibold text-gray-900" x-show="!loading" x-cloak>
                Solicitud <span x-text="field('numero_solicitud')"></span>
            </h2>
        </div>

        <div x-show="loading" class="card text-center text-sm text-gray-500">Cargando...</div>

        <div x-show="!loading" x-cloak class="card">
            <div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200">
                <button type="button" @click="tab('solicitud')" class="border-b-2 px-3 py-2 text-sm font-medium"
                    :class="activeTab === 'solicitud' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'">Solicitud</button>
                <button type="button" @click="tab('solicitante')" class="border-b-2 px-3 py-2 text-sm font-medium"
                    :class="activeTab === 'solicitante' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'">Solicitante</button>
                <button type="button" @click="tab('ubicacion')" class="border-b-2 px-3 py-2 text-sm font-medium"
                    :class="activeTab === 'ubicacion' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'">Ubicación</button>
                <button type="button" @click="tab('instalacion')" class="border-b-2 px-3 py-2 text-sm font-medium"
                    :class="activeTab === 'instalacion' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'">Instalación</button>
                <button type="button" @click="tab('proyecto')" class="border-b-2 px-3 py-2 text-sm font-medium"
                    :class="activeTab === 'proyecto' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'">Proyecto</button>
                <button type="button" @click="tab('asesor')" class="border-b-2 px-3 py-2 text-sm font-medium"
                    :class="activeTab === 'asesor' ? 'border-brand-600 text-brand-600' : 'border-transparent text-gray-500 hover:text-gray-700'">Asesor</button>
            </div>

            <div x-show="activeTab === 'solicitud'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-detail-field label="Número de Solicitud" field="numero_solicitud" />
                    <x-detail-field label="Número de Suministro" field="numero_suministro" />
                    <x-detail-field label="Número de Contrato" field="numero_contrato_suministro" />
                    <x-detail-field label="Estado" field="estado_nombre" />
                    <x-detail-field label="Fecha Aprobación" field="fecha_aprobacion_contrato" />
                    <x-detail-field label="Fecha Registro" field="fecha_registro_portal" />
                </div>
            </div>

            <div x-show="activeTab === 'solicitante'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-detail-field label="Nombre" field="solicitante_nombre" />
                    <x-detail-field label="Tipo Documento" field="solicitante_tipo_documento_nombre" />
                    <x-detail-field label="Número Documento" field="solicitante_numero_documento" />
                    <x-detail-field label="Email" field="solicitante_email" />
                    <x-detail-field label="Celular" field="solicitante_celular" />
                    <x-detail-field label="Usuario FISE" field="solicitante_usuario_fise" />
                </div>
            </div>

            <div x-show="activeTab === 'ubicacion'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-detail-field label="Dirección" field="direccion" />
                    <x-detail-field label="Ubicación" field="ubicacion" />
                    <x-detail-field label="Departamento" field="departamento" />
                    <x-detail-field label="Provincia" field="provincia" />
                    <x-detail-field label="Distrito" field="distrito" />
                    <x-detail-field label="Código Mz" field="codigo_manzana" />
                    <x-detail-field label="Nombre Malla" field="nombre_malla" />
                    <x-detail-field label="Zona no gasificada" field="venta_zona_no_gasificada" />
                </div>
            </div>

            <div x-show="activeTab === 'instalacion'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-detail-field label="Tipo Instalación" field="tipo_instalacion" />
                    <x-detail-field label="Tipo Acometida" field="tipo_acometida" />
                    <x-detail-field label="Número de Puntos" field="numero_puntos_instalacion" />
                    <x-detail-field label="Fin. Instalación Interna" field="fecha_finalizacion_instalacion_interna" />
                    <x-detail-field label="Fin. Instalación Acometida" field="fecha_finalizacion_instalacion_acometida" />
                    <x-detail-field label="Resultado Instalación TC" field="resultado_instalacion_tc" />
                    <x-detail-field label="Programación Habilitación" field="fecha_programacion_habilitacion" />
                </div>
            </div>

            <div x-show="activeTab === 'proyecto'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-detail-field label="Tipo Proyecto" field="tipo_proyecto" />
                    <x-detail-field label="Código Proyecto" field="codigo_proyecto" />
                    <x-detail-field label="Categoría" field="categoria_proyecto" />
                    <x-detail-field label="Sub Categoría" field="sub_categoria_proyecto" />
                    <x-detail-field label="Código Objeto Conexión" field="codigo_objeto_conexion" />
                </div>
            </div>

            <div x-show="activeTab === 'asesor'">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-detail-field label="Nombre" field="asesor_nombre" />
                    <x-detail-field label="Tipo Documento" field="asesor_tipo_documento" />
                    <x-detail-field label="Número Documento" field="asesor_numero_documento" />
                    <x-detail-field label="Teléfono" field="asesor_telefono" />
                    <x-detail-field label="Email" field="asesor_email" />
                    <x-detail-field label="Dirección" field="asesor_direccion" />
                </div>
            </div>
        </div>
    </div>
@endsection
