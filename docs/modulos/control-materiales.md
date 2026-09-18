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

### CM-6. Registro rápido + Ejecutado (solo personal directo)
Formulario de campo (REGISTRO RÁPIDO): técnico, fecha, tipo de trabajo, N° de suministro, movimiento (SALIDA/DEVOLUCIÓN) y cantidades sobre el catálogo — muestra cuánto tiene "en su poder" (por vales) antes de reportar. Al guardar, pasa cada línea a EJECUTADO (base de datos de lo realmente usado y lo devuelto, valorizado a costo). Los contratistas nunca pasan por acá.

### CM-7. Entregas de herramientas
Registro de entrega/devolución de herramientas a las cuadrillas: cada fila es un movimiento (ENTREGA o DEVOLUCIÓN); de ahí sale el responsable actual y la ubicación (ALMACÉN / EN CAMPO / PERDIDA) que se ve en el catálogo de herramientas (CM-1).

### CM-8. Resumen / Panel de control
Equivalente a RESUMEN: indicadores generales (valor del inventario, ítems sin stock/por reponer, alarmas de precio, total valorizado a contratistas, ejecutado por personal directo, cotizaciones pendientes, herramientas en campo/perdidas) + el corte por cuadrilla:
- **Contratista**: lista de cotizaciones PENDIENTES hasta una fecha de corte, con opción de marcarlas como DESCONTADO EN VALORIZACIÓN (con su N° de valorización).
- **Personal directo**: liquidación automática por material (retirado por vales − ejecutado − devuelto = saldo en su poder, valorizado a costo; saldo negativo no cuenta a favor).
Genera un PDF del corte (con las cotizaciones pendientes anexadas, si se quiere, para contratistas).

### CM-9. Imprimibles
Actas de entrega de materiales (formato en blanco para firmar en campo) y stickers de herramientas (código + datos, varios por hoja A4) — son documentos de impresión, no requieren tanto backend como una vista lista para imprimir/exportar a PDF.

### CM-10. Inventario físico
Cierre de conteo: lista de materiales con stock del sistema, conteo físico (a llenar), diferencia calculada, observación/ubicación, y firmas de almacenero/supervisor.

### CM-11. Cierre de mes (opcional, acción manual)
Archiva un snapshot del estado del módulo, migra stock final → inicial, consolida el precio base con el vigente, limpia INGRESOS/EJECUTADO/movimientos, y conserva cotizaciones PENDIENTES + catálogo + cuadrillas + herramientas. Es la operación más delicada (irreversible salvo por el archivo histórico) — se deja para el final del roadmap, cuando todo lo demás ya esté probado.

## 4. Orden sugerido para ir "por partes"

1. ~~**CM-1** Parámetros + Catálogo (materiales y herramientas)~~ — hecho el 18/09/2026 (falta `migrate`), la base de todo lo demás.
2. ~~**CM-2** Cuadrillas~~ — hecho el 18/09/2026 (falta `migrate`), necesario antes de poder emitir nada.
3. ~~**CM-3** Ingresos~~ — hecho el 18/09/2026 (falta `migrate`), para tener kardex real antes de probar salidas.
4. ~~**CM-4** Cotización/Vale + **CM-5** Registro de emitidos~~ — hecho el 18/09/2026 (falta `composer require` + `migrate`).
5. **CM-6** Registro rápido + Ejecutado.
6. **CM-7** Entregas de herramientas.
7. **CM-8** Resumen/Panel — necesita todo lo anterior para tener datos que resumir.
8. **CM-9** Imprimibles + **CM-10** Inventario físico (documentos, bajo riesgo).
9. **CM-11** Cierre de mes — al final, cuando todo esté validado en uso real.

## 5. Puntos a confirmar con Turco antes/durante la implementación

- ~~Exactamente en qué momento se asocia cada cotización/cuadrilla a una `Empresa` (CYC/CLB)~~ — **resuelto en CM-2** (18/09/2026): relación muchos-a-muchos `Cuadrilla`↔`Empresa`, una cuadrilla puede estar ligada a una o ambas empresas. Sigue abierto si además hace falta asociar la `Empresa` a nivel de cada cotización individual (CM-4) — se decide cuando lleguemos ahí.
- Generación de PDF: el proyecto no tiene ninguna librería de PDF instalada todavía (Control Interno no generó ningún PDF) — se usaría `barryvdh/laravel-dompdf` (estándar en Laravel), salvo que Turco prefiera otra.
- CM-11 (Cierre de mes) es una operación destructiva por diseño (limpia movimientos) — su implementación exacta se conversa con Turco recién cuando lleguemos ahí, con los demás submódulos ya probados en uso real.

---
*Generado a partir del análisis de `CONTROL_MATERIALES_CC_08-2026 rev 02.xlsm` (hojas INICIO, CATALOGO, INGRESOS, REGISTRO RAPIDO, EJECUTADO, COTIZACION, COTIZACIONES, ENTREGAS, IMPRIMIBLES, INVENTARIO FISICO, RESUMEN, RESUMEN CUADRILLA, COTIZ_DETALLE, y macro MacrosCYC.bas) el 18/09/2026.*
