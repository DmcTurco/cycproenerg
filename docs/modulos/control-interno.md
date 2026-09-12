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

### CI-3. Columnas de control manual — ✅ implementado (12/09/2026)
Las columnas amarillas del Excel: nunca se sobrescriben con la carga del portal, las llena el staff a mano.

**Estado:** esquema, relaciones y vista de edición listos.

- Migración `add_control_manual_columns_to_fase_control_internos_table`: agrega `fecha_construccion_control` (date, nullable), `observacion_control` (text, nullable) y `marcado_para_anular` (boolean, default false) a `fase_control_internos` (decisión confirmada con Turco: junto a la fase, no en `solicituds`, para mantener todo lo de Control Interno en una sola tabla).
- `FaseControlInterno::$fillable`/`$casts` actualizados con las 3 columnas nuevas.
- `Solicitud::asesor()` activada (ya no está comentada) — ahora `belongsTo(Asesor::class, 'asesor_id')` funciona.
- Cuadrilla técnico (decisión confirmada con Turco): Control Interno **reutiliza** `Solicitud::tecnico()` (la misma relación `belongsToMany` vía `solicitud_tecnico` que usa el módulo de Asignación a técnicos de campo) — no se crea una relación independiente, es la misma cuadrilla física.
- Vista: en vez de esperar a CI-9, se agregó **una pantalla propia de Control Interno por solicitud** (`GET employee/solicitudes/{id}/control-interno`, vista `employee/pages/clients/control-interno.blade.php`, componente Alpine `controlInternoDetail` en `resources/js/control-interno-detail.js`) — muestra fase actual + fecha de ingreso a GENERAL (solo lectura), los indicadores de CI-5 y un formulario para las 3 columnas manuales. Se accede desde un botón "Ver Control Interno" en el Detalle de Solicitud (`employee/pages/clients/detail.blade.php`), y desde ahí un botón "Ver Detalle de Solicitud" para volver. **Decisión (12/09/2026, a pedido de Turco):** originalmente esto era una pestaña más dentro del Detalle de Solicitud, pero esa pantalla existe para una vista rápida de solo lectura de lo ya registrado — la pestaña forzaba un fetch adicional de Control Interno en cada carga del detalle (en paralelo, `solicitud-detail.js` `load()`) aunque nadie la abriera, más un formulario de edición que no pertenece ahí conceptualmente. Se separó en su propia pantalla para que el Detalle de Solicitud vuelva a ser liviano y Control Interno cargue (y module) su propio JS solo cuando se visita. Nuevo `ControlInternoManualController` (`GET`/`PUT employee/control-interno/solicitudes/{solicitud}/manual`, con `firstOrCreate` por si la solicitud es anterior a CI-2 y no tiene fila en `fase_control_internos` todavía) sigue siendo el mismo endpoint JSON, ahora consumido por la pantalla dedicada en vez de la pestaña. CI-9 seguirá siendo la vista de LISTADO por fase (varias solicitudes a la vez); esta pantalla es para ver/editar una solicitud puntual.
- **Pendiente para que corra:** `php artisan migrate` (mismo motivo que CI-1/CI-2).

### CI-4. Motor de clasificación y movimientos — ✅ implementado (12/09/2026)
El corazón del VBA (`Procesar`, `AplicarMovimientos`, `CargarPortal`): decide, para cada solicitud, si se queda donde está o se mueve de fase, sin pisar nunca las columnas manuales.

**Estado:** implementado dentro de `ProcessExcelJob::processFaseControlInterno($row, $solicitud)` (se ejecuta al final de `processRows`, después de `processInstalacion`, dentro de la misma transacción por fila).

- Para tener la regla exacta (y no una interpretación) se descompiló el VBA del archivo original (`CONTROL INTERNAS - CYC CLB v5.4.xlsm`, módulo `Modulo_Internas.bas`, macro `Procesar` + funciones `EsTC`/`PortalAnulada`) con `oletools`. De ahí se confirmó: `EsTC` = `UCase(Trim("Resultado de la Instalación de TC")) = "CONCLUIDA"` (columna que `ProcessExcelJob::processInstalacion()` ya captura tal cual, sin necesidad de agregar nada nuevo al schema).
- Reglas implementadas, en orden de prioridad (igual que el VBA):
  1. `PEND_ANULACION` es terminal en este alcance (ver nota de CI-6 abajo).
  2. `marcado_para_anular` (CI-3) = true, en cualquier fase abierta (GENERAL/CONSTRUIDO/TC) → `PEND_ANULACION`.
  3. `TC` es histórico cerrado (igual que en el Excel, que congela sus fórmulas): no se reclasifica con datos del portal, solo sale por el punto 2.
  4. `CONSTRUIDO` → `TC` en cuanto el portal reporta TC concluida.
  5. `GENERAL` → `CONSTRUIDO` (o directo a `TC` si ya viene concluida en la misma carga) en cuanto `fecha_construccion_control` (CI-3) tiene fecha.
- **Deliberadamente fuera de este alcance:** la regla del VBA que borra la fila cuando el portal confirma `Rechazada`/`Anulada` = "Sí" (columnas que todavía no capturamos). No se implementó porque `Solicitud` es una tabla compartida con el módulo de Asignación a Técnicos (preexistente) — decidir si eso significa soft-delete de la `Solicitud` completa, o solo archivar del lado de Control Interno, es una decisión de producto que le corresponde a CI-6 (que es justamente donde ya estaba planeada), no algo para decidir de encargado dentro del motor de clasificación.
- El motor nunca escribe `fecha_construccion_control`, `observacion_control` ni `marcado_para_anular` (CI-3): solo las lee.

### CI-5. Indicadores calculados (DESFACE, SEMÁFORO, DÍAS HÁBILES, FUERA DE PLAZO, TRIMESTRE, SEMANA) — ✅ implementado (12/09/2026)
En el Excel son fórmulas que se regeneran en cada carga. En Laravel no se guardan en columnas (se desactualizarían) — se calculan al vuelo con `App\Services\ControlInternoIndicadores::para($solicitud)`.

- **Bug encontrado y corregido de paso:** al revisar una descarga real del portal (`Solicitudes de Instalación - 2026-09-10...xlsx`, que Turco compartió para esto) se confirmó que `ProcessExcelJob` buscaba la columna **"Fecha de aprobación del contrato"**, que **no existe** en el portal real — la columna real se llama **"Fecha de suscripción de contrato"**. Este campo (`Solicitud.fecha_aprobacion_contrato`, que se mantiene con ese nombre de columna para no romper las pantallas que ya lo usan) quedaba **siempre en null** desde que existe la carga de Excel — no es un bug nuevo de Control Interno, es preexistente, pero DESFACE/SEMÁFORO de CI-5 dependen de esa fecha así que había que arreglarlo para que el indicador sirva de algo. Corregido en `ProcessExcelJob::processSolicitud()`.
- Se agregó `FaseControlInterno.fecha_tc` (migración `add_fecha_tc_to_fase_control_internos_table`): equivalente a "F. TC (portal)" del Excel — la fecha de "Fecha de Registro de resultado de TC" del portal, que CI-4 completa al mover una solicitud a fase TC. La usa el indicador CICLO/DESFACE de la fase TC.
- Se agregó `Solicitud::instalacion()` (relación `hasOne` que faltaba — ya existía la tabla y el `belongsTo` inverso, solo faltaba este lado).
- **CI-4 se refactorizó** a `App\Services\ControlInternoClasificador::clasificar()` (mismas reglas, sin cambios de comportamiento) para que tanto `ProcessExcelJob` (carga de Excel) como `ControlInternoManualController::update()` (guardado manual desde la pestaña Control Interno) puedan reclasificar la fase — así, si el staff marca F. CONSTRUCCIÓN a mano, ve el cambio de fase y los indicadores al toque, sin esperar la próxima carga de Excel (equivalente al botón "Aplicar movimientos" del VBA, pero automático al guardar).
- `App\Services\ControlInternoIndicadores::para($solicitud)` devuelve: `fase`, `desface_dias` + `desface_label` (el significado de DESFACE cambia según la fase, igual que en el Excel — "días desde suscripción" en GENERAL/PEND_ANULACION, "días esperando TC" en CONSTRUIDO, "ciclo total" en TC), `semaforo` (VERDE/AMBAR/ROJO/SIN RED/TC/ANULAR), `dias_habiles` y `fuera_de_plazo` (basados en suscripción → fecha de finalización de instalación interna, igual en las 4 fases del Excel), `trimestre`, `semana_inicio`, `nuevo` (fecha de ingreso = hoy). Incluye una función `NETWORKDAYS` propia (cuenta días hábiles excluyendo fines de semana y los feriados de CI-1).
- Vista: la pantalla de Control Interno por solicitud (CI-3) ahora también muestra estos indicadores (badge de fase con etiqueta NUEVO, badge de semáforo con color, DESFACE con su etiqueta dinámica, y — cuando hay fecha de fin de instalación interna — días hábiles, fuera de plazo, trimestre y semana).
- **Hallazgo importante para CI-6** (no es un bug, es un dato real a tener en cuenta): en la descarga real revisada, la columna "Anulada" del portal viene en **"Sí" para ~73% de las filas** (3458 de 4761). Confirma que la decisión de CI-4 de NO auto-eliminar/archivar por esta columna fue la correcta — aplicar esa regla tal cual del VBA borraría o archivaría la gran mayoría de las solicitudes del sistema. Antes de construir CI-6 hay que confirmar con Turco qué significa realmente ese "Sí" tan frecuente (¿son solicitudes viejas ya cerradas del ciclo de vida del portal, y no "anuladas" en el sentido de Control Interno?).
- **Pendiente para que corra:** `php artisan migrate` (por `fecha_tc`); `npm run build` (el proyecto sirve el build de producción, no hay servidor Vite en modo dev — cualquier cambio en `resources/js/*` necesita este paso para reflejarse en el navegador).

### CI-6. Anulación / Rechazo (PEND_ANULACION)
La regla "`marcado_para_anular` = true → `PEND_ANULACION`" ya la aplica CI-4 (es la columna manual de CI-3). Lo que queda acá es específicamente el lado **portal**: Rechazada/Anulada.

- **Ya existe:** nada — el portal trae "Rechazada" y "Anulada" pero `ProcessExcelJob` no las captura hoy en `Instalacion`. Ya se confirmó (al decompilar el VBA para CI-4) que son exactamente esas dos columnas del portal (valores "Sí"/"No"), más "Motivo de anulación".
- **Falta:** capturar esos 3 campos del portal; y decidir la regla de "portal confirma Anulada/Rechazada = Sí" — el VBA elimina la fila del libro, pero en Laravel `Solicitud` es una tabla **compartida** con el módulo de Asignación a Técnicos (preexistente, no forma parte de Control Interno), así que hay que decidir con Turco si "eliminar" significa `soft delete` de la `Solicitud` completa (la saca de TODO el sistema, no solo de Control Interno) o algún archivado más acotado del lado de Control Interno únicamente. Esta es la razón por la que CI-4 la dejó explícitamente afuera en vez de implementarla de encargado.
- **Sugerido:** agregar esos 3 campos a `Instalacion` (o a una tabla nueva si se prefiere no ensuciar esa tabla); la regla de transición, una vez decidida, se agrega al mismo método `ProcessExcelJob::processFaseControlInterno()` de CI-4.

### CI-7. Indicador IND 2 (FISE) y Puntaje trimestral
Equivalente a la hoja `PUNTAJE`: % de instalaciones FISE construidas dentro de plazo, por empresa y por mes, acumulado del trimestre, excluyendo multifamiliares y NO FISE.

- **Ya existe:** el dato fuente `Solicitante.usuario_fise` y `Proyecto.categoria_proyecto` ya están capturados.
- **Falta:** todo el reporte — es un query agregado (no una tabla), se apoya en CI-5 (días hábiles y fuera de plazo) y CI-1 (plazo FISE = 20 días hábiles, meta 95%).
- **Sugerido:** un `Report`/`Service` de solo lectura, sin tabla propia; se puede cachear por trimestre si el cálculo es pesado.

### CI-8. Bitácora de cargas
- **Ya existe:** la tabla `logs` (nombre_archivo, total_filas, filas_procesadas, filas_con_error, errores, estado, employee_id) es prácticamente igual a la bitácora de la hoja INICIO. Hay que confirmar si `ProcessExcelJob` ya escribe ahí (no lo vi en el job — revisar `ClientController`, que es quien probablemente crea el registro `Logs` antes de despachar el job).
- **Falta:** los contadores específicos de Control Interno (nuevas a GENERAL, movidas GENERAL→CONSTRUIDO, CONSTRUIDO→TC, →PEND_ANULACION, eliminadas por anulación confirmada, ignoradas por fuera de ámbito / sin empresa / sin contrato). Se pueden guardar como columnas nuevas en `logs` o como JSON en un campo `detalle`.
- **Sugerido:** ampliar `logs` en vez de crear una tabla paralela, ya que conceptualmente es la misma bitácora de "una carga de Excel".

### CI-9. Vistas de Control Interno (listados por fase) — ✅ implementado (12/09/2026)
Equivalente a las hojas GENERAL / CONSTRUIDO / TC / PEND_ANULACION como pantallas, con filtros por empresa (CYC/CLB) y categoría (RES/MULTI/COM) — como los botones `FiltrarCYC`, `FiltrarRES`, etc. del VBA.

**Estado:** implementado el listado con filtro por fase y búsqueda por número de solicitud; quedan pendientes los filtros por empresa/categoría del VBA original.

- Nuevo `App\Http\Controllers\Employee\ControlInternoController::index` (`GET employee/control-interno`, vista `employee.pages.control-interno.index`): pagina `Solicitud` (20 por página), con filtro `?fase=` (GENERAL/CONSTRUIDO/TC/PEND_ANULACION) y `?search=` (número de solicitud), y calcula `ControlInternoIndicadores::para()` por fila para mostrar fase, semáforo y desface — igual que el detalle individual (CI-3/CI-5), pero de un vistazo para muchas solicitudes a la vez.
- El sidebar del empleado ("Control Interno") ahora apunta a este listado en vez de ir directo a Parámetros; el listado tiene un botón "Parámetros" en la cabecera para no perder ese acceso.
- Cada fila enlaza a la pantalla individual de CI-3 (`employee/solicitudes/{id}/control-interno`) para ver el detalle completo y editar las columnas manuales.
- **Decisión de diseño (12/09/2026):** originalmente Control Interno era una pestaña dentro del Detalle de Solicitud; se separó primero en una pantalla individual por solicitud (ver nota en CI-3) y ahora en este listado, porque lo que Turco pedía desde el principio era justamente esto — un índice por número de solicitud con sus fases/semáforos, no un dato más dentro del detalle de una solicitud puntual.
- **Falta:** filtros por empresa (CYC/CLB) y categoría (RES/MULTI/COM); columnas ASESOR/CUADRILLA del Excel original (fáciles de agregar reusando `Solicitud::asesor()`/`Solicitud::tecnico()` si se necesitan más adelante).

### CI-10. Dashboard / Resumen ejecutivo
Equivalente al bloque "RESUMEN" de la hoja INICIO: conteo por fase y por empresa, nuevas de la última carga, fuera de plazo, IND 2 del trimestre y puntaje.

- **Ya existe:** nada como pantalla; los datos fuente estarán disponibles una vez estén CI-2, CI-5 y CI-7.
- **Falta:** todo — es la última pieza porque depende de las anteriores.

### CI-11. Relación con el módulo de Asignación a Técnicos
No es una construcción nueva, es una decisión de diseño a dejar explícita: la fase de Control Interno (CI-2) y el estado de asignación a técnico (`estado_internos` ya existente) son dos ejes independientes de la misma `Solicitud` — una solicitud puede estar en fase CONSTRUIDO y a la vez "Iniciado" para el técnico. Documentar esto evita que a futuro alguien intente fusionar ambas tablas.

## 3. Orden sugerido para ir "por partes"

1. ~~**CI-1** Parámetros~~ — hecho el 12/09/2026, con pantalla de administración incluida (falta correr `migrate` + `db:seed`).
2. ~~**CI-2** Fase de Control Interno~~ — hecho el 12/09/2026 (tabla `fase_control_internos`, modelo, enganche mínimo en la carga; falta `migrate` y el motor completo de CI-4).
3. ~~**CI-3** Columnas de control manual~~ — hecho el 12/09/2026 (esquema en `fase_control_internos`, `Solicitud::asesor()` activada, cuadrilla reutiliza `Solicitud::tecnico()`, vista de edición en la pestaña "Control Interno" del Detalle de Solicitud; falta `migrate`).
4. ~~**CI-4** Motor de clasificación~~ — hecho el 12/09/2026 (`App\Services\ControlInternoClasificador::clasificar()`, usado por `ProcessExcelJob` y por el guardado manual de CI-3, mueve GENERAL→CONSTRUIDO→TC y aplica PEND_ANULACION por marca manual, según las reglas exactas del VBA original; falta `migrate` para que corra sin errores. Rechazada/Anulada del portal queda para CI-6 a propósito).
5. ~~**CI-5** Indicadores calculados~~ — hecho el 12/09/2026 (`App\Services\ControlInternoIndicadores`, visibles en la pestaña Control Interno de Detalle de Solicitud; de paso se corrigió un bug preexistente en la fecha base — ver la sección de CI-5. Falta `migrate` + `npm run build`).
6. ~~**CI-9** Vistas por fase~~ — hecho el 12/09/2026 (listado en `employee/control-interno`, filtro por fase y búsqueda por número; ya se puede usar el sistema en el día a día).
7. **CI-6** Anulación/Rechazo completo.
8. **CI-8** Bitácora ampliada.
9. **CI-7** IND 2 / Puntaje.
10. **CI-10** Dashboard resumen.

(CI-11 es solo documentación, no tiene entregable de código.)

---
*Generado a partir del análisis de `CONTROL INTERNAS - CYC CLB v5.4.xlsm` (hojas INICIO, GENERAL, CONSTRUIDO, TC, PEND_ANULACION, PUNTAJE, PARAM, INSTRUCTIVO y macros VBA) y del código actual de `cycproenerg` (modelos, controllers, migrations y rutas revisados el 12/09/2026).*
