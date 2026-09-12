# Módulo: Control Interno — Submódulos

> Plan de migración de `CONTROL INTERNAS - CYC CLB v5.4.xlsm` (Excel + VBA) al sistema Laravel **cycproenerg**.
> Este documento es el punto de partida: define los submódulos, qué reutilizamos del código ya existente y qué falta construir. Se implementa por partes, submódulo por submódulo, en el orden sugerido al final.

## 1. Dónde encaja "Control Interno" en el sistema

`cycproenerg` ya tiene varios módulos funcionando. Para no duplicar trabajo ni conceptos, así se ve el mapa completo hoy:

| Módulo | Estado | Piezas clave existentes |
|---|---|---|
| Carga del Excel del portal | **Existe** | `ProcessExcelJob`, `ClientController`, tabla `logs` |
| Solicitudes / Clientes | **Existe** | `Solicitud`, `Solicitante`, `Empresa`, `Concesionaria`, `Ubicacion`, `Proyecto`, `Instalacion` |
| Asignación a técnicos (cuadrilla de campo) | **Existe** | `SolicitudTecnicoController`, `ApiSolicitudTecnico`, tabla `estado_internos` (pendiente/asignado/Iniciado/finalizado/reasignado/cancelado/expirado) |
| App móvil del técnico | **Existe** | rutas `api.php`, login Sanctum, `updateEstado` |
| Asesores | **Existe** | `AsesorController`, modelo `Asesor` |
| **Control Interno** (semáforo de plazos, fases GENERAL/CONSTRUIDO/TC, IND 2 FISE) | **No existe — es lo que vamos a construir** | — |

Importante: el Excel usa la palabra "estado" para el flujo de fases (GENERAL → CONSTRUIDO → TC → PEND. ANULACIÓN), pero en Laravel `estado_internos` **ya está tomado** por el flujo de asignación a técnicos (pendiente/asignado/Iniciado/finalizado...). Son dos cosas distintas de la misma `Solicitud` y no hay que mezclarlas. En este documento la fase de Control Interno se llama **"fase de control interno"** (nombre de tabla/clase a confirmar en CI-2) para no chocar con `EstadoInterno`.

## 2. Submódulos de Control Interno

### CI-1. Parámetros de Control Interno — ✅ implementado (12/09/2026)
Equivalente a la hoja `PARAM`. Hoy vive hardcodeado en el Excel; en Laravel debería ser configurable sin tocar código (tabla, no `config/*.php`), porque son valores que el negocio ajusta (plazos, feriados, meta).

**Estado:** listo el esquema, los datos por defecto y la pantalla de administración.

- Migraciones: `create_parametros_control_internos_table`, `create_feriados_table`, `add_codigo_to_empresas_table`.
- Modelos: `ParametroControlInterno` (fila única, `ParametroControlInterno::actual()`), `Feriado` (`Feriado::fechas()`).
- `Empresa::$fillable` ahora incluye `codigo`.
- `config/const.php` ganó `control_interno.categorias` (mapeo Residencial/Multifamiliar/Comercio → RES/MULTI/COM), que sí es un dato fijo de código, a diferencia de plazos/feriados/RUC que van en base de datos.
- Seeder `ControlInternoParametrosSeeder` (registrado en `DatabaseSeeder`): carga los valores por defecto del Excel (plazo 20 días hábiles, semáforo 10/20, espera TC 15/30, meta IND2 95%, ámbito LIMA/CALLAO), los 16 feriados 2026 de `PARAM!J17:J40`, y asigna `codigo` CYC/CLB a las empresas existentes por RUC.
- **Pendiente para que corra:** ejecutar `php artisan migrate` y `php artisan db:seed --class=ControlInternoParametrosSeeder` en el entorno de Turco (no se pudo ejecutar desde esta sesión por una falla de montaje de carpeta reportada por Windows).
- Pantalla: `GET/PUT employee/control-interno/parametros` (`ParametroControlInternoController`, vista `employee.pages.control-interno.parametros`) — formulario de plazos/semáforo/meta/ámbito, más una tabla de feriados con alta/edición/baja (`FeriadoController`, reutilizando el componente `crudModal` que ya usa la pantalla de Asesores). Enlace "Control Interno" agregado al sidebar del empleado.

- **Ya existe:** `Empresa` (RUC, nombre) — pero sin el código corto CYC/CLB del portal. `Ubicacion.departamento` sirve para el filtro de ámbito (Lima/Callao). `Proyecto.categoria_proyecto` ya trae el texto del portal (Residencial/Multifamiliar/Comercio).
- **Falta:** código interno por empresa (CYC/CLB) en `Empresa`; tabla de plazos y semáforo (plazo construcción en días hábiles, verde/ámbar/rojo, espera TC verde/ámbar, meta IND 2); tabla de feriados (fecha) para el cálculo de días hábiles; lista de departamentos en ámbito.
- **Sugerido:** tabla `parametros_control_interno` (clave/valor o columnas fijas) + tabla `feriados` (una fila por fecha) + columna `codigo` en `empresas`.

### CI-2. Fase de Control Interno (máquina de estados) — ✅ implementado (12/09/2026)
Equivalente al hecho de que una solicitud "viva" en la hoja GENERAL, CONSTRUIDO, TC o PEND_ANULACION.

**Estado:** existe la tabla, el modelo y un enganche mínimo en la carga del portal (toda solicitud nueva entra en GENERAL). El motor completo que mueve de fase (CONSTRUIDO/TC/PEND_ANULACION) es CI-4, todavía pendiente.

- Migración `create_fase_control_internos_table`: tabla `fase_control_internos` (1:1 con `solicitud_id`, con `fase` y `fecha_ingreso_general`).
- Modelo `FaseControlInterno` con las constantes `GENERAL`/`CONSTRUIDO`/`TC`/`PEND_ANULACION`.
- `Solicitud::faseControlInterno()` — relación nueva, separada a propósito de `estadoSolicitud()` (asignación a técnico).
- `ProcessExcelJob::processFaseControlInterno()`: a toda solicitud sin fase todavía, la carga del portal le pone GENERAL con fecha de ingreso de hoy. No reclasifica solicitudes que ya tienen fase — eso es CI-4.
- **Pendiente para que corra:** `php artisan migrate` (mismo motivo que en CI-1: no se pudo ejecutar desde esta sesión).

### CI-3. Columnas de control manual
Las columnas amarillas del Excel: nunca se sobrescriben con la carga del portal, las llena el staff a mano.

- **Ya existe:** `Solicitud.asesor_id` (columna ya está en la tabla `solicituds`, pero la relación `asesor()` está comentada en el modelo — hay que activarla). Asignación de técnico/cuadrilla ya existe vía `solicitud_tecnico` (la misma tabla que usa el módulo de Asignación — a definir si Control Interno reusa esa relación o necesita una propia "cuadrilla técnico" independiente del técnico de campo).
- **Falta:** `F. CONSTRUCCIÓN (control)` — fecha que el staff ingresa a mano y que es distinta de `Instalacion.fecha_finalizacion_instalacion_interna` (la que trae el portal); columna `OBSERVACIÓN`; flag `ANULAR` (booleano/checkbox, equivalente a la "X").
- **Sugerido:** agregar `fecha_construccion_control`, `observacion_control`, `marcado_para_anular` en la tabla de fase (CI-2) o en `solicituds` — a decidir según cómo quede CI-2.

### CI-4. Motor de clasificación y movimientos
El corazón del VBA (`Procesar`, `AplicarMovimientos`, `CargarPortal`): decide, para cada solicitud, si se queda donde está o se mueve de fase, sin pisar nunca las columnas manuales.

- **Ya existe:** `ProcessExcelJob` ya hace la mitad del trabajo — lee el Excel del portal por nombre de columna (no por letra, más robusto que el VBA original), crea/actualiza `Solicitud`, `Solicitante`, `Empresa`, `Concesionaria`, `Ubicacion`, `Proyecto`, `Instalacion` con `updateOrCreate`/`firstOrCreateSafe`, todo en una transacción por fila. Ese diseño es exactamente el patrón que necesita el motor de clasificación.
- **Falta:** la lógica de negocio que decide la fase (equivalente a `destG`/`destC`/`destT`/`destA` del VBA): si `F. CONSTRUCCIÓN` (control) está llena → CONSTRUIDO; si el portal trae "Concluida" en TC → TC; si `ANULAR` = true o el portal reporta Rechazada/Anulada = Sí → PEND_ANULACION o eliminación. Hoy `ProcessExcelJob` no toca fases porque la tabla de CI-2 no existe todavía.
- **Sugerido:** un método `ProcessFaseControlInterno($row, $solicitud)` en el mismo job (o un `Service` aparte, `ControlInternoService`), llamado igual que `processEstadoInterno` ya se llama hoy dentro de `processRows`.

### CI-5. Indicadores calculados (DESFACE, SEMÁFORO, DÍAS HÁBILES, FUERA DE PLAZO, TRIMESTRE, SEMANA)
En el Excel son fórmulas que se regeneran en cada carga. En Laravel no conviene guardarlos en columnas (se desactualizarían) — mejor calcularlos al vuelo.

- **Ya existe:** nada. Las fechas fuente sí existen (`Solicitud.fecha_aprobacion_contrato`, `Instalacion.fecha_finalizacion_instalacion_interna`).
- **Falta:** función de "días hábiles" en PHP (equivalente a `NETWORKDAYS` de Excel) usando la tabla de feriados de CI-1; accessors o un `ControlInternoIndicadores` value object con desface, semáforo, días hábiles, fuera de plazo, trimestre, semana.
- **Sugerido:** un `Illuminate\Support\Facades` helper o un `Casts`/accessor en el modelo de fase (CI-2), para no repetir la lógica en cada vista.

### CI-6. Anulación / Rechazo (PEND_ANULACION)
- **Ya existe:** nada — el portal trae "Rechazada" y "Anulada" pero `ProcessExcelJob` no las captura hoy en `Instalacion`.
- **Falta:** capturar `Rechazada`, `Anulada`, `Motivo de anulación` del portal; regla "marcado_para_anular = true → PEND_ANULACION" y "portal confirma Anulada/Rechazada = Sí → se archiva/soft-delete la solicitud del control interno" (el Excel la elimina del libro; en Laravel probablemente conviene `soft delete` en vez de borrar, para no perder historial).
- **Sugerido:** agregar esos 3 campos a `Instalacion` (o a una tabla nueva si se prefiere no ensuciar esa tabla), y la regla vive en CI-4.

### CI-7. Indicador IND 2 (FISE) y Puntaje trimestral
Equivalente a la hoja `PUNTAJE`: % de instalaciones FISE construidas dentro de plazo, por empresa y por mes, acumulado del trimestre, excluyendo multifamiliares y NO FISE.

- **Ya existe:** el dato fuente `Solicitante.usuario_fise` y `Proyecto.categoria_proyecto` ya están capturados.
- **Falta:** todo el reporte — es un query agregado (no una tabla), se apoya en CI-5 (días hábiles y fuera de plazo) y CI-1 (plazo FISE = 20 días hábiles, meta 95%).
- **Sugerido:** un `Report`/`Service` de solo lectura, sin tabla propia; se puede cachear por trimestre si el cálculo es pesado.

### CI-8. Bitácora de cargas
- **Ya existe:** la tabla `logs` (nombre_archivo, total_filas, filas_procesadas, filas_con_error, errores, estado, employee_id) es prácticamente igual a la bitácora de la hoja INICIO. Hay que confirmar si `ProcessExcelJob` ya escribe ahí (no lo vi en el job — revisar `ClientController`, que es quien probablemente crea el registro `Logs` antes de despachar el job).
- **Falta:** los contadores específicos de Control Interno (nuevas a GENERAL, movidas GENERAL→CONSTRUIDO, CONSTRUIDO→TC, →PEND_ANULACION, eliminadas por anulación confirmada, ignoradas por fuera de ámbito / sin empresa / sin contrato). Se pueden guardar como columnas nuevas en `logs` o como JSON en un campo `detalle`.
- **Sugerido:** ampliar `logs` en vez de crear una tabla paralela, ya que conceptualmente es la misma bitácora de "una carga de Excel".

### CI-9. Vistas de Control Interno (listados por fase)
Equivalente a las hojas GENERAL / CONSTRUIDO / TC / PEND_ANULACION como pantallas, con filtros por empresa (CYC/CLB) y categoría (RES/MULTI/COM) — como los botones `FiltrarCYC`, `FiltrarRES`, etc. del VBA.

- **Ya existe:** el patrón de listado con filtros y paginación ya está resuelto en `SolicitudTecnicoController::index` (búsqueda, joins, paginación) — se puede clonar ese patrón.
- **Falta:** el controlador/vista propiamente dicho, con columnas iguales a las del Excel (DESFACE, ASESOR, CUADRILLA, SEMÁFORO, etc. de CI-3/CI-5).

### CI-10. Dashboard / Resumen ejecutivo
Equivalente al bloque "RESUMEN" de la hoja INICIO: conteo por fase y por empresa, nuevas de la última carga, fuera de plazo, IND 2 del trimestre y puntaje.

- **Ya existe:** nada como pantalla; los datos fuente estarán disponibles una vez estén CI-2, CI-5 y CI-7.
- **Falta:** todo — es la última pieza porque depende de las anteriores.

### CI-11. Relación con el módulo de Asignación a Técnicos
No es una construcción nueva, es una decisión de diseño a dejar explícita: la fase de Control Interno (CI-2) y el estado de asignación a técnico (`estado_internos` ya existente) son dos ejes independientes de la misma `Solicitud` — una solicitud puede estar en fase CONSTRUIDO y a la vez "Iniciado" para el técnico. Documentar esto evita que a futuro alguien intente fusionar ambas tablas.

## 3. Orden sugerido para ir "por partes"

1. ~~**CI-1** Parámetros~~ — hecho el 12/09/2026, con pantalla de administración incluida (falta correr `migrate` + `db:seed`).
2. ~~**CI-2** Fase de Control Interno~~ — hecho el 12/09/2026 (tabla `fase_control_internos`, modelo, enganche mínimo en la carga; falta `migrate` y el motor completo de CI-4).
3. **CI-3** Columnas de control manual (para que el staff pueda empezar a cargar F. CONSTRUCCIÓN, OBSERVACIÓN, ANULAR).
4. **CI-4** Motor de clasificación (conecta CI-1+CI-2+CI-3 con `ProcessExcelJob`).
5. **CI-5** Indicadores calculados (desbloquea semáforo y filtros "fuera de plazo").
6. **CI-9** Vistas por fase (ya se puede usar el sistema en el día a día).
7. **CI-6** Anulación/Rechazo completo.
8. **CI-8** Bitácora ampliada.
9. **CI-7** IND 2 / Puntaje.
10. **CI-10** Dashboard resumen.

(CI-11 es solo documentación, no tiene entregable de código.)

---
*Generado a partir del análisis de `CONTROL INTERNAS - CYC CLB v5.4.xlsm` (hojas INICIO, GENERAL, CONSTRUIDO, TC, PEND_ANULACION, PUNTAJE, PARAM, INSTRUCTIVO y macros VBA) y del código actual de `cycproenerg` (modelos, controllers, migrations y rutas revisados el 12/09/2026).*
