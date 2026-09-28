# Módulo: Control de Materiales — Submódulos

## 1. Dónde encaja "Control de Materiales" en el sistema

Es un módulo **nuevo e independiente** de Control Interno (otro negocio: almacén, cotizaciones a contratistas y liquidación de materiales — no tiene que ver con el flujo de instalaciones del portal de gas), pero **ligado a `Empresa` (CYC/CLB)** igual que Control Interno, para poder cruzar reportes entre ambos módulos más adelante (a confirmar con Turco en qué punto exacto se asocia: por cotización, por cuadrilla, o ambos).

Las personas que retiran materiales (**cuadrillas**: CONTRATISTA o PERSONAL DIRECTO) son gente **distinta** de la tabla `tecnicos` existente (la de Asignación a Técnicos) — se crea una tabla nueva, sin tocar `tecnicos`.

Fuente: `CONTROL_MATERIALES_CC_08-2026 rev 02.xlsm` (13 hojas + macro `MacrosCYC.bas`: `GenerarPDF`, `CorregirCotizacion`, `GenerarResumenPDF`, `CerrarMes`, `GuardarEjecutado`), analizado con `openpyxl` (fórmulas) y `oletools` (VBA) el 18/09/2026.

## 2. Cómo funciona el Excel (resumen para orientarse)

- **CATALOGO** es el corazón: una fila por material (con su kardex: stock inicial + ingresos − salidas = stock actual) y, en el mismo sheet, un bloque aparte de **herramientas** (código `HER-###` autogenerado, responsable actual y ubicación calculados desde ENTREGAS).
- El **precio vigente** de un material solo sube: se actualiza solo si el último precio de compra registrado en INGRESOS es mayor al precio base; si baja, se deja una alerta pero el precio vigente NO cambia solo (decisión manual).
- Hay **dos circuitos de salida de stock** distintos:
  - **Contratistas**: descuentan el stock automáticamente al emitir una `COTIZACION` (quedan `PENDIENTE` de descontarse en una valorización real, fuera del sistema).
  - **Personal directo**: retira con un `VALE DE ENTREGA` (mismo formulario que la cotización, pero a precio costo, sin IGV) y **además** reporta después, en REGISTRO RÁPIDO, lo que realmente **ejecutó** (y lo que devolvió) — el saldo "en su poder" (vale − ejecutado − devuelto) es lo que se liquida en RESUMEN.
- Las tuberías se entregan en **rollos** pero se ejecutan/reportan en **metros** — hay un factor de conversión por ítem (columna R del catálogo, ej. 200 o 100).
- **CIERRE DE MES** es una acción manual y opcional (el Excel funciona sin cerrar meses): archiva una copia histórica, pasa el stock actual a inicial, consolida el precio base, limpia movimientos, y conserva cotizaciones pendientes + catálogo + cuadrillas + herramientas.

## 3. Submódulos propuestos

### CM-1. Parámetros generales + Catálogo (materiales y herramientas) — ✅ implementado (18/09/2026)
Equivalente a INICIO (parámetros: IGV, margen general, umbral de alarma de precio) + CATALOGO.

- Parámetros: IGV (18%), margen sobre compra general (10%, editable por ítem), umbral de alarma de precio (5%).
- Catálogo de materiales: código, descripción, unidad, precio base (editable), margen % por ítem, precio vigente (autocalculado, solo sube), precio venta (con y sin IGV), stock inicial/mínimo (editables), stock actual (kardex, calculado), estado (OK/REPONER/SIN STOCK), factor metros-por-unidad (para tuberías).
- Catálogo de herramientas (mismo sheet en el Excel, tabla aparte): código autogenerado, descripción, marca/modelo, N° serie, fecha de compra, precio, estado (OPERATIVA/MALOGRADA/PERDIDA), responsable actual y ubicación (calculados desde CM-7, no editables a mano).

**Estado:** implementado. Migraciones `parametros_control_materiales`, `materiales`, `herramientas`; modelos `ParametroControlMaterial` (patrón `actual()`, igual que `ParametroControlInterno`), `Material` y `Herramienta`. Ni `materiales` ni `herramientas` tienen columna `empresa_id`: el almacén es uno solo, compartido — la asociación con `Empresa` (CYC/CLB) que se decidió con Turco entra recién en CM-2/CM-4 (cuadrillas y cotizaciones), no en el catálogo.

- **Kardex parcial a propósito:** `Material` calcula `stockActual()`, `precioVigente()`, `estado()`, etc. como métodos (no columnas, para no desactualizarse — mismo criterio que `ControlInternoIndicadores`), pero `ingresos()`, `salidas()`, `ultimoPrecioIngresos()` y `alertaPrecio()` devuelven 0/null a propósito: las tablas que los alimentan (`ingresos` en CM-3, cotizaciones/ejecutado en CM-4/CM-6) todavía no existen. Por ahora `stockActual()` = `stock_inicial` y `precioVigente()` = `precio_base`. Extender estos métodos es parte del trabajo de CM-3/CM-4/CM-6, no algo nuevo que programar en un lugar distinto.
- **Herramientas — mismo criterio:** `Herramienta::responsableActual()` devuelve `null` y `Herramienta::ubicacion()` devuelve `ALMACEN` (o `PERDIDA` si el estado lo dice) hasta que exista `entregas` (CM-7). El código `HER-###` sí se autogenera ya (`Herramienta::siguienteCodigo()`), igual que en el Excel.
- Pantallas: `employee/materiales` (Catálogo, con pestañas Materiales/Herramientas, cada una con su propio `crudModal` — sin paginar, como Feriados: los volúmenes son manejables) y `employee/materiales/parametros`. Sidebar del empleado: nuevo ítem "Materiales", independiente de "Control Interno".
- Rutas explícitas (no `Route::resource`) para `items` y `herramientas`: se evitó depender de que `Str::singular()` adivine bien el nombre del parámetro en español.
- **Pendiente para que corra:** `php artisan migrate` (3 tablas nuevas). No requiere `npm run build`: la pantalla reutiliza el componente `crudModal` que ya estaba importado globalmente en `resources/js/app.js`.

### CM-2. Cuadrillas (registro de personas) — ✅ implementado (18/09/2026)
Equivalente a la tabla REGISTRO DE CUADRILLAS dentro de RESUMEN. Tabla nueva (no `tecnicos`): nombre, tipo (CONTRATISTA/PERSONAL DIRECTO), estado (ACTIVO/INACTIVO), DNI, fecha de nacimiento, celular. El tipo determina todo el comportamiento aguas abajo (cotización con IGV vs. vale a costo; pasa o no por Ejecutado).

- **Empresa CYC/CLB — muchos a muchos:** el Excel real tiene cuadrillas con ambas empresas juntas en el mismo campo de texto ("CLB/C&C"). Turco confirmó (18/09/2026) modelarlo como relación muchos-a-muchos (`Cuadrilla::empresas()` `belongsToMany(Empresa::class)`, tabla pivote `cuadrilla_empresa`) en vez de un `empresa_id` único — así no se pierde el caso real de gente que trabaja para las dos. Punto abierto de la sección 5 original: **resuelto**.
- **N° retiros / total valorizado / pendiente de descuento** (columnas I/J/K de RESUMEN): son cálculos sobre COTIZACIONES, que no existen todavía — `Cuadrilla::numRetiros()`, `totalValorizado()`, `pendienteDescuento()` devuelven 0 a propósito (mismo patrón placeholder de CM-1), a extender en CM-5.
- Migraciones: `cuadrillas` (softDeletes) + pivote `cuadrilla_empresa` (con `unique(['cuadrilla_id','empresa_id'])`).
- Modelo: `app/Models/Cuadrilla.php`.
- Controlador: `app/Http/Controllers/Employee/CuadrillaController.php` (mismo patrón `crudModal` — `store()` decide crear/actualizar según `id`).
- El checkbox múltiple de empresas viaja como `form.empresas` (array en Alpine) → el `crudModal` genérico lo serializa con `FormData.append` sin tocar `resources/js/crud-modal.js` (un array se auto-convierte a `"1,2"` por `Array.prototype.toString()`); el controlador lo separa por coma y hace `sync()`. `edit()` devuelve `empresas` como array de ids en string para que los checkboxes marquen bien.
- Pantalla: `employee/materiales/cuadrillas` (fuera de las pestañas del Catálogo, con su propio botón "Cuadrillas" en el header de `employee/materiales`, al lado de "Parámetros").
- Rutas explícitas bajo el mismo prefijo `materiales` (`cuadrillas`, `cuadrillas/{cuadrilla}/edit`, etc.), mismo criterio que `items`/`herramientas` en CM-1.
- **Pendiente para que corra:** `php artisan migrate` (2 tablas nuevas: `cuadrillas`, `cuadrilla_empresa`).

### CM-3. Ingresos (kardex de entradas) — ✅ implementado (18/09/2026)
Una fila por cada llegada de material al almacén: fecha, material (FK a `materiales`, no texto libre), cantidad, proveedor, N° guía/factura, precio de compra (opcional) y alerta de precio (sube/baja/estable) comparado contra el precio base del catálogo.

- **Dos fórmulas de alerta distintas, copiadas tal cual del Excel** (no es la misma cuenta dos veces):
  - `Ingreso::alertaPrecio()` (columna I de INGRESOS, por fila): compara ESTE precio de compra contra el precio base usando el **umbral** de `parametros_control_materiales` — "SUBIO +X.X%" solo si la subida supera el umbral, si no "BAJO"/"ESTABLE".
  - `Material::alertaPrecio()` (columna Q de CATALOGO, por material): compara el **último** precio de ingreso contra el precio base, sin umbral — simple mayor/menor/igual ("SUBIO: vigente actualizado" / "BAJO: decidir si actualizar BASE" / "ESTABLE").
- Con CM-3 ya existiendo la tabla `ingresos`, se completaron los placeholders de CM-1: `Material::ingresos()` (suma `ingresos.cantidad`), `Material::ultimoPrecioIngresos()` (el ingreso con precio_compra no nulo más reciente por fecha) y `Material::alertaPrecio()`. `Material::salidas()` sigue en 0 (depende de CM-4/CM-6).
- Listado paginado (`paginate(20)`, patrón de Control Interno/Asesores/Técnicos) con filtro por material y búsqueda por proveedor/guía/código — a diferencia de CM-1/CM-2 (catálogos acotados sin paginar), este es un ledger que crece con el tiempo.
- La pantalla de Catálogo (CM-1) ahora muestra un badge SUBIO/BAJO junto al precio vigente cuando `Material::alertaPrecio()` no es null/ESTABLE.
- Pantalla: `employee/materiales/ingresos`, con su propio botón en el header de `employee/materiales`.
- **Pendiente para que corra:** `php artisan migrate` (1 tabla nueva: `ingresos`).

### CM-4. Cotización / Vale de entrega (salida a contratistas y personal directo) — ✅ implementado (18/09/2026)
Formulario dual: si la cuadrilla es CONTRATISTA genera una COTIZACIÓN (con IGV, descuenta stock al toque, queda PENDIENTE de descontarse en una valorización real); si es PERSONAL DIRECTO genera un VALE DE ENTREGA (a costo, sin IGV, no descuenta stock todavía — eso lo hace CM-6 cuando se reporta lo ejecutado). Numeración correlativa por prefijo+fecha (`C&C-yymmdd-NN` / `VALE-yymmdd-NN`). Incluye "corregir": recuperar un documento ya emitido, editarlo y regenerarlo sin crear un número nuevo.

- **PDF real**, a pedido de Turco (no la alternativa "vista imprimible"): se agrega `barryvdh/laravel-dompdf` como dependencia nueva — **Turco tiene que correr `composer require barryvdh/laravel-dompdf:^3.1`** (no se pudo desde esta sesión, ver nota operativa de siempre). Sin publicar config: se usa el facade `Barryvdh\DomPDF\Facade\Pdf` directo, con auto-discovery de Laravel.
- Modelos: `Cotizacion` (con `es_vale` como *snapshot* del tipo de cuadrilla al emitir — si la cuadrilla cambia de tipo después, el documento ya emitido no cambia de naturaleza) + `CotizacionDetalle` (líneas, equivalente a la hoja oculta COTIZ_DETALLE). `Cotizacion::siguienteNumero()` y `Cotizacion::emitir()` (crear O corregir, un solo método) son la traducción directa de `SiguienteNumero`/`GenerarPDF` de la macro.
- **Precio unitario según tipo** (igual que columna F de COTIZACION): VALE usa `Material::precioVigente()`, COTIZACIÓN usa `Material::precioVentaSinIgv()` — se guarda como foto en `cotizacion_detalles.precio_unitario`, no se recalcula si el catálogo cambia después.
- **Diferencia a propósito con el Excel:** al corregir, la macro pisaba la observación con "MODIFICADA dd/mm/yyyy" (perdía cualquier nota anterior). Acá se ANEXA en vez de pisar — mismo espíritu que la regla de anulación de Control Interno ("no se elimina el dato"). El ESTADO tampoco se toca al corregir, igual que el Excel.
- Con esto se completó el placeholder de CM-1 `Material::salidas()` para el lado CONTRATISTA (descuenta ya, vía `cotizacion_detalles` con `es_vale=false`); el lado PERSONAL DIRECTO sigue en 0 hasta CM-6 (EJECUTADO). También se completaron los placeholders de CM-2 en `Cuadrilla`: `numRetiros()`, `totalValorizado()`, `pendienteDescuento()`.
- Formulario de página completa (no crudModal): tiene una lista dinámica de ítems (agregar/quitar filas) con Alpine, mismo patrón visual que el resto del módulo pero sin el modal genérico — la primera pantalla de este tipo en el módulo.
- Pantallas: `employee/materiales/cotizaciones` (listado con filtros + acción "marcar descontado en valorización") y `employee/materiales/cotizaciones/crear` / `.../{id}/editar` (formulario). Botón "Cotizaciones" en el header de Catálogo.
- **Pendiente para que corra:** `composer require barryvdh/laravel-dompdf:^3.1` Y `php artisan migrate` (2 tablas nuevas: `cotizaciones`, `cotizacion_detalles`).

### CM-5. Registro de cotizaciones/vales emitidos — ✅ implementado junto con CM-4 (18/09/2026)
Tabla automática de lo emitido en CM-4: fecha, número, cuadrilla, monto, IGV, total, estado (PENDIENTE / DESCONTADO EN VALORIZACIÓN / VALE - USO INTERNO), N° de valorización y observación. El estado PENDIENTE es lo que CM-8 (resumen) corta quincenalmente.

- No es una pantalla aparte: es el mismo listado `employee/materiales/cotizaciones` de CM-4 (la tabla `cotizaciones` ES el registro), con la acción "marcar descontado en valorización" (pide N° de valorización, solo disponible para CONTRATISTA + PENDIENTE).

### CM-6. Registro rápido + Ejecutado (solo personal directo) — ✅ implementado (19/09/2026)
Formulario de campo (REGISTRO RÁPIDO): cuadrilla (personal directo), fecha, tipo de trabajo, N° de suministro, movimiento (SALIDA/DEVOLUCIÓN) y cantidades sobre el catálogo — muestra cuánto tiene "en su poder" (por vales) antes de reportar. Al guardar, pasa cada línea a EJECUTADO (base de datos de lo realmente usado y lo devuelto, valorizado a costo). Los contratistas nunca pasan por acá.

- **Valorización EN VIVO, no una foto:** a diferencia de `cotizacion_detalles` (que sí guarda el precio del momento porque es un documento emitido), `Ejecutado::precioCosto()`/`total()` recalculan siempre con el precio vigente ACTUAL del material — igual que las columnas J/K de EJECUTADO en el Excel, que son fórmulas, no valores fijos.
- **"En su poder"** (`Cuadrilla::saldosEnPoder()`): vale recibido (convertido a la unidad reportada con `factor_metros_por_unidad`) menos TODO lo ejecutado, sea SALIDA o DEVOLUCIÓN — las dos reducen el saldo, por motivos distintos (una lo consume, la otra lo devuelve). Se calcula en bloque para todos los materiales de una cuadrilla con 2 consultas agregadas, no una por fila.
- Con esto se completó el último placeholder de CM-1: `Material::salidas()` ahora suma también el lado PERSONAL DIRECTO — `(SALIDA − DEVOLUCION) / factor_metros_por_unidad`, igual que CATALOGO!K del Excel. `Material::salidas()` queda 100% real, sin partes pendientes.
- No hay "corregir" como en CM-4: si algo se reportó mal, se elimina (soft delete, no se pierde el dato) y se vuelve a cargar — cada fila de Ejecutado es un movimiento atómico, no un documento con número.
- Formulario de página completa con lista dinámica de ítems (mismo patrón que CM-4). Se elige la cuadrilla primero (recarga la página por GET) para traer su saldo "en su poder" actualizado antes de cargar cantidades.
- Pantallas: `employee/materiales/ejecutados` (listado con filtros) y `.../ejecutados/crear` (formulario). Botón "Ejecutado" en el header de Catálogo.
- **Pendiente para que corra:** `php artisan migrate` (1 tabla nueva: `ejecutados`).

### CM-7. Entregas de herramientas — ✅ implementado (19/09/2026)
Registro de entrega/devolución de herramientas a las cuadrillas: cada fila es un movimiento (ENTREGA o DEVOLUCIÓN); de ahí sale el responsable actual y la ubicación (ALMACÉN / EN CAMPO / PERDIDA) que se ve en el catálogo de herramientas (CM-1).

- **Sin macro dedicada en el Excel** (a diferencia de COTIZACION/REGISTRO RAPIDO): ENTREGAS es una tabla de llenado manual, así que el UI es un CRUD simple con `crudModal` — mismo patrón que CM-1/CM-2/CM-3, no el formulario de página completa de CM-4/CM-6.
- **PERSONA (texto libre en el Excel) → `cuadrilla_id`** (FK a `cuadrillas`), misma convención del módulo: nunca texto libre, para poder cruzar reportes por cuadrilla.
- Con esto se completaron los últimos placeholders de CM-1: `Herramienta::responsableActual()`/`ubicacion()` ya no son stubs — replican `LOOKUP(2,1/(ENTREGAS!$B=código),...)` del Excel: se busca el ÚLTIMO movimiento (por fecha, luego id) de la herramienta; si es DEVOLUCION → vuelve a ALMACEN; si es ENTREGA → responsable = la cuadrilla de esa fila; sin movimientos → ALMACEN. `ubicacion()` deriva de eso (PERDIDA si el estado lo dice; si no, EN CAMPO cuando hay responsable, si no ALMACEN) — ya se veía en el catálogo de herramientas (CM-1), ahora con datos reales en vez de siempre "ALMACEN".
- Pantalla: `employee/materiales/entregas` (listado con filtros por herramienta/cuadrilla/búsqueda + modal crear/editar). Botón "Entregas" en el header de Catálogo.
- **Pendiente para que corra:** `php artisan migrate` (1 tabla nueva: `entregas`).

### CM-8. Resumen / Panel de control — ✅ implementado (19/09/2026)
Equivalente a RESUMEN (indicadores generales, filas 4-15) + la macro `GenerarResumenPDF` de `MacrosCYC.bas` (corte por cuadrilla, hoja oculta RESUMEN CUADRILLA). A diferencia de los indicadores generales (100% fórmulas de hoja, igual que PUNTAJE/INICIO en Control Interno), el corte por cuadrilla SÍ tenía macro — se descompiló con oletools en vez de adivinar, porque tiene reglas no obvias (ver abajo).

- **Sin tabla ni migración propia**: todo se calcula en vivo sobre lo que ya existe (CM-1 a CM-7), en `App\Services\ControlMaterialesResumen`. `generales()` se cachea 5 min (igual que `ControlInternoDashboard`) porque recorre todo el catálogo/ejecutados en PHP (`Material::precioVigente()`/`Ejecutado::total()` son cálculos en vivo, no columnas).
- **Indicadores generales** (`RESUMEN!C4:C15`, sin partir por Empresa CYC/CLB: el almacén es único, decisión de CM-1): valor del inventario = SUMPRODUCT(precio **vigente** × stock actual, no precio base, pese a la etiqueta "a costo" del Excel); ítems SIN STOCK/POR REPONER; alarmas de precio — dos conteos DISTINTOS, no el mismo dos veces (`Ingreso::alertaPrecio()` por fila con umbral vs. `Material::alertaPrecio()` por material sin umbral, ver CM-3); total valorizado a CONTRATISTAS = TODAS las cotizaciones "C&C-*" alguna vez emitidas (cualquier estado, no solo pendientes); ejecutado PERSONAL DIRECTO = neto SALIDA−DEVOLUCION (`Ejecutado::total()` ya trae el signo); cotizaciones PENDIENTES + su monto; herramientas en campo/perdidas. **Diferencia deliberada con el Excel:** "herramientas en campo" ahí es un conteo crudo (ENTREGA−DEVOLUCION en todo el historial, sin agrupar); acá se usa `Herramienta::ubicacion()` (CM-7, basado en el ÚLTIMO movimiento por herramienta) — más preciso si algún historial es irregular, mismo criterio que otras veces de preferir la fuente de verdad ya construida.
- **Corte por cuadrilla** (`corteCuadrilla()`), dos formatos completamente distintos según el tipo — traducción directa de `GenerarResumenPDF`:
  - **CONTRATISTA**: sus cotizaciones `PENDIENTE` con fecha ≤ la fecha de corte (**sin filtro "desde"**: el corte siempre es acumulado hasta una fecha, igual que el Excel — "desde" es solo una referencia visual, no filtra nada, fiel a la macro). Acción "cerrar corte": marca TODAS las listadas como `DESCONTADO EN VALORIZACION` con un N° de valorización, en un solo paso (a diferencia de la acción individual de CM-4/CM-5, que marca una cotización a la vez). Igual que `Cotizacion::emitir()` al corregir, la observación se **anexa**, nunca se pisa (el Excel sí pisaba la columna OBSERVACION con la nota del corte — se decidió no perder datos, mismo criterio que el resto del módulo).
  - **PERSONAL DIRECTO**: liquidación acumulada al `hasta`, material por material: retirado por vale (convertido a la unidad reportada con `factor_metros_por_unidad`, igual que `Cuadrilla::saldosEnPoder()`) − ejecutado − devuelto = saldo en su poder, valorizado al costo vigente actual. Saldo negativo (usó más de lo que retiró con vale) se muestra para revisar pero su VALOR es 0 (no cuenta a favor, igual que la macro). Solo se listan materiales con algún movimiento.
- **PDF** (`Barryvdh\DomPDF`, mismo patrón que CM-4): un solo template con las dos variantes (`resumen/pdf.blade.php`). **Simplificación deliberada** respecto al Excel: no se implementó la opción de la macro de anexar las cotizaciones completas como PDF adjunto (mergear varios PDFs) — se dejó fuera por complejidad/bajo valor frente al resto del roadmap; el PDF del corte trae el resumen solamente, con enlace a cada cotización individual desde la pantalla (no desde el PDF).
- Pantallas: `employee/materiales/resumen` (indicadores + tabla "Registro de cuadrillas y valorizado por persona", reutilizando `Cuadrilla::numRetiros()/totalValorizado()/pendienteDescuento()` de CM-4/CM-5 — no se repitió esa lógica) y `employee/materiales/resumen/{cuadrilla}/corte` (detalle + acción de cierre para contratistas + botón de PDF). Botón "Resumen" agregado al header de Catálogo.
- **Sin nada pendiente para que corra**: no agrega tablas ni columnas nuevas, así que no hace falta `php artisan migrate` esta vez.

### CM-9. Imprimibles — ✅ implementado (19/09/2026)
Equivalente a la hoja IMPRIMIBLES (leída con openpyxl campo por campo antes de programar: sin macro dedicada, es la única hoja del módulo que es puro layout de impresión). Tres piezas, sin tabla ni migración propia:

- **Acta de entrega de materiales** (`IMPRIMIBLES!A1:F30`): formato EN BLANCO a propósito — las filas (N°/código/descripción/cantidad/unidad) se llenan a mano en campo, antes de que exista la cotización/vale formal de CM-4 (por eso el Excel aclara que lo entregado a CONTRATISTA se cotiza después y lo de PERSONAL DIRECTO se sustenta con Ejecutado). Elegir una cuadrilla es opcional y solo pre-llena el encabezado (nombre, empresa(s), tipo).
- **Acta de entrega de herramientas** (`IMPRIMIBLES!A33:F57`): a diferencia de la de materiales, **sí** conviene pre-llenar filas cuando se eligen herramientas puntuales — cada una ya tiene identidad única en el catálogo (código, descripción, marca/serie, estado), no es una cantidad a decidir en campo como los materiales. Máximo 12 filas por página, igual que el Excel; si se eligen más, se reparten en varias páginas automáticamente (mejora sobre el Excel, que estaba fijo a 12).
- **Stickers de herramientas** (`IMPRIMIBLES!A61` en adelante, para papel adhesivo A4): traducción literal de la fórmula de cada etiqueta — "C&C PROENERG" + código + descripción + marca/modelo (si tiene) + "SERIE: ..." (si tiene) + "RESP.: " (el responsable actual de CM-7, o una línea en blanco si está en ALMACEN). 3 columnas × 7 filas = 21 por hoja, igual que el Excel. **Diferencia deliberada:** el Excel estaba limitado a 100 herramientas fijas repartidas en 5 hojas A4 fijas (`HOJA 1..5`, `21/21/21/21/16` etiquetas); acá se genera para las herramientas que se elijan (o todas si no se marca ninguna) y dompdf pagina solo, sin ese límite de 100.
- Controlador `ImprimibleController` (sin service propio: no hay cálculo de negocio, solo armar los PDFs) + pantalla `employee/materiales/imprimibles` (los tres formularios de descarga) + botón "Imprimibles" en el header de Catálogo.
- **Sin nada pendiente para que corra**: no agrega tablas ni columnas nuevas.

### CM-10. Inventario físico — ✅ implementado (22/09/2026)
Equivalente a la hoja "INVENTARIO FISICO" (leída con openpyxl fila por fila antes de programar: sin macro, es una hoja de trabajo con fórmulas simples). A diferencia del Excel, que BORRA los conteos al cerrar el mes (no queda historial salvo la copia completa del archivo), Turco pidió que el sistema SÍ guarde historial: cada conteo guardado es una fila en `inventarios_fisicos`, con su detalle en `inventario_fisico_detalles` — se puede consultar cualquier inventario pasado.

- **Dos secciones dentro de la misma hoja**, confirmado leyendo las fórmulas exactas: MATERIALES (INVENTARIO FISICO!A6:G162, filas = CATALOGO 5-161) y HERRAMIENTAS (A166:G265, filas = CATALOGO 165-264, con el título "HERRAMIENTAS (verificar presencia física...)" en A164). Son conceptualmente distintas:
  - **Materiales**: stock sistema = `Material::stockActual()`; conteo físico = cantidad real contada (número); diferencia = conteo − stock; observación/ubicación es texto libre a mano.
  - **Herramientas**: stock sistema = 1 si el sistema espera que esté en ALMACEN, 0 si espera EN CAMPO (`Herramienta::ubicacion()`, igual que `CATALOGO!J164+ = IF(CATALOGO!$J="ALMACEN",1,0)`); conteo físico = 1/0 según si efectivamente se encontró en el almacén; observación/ubicación es AUTOMÁTICA (la ubicación que el sistema tenía registrada), no manual como en materiales.
- **Solo se guarda fila para lo que se contó**: si el conteo físico de un ítem quedó vacío, no se crea `InventarioFisicoDetalle` para él (igual que el Excel, que deja la columna DIFERENCIA en blanco si CONTEO FISICO está vacío).
- `codigo`/`descripcion`/`unidad` se guardan como FOTO en cada detalle (no solo el FK), para que el historial de un inventario viejo se siga leyendo igual aunque el material/herramienta cambie de nombre o se elimine después — mismo criterio de "nunca perder el dato" del resto del módulo.
- Formulario de página completa, SIN Alpine/JS nuevo (a diferencia de CM-4/CM-6): la lista de ítems es fija (todo el catálogo actual, no una lista que el usuario arma), así que es un `@foreach` plano — no hace falta `npm run build` para este submódulo.
- Encabezado (INVENTARIO FISICO!B3/E3: fecha + "REALIZADO POR") + pie de firma (B268/B270: Almacenero/Supervisor del día) se guardan como campos de texto — no son firmas digitales reales, el papel impreso sigue siendo el que se firma a mano si Turco lo necesita así.
- Pantallas: `employee/materiales/inventario-fisico` (listado con historial, resalta cuántos ítems tuvieron descuadre) → `.../crear` (formulario) → `.../{id}` (detalle de un conteo guardado, filas con diferencia resaltadas en rojo).
- **Pendiente para que corra:** `php artisan migrate` (2 tablas nuevas: `inventarios_fisicos`, `inventario_fisico_detalles`).

### CM-11. Cierre de mes — ✅ implementado (22/09/2026)
Traducción de la macro `CerrarMes` de `MacrosCYC.bas` (descompilada con oletools antes de programar, en vez de adivinar). El Excel original, al cerrar: (1) guarda una COPIA completa del archivo como histórico, (2) pasa STOCK ACTUAL → STOCK INICIAL, (3) consolida el PRECIO VIGENTE como PRECIO BASE (solo si sube), (4) BORRA por completo Ingresos, Ejecutado, TODOS los vales de personal directo, y las cotizaciones de contratista ya DESCONTADAS (solo sobreviven las PENDIENTES), (5) conserva catálogo, cuadrillas y herramientas.

- **Diferencia deliberada acordada con Turco** (22/09/2026, mismo criterio de "nunca perder el dato" del resto del módulo): en vez de BORRAR, el sistema ARCHIVA — cada fila que la macro original borraría se marca con `cierre_materiales_id` (nueva columna en `ingresos`, `ejecutados` y `cotizaciones`) y se le hace soft-delete. Como TODOS los cálculos del módulo (`Material::salidas()`, `Cuadrilla::saldosEnPoder()`, `Cuadrilla::numRetiros()`, etc.) ya usan el scope estándar de soft-delete de Eloquent, el kardex y los saldos del mes nuevo arrancan limpios automáticamente — no hizo falta tocar ningún accessor existente.
- **No se toca ninguna `Cotizacion` PENDIENTE**: a diferencia de la macro (que las conservaba pero tenía que "compensar" el stock porque limpiaba y reconstruía filas), acá simplemente no se archivan — siguen contando en `Material::salidas()` exactamente igual que antes del cierre. Sin compensación, más simple y sin riesgo de descuadre.
- **Snapshot antes de archivar**: cada cierre guarda en `cierres_materiales.resumen` (json) los indicadores generales (`ControlMaterialesResumen::generales()`), el registro de cuadrillas, y el catálogo completo de materiales (stock/precio antes y después) y herramientas — así el detalle de ESE mes queda disponible para siempre, aunque después se archiven sus movimientos.
- Servicio `App\Services\ControlMaterialesCierre::cerrar($etiqueta, $fechaCierre, $employeeId)`, dentro de una transacción. `App\Models\CierreMaterial` (etiqueta única, no se puede cerrar el mismo mes dos veces). Validación: la fecha de corte de un cierre nuevo debe ser posterior a la del último cierre.
- Pantallas: `employee/materiales/cierres` (indicadores actuales antes de cerrar + formulario de cierre con casilla de confirmación + historial de cierres pasados) → `.../{id}` (detalle del snapshot de un cierre).
- **Pendiente para que corra:** `php artisan migrate` (tabla nueva `cierres_materiales` + columna `cierre_materiales_id` en `ingresos`/`ejecutados`/`cotizaciones`).

## 4. Orden sugerido para ir "por partes"

1. ~~**CM-1** Parámetros + Catálogo (materiales y herramientas)~~ — hecho el 18/09/2026 (falta `migrate`), la base de todo lo demás.
2. ~~**CM-2** Cuadrillas~~ — hecho el 18/09/2026 (falta `migrate`), necesario antes de poder emitir nada.
3. ~~**CM-3** Ingresos~~ — hecho el 18/09/2026 (falta `migrate`), para tener kardex real antes de probar salidas.
4. ~~**CM-4** Cotización/Vale + **CM-5** Registro de emitidos~~ — hecho el 18/09/2026 (falta `composer require` + `migrate`).
5. ~~**CM-6** Registro rápido + Ejecutado~~ — hecho el 19/09/2026 (falta `migrate`).
6. ~~**CM-7** Entregas de herramientas~~ — hecho el 19/09/2026 (falta `migrate`).
7. ~~**CM-8** Resumen/Panel~~ — hecho el 19/09/2026, sin migración nueva.
8. ~~**CM-9** Imprimibles~~ — hecho el 19/09/2026, sin migración nueva.
9. ~~**CM-10** Inventario físico~~ — hecho el 22/09/2026 (falta `migrate`).
10. ~~**CM-11** Cierre de mes~~ — hecho el 22/09/2026 (falta `migrate`). Con esto, el roadmap completo de Control de Materiales (CM-1 a CM-11) queda implementado.

## 5. Puntos a confirmar con Turco antes/durante la implementación

- ~~Exactamente en qué momento se asocia cada cotización/cuadrilla a una `Empresa` (CYC/CLB)~~ — **resuelto en CM-2** (18/09/2026): relación muchos-a-muchos `Cuadrilla`↔`Empresa`, una cuadrilla puede estar ligada a una o ambas empresas. Sigue abierto si además hace falta asociar la `Empresa` a nivel de cada cotización individual (CM-4) — se decide cuando lleguemos ahí.
- Generación de PDF: el proyecto no tiene ninguna librería de PDF instalada todavía (Control Interno no generó ningún PDF) — se usaría `barryvdh/laravel-dompdf` (estándar en Laravel), salvo que Turco prefiera otra.
- ~~CM-11 (Cierre de mes) es una operación destructiva por diseño~~ — resuelto el 22/09/2026: Turco pidió que en vez de borrar (como el Excel), el sistema ARCHIVE (soft-delete + referencia al cierre), conservando todo el detalle para siempre. Ver CM-11 arriba y App\Services\ControlMaterialesCierre.

---
*Generado a partir del análisis de `CONTROL_MATERIALES_CC_08-2026 rev 02.xlsm` (hojas INICIO, CATALOGO, INGRESOS, REGISTRO RAPIDO, EJECUTADO, COTIZACION, COTIZACIONES, ENTREGAS, IMPRIMIBLES, INVENTARIO FISICO, RESUMEN, RESUMEN CUADRILLA, COTIZ_DETALLE, y macro MacrosCYC.bas) el 18/09/2026.*
