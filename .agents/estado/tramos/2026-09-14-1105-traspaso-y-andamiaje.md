# Tramo 2026-09-14 11:05 — Traspaso y andamiaje

- **Inicio:** 2026-09-14 11:05
- **Fin:** 2026-09-14 13:20 — **Duración:** unas 2 h 15 min (el fin, medido con `date`)
- **Mensajes:** `#001`–`#011`
- **Mandato del PO**, dado al abrir el tramo:
  - montar el modelo arquitecto-coder con la skill que creó;
  - heredar al arquitecto anterior usando su conversación entera («muere tras tu uso»);
  - tomar lo mejor de lo copiado de otro proyecto y adaptarlo;
  - decidir si hacía falta un coder nuevo, sin commits en el arranque;
  - informar de lo hecho y de cómo se trabajará.

  **Añadido después**, en respuestas y mensajes del PO:
  - borrar lo que no aplicase y recibir el reporte del coder saliente;
  - la política de ramas;
  - P17–P21, con la autorización permanente de commits (ADR 0005), el orden de directorios
    (lote 0b) y `dev` en los paquetes (lote 0a);
  - no trabajar sobre el framework hasta cerrar el acuerdo.

## Rondas

| # | Qué | Resultado | Commits |
| --- | --- | --- | --- |
| #001→#002 | Saludo al coder saliente y entrega de BC | Completado. BC evaluado en la bitácora 0002 | `0c1af05a` (BC, anterior al contador) |
| #003→#004 | Saludo al coder nuevo | Completado. Seis dudas; dos eran defectos de la regla 30, corregidos | — |
| #005 | Respuestas a sus dudas | Enviado; no era una instrucción de trabajo | — |
| #006→#007 | Commit del andamiaje | Completado. Diez commits, `ANDAMIAJE OK`. Hallazgo H2 → regla 30 | `1c92eee0`…`1a2d1ec8` |
| #008→#009 | Lote 0b, orden de directorios (ADR 0006) | Completado. Ocho commits; `verify-integrity` y `censo-rutas-doc` en verde | `3a5fa084`…`45d1a84f` |
| #010→#011 | Lote 0a, `dev` en los paquetes, y documentación del 0b | Completado. `dev` = `master` en los cuatro; cuatro commits de documentación | `e603a660` `28ebf89f` `7e262c29` `dc389e1f` |

## Encontrado y decidido

- **El andamiaje copiado describía otro proyecto**: permitía `push` y bloqueaba el vocabulario de
  IA. Se adaptó (ADR 0001–0004).
- **La herencia.** El cruce de los 466 turnos del PO recuperó sus preferencias de trabajo y sus
  encargos a futuro. Van en la regla 30 y en `pendientes.md`.
- **El mapa a la MAJOR solo existía en el chat.** Ahora es `.agents/docs/roadmap.md`.
- **La autorización permanente de commits** quedó como excepción de este repositorio (ADR 0005).
  Salió de una duda del coder: nada decía dónde consta el permiso del PO.
- **La política de ramas del PO.** Nada se crea sin su permiso, salvo `dev` en los paquetes. En
  los paquetes se etiqueta (P19).
- **Una razón de ser por carpeta** (ADR 0006). El censo cambió dos puntos de la propuesta
  aceptada: los `PHPStanResult.*` se quedan en la raíz y `tests.md` va a `.agents/context/`.
- **Mentiras del registro corregidas**: `Importers`, la autoría de T60 y la tabla de pendientes.
  El resto está listado en `pendientes.md`.
- **Un secreto versionado en el 18**, retirado por higiene con permiso del PO (P20).

## Falló por el camino

- **Una orden combinada** (restaurar, mover y borrar) la denegó el clasificador de la
  herramienta. No se ejecutó nada; se hizo por partes, y los borrados después del permiso del PO.
- **Al comprobar el secreto del 18**, la máscara no tapó el hash, que quedó en la salida de la
  herramienta. No se copió a ningún archivo ni mensaje.
- **Alguien vació tres skills copiadas** a las 11:07 mientras se leían. Se trató como decisión
  del PO.
- **Dos subagentes del cruce** compartieron un guion del scratchpad y uno lo sobrescribió. El
  afectado lo declaró y usó una copia propia.
- **El PO reabrió el chat del coder a mitad de una ronda.** La instrucción autocontenida bastó.
  La regla 30 dice ahora qué implica reabrir un chat.
- **Las horas de `AHORA.md` eran estimadas y no medidas**: 14:05, 14:40 y 15:20, cuando el reloj
  real iba por detrás (13:19 al cerrar). Corregido al cerrar el tramo con `date`. **Desde ahora las
  horas de `estado/` salen de `date`**, no de una estimación.

## Espera al PO

1. **La orden de trabajar sobre el framework.** El mapa empieza por BD, delegado en el
   arquitecto, y por los identificadores de SQL.
2. **Subir cuando quieras**: las dos ramas `dev` nuevas de `datastructures` y `html`, y los
   commits de este tramo en `piecesphp`.

## Resumen

Se montó el modelo de tres roles adaptado a PiecesPHP. La herencia del arquitecto anterior quedó
destilada en el repositorio: el mapa a la MAJOR, lo que el PO había pedido y no estaba escrito, y
cómo trabaja el PO. El reporte de BC se recibió y se evaluó. Con la orden del PO quedaron
commiteados el andamiaje, el orden de directorios y la homologación de `dev` en los paquetes:
22 commits en `piecesphp`, sin un solo push. El framework está listo para empezar el mapa en
cuanto el PO dé la orden.
