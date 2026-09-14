# 0004 — Lote 0a: `dev` en los cuatro paquetes

- **Fecha:** 2026-09-14
- **Pedido por:** Product Owner (P19)
- **ADR relacionados:** 0003 (la guarda deja crear `dev` en los paquetes y ninguna otra rama)
- **Bloque:** 0a. **Mensajes:** `#010`–`#011`
- **Commits:** ninguno en los paquetes. En este repositorio, los de documentación del lote 0b
  (`e603a660` `28ebf89f` `7e262c29` `dc389e1f`)

## Qué se pidió

El PO, el 2026-09-14: *«que master es el estable permanece, mejor que todos tengan un dev.
Homologuen dev y master y luego se sigue esta dinámica.»*

## Qué se encontró al explorar

`database` y `geojson` ya tenían `dev`, en el mismo commit que `master`
(`git rev-list --left-right --count master...dev` dio 0 y 0). `datastructures` y `html` no la
tenían.

## Qué se instruyó

En `#010`: crear `dev` sobre `master` en esos dos paquetes, sin cambiar de rama y sin commits,
etiquetas ni push. Y commitear la documentación del lote 0b.

## Qué reportó el coder

**`#011`: completado.**

- `dev` y `master` apuntan al mismo commit en los cuatro paquetes:
  - `database`: `aa64cf5`;
  - `datastructures`: `824075e`;
  - `geojson`: `7d8c91b`;
  - `html`: `2dca64d`.
- Árboles limpios, sin push. El arquitecto lo comprobó en los cuatro repositorios.
- Corrió en paralelo los pasos de los paquetes y los commits de aquí, que usan índices
  distintos.

**Hallazgos:**
- **H1.** La rama activa de los cuatro sigue siendo `master`. Queda escrito en la regla 30: la
  instrucción que toque un paquete empieza con `switch dev`.
- **H2.** Es el mismo final de línea cosmético de siempre en `AHORA.md`.

## Qué quedó fuera

Subir las dos ramas nuevas al remoto: eso lo hace el PO.

## Aprendido

Una homologación que ya estaba hecha a medias se mide antes de ordenarla: dos de los cuatro
paquetes no necesitaban nada.
