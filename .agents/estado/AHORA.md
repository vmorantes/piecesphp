# Ahora

- **Actualizado:** 2026-09-14 17:26 (medido con `date`). **Tramo sin el PO**: «Puedes trabajar
  unas tres o cinco rondas, pero toma en cuenta que no estaré así que no responderé nada».
- **Último mensaje:** `#030 · ARQ`, en vuelo: lote 3a. El próximo número es `#031`.
- **Tramo en curso:** [`tramos/2026-09-14-1726-desatendido-lote-3a.md`](tramos/2026-09-14-1726-desatendido-lote-3a.md).
- **Tramo anterior:** [`tramos/2026-09-14-1441-mapa-a-la-major.md`](tramos/2026-09-14-1441-mapa-a-la-major.md),
  cerrado a las 16:26, con su resumen.
- **Informe del estado del proyecto**, actualizado al cierre:
  [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md).
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, en `a039f4b8` tras `#029`. **El PO subió todo** («Subidos», 2026-09-14
  16:37): `origin/dev..dev` da 0 en los cinco repositorios, y `dev` ya existe en el remoto de
  datastructures y html. Los cuatro paquetes están en `dev`.
- **Sin commitear, del arquitecto, escritos después del cierre:** `docs/pendientes.md`
  (P24 aprobada y la guía de parches como idea), `docs/roadmap.md`, el informe del estado y este
  archivo. Van en el PASO 1 de `#030`.

## Autorización de commits del PO (ADR 0005)

**Vigente desde el 2026-09-14.** El coder prepara y commitea, **en commits atómicos**, el trabajo
que el PO nombra y el arquitecto instruye, sin pedir permiso commit a commit.

**Reservado al PO:**

- `git push` y todo lo que toca un remoto;
- etiquetar `piecesphp` y tocar su `master` o su `last-stable`;
- crear ramas, salvo `dev` en los paquetes;
- reescribir historia;
- escribir o destruir datos de una base de datos, salvo lo que nombre la instrucción con su
  autorización;
- dependencias, builds, servidores y credenciales. Excepciones: las herramientas de análisis
  (ADR 0007) y `piecesphp/*` dentro de los paquetes hermanos (ADR 0008).

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:** el mapa, `../docs/roadmap.md`, en su orden. Orden del PO:
«Trabaja. Adelante.» (2026-09-14).

## Espera al PO

Nada bloquea. Contestado el 2026-09-14 16:37:
- **La inyección, enterado.** «Hay muchos ya viejos y sin soporte. No puedo hacer mucho». Idea
  suya para después de la MAJOR: una guía de «parches» de seguridad para versiones viejas
  (`docs/pendientes.md`, encargos).
- **P24, aprobada** tal como estaba el predeterminado. Publications va primero, como arquetipo.
- **Subido.**

Siguen abiertas en `docs/pendientes.md`: qué perfeccionar del geovisor (ya se sabe cuál es), el
francés, el rol 50 con nombre `null` y `Components`.

## En curso

**`#030` — ⚠ lote 3a.** Tareas:
- T1: commitear lo del arquitecto.
- T2: `PageQuery` gana valores ligados, opcionales y compatibles con los 14 usuarios actuales.
- T3: auditar los 14 usuarios de `PageQuery` y GeoJSON; todo valor de la petición que acabe en
  su SQL pasa a marcador. Incluye `title` y `ignoreSlugs` (públicas), `newsTitle`, `name` y
  `search`.
- T4: pruebas de rechazo, vistas fallar.

Si se corta ahora: puede quedar código a medio cambiar. `git status` y `verify-integrity` lo
dicen, y la ronda se termina antes de seguir.

## Siguiente — la próxima jornada

1. **Las dos órdenes `/rename`**, antes que nada.
2. **`#030` — ⚠ Lote 3a (urgente): las búsquedas concatenadas.**
   - `PageQuery` gana un parámetro opcional de valores ligados, que usan `getTotal()`,
     `getResult()` y `getPageResult()` (hoy hacen `prepare()` y `execute()` sin nada).
   - Van por marcador:
     - `title` en `PublicationsController::_all()` (`:1438`) y en
       `BuiltInBannerController::_all()` (`:996`);
     - `newsTitle` en `NewsController.php:1225`;
     - `name` en `OrganizationsController.php:1350`;
     - `search` en `GeoJsonManagerController.php:143-144` y `250-251`, que pasa a
       `HavingSegment`.
   - El coder audita además cada criterio de esos `_all()` que venga de la petición (por
     ejemplo, `ignoreSlugs` y `category` en publications).
   - Una prueba de rechazo por sitio, vista fallar al quitar el arreglo. Si los datos locales
     no la hacen discriminar, se prueba el texto SQL y los valores sin base de datos.
   - Los comodines `%` y `_` del `LIKE` siguen en manos del visitante: van con el lote 4.
   - CHANGELOG: «⚠ Corregido», que escribe el arquitecto.
3. **`#032` — H3 y `ProtectFileMiddleware`.** Diseño, medido por el arquitecto:
   - **Consumidores públicos.** Las vistas públicas llaman a `ajax-all` SIN `status`:
     `PublicationsPublicController.php:118` y `:222`, `PublicAreaController.php:117-118` y
     `214`. `home.js` del banner tampoco lo pasa.
   - **H3.** Sin sesión, o sin un tipo de usuario en `CAN_VIEW_DRAFT` (`PublicationMapper:214`),
     `status` se fuerza a ACTIVE, con fechas vigentes y aprobación. Es el mismo criterio que
     `singleView()` (`PublicationsPublicController.php:150-185`). Con sesión y ese permiso, se
     mantiene lo de hoy, porque puede haber clientes sin interfaz autenticados que lo usen. Lo
     mismo en el banner (`BuiltInBannerMapper::isActiveByDates()`, `:234`).
   - **H10**, en `ProtectFileMiddleware`:
     - `protect()` crea la carpeta si no existe, en vez de volver sin registrar (`:39-42`);
     - la comparación por prefijo exige el separador (`$dir . DIRECTORY_SEPARATOR`) en
       `validateAccess()` e `isProtected()` (`:74`, `:96` y `:104`).
     Cada cosa, con su prueba de rechazo.
4. **`#034` — lote 3, bloque 2, con P24 aprobada.**
   - **Primero Publications, como arquetipo.** El validador saca la carpeta (el campo `folder`,
     `PublicationMapper:109`) de la ruta del archivo, busca la publicación con `getBy($folder,
     'folder')` y sirve el archivo si es visible al público (ACTIVE, `isActiveByDates()`,
     aprobada) o si hay sesión.
   - **Después:** documents, organizations y news-categories, con sesión; el banner y generic,
     declarados públicos con su motivo.
   - **Se retiran** los `UPLOAD_DIR` sin archivos (document-types, categories y
     system-approval) y los `UPLOAD_DIR_TMP` muertos.
   - **Una comprobación nueva en verify-integrity:** todo `UPLOAD_DIR` está en `protect()` o
     declarado público en un registro de `files/dev/`, con su motivo.
   - **La guía de módulos** explica el patrón (la escribe el arquitecto).
   - Sin arreglar todavía: los huérfanos al borrar (H8), los nombres adivinables (H5) y el SVG
     (H6). Una vez protegidos, pesan menos; se reevalúan con la tabla delante.
5. El mapa, en su orden.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`, el informe del estado del proyecto y el último tramo.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
