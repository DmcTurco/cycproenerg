# Control General (Internas · TC · Habilitación)

Fuente: `Control General CLB - C&C.xlsm` (Excel que usa la usuaria de Internas, TC, Habilitación y Almacén, versión del 05/11/2026) y sus macros `ActualizarControl` / `MoverMarcadas`.
Estado: **implementado (06/11/2026)**. Reemplaza al antiguo módulo Control Interno (Excel "CONTROL INTERNAS - CYC CLB v5.4"), que se eliminó por completo. Falta correrlo en el entorno (ver sección 9).

## 1. Contexto y decisión de diseño
- La usuaria trabaja 4 áreas: Internas, TC, Habilitación y Almacén (Almacén = módulo Materiales ya existente).
- El sistema importa el Excel del portal y esa carga es la base de todos los datos (`ProcessExcelJob`).
- Tabla `control_generales` (1 fila por solicitud = "en qué hoja vive"). Control Interno (fases GENERAL/CONSTRUIDO/TC/PEND_ANULACION, semáforo, días hábiles, feriados, IND2/puntaje, resumen trimestral y su comando diario) se eliminó: la usuaria no lo usaba. Materiales no se tocó.

## 2. Etapas (= hojas del Excel)
| Etapa (`etapa`) | Entra | Lo llena ella (celdas amarillas) | Sale |
|---|---|---|---|
| `APROBADA` (1-Aprobadas) | Estado 02 del portal | ¿Construida?, asesor, cuadrilla, Reingresar/Anular, observación | Construida = Sí → TC; Anular/Reingresar → Anulación; portal 06/08 → TC; TC Concluida → Habilitación |
| `TC_CONSTRUIDA` (2-TC Construidas) | Estados 06/08 sin TC concluida | Resultado TC, observación (pone F. obs. sola), contratista, cuadrilla | TC Concluida → Habilitación |
| `HABILITACION` (3-Habilitación) | TC concluida (y estado ≠ 02) | Fecha programación, técnico, ¿observado?, ¿habilitado? | Habilitado = Sí → Habilitada; portal vuelve a 02 → Aprobada |
| `ANULACION` (4-Anulaciones-Reingresos) | Marcadas en Aprobadas | Acción, estado del trámite (Pendiente/En trámite/Reingresado/Anulado) | Quitar la acción → vuelve a Aprobada |
| `HABILITADA` (5-Habilitadas) | Habilitadas en 3 | (F. habilitación = día de la marca) | Quitar "Habilitado" → vuelve a Habilitación (mejora: en el Excel no había vuelta) |

Calculados: días desde aprobación y control de plazo en Aprobadas (`Sin fecha | Avanzó en portal | EN DESFASE | En plazo`, límite en Parámetros, 20 días calendario); situación en Habilitación (`Por programar | Programado | Observado`); duplicado por suministro en Habilitación.

Las marcas mueven la fila **al guardar** (en el Excel pasaba al correr la macro). Las que mueven de etapa piden confirmación.

## 3. Reglas de la carga del portal (`ControlGeneralClasificador::aplicarPortal`)
- Solo Provincia = LIMA o CALLAO (parámetro del módulo). Las demás ni entran ni se actualizan.
- Filas con Anulada o Rechazada = Sí en el portal: ni entran ni se actualizan (en el Excel de la usuaria no hay ninguna; sin este filtro Aprobadas se llenaba con ~1.900 solicitudes viejas).
- Empresa: por `empresas.codigo` (CYC se muestra como "C&C"); sin código, por nombre ("C & C"/"C&C" = C&C, si no CLB).
- Fecha de aprobación = Fecha de suscripción de contrato; si falta, Fecha de entrega de documentos al Concesionario.
- Observado = estado del portal empieza con `08.1` o `10.1`.
- Solicitud nueva: TC Concluida y estado ≠ 02 → Habilitación; estado 06/08 → TC Construidas; estado 02 → Aprobadas; otro estado → no entra.
- Ya existente: Anulación y Habilitada no cambian de etapa. Habilitación vuelve a Aprobada si el portal regresa a 02. Aprobada pasa a TC si el portal reporta 06/08, y a Habilitación si la TC ya está Concluida.

### "Manda el portal" (MergeVal)
En resultado de TC y observado se guarda el último valor del portal (`resultado_tc_portal`, `observado_portal`). En cada carga: si el portal trae un valor **distinto** al anterior, gana el portal; si repite el mismo, se respeta lo que ella marcó. Lo que solo escribe ella (observación, asesor, cuadrilla, técnico, contratista, acción, trámite, fechas) nunca se pisa. Confirmado con la usuaria: "me guío del portal, porque las observaciones se mantienen".

## 4. Pantallas
- `employee/control-general` (`ControlGeneralController@index`): pestañas por etapa con contador, empresa (Todas / CLB / C&C), buscador (suministro, N° solicitud, cliente, DNI), filtros rápidos con contador por pestaña, edición en la tabla (se guarda sola), sugerencias de nombres (asesores y personal de campo del sistema + nombres ya escritos; también acepta nombres nuevos, como el Excel), botones de llamar / mapa / detalle, y casilla "Más datos del cliente" (DNI, celular, dirección, tipo, FISE, puntos, estado del portal; se recuerda en el navegador).
- `employee/control-general/resumen`: la hoja Resumen (conteos por etapa y empresa CLB / C&C / Total).
- `employee/control-general/parametros`: días límite desde la aprobación y provincias (tabla `parametros_control_generales`, modelo `ParametroControlGeneral`).
- Detalle de solicitud: botón "Ver en Control General" (abre la pestaña donde está la solicitud).
- JS: `resources/js/control-general.js` (componente Alpine `cgFila`).

## 5. Datos
- Tabla `control_generales` (migración `2026_11_06_100000_create_control_generales_table`). Asesor, cuadrilla, técnico y contratista son **texto** (como el Excel: se elige de la lista o se escribe uno nuevo).
- Modelo `App\Models\ControlGeneral`, relación `Solicitud::controlGeneral()`.
- La bitácora de cada carga (`logs.resumen_carga`) guarda nuevas por etapa, movidas, actualizadas y filas omitidas.
- `config/const.php`: `empresas.codigos_por_ruc` (CYC/CLB) y `categorias_proyecto` (antes bajo `control_interno`).

## 6. Importar el Excel actual de la usuaria
`php artisan control-general:importar-excel "ruta\Control General CLB - C&C.xlsm"` muestra qué haría; con `--confirmar` guarda. Lee cada hoja por las claves de la fila 3 (igual que la macro), toma lo escrito en las filas "vivas" (fórmulas), la foto del portal de `_base`, y aplica las marcas pendientes (Construida, Anular, Habilitado = Sí) como lo haría la macro. Se puede repetir sin duplicar. Las solicitudes que no están en el sistema se listan: hay que cargar antes el Excel del portal que las tenga (CLB y C&C).

## 7. Roles
Permisos `control_general.ver` / `.editar` / `.parametros` (`config/permisos.php`). El rol **"Internas, TC, Habilitación y Almacén"** lo crea `RolInternasAlmacenSeeder` (lo llama `DatabaseSeeder`, después de `RolesPermisosSeeder`): clientes (ver y cargar Excel), Control General (ver y editar, sin parámetros) y Materiales sin eliminar, sin parámetros y sin cierre de mes. El seeder es la fuente de verdad: al volver a correrlo, el rol queda exactamente con esos permisos (`php artisan db:seed --class=RolInternasAlmacenSeeder`).

## 8. Decisiones confirmadas por la usuaria (06/11/2026)
- Trabaja solo Internas, TC, Habilitación y Almacén.
- Le basta el plazo de 20 días: no se construye semáforo ni IND2 para ella. Filtro "EN DESFASE" en Aprobadas.
- Manda el portal, sin perder sus observaciones (sección 3).
- Filtro por empresa en todas las listas; carga el Excel del portal a diario.
- Pendiente: qué columnas del portal mira a diario (hoy: las de la sección 4 + "Más datos").

## 9. Para que corra en tu entorno
1. `php artisan migrate:fresh --seed` (se borraron migraciones de Control Interno y se cambió la de `logs`)
2. `npm run build`
3. Cargar el Excel del portal del día (CLB y C&C) desde Carga Excel.
4. `php artisan control-general:importar-excel "...\Control General CLB - C&C.xlsm"` → revisar → repetir con `--confirmar`.
5. Asignarle el rol a la usuaria en Usuarios.

## 10. Posibles mejoras (no hechas)
- Pasar sola a Habilitadas una solicitud que el portal ya reporta en estado 12 (Instalación habilitada). Hoy, igual que la macro, queda en Habilitación hasta que ella marca Habilitado.
- Exportar cada pestaña a Excel.
