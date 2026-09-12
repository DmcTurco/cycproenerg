@extends('employee.layouts.user_type.auth')

@php($fullBleed = true)

@section('content')
    <div
        x-data="solicitudDetail({ detailUrl: '{{ route('employee.getFullSolicitudDetails', $id) }}' })"
        x-init="load()"
        class="flex flex-1 flex-col overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-gray-200"
    >
        <div class="flex items-center justify-between gap-3 bg-brand-600 px-4 py-2.5 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <h2 class="text-base font-semibold text-white">Detalle de Solicitud</h2>
                <span x-show="!loading && !loadError" x-cloak
                    class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-medium text-white">
                    N° <span x-text="field('numero_solicitud')"></span>
                </span>
            </div>
            <a href="{{ route('employee.client.index') }}" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-brand-700 shadow-sm hover:bg-brand-50">Volver</a>
        </div>

        <div class="flex flex-1 flex-col p-4 sm:p-6 lg:p-8">
            <div x-show="loading" class="flex flex-1 flex-col items-center justify-center gap-3 py-16">
                <div class="h-12 w-12 animate-spin rounded-full border-4 border-brand-100 border-t-brand-600"></div>
                <p class="text-sm text-gray-500">Cargando datos de la solicitud...</p>
            </div>

            <div x-show="!loading && loadError" x-cloak class="flex flex-1 flex-col items-center justify-center gap-3 py-16 text-center">
                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">No se pudo cargar la solicitud</p>
                <p class="max-w-sm text-xs text-gray-500" x-text="errorMessage"></p>
                <button type="button" @click="load()" class="btn-secondary mt-1">Reintentar</button>
            </div>

            <div x-show="!loading && !loadError" x-cloak>
                <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">Estado</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="field('estado_nombre')"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">N° Suministro</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="field('numero_suministro')"></p>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">N° Contrato</p>
                        <p class="mt-1 text-sm font-semibold text-gray-900" x-text="field('numero_contrato_suministro')"></p>
                    </div>
                </div>

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

        <div class="border-t border-gray-100 px-4 py-3 text-center text-xs text-gray-400 sm:px-6">
            &copy; {{ date('Y') }} CYC PROENERG. Todos los derechos reservados.
        </div>
    </div>
@endsection
