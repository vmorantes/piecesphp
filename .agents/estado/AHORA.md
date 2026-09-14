# Ahora

- **Actualizado:** 2026-09-14 18:35 (medido con `date`). **Tramo sin el PO**: «Puedes trabajar
  unas tres o cinco rondas, pero toma en cuenta que no estaré así que no responderé nada».
  Rondas hechas en este tramo: 3 de entre 3 y 5. La `#036` es la 4.ª y la `#038`, el cierre.
- **Último mensaje:** `#036 · ARQ`, en vuelo: el lote 4 (ADR 0009) y la medición de H1 de
  `#035`. El próximo número es `#037`.
- **`#035`: lote 3, bloque 2, completado.**
  - Cuatro carpetas protegidas; dos declaradas públicas.
  - Comprobación 29.
  - PHPStan pasa a 738: murieron 6 errores con el código retirado.
  - Ruptura 17 en el `CHANGELOG.md`; la guía de subidas, reescrita.
- **`#033`: completado.**
  - Las rutas públicas ya no devuelven borradores ni borrados sin permiso.
  - La clave de caché de publications refleja los valores efectivos.
  - `protect()` crea la carpeta que falta y exige el separador.
  - Documents filtra por estado.
  - Rupturas 15 y 16 en el `CHANGELOG.md`.
- **`#031`: lote 3a CERRADO** (bitácora 0007). Siete vías por marcador, tres de ellas públicas;
  `sql-placeholders` pasa a 73/73; todo en verde, 744.
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

**`#036`**, en tres tareas:
- T1: commitear lo del arquitecto: el ADR 0009, el `CHANGELOG.md`, la guía de subidas,
  `pendientes.md`, el mapa y el estado.
- T2: lote 4. Los 23 usos de `escapeString()` pasan a marcador, o se declaran; la función queda
  `@deprecated`; una prueba falla si reaparece un uso.
- T3: medir si alguna vista PÚBLICA muestra archivos de documents, organizations o
  news-categories. Si alguna lo hace, PARA y lo reporta: es una decisión.

**Hecho en `#034` (histórico de la ronda anterior)** — lote 3, bloque 2 (P24 aprobada). Tareas:
- T1: commitear lo del arquitecto: el `CHANGELOG.md` (rupturas 15 y 16, y `protect()`),
  `pendientes.md` y el estado.
- T2: Publications como arquetipo. Su validador sirve un archivo si la publicación es visible al
  público (el mismo criterio que `singleView()`, extraído a un método del mapper) o si hay
  sesión.
- T3: documents, organizations y news-categories, con sesión. El banner y el `homeImage` de
  generic, declarados públicos con su motivo en un registro de `files/dev/`. Los `UPLOAD_DIR`
  sin archivos (document-types, categories y system-approval) se retiran solo si todo lo que
  los usa está muerto; si no, se declaran.
- T4: la comprobación 29 de verify-integrity: todo `UPLOAD_DIR` está protegido o declarado.
- T5: se declaran los universos de los censos (H3 de `#033`).

Si se corta ahora: puede quedar código a medio cambiar. `git status` y `verify-integrity` lo
dicen.

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
     - **Verificado por el arquitecto:** `folder` NO está en `$translatableProperties`
       (`PublicationMapper:248-255`), así que es una columna y `getBy()` sirve. Los adjuntos van
       en `<folder>/attachments` (`PublicationsController.php:656`), y la misma carpeta los
       cubre.
     - **La sesión llega también por cookie:** `SessionToken::getJWTReceived()` lee la cabecera
       `JWTAuth` o la cookie del mismo nombre (`SessionToken.php:99-115`). Un `<img src>` del
       panel lleva la cookie, así que el validador de «sesión activa» no deja sin imágenes al
       administrador. Validador de sesión:
       `SessionToken::isActiveSession(SessionToken::getJWTReceived())`, la línea que ya estaba
       comentada en `protected-files.php:11`.
     - **El validador corre en `ServerStatics::verifyFile()`** (`:543-546`), dentro de la
       petición de la aplicación: sin el `.htaccess` de `protect()`, Apache sirve el archivo
       sin pasar por él.
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

## Plan de las rondas que quedan en este tramo (máximo 5; van 3 con `#034`)

- **`#036` — lote 4, `escapeString()`.** El borrador del ADR 0009 está en el scratchpad del
  arquitecto y se deposita al recibir `#035`.
  - Los 23 usos de 13 archivos pasan a marcador, o se declaran con su motivo.
  - `escapeString()` queda `@deprecated`, y una prueba falla si reaparece un uso.
  - No se toca `sql_mode`: vive en el paquete, cambiaría todas las consultas y trata el
    síntoma.
  - Medido hoy: la conexión solo fija `SET NAMES` y `time_zone`
    (`database/src/Core/Database/Database.php:240`).
- **`#038` — cierre del tramo.** Solo commitea documentos: la bitácora 0008 del lote 3, la guía
  de subidas en `source-docs/project/docs/piecesphp/new-features/protected-files.md`, el
  `CHANGELOG.md` y el estado.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`, el informe del estado del proyecto y el último tramo.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
