# Mapa hasta la MAJOR

Qué falta para publicar `v8.0.0`, en orden. **Ordena y apunta; no describe**: cada lote tiene
una línea y el puntero a donde está descrito (ADR 0002). Lo cerrado sale; su historia queda en
la bitácora.

**Procedencia.** Es el mapa que el arquitecto anterior dio en el chat el 2026-09-13 y que nunca
llegó a un archivo, con las correcciones que el PO le hizo ese mismo día y lo que recuperó el
cruce de sus 466 turnos (2026-09-14, bitácora 0001). **No está re-medido**: cada lote se mide al
instruirlo (LEY 17).

**Criterio de alcance del PO**: lo que **corrige** una trampa entra en la campaña; lo que
**extiende** una capacidad va después de la MAJOR (20 §7). Y la MAJOR espera a la campaña
entera: *«la major depende de que terminemos toda la campaña, toda es toda»* (2026-08-29).

> **Poda del 2026-09-15**, por orden del PO al cerrar el tirón de 20 rondas. Salieron de esta
> tabla, cerrados: **3b** (traducciones), **4** (`escapeString`), **4b** (archivos protegidos:
> cron, cabeceras y el sufijo `.protected` con su migración), **4c** (aprobaciones y P25),
> **5** (OTP), **5b** (tokens y `app_key`), **6** (E3) y **7b** (las 19 guardas).
> Su historia está en `pendientes.md`, ronda a ronda, y en el `CHANGELOG` (rupturas 18 a 25).

## Pendiente, en orden

| # | Lote | En una línea | Dónde está descrito | Notas |
| --: | :-- | :-- | :-- | :-- |
| 4b-4 | **FileManager y el sufijo** | elFinder lista y renombra los nombres reales; hoy solo se bloquea el renombrado de lo protegido. Falta la vista previa por contenido y el reparto de raíces por módulo (P26) | `docs/pendientes.md`, «Rendimiento de los archivos protegidos» | Resto del 4b, que por lo demás está cerrado |
| 8 | **E5 · `DataImportExportUtility`** | Consolidación y arquetipo, absorbiendo `Importers` | `20` §7, «Abierto, sin decidir»; `18`, «La fusión … es una REFACTORIZACIÓN PLANIFICADA»; `docs/pendientes.md` (cruce) | `Importers` **no** se borra. Se unifica y se optimiza como base de la que se parte, con ejemplos que funcionan (PO, 2026-09-14). La dirección la dijo el PO el 2026-08-29: hacia `DataImportExportUtility`. El 18 dice la contraria |
| 9 | **E6 · documentación** | `source-docs/` completo; `16-frontend-arquitectura.md`; la documentación de la API (`source-docs/api/`) y Postman; los 9 selectores; la protección de módulos en la guía; los seis módulos sin punto de extensión (P4); el cierre de PHPStan en dos listas; `processFromQuery` documentado antes de que muera el 18 | `20` §7, «Abierto, sin decidir»; `docs/pendientes.md` (cruce) | 2-3 bloques. **Añadido por el PO el 2026-09-15**: las cuatro capas de documentación, el árbol del proyecto para quien desarrolla, el catálogo de componentes de Fomantic del panel, y el manifiesto de capas A/B/C con su comprobación. **Añadido por el PO el 2026-09-16 (P32)**: la documentación de los cuatro paquetes, en español (`docs/pendientes.md`, punto 22)  **Plan en ocho rondas (9.1 a 9.8) en `docs/pendientes.md`, punto 33; en curso.** |
| 10 | **Residuos con nombre** | Barrido final | Solo en el mapa del 2026-09-13; `docs/pendientes.md` acumula los del tirón | 1-2 bloques. Incluye `SOLO_PROPIAS` (P2), el skip muerto de Rector, `verify_expected_file()` sin llamadores, el `2777` de `server-delegated` y los contratos congelados H-AF a H-AH |
| 11 | **Usuarios a `classes/`** | El núcleo sale de la disposición vieja | `docs/pendientes.md` §1 | **Rompe**. «Al final» (PO, 2026-09-13). Sin medir |
| 12 | **Renombrado de columnas** | 8 columnas y 247 referencias, con `column-renames.json` | `20` §7, «EL RENOMBRADO DE COLUMNAS» | **Rompe**. Con la puerta de columnas (`docs/pendientes.md`, cruce) |
| 13 | **Borrado del registro** | El 18 se disuelve según su cláusula | `18`, cabecera | Antes, lo que solo vive en él sube a los documentos numerados |
| 13b | **Documentación para personas y raíz limpia** | Fuentes en `source-docs/` y builds en `docs/<ámbito>/`; desarrolladores, mantenedores e integradores; `bin/docs-build`; la raíz sin basura | `docs/pendientes.md`, punto 41 | Después del 13 porque mantenedores describe el estado final. Las guías de terceros se revisan después de la MAJOR |
| 14 | **La MAJOR** | `v8.0.0`, `master` y `last-stable` | `12-convenciones.md`, convención de etiquetas | **Punto serio**: se habla con el PO antes |

## Decisiones del PO que atraviesan varios lotes

- **Mandato del 2026-09-16 (A-031): trabajar sin parar hasta cerrar los lotes 7 a 11**, con todo lo
  que va antes en esta tabla, y **parar antes del 12**. Las paradas obligatorias siguen valiendo: la
  regla de los diez (el lote 7 toca unos 168 archivos) y los puntos serios de la regla 30.
- **El backoffice usa Fomantic-UI conservando la estética que ya hay**, con Publications como
  referencia (2026-09-15). Toda instrucción que toque una vista del panel nombra el componente y
  dice si es descartable y apagable.
- **Espera al PO**, con su predeterminado, en `estado/AHORA.md` y en el resumen del tramo
  `2026-09-15-1022`.

## Después de la MAJOR

`roadmap-posterior/` (16 documentos) y la sección «Después de la MAJOR» del cruce en
`pendientes.md`. No se copian aquí. Entre ellos, lo que el PO ha vuelto a nombrar el 2026-09-15:
la **vista «Sistema» en el panel** (a la que añade los paquetes de Composer instalados frente a
recomendados, y los **binarios externos del servidor**, como `ffmpeg`, con aviso si faltan), el
**log de correo con su vista de SMTP probable**, y **los cuatro registros**.

## Fuera del repositorio

- **La guía personal del PO**: sus requisitos están en
  `roadmap-posterior/Requisitos de la guia personal.md`; la guía **no está escrita**.
  - **NO SE VERSIONA. Es personal** (PO, 2026-08-29 y confirmado el 2026-09-15). Lo que no se
    versiona no se respalda y ninguna sesión futura lo lee: no puede ser fuente de nada.
  - **El PO insistió el 2026-09-15 en que sea más específica**, porque el volumen de la campaña
    la hace imprescindible para reconocer su propio framework.
  - Propuesta del arquitecto, sin aprobar: escribirla DURANTE la campaña, un párrafo por ronda, y
    **entregársela en el chat para que la archive donde quiera**. Opciones de ubicación en
    `pendientes.md`.
- «Perfeccionar geovisor»: ya se sabe cuál es (`/var/www/html/espacio-publico/espacio-publico-backend`,
  nombrado por el PO el 2026-09-14). Falta que diga qué quiere perfeccionar
  (`docs/pendientes.md`).
