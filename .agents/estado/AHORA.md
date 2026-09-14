# Ahora

- **Actualizado:** 2026-09-14 16:26 (medido con `date`). **Jornada cerrada** por orden del PO:
  «ve terminando la jornada por hoy apenas puedas, claro sin dejar nada roto ni a medias».
- **Último mensaje:** `#028 · ARQ`, la ronda de cierre, que solo commitea documentos. El próximo
  número es `#029`, el reporte del cierre. Después viene `#030`.
- **Tramo:** [`tramos/2026-09-14-1441-mapa-a-la-major.md`](tramos/2026-09-14-1441-mapa-a-la-major.md),
  cerrado a las 16:26, con su resumen.
- **Informe del estado del proyecto**, actualizado al cierre:
  [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md).
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, en `9e708797` antes de `#028`.
  - Sin empujar, según la referencia local de `origin/dev`: 24 commits en piecesphp, 2 en
    database y 1 en geojson.
  - En datastructures y html, `dev` aún no existe en el remoto.
  - Los cuatro paquetes están en `dev`.

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

1. **⚠ Saber lo de la inyección SQL**, por si hay despliegues en producción con
   `publications-ajax-all` o `built-in-banner-ajax-all` (informe, sección 8).
2. **P24 — qué control de acceso lleva cada carpeta de subidas.** La tabla y el predeterminado
   están en `docs/pendientes.md`. Si no contesta, se aplica el predeterminado en el bloque 2 de
   subidas.
3. **Subir cuando quiera.**

Siguen abiertas en `docs/pendientes.md`: qué perfeccionar del geovisor (ya se sabe cuál es), el
francés, el rol 50 con nombre `null` y `Components`.

## En curso

`#028`, cierre: el coder commitea el mapa, `pendientes.md`, el informe y el estado. Nada del
producto. Si se corta, quedan documentos sin commitear, y basta con commitearlos.

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
3. **H3**: las dos rutas públicas aceptan `status=ANY`. Antes de tocarlo, medir sus
   consumidores públicos.
4. **Lote 3, bloque 2**, con P24 o su predeterminado.
5. El mapa, en su orden.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`, el informe del estado del proyecto y el último tramo.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
