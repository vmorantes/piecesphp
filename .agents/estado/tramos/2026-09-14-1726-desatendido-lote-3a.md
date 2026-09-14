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
| #030 | ⚠ Lote 3a: `PageQuery` con valores ligados; el valor de la petición va por marcador en los 14 usuarios de `PageQuery` y en GeoJSON; pruebas de rechazo | en vuelo | — |

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
