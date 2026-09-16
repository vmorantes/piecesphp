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
| 4d | **⚠ El escape doble del ORM** | `EntityMapper::castPHPToSQLTypes()` aplica `stripslashes`+`addslashes` a todo campo de texto, y el `INSERT` ya liga valores: **borra las barras invertidas legítimas al guardar** y deja `O\'Brien` en la columna | `docs/pendientes.md`, «H-R» (`#088`) | **Lo primero del producto**, tras rehacer el aviso de `app_key` con `nag`. 112 campos en 26 mappers. Vive en el paquete `piecesphp/database`: se toca ahí, se etiqueta y se instala. El PO lo aprobó el 2026-09-15 (A-022 §5.1) y **lo decidió el 2026-09-16 (A-031): el requisito es que lo que se guarde desde ahora no falle nunca**. Lo escapado (`O\'Brien`) se repara con la tarea de la parte B; las barras ya perdidas no vuelven. **Parte A cerrada** (`database` v5.0.0); la parte B quita **10** compensaciones y espera el push del PO |
| 4e | **El límite del OTP no cuenta los nombres con comilla** | `login_attempts` guarda el nombre escapado y `OTPRateLimiter` compara con el crudo | `docs/pendientes.md`, «H-L» | **Depende de 4d**: es su síntoma, no otra causa |
| 4b-4 | **FileManager y el sufijo** | elFinder lista y renombra los nombres reales; hoy solo se bloquea el renombrado de lo protegido. Falta la vista previa por contenido y el reparto de raíces por módulo (P26) | `docs/pendientes.md`, «Rendimiento de los archivos protegidos» | Resto del 4b, que por lo demás está cerrado |
| 7 | **Avatares y `see-more`** | Muere el creador de avatares; `see-more` se restaura | `20` §7, «El lote del CREADOR DE AVATARES» y «`see-more`: DAÑADO» | `see-more`, decidido por el PO el 2026-08-31. **Retirar el creador toca ~168 archivos: la regla de los diez obliga a enseñar el plan al PO antes de commitear**. Medido: `see-more` no necesita compilar; el rediseño de v6.1.0 quitó el botón |
| 7c | **E4 · ventana de correo** | Pruebas de los 10 envíos, en tres capas: composición sin red, sumidero SMTP local y entrega real revisada a mano | `18` T7; ADR 0015 | Sin empezar. La capa 2, con **Mailpit** (ADR 0015, verificado: MIT, sin registro, atado a `127.0.0.1`). La capa 3, a Mailinator (ADR 0011) |
| 8 | **E5 · `DataImportExportUtility`** | Consolidación y arquetipo, absorbiendo `Importers` | `20` §7, «Abierto, sin decidir»; `18`, «La fusión … es una REFACTORIZACIÓN PLANIFICADA»; `docs/pendientes.md` (cruce) | `Importers` **no** se borra. Se unifica y se optimiza como base de la que se parte, con ejemplos que funcionan (PO, 2026-09-14). La dirección la dijo el PO el 2026-08-29: hacia `DataImportExportUtility`. El 18 dice la contraria |
| 9 | **E6 · documentación** | `source-docs/` completo; `16-frontend-arquitectura.md`; la documentación de la API (`source-docs/api/`) y Postman; los 9 selectores; la protección de módulos en la guía; los seis módulos sin punto de extensión (P4); el cierre de PHPStan en dos listas; `processFromQuery` documentado antes de que muera el 18 | `20` §7, «Abierto, sin decidir»; `docs/pendientes.md` (cruce) | 2-3 bloques. **Añadido por el PO el 2026-09-15**: las cuatro capas de documentación, el árbol del proyecto para quien desarrolla, el catálogo de componentes de Fomantic del panel, y el manifiesto de capas A/B/C con su comprobación. **Añadido por el PO el 2026-09-16 (P32)**: la documentación de los cuatro paquetes, en español (`docs/pendientes.md`, punto 22) |
| 10 | **Residuos con nombre** | Barrido final | Solo en el mapa del 2026-09-13; `docs/pendientes.md` acumula los del tirón | 1-2 bloques. Incluye `SOLO_PROPIAS` (P2), el skip muerto de Rector, `verify_expected_file()` sin llamadores, el `2777` de `server-delegated` y los contratos congelados H-AF a H-AH |
| 11 | **Usuarios a `classes/`** | El núcleo sale de la disposición vieja | `docs/pendientes.md` §1 | **Rompe**. «Al final» (PO, 2026-09-13). Sin medir |
| 12 | **Renombrado de columnas** | 8 columnas y 247 referencias, con `column-renames.json` | `20` §7, «EL RENOMBRADO DE COLUMNAS» | **Rompe**. Con la puerta de columnas (`docs/pendientes.md`, cruce) |
| 13 | **Borrado del registro** | El 18 se disuelve según su cláusula | `18`, cabecera | Antes, lo que solo vive en él sube a los documentos numerados |
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
