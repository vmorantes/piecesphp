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
| 3a | **⚠ Búsquedas concatenadas** | Inyección SQL confirmada por lectura en dos rutas PÚBLICAS (`publications-ajax-all` y `built-in-banner-ajax-all`, parámetro `title`), y el mismo patrón tras sesión en News, Organizations y GeoJSON. `PageQuery` no admite valores ligados | `docs/pendientes.md`, «Hallazgos del lote 3, bloque 1» (H1 y H2 de `#027`) | **Urgente**: va antes del bloque 2 de subidas (`#028`). Nace el 2026-09-14. Ningún censo lo veía: la traza no cruza de método |
| 3 | **Subidas** | `UPLOAD_DIR` sin puerta y `ProtectFileMiddleware`, y los datos de los módulos privados | `20` §7, «UPLOADS»; `docs/pendientes.md`, «Subidas» | 2 bloques. El PO pidió auditar los **datos**, no solo la puerta |
| 4 | **`escapeString`** | Depende de un `sql_mode` que nadie fija | `20` §7, «EL HALLAZGO MÁS PROFUNDO» | 1 bloque |
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
- «Perfeccionar geovisor»: un recordatorio suyo sin contenido. Qué es el geovisor lo tiene que
  decir él (`docs/pendientes.md`).
