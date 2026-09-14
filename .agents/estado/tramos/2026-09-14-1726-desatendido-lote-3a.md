# Tramo 2026-09-14 17:26 — Sin el PO: lote 3a y lo que siga

- **Inicio:** 2026-09-14 17:26 (medido con `date`)
- **Fin:** 2026-09-14 18:52. Cinco rondas, el máximo autorizado: `#030`, `#032`, `#034`, `#036`
  y la de cierre, `#038`.
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
| #034 / #035 | Lote 3, bloque 2 (P24): validadores de las carpetas de subidas con Publications como arquetipo; registro y comprobación 29; universos de los censos declarados | **Completado.** 4 protegidas y 2 públicas declaradas; 3 `UPLOAD_DIR` muertos retirados; PHPStan 744 → 738 (6 murieron con el código retirado); comprobación 29 vista fallar; protect-file-middleware 30/30 | `0427b0c9` `d46b1fa9` `95758ca3` `4ca2e99d` `3a627ea8` `22446022` `2845cb40` `01e8b1b4` |
| #036 / #037 | Lote 4 (ADR 0009): `escapeString()` cede al marcador; y medir si una vista pública muestra archivos de las carpetas protegidas (H1 de `#035`) | **Completado con dos paradas.** 17 de 22 usos por marcador; sql-placeholders 103/103; provocación vista fallar. Parados: las etiquetas de `OrganizationMapper` y la búsqueda de `DataTablesHelper::process()` (regla de los diez). `@deprecated`, sin poner (subiría PHPStan 5). H1: ninguna vista pública rompía. Lote 3 cerrado (bitácora 0008); bitácora 0009 | `13708e8b` `a47be2a3` `2dcea3ba` `a5e5e231` `69b48dc7` |
| #038 | Cierre del tramo: solo documentos | en vuelo | — |

## Encontrado y decidido

- **`ignoreSlugs`**, en la misma ruta pública `publications-ajax-all`, es otra vía de
  inyección: `NOT IN ("…")` sin escapar. Su validador solo mira el último elemento.
- **`PageQuery` está en 14 controladores.** Se auditan todos, no solo los cinco del reporte de
  `#027`.
- **El orden del tramo:** lote 3a; después H3 (`status=ANY` en rutas públicas), el bloque 2 de
  subidas con P24 aprobada (Publications como arquetipo) y un instrumento que vea por su forma
  el valor entre comillas interpolado en SQL.

## Falló por el camino

- **El mensaje de commit que dicté para `a5e5e231`** («la funcion queda obsoleta») afirmaba el
  resultado, y el resultado cambió a mitad de la ronda: la función NO quedó obsoleta. Se corrige
  en la documentación, sin reescribir la historia. La lección está en la bitácora 0009.
- **La auditoría de `#031`** tuvo que cambiar de carga, de la de resultado a la de sintaxis,
  porque la base local no tiene filas. La de GeoJSON, además, emparejaba comillas.

## Espera al PO

Nada bloquea. Para cuando vuelva:
1. **El plan del bloque 2 del lote 4**: migrar los 14 llamadores de
   `DataTablesHelper::process()`. Pasa de diez archivos, así que lo ve antes (regla de los
   diez).
2. **P25 (candidata)**: ¿una publicación sin aprobar debe verse por su enlace directo y servir
   sus archivos? Hoy sí; el listado la oculta. Predeterminado: se queda así.
3. **Locations**: los listados públicos de puntos, ciudades y estados enseñan tablas enteras sin
   sesión. Predeterminado: se quedan públicos (datos de referencia).

## Resumen

- **Duración:** de 17:26 a 18:52. Rondas `#030`–`#038`, sin el PO.
- **Cerrado:**
  - **Lote 3a:** la inyección SQL en rutas públicas. Siete vías por marcador; `PageQuery` con
    valores ligados (bitácora 0007).
  - **La antesala de subidas:** las rutas públicas solo devuelven lo publicado; la caché, la
    carpeta de `protect()` y el filtro de Documents.
  - **Lote 3:** las subidas protegidas según P24, con Publications como arquetipo, y la
    comprobación 29 (bitácora 0008).
  - **Lote 4, bloque 1:** 17 de 22 usos de `escapeString()` por marcador (bitácora 0009, ADR
    0009).
- **Cifras:**
  - PHPStan, de 744 a 738: murieron 6 errores con el código retirado.
  - verify-integrity, 29 comprobaciones.
  - gates, 26 suites: sql-placeholders 103, access-guards 43 y protect-file-middleware 30.
- **Rupturas nuevas en el CHANGELOG:** 15, 16 y 17.
