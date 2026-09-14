# 0007 — Lote 3a: las búsquedas de los listados paginados van por marcador

- **Fecha:** 2026-09-14
- **Pedido por:** el mapa («Trabaja. Adelante.»). Una corrección de seguridad dentro de la
  campaña, en un tramo sin el PO («Puedes trabajar unas tres o cinco rondas…»).
- **Bloque:** BI. **Mensajes:** `#030`–`#031`
- **Commits:** `8aa50fe6` `50a3faf8` (documentación), `2bd68526` `62de7073` `3fa32294` y
  `c7eae5a1` (artefactos de PHPStan)

## Qué se pidió

Cerrar la inyección SQL que encontró el coder en la auditoría de subidas (`#027`, H1). Dos rutas
PÚBLICAS, `publications-ajax-all` y `built-in-banner-ajax-all`, metían el parámetro `title` sin
escapar en `LIKE UPPER('%…%')`. `PageQuery` ejecuta su SQL sin valores ligados.

## Qué se encontró al explorar

- **En la misma ruta pública había una tercera vía, `ignoreSlugs`**: un `NOT IN ("…")` sin
  escapar, con un validador que solo miraba el último elemento.
- **`PageQuery` está en 14 controladores**, no en los cinco del reporte. Se auditaron todos.

## Qué se instruyó y qué reportó el coder

- **`PageQuery` acepta valores ligados.** Es un parámetro opcional y cada consulta recibe solo
  las claves de sus marcadores; si no, PDO da HY093.
- **La auditoría de los 14 y de GeoJSON**, clasificando cada variable como ENTERO, SERVIDOR o
  PETICIÓN, siguiéndola hasta su `Parameter` aunque cruce de método. Salieron **siete vías**:
  - las tres públicas: `title` en publications y en el banner, e `ignoreSlugs` en publications;
  - las cuatro tras sesión: `newsTitle` e `ignoreSlugs` en news, `name` en organizations y
    `search` en GeoJSON.
  Ninguna era un identificador.
- **Todas van por marcador**, conservando el `UPPER`. GeoJSON pasa a `HavingSegment` con dos
  grupos, porque `having()` sustituye y no acumula. Los dos validadores de `ignoreSlugs`
  validan ya todos los elementos.
- **Pruebas:** la sección 14 de `sql-placeholders`, que pasa de 58 a 73. Todas las vías
  cayeron en la provocación.

## Qué quedó fuera

- **Los comodines `%` y `_` del `LIKE`**, que siguen en manos del visitante. Van con el lote 4.
- **`status=ANY` en las rutas públicas (H3 de `#027`)**, la clave de caché de publications,
  que no incluye `ignoreSlugs` (H2 de `#031`), y **`ProtectFileMiddleware` (H10)**: todo va en
  `#032`.
- **El instrumento.** El censo interpolado sigue sin ver `PageQuery` como sumidero, y la traza
  no cruza de método. Hoy lo que cierra estas siete vías son las pruebas, no un censo.
- **Documents lista también los inactivos** (H1 de `#031`), y **los listados públicos de
  Locations enseñan tablas enteras sin sesión** (H3 de `#031`, decisión del PO).

## Aprendido

- **Una auditoría que sigue al valor hasta su `Parameter` encuentra lo que el censo no ve.** El
  censo mira el mecanismo dentro de un método; aquí el valor entraba por parámetro. Las dos
  cosas hacen falta.
- **Sin filas en la base local, una prueba por resultado no discrimina.** La comilla suelta sí:
  si se concatena, rompe el SQL, y si va ligada, es un dato. Y una carga que aparece dos veces
  en el mismo SQL puede emparejar sus comillas y pasar: se vio en GeoJSON y se cambió por `'(`.
- **Medir el alcance antes de dictar amplió la ronda**: de dos rutas a siete vías en cinco
  archivos.
