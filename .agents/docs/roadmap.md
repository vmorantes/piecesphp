# Mapa hasta la MAJOR

Qué falta para publicar `v8.0.0`, en orden. **Ordena y apunta; no describe**: cada lote tiene
una línea y el puntero a donde está descrito (ADR 0002). Lo cerrado sale; su historia queda en
la bitácora.

**Procedencia.** Es el mapa que el arquitecto anterior dio en el chat el 2026-09-13 y que nunca
llegó a un archivo, con las correcciones que el PO le hizo ese mismo día y lo que recuperó el
cruce de sus 466 turnos (2026-09-14, bitácora 0001). **No está re-medido**: cada lote se mide al
instruirlo (LEY 17). Las cifras son del 2026-09-13 y vienen de ese mapa.

**Criterio de alcance del PO**: lo que **corrige** una trampa entra en la campaña; lo que
**extiende** una capacidad va después de la MAJOR (20 §7). Y la MAJOR espera a la campaña
entera: *«la major depende de que terminemos toda la campaña, toda es toda»* (2026-08-29).

## Pendiente, en orden

| # | Lote | En una línea | Dónde está descrito | Notas |
| --: | :-- | :-- | :-- | :-- |
| 3b | **⚠ Traducciones dinámicas** | Cualquier usuario con sesión reescribe cualquier traducción, y las vistas la imprimen sin escapar: control de acceso roto y XSS almacenado | `docs/pendientes.md`, «H1 de `#041`» (P28) | **Urgente.** Nace el 2026-09-15. La vía SQL ya está cerrada (`c250c2ee`). Espera P28: quién puede editar. Predeterminado: solo administración, con lista blanca de grupos e idiomas y el texto sin HTML |
| 4 | **`escapeString`** | Depende de un `sql_mode` que nadie fija | `20` §7, «EL HALLAZGO MÁS PROFUNDO»; ADR 0009; bitácora 0009 | **Bloque 1 hecho** (`#037`): 17 de 22 usos, por marcador. **Queda el bloque 2**: las etiquetas de `OrganizationMapper:664-667` (del servidor; pasarlas a PHP o un literal hexadecimal), la búsqueda de `DataTablesHelper:1324` (migrar los 14 llamadores de `process()`: **regla de los diez**, el PO ve el plan antes) y, al final, `@deprecated` |
| 4b | **Rendimiento de los archivos protegidos** | Desde el lote 3, cada archivo de una carpeta protegida arranca PHP, y los de Publications consultan la base sin sesión. Diseño elegido con el PO (2026-09-15): el `.htaccess` de protección se genera **por registro**. La carpeta de un registro visible no lo lleva, y Apache la sirve directamente; la de uno no visible, sí. Se regenera al crear, editar o borrar, y con un cronjob para lo que cambia por fecha. El validador sigue como red | `docs/pendientes.md`, «Rendimiento de los archivos protegidos» | Encargo del PO: lo que dé mejor rendimiento y sea comprensible para quien use el framework. Arquetipo en Publications. **Rediseño pendiente por HestiaCP**: Nginx no lee el `.htaccess`, así que decide la UBICACIÓN del archivo (privado fuera de la raíz web). Todo va reunido en su propia carpeta de `Core/` (PO, 2026-09-15). Detalle y dudas abiertas en pendientes |
| 5 | **OTP** | Cerrojo por usuario e IP, respuesta uniforme, documentado | `20` §7, «`generate-otp` — la asimetría» | Sin pasar a POST: lo consumen apps headless |
| 5b | **Tokens genéricos** | La URL de `GenericTokenController` lleva un `id` enumerable; la ruta es pública y `entryPoint()` borra filas de cualquier tipo. Las claves JWT constantes pasan a `app_key` | `docs/pendientes.md` (P22) | Nace el 2026-09-14 de P22, resuelto por delegación. Corrige una trampa, así que entra en la campaña. Primero se confirma la SOSPECHA con una prueba sin base de datos |
| 6 | **E3 / experience** | El borrado no terminó: tablas, JS y SCSS residuales | `20` §7, «EL BORRADO NO FUE FIABLE» | LEY 28 |
| 7 | **Avatares y `see-more`** | Muere el creador de avatares; `see-more` se restaura | `20` §7, «El lote del CREADOR DE AVATARES» y «`see-more`: DAÑADO» | `see-more`, decidido por el PO el 2026-08-31 |
| 7b | **E4 · lote 2 de guardas** | Pruebas de rechazo para las ~22 guardas con forma de fallo abierto. Quedan 169 guardas sin prueba de rechazo | `20` §7, «E4 arrancó»; `18` T146–T147 | **Faltaba en el mapa del 2026-09-13**; lo exige la escalera (`18` T34) |
| 7c | **E4 · ventana de correo** | Pruebas de los 10 envíos de correo, en tres capas: composición sin red, sumidero SMTP local y entrega real revisada a mano | `18` T7 | **Faltaba en el mapa del 2026-09-13.** Sin empezar. La capa 2 pide Mailpit o MailHog, que son dependencia y servicio: los autoriza el PO |
| 8 | **E5 · `DataImportExportUtility`** | Consolidación y arquetipo, absorbiendo `Importers` | `20` §7, «Abierto, sin decidir»; `18`, «La fusión … es una REFACTORIZACIÓN PLANIFICADA»; `docs/pendientes.md` (cruce) | `Importers` **no** se borra. Se unifica y se optimiza como base de la que se parte, con ejemplos que funcionan (PO, 2026-09-14). La dirección la dijo el PO el 2026-08-29: hacia `DataImportExportUtility`. El 18 dice la contraria |
| 9 | **E6 · documentación** | `source-docs/` completo; `16-frontend-arquitectura.md`; la documentación de la API (`source-docs/api/`) y Postman; los 9 selectores; la protección de módulos en la guía; los seis módulos sin punto de extensión (P4); el cierre de PHPStan en dos listas; `processFromQuery` documentado antes de que muera el 18 | `20` §7, «Abierto, sin decidir»; `docs/pendientes.md` (cruce) | 2-3 bloques |
| 10 | **Residuos con nombre** | Barrido final | Solo en el mapa del 2026-09-13 | 1-2 bloques. Incluye `SOLO_PROPIAS` (P2) |
| 11 | **Usuarios a `classes/`** | El núcleo sale de la disposición vieja | `docs/pendientes.md` §1 | **Rompe**. «Al final» (PO, 2026-09-13). Sin medir |
| 12 | **Renombrado de columnas** | 8 columnas y 247 referencias, con `column-renames.json` | `20` §7, «EL RENOMBRADO DE COLUMNAS» | **Rompe**. Con la puerta de columnas (`docs/pendientes.md`, cruce) |
| 13 | **Borrado del registro** | El 18 se disuelve según su cláusula | `18`, cabecera | Antes, lo que solo vive en él sube a los documentos numerados |
| 14 | **La MAJOR** | `v8.0.0`, `master` y `last-stable` | `12-convenciones.md`, convención de etiquetas | **Punto serio**: se habla con el PO antes |

## Después de la MAJOR

`roadmap-posterior/` (16 documentos) y la sección «Después de la MAJOR» del cruce en
`pendientes.md`. No se copian aquí.

## Fuera del repositorio

- La evaluación personal que pidió el PO: no entra en el registro, por su orden del 2026-08-29.
  Incluye explicarle sus instrumentos (`verify-integrity` y compañía).
- «Perfeccionar geovisor»: ya se sabe cuál es (`/var/www/html/espacio-publico/espacio-publico-backend`,
  nombrado por el PO el 2026-09-14). Falta que diga qué quiere perfeccionar
  (`docs/pendientes.md`).
