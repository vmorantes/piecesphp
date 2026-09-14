# Tramo 2026-09-14 17:26 — Sin el PO: lote 3a y lo que siga

- **Inicio:** 2026-09-14 17:26 (medido con `date`)
- **Fin:** abierto
- **Mensajes:** desde `#030`
- **Mandato del PO:** «Puedes trabajar unas tres o cinco rondas, pero toma en cuenta que no estaré
  así que no responderé nada» (2026-09-14). Trabajo nombrado: el mapa, en su orden («Trabaja.
  Adelante.»).
- **Regla del tramo:** entre tres y cinco rondas. Sin preguntas al PO: lo que necesite su decisión
  se anota arriba en `AHORA.md` y se sigue con lo que no dependa de ello.

## Rondas

| # | Qué | Resultado | Commits |
| --- | --- | --- | --- |
| #030 / #031 | ⚠ Lote 3a: `PageQuery` con valores ligados; el valor de la petición va por marcador en los 14 usuarios de `PageQuery` y en GeoJSON; pruebas de rechazo | **Completado.** 7 vías por marcador; cada una, vista caer con la carga de sintaxis; 73/73; 744. Bitácora 0007 | `8aa50fe6` `50a3faf8` `2bd68526` `62de7073` `3fa32294` `c7eae5a1` |
| #032 / #033 | H3 (`status` en rutas públicas), la clave de caché de publications, H10 (`ProtectFileMiddleware`) y el filtro de estado de Documents | **Completado.** Todo visto fallar en la provocación; gates 26/26; 744; suite nueva `core/protect-file-middleware` | `f0acfc7a` `962f6baf` `18bdf1b0` `b0f04ce4` `5ff91984` `87559cc5` `43ba6169` `0761bc1b` |
| #034 | Lote 3, bloque 2 (P24): validadores de las carpetas de subidas con Publications como arquetipo; registro y comprobación 29; universos de los censos declarados | en vuelo | — |

## Encontrado y decidido

- **`ignoreSlugs`**, en la misma ruta pública `publications-ajax-all`, es otra vía de
  inyección: `NOT IN ("…")` sin escapar. Su validador solo mira el último elemento.
- **`PageQuery` está en 14 controladores.** Se auditan todos, no solo los cinco del reporte de
  `#027`.
- **El orden del tramo:** lote 3a; después H3 (`status=ANY` en rutas públicas), el bloque 2 de
  subidas con P24 aprobada (Publications como arquetipo) y un instrumento que vea por su forma
  el valor entre comillas interpolado en SQL.

## Falló por el camino

—

## Espera al PO

Nada bloquea.

## Resumen

*(al cerrar)*
