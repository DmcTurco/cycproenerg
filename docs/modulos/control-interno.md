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

### CI-6. Anulación / Rechazo (PEND_ANULACION) — parte segura ✅ implementada (12/09/2026), regla de negocio pendiente
La regla "`marcado_para_anular` = true → `PEND_ANULACION`" ya la aplica CI-4 (es la columna manual de CI-3). Lo que quedaba acá era específicamente el lado **portal**: Rechazada/Anulada.

**Estado:** se capturan los 3 campos del portal (parte segura, sin efectos colaterales); la regla de qué hacer con una solicitud marcada así sigue **sin implementar a propósito** — es una decisión de producto que le corresponde a Turco, no algo para decidir de encargado.

- Migración `add_anulacion_columns_to_instalacions_table`: agrega `rechazada` (boolean, default false), `anulada` (boolean, default false) y `motivo_anulacion` (text, nullable) a `instalacions` — confirmadas como columnas 55/56/57 del portal ("Rechazada", "Anulada", "Motivo de anulación") con la descarga real usada en CI-5.
- `Instalacion::$fillable`/`$casts` actualizados.
- `ProcessExcelJob::processInstalacion()` ahora captura los 3 campos en cada carga (nuevo helper `esSi()`, igual al `Trim(CStr(...)) = "Sí"` del VBA original). **No dispara ninguna regla de eliminación, archivado ni cambio de fase** — solo guarda el dato, exactamente igual que cualquier otro campo de `Instalacion`.
- **Deliberadamente sin implementar:** la regla del VBA que borra la fila del libro cuando `Rechazada`/`Anulada` = "Sí". En Laravel, `Solicitud` es una tabla **compartida** con el módulo de Asignación a Técnicos (preexistente, no forma parte de Control Interno) — hay que decidir con Turco si "eliminar" significa `soft delete` de la `Solicitud` completa (la saca de TODO el sistema) o algún archivado acotado solo del lado de Control Interno. Con la columna `anulada` ya capturada y visible, Turco puede revisar cuántas/cuáles solicitudes reales caen en ese ~73% antes de decidir la regla — el dato ya no es una incógnita, solo falta la decisión.
- **Pendiente para que corra:** `php artisan migrate`.
- **Pendiente de decisión de Turco (bloqueante para cerrar CI-6 del todo):** qué hacer exactamente con una `Solicitud` cuando el portal confirma `Rechazada`/`Anulada` = "Sí".

### CI-7. Indicador IND 2 (FISE) y Puntaje trimestral — ✅ implementado (12/09/2026)
Equivalente a la hoja `PUNTAJE`: % de instalaciones FISE construidas dentro de plazo, por empresa y por mes, acumulado del trimestre, excluyendo multifamiliares y NO FISE.

**Estado:** servicio de solo lectura listo (`App\Services\ControlInternoPuntaje::trimestre($anio, $trimestre)`); sin vista propia todavía — la consumirá CI-10 (dashboard).

- A diferencia de CI-4/CI-6, la hoja `PUNTAJE` no tiene lógica en VBA — es 100% fórmulas de hoja de cálculo. Se leyeron directamente del archivo original (filas 8-25) en vez de adivinar la definición de IND 2. Fórmula real, por empresa (CYC/CLB) y por cada uno de los 3 meses del trimestre: cuenta solicitudes con Usuario FISE = "Sí", que ya llegaron a fase CONSTRUIDO o TC (el Excel las suma desde las hojas CONSTRUIDO + TC — antes de CONSTRUIDO no cuentan), categoría Residencial o Comercio (Multifamiliares excluidas, "FP: 5" del Excel), con "Fecha de finalización de la Instalación Interna" dentro de ese mes. FUERA DE PLAZO = las de ese grupo cuyo indicador FUERA DE PLAZO de CI-5 es "SÍ". IND 2 = en plazo / total FISE construidas. **PUNTAJE = IND2 × 5** (máximo 5 puntos, fórmula literal `PUNTAJE!G8/G9`) — el `meta_ind2` de CI-1 (95%) NO es parte de esta fórmula, es solo un valor de referencia para comparar contra el IND2 obtenido.
- `ControlInternoPuntaje::trimestre()` reutiliza `ControlInternoIndicadores::para()` por solicitud (mismo cálculo ya probado de días hábiles/fuera de plazo de CI-5) en vez de duplicar el NETWORKDAYS — sin tabla propia, se calcula al vuelo (tal como sugería el roadmap).

### CI-8. Bitácora de cargas — ✅ implementado (12/09/2026)
**Estado:** confirmado (revisando `ClientController` y `ProcessExcelJob`) que la tabla `logs` existía desde antes pero **nada escribía ahí** — el progreso de una carga solo vivía en caché (`excel_progress_*`, efímera, para la barra de progreso), así que la bitácora persistente de la hoja INICIO del Excel nunca se había implementado de verdad. Corregido de raíz, no solo agregados los contadores de Control Interno.

- Migración `add_control_interno_columns_to_logs_table`: agrega `resumen_control_interno` (json, nullable) a `logs`.
- **Bug preexistente encontrado y corregido de paso:** `Logs::$fillable` tenía `tamano_archivo` (sin ñ), pero la columna real de la migración original es `tamaño_archivo` (con ñ) — nunca se había notado porque nada creaba filas en `logs` todavía. Se corrigió el fillable para usar el nombre real de columna.
- `ClientController::change()` ahora crea la fila de `Logs` (nombre_archivo, tamaño_archivo, estado='en_proceso', employee_id vía `Auth::id()`) antes de despachar `ProcessExcelJob`, pasándole su id.
- `ProcessExcelJob` recibe ese `$logId` (tercer parámetro del constructor) y actualiza la bitácora: `total_filas` apenas lee el archivo, y al terminar (`filas_procesadas`, `filas_con_error`, `estado`, `resumen_control_interno`) o si falla (`estado='error'`, `errores`).
- `resumen_control_interno` trae los contadores específicos de Control Interno: `nuevas_general`, `movidas_general_construido`, `movidas_general_tc`, `movidas_construido_tc`, `movidas_a_pend_anulacion`, `filas_omitidas_validacion` (filas que no pasan `validateRow`, antes se saltaban en silencio sin contarse en ningún lado). Se calculan comparando la fase ANTES y DESPUÉS de `ControlInternoClasificador::clasificar()` en cada fila (una consulta extra por fila).
- **No incluido a propósito:** "eliminadas por anulación confirmada" — no aplica todavía porque CI-6 no implementa ninguna eliminación (ver CI-6). "Ignoradas por fuera de ámbito / sin empresa / sin contrato" tampoco aplica: el filtro de ámbito de CI-1 (departamentos) no está enganchado en ningún punto del `ProcessExcelJob` actual — es una limitación preexistente, no algo que CI-8 debía resolver.
- **Pendiente para que corra:** `php artisan migrate`.

### CI-9. Vistas de Control Interno (listados por fase) — ✅ implementado (12/09/2026)
Equivalente a las hojas GENERAL / CONSTRUIDO / TC / PEND_ANULACION como pantallas, con filtros por empresa (CYC/CLB) y categoría (RES/MULTI/COM) — como los botones `FiltrarCYC`, `FiltrarRES`, etc. del VBA.

**Estado:** completo — listado con pestañas por fase (con contador), filtros de empresa/categoría/búsqueda, y columnas ASESOR/CUADRILLA/SEMÁFORO/DESFACE/FUERA DE PLAZO.

- `App\Http\Controllers\Employee\ControlInternoController::index` (`GET employee/control-interno`, vista `employee.pages.control-interno.index`): pagina `Solicitud` (20 por página) con pestañas por fase (Todas/GENERAL/CONSTRUIDO/TC/PEND_ANULACION, cada una con su contador — respetando los filtros activos), `?empresa=` (código CYC/CLB, vía `Empresa.codigo` de CI-1), `?categoria=` (RES/MULTI/COM, resuelto contra `Proyecto.categoria_proyecto` usando el mapeo de `config('const.control_interno.categorias')`) y `?search=` (número de solicitud). Calcula `ControlInternoIndicadores::para()` por fila para mostrar fase, semáforo, desface y fuera de plazo — igual que la pantalla individual (CI-3/CI-5), pero de un vistazo para muchas solicitudes a la vez. Columnas ASESOR y CUADRILLA reutilizan `Solicitud::asesor()`/`Solicitud::tecnico()`, sin relaciones nuevas.
- El sidebar del empleado ("Control Interno") apunta a este listado; el listado tiene un botón "Parámetros y feriados" en la cabecera para no perder ese acceso, y la pantalla de Parámetros tiene su botón "Salir" de vuelta al listado.
- Cada fila enlaza directo a la pantalla individual de Control Interno de esa solicitud (`employee/solicitudes/{id}/control-interno`, CI-3/CI-5) y, aparte, al Detalle de Solicitud general si se necesita ver el resto de los datos.
- **Decisión de diseño (12/09/2026):** originalmente Control Interno era una pestaña dentro del Detalle de Solicitud; se separó en una pantalla individual por solicitud (CI-3) y ahora en este listado, porque lo que Turco pedía desde el principio era justamente esto — un índice por número de solicitud con sus fases/semáforos, no un dato más dentro del detalle de una solicitud puntual.
- **Bug encontrado y corregido de paso:** la pantalla individual `employee/solicitudes/{id}/control-interno` (vista `clients/control-interno.blade.php`) ya usaba el componente Alpine `controlInternoDetail` y `resources/js/app.js` ya lo importaba, pero el archivo `resources/js/control-interno-detail.js` nunca se había creado — la pantalla se habría roto (Alpine sin poder resolver `controlInternoDetail`) en cuanto alguien la abriera. Creado con la misma lógica de `load()`/`save()`/`semaforoClase()` que tenía la versión anterior (cuando Control Interno todavía era una pestaña de `solicitud-detail.js`).
- **Pendiente para que corra:** `npm run build` (nuevo archivo JS).

### CI-10. Dashboard / Resumen ejecutivo — ✅ implementado (12/09/2026)
Equivalente al bloque "RESUMEN" + "BITÁCORA DE LA ÚLTIMA CARGA" de la hoja INICIO: conteo por fase y por empresa, nuevas de la última carga, fuera de plazo, IND 2 del trimestre y puntaje.

**Estado:** completo — última pieza del roadmap con entregable de código.

- Igual que con PUNTAJE (CI-7), el bloque RESUMEN de INICIO (`B34:D45`) es 100% fórmulas de hoja — se leyó directo del archivo original en vez de adivinar qué contaba cada indicador: GENERAL (total + NUEVAS + SIN RED + ROJO por empresa), CONSTRUIDO (total + cuántas no tienen fecha de fin de instalación interna todavía, "no cuentan en IND2"), TC (total), PENDIENTES DE ANULACIÓN (total), IND2 del trimestre y PUNTAJE (tomados de CI-7).
- Nuevo `App\Services\ControlInternoDashboard::resumen($anio, $trimestre)`: arma todo lo anterior por empresa, más la bitácora de la última carga (bloque `B5:B15` de INICIO, tomada de la última fila de `logs` de CI-8). Los dos conceptos del Excel sin equivalente todavía ("Eliminadas: anulación confirmada", "Ignoradas: fuera de Lima/Callao") se muestran como no disponibles en vez de inventar un número — dependen de la decisión de negocio pendiente de CI-6 y de un filtro de ámbito que CI-1 dejó como parámetro pero que `ProcessExcelJob` todavía no aplica.
- Se cachea 5 minutos (`Cache::remember`) porque el desglose de GENERAL recorre cada solicitud para calcular su semáforo (días hábiles) — igual que el propio roadmap sugería para CI-7.
- Pantalla nueva `GET employee/control-interno/resumen` (`ControlInternoDashboardController`, vista `employee.pages.control-interno.resumen`), con selector de año/trimestre. El listado de CI-9 ganó un botón "Resumen" en la cabecera (junto a "Parámetros y feriados") para llegar ahí, y el resumen tiene su "Ver listado" de vuelta.
- Con esto quedan implementados los 11 submódulos del roadmap. Lo único que sigue abierto es la decisión de negocio de CI-6 (qué significa "eliminar" una `Solicitud` cuando el portal confirma Rechazada/Anulada), que se dejó a propósito para que la tome Turco.

### CI-11. Relación con el módulo de Asignación a Técnicos
No es una construcción nueva, es una decisión de diseño a dejar explícita: la fase de Control Interno (CI-2) y el estado de asignación a técnico (`estado_internos` ya existente) son dos ejes independientes de la misma `Solicitud` — una solicitud puede estar en fase CONSTRUIDO y a la vez "Iniciado" para el técnico. Documentar esto evita que a futuro alguien intente fusionar ambas tablas.

## 3. Orden sugerido para ir "por partes"

1. ~~**CI-1** Parámetros~~ — hecho el 12/09/2026, con pantalla de administración incluida (falta correr `migrate` + `db:seed`).
2. ~~**CI-2** Fase de Control Interno~~ — hecho el 12/09/2026 (tabla `fase_control_internos`, modelo, enganche mínimo en la carga; falta `migrate` y el motor completo de CI-4).
3. ~~**CI-3** Columnas de control manual~~ — hecho el 12/09/2026 (esquema en `fase_control_internos`, `Solicitud::asesor()` activada, cuadrilla reutiliza `Solicitud::tecnico()`, pantalla propia `employee/solicitudes/{id}/control-interno`; falta `migrate`).
4. ~~**CI-4** Motor de clasificación~~ — hecho el 12/09/2026 (`App\Services\ControlInternoClasificador::clasificar()`, usado por `ProcessExcelJob` y por el guardado manual de CI-3, mueve GENERAL→CONSTRUIDO→TC y aplica PEND_ANULACION por marca manual, según las reglas exactas del VBA original; falta `migrate` para que corra sin errores. Rechazada/Anulada del portal queda para CI-6 a propósito).
5. ~~**CI-5** Indicadores calculados~~ — hecho el 12/09/2026 (`App\Services\ControlInternoIndicadores`, visibles en la pantalla de Control Interno por solicitud y en el listado de CI-9; de paso se corrigió un bug preexistente en la fecha base — ver la sección de CI-5. Falta `migrate` + `npm run build`).
6. ~~**CI-9** Vistas por fase~~ — hecho el 12/09/2026 (listado en `employee/control-interno` con pestañas por fase, filtros de empresa/categoría/búsqueda y columnas ASESOR/CUADRILLA/SEMÁFORO/DESFACE; ya se puede usar el sistema en el día a día. De paso se encontró y corrigió un bug: faltaba crear `resources/js/control-interno-detail.js`, que la pantalla individual de CI-3 ya necesitaba. Falta `npm run build`).
7. ~~**CI-6** Anulación/Rechazo — parte segura~~ — hecho el 12/09/2026 (captura de Rechazada/Anulada/Motivo de anulación en `Instalacion`, sin ninguna regla de eliminación/archivado; falta `migrate`). **La regla de negocio en sí sigue pendiente de que Turco decida** qué significa "eliminar" para una `Solicitud` compartida con Asignación a Técnicos.
8. ~~**CI-8** Bitácora ampliada~~ — hecho el 12/09/2026 (se encontró que `logs` nunca se escribía y se implementó de raíz, no solo los contadores nuevos; falta `migrate`).
9. ~~**CI-7** IND 2 / Puntaje~~ — hecho el 12/09/2026 (`App\Services\ControlInternoPuntaje::trimestre()`, fórmulas reales tomadas de la hoja PUNTAJE del Excel original; sin vista propia, la usará CI-10).
10. ~~**CI-10** Dashboard resumen~~ — hecho el 12/09/2026 (`ControlInternoDashboard::resumen()`, pantalla `employee/control-interno/resumen`; falta `migrate`+`npm run build` acumulados de los pasos anteriores). **Con esto quedan los 11 submódulos implementados** — solo falta que Turco decida la regla de negocio de CI-6 (ver esa sección) y corra `migrate`/`db:seed`/`npm run build` en su entorno.

(CI-11 es solo documentación, no tiene entregable de código.)

## 4. Para que todo esto corra en tu entorno

Esta sesión no pudo ejecutar comandos en tu máquina (falla de montaje de carpeta reportada por Windows desde el 8-sep-2026), así que falta correr, en orden, en la carpeta del proyecto:

1. `php artisan migrate` — aplica TODAS las migraciones nuevas de CI-1 a CI-6 (parámetros, feriados, código de empresa, fase de control interno, columnas manuales, F. TC, Rechazada/Anulada/Motivo de anulación, resumen_control_interno de logs).
2. `php artisan db:seed --class=ControlInternoParametrosSeeder` — carga los valores por defecto (plazos, feriados 2026, código CYC/CLB por RUC). Sin esto, `ParametroControlInterno::actual()` y `Empresa::codigo` igual funcionan (tienen valores por defecto/`firstOrCreate`), pero es mejor correrlo para tener los datos reales del Excel desde el día uno.
3. `npm run build` — el proyecto sirve el build de producción (no hay servidor Vite en modo dev), así que los cambios de `resources/js/*` (más recientemente `control-interno-detail.js`) no se van a ver en el navegador hasta correr esto.
4. Un hard-refresh del navegador después del build.

**Pendiente de decisión (no es un comando, es tuyo):** qué debe pasar con una `Solicitud` cuando el portal confirma `Rechazada`/`Anulada` = "Sí" (CI-6) — el dato ya se captura, solo falta la regla. Con `migrate` corrido, puedes revisar en el listado de CI-9 o directamente en la base de datos cuántas/cuáles solicitudes reales caen ahí antes de decidir.

---
*Generado a partir del análisis de `CONTROL INTERNAS - CYC CLB v5.4.xlsm` (hojas INICIO, GENERAL, CONSTRUIDO, TC, PEND_ANULACION, PUNTAJE, PARAM, INSTRUCTIVO y macros VBA) y del código actual de `cycproenerg` (modelos, controllers, migrations y rutas revisados el 12/09/2026).*
