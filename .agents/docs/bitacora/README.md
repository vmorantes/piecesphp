# Bitácora

Registro de cómo se llegó a cada cambio: qué se pidió, qué se encontró que no se esperaba, qué
se instruyó, qué reportó el coder, qué quedó fuera y qué se aprendió.

**No es una transcripción.** Una entrada por tarea cerrada, nunca una por mensaje.

## Para qué sirve

El CHANGELOG cuenta qué cambió. Los ADR, por qué se eligió una opción sobre otra. La bitácora
cuenta lo que ninguno guarda: **cómo se llegó hasta ahí**. Contesta «¿por qué esto tardó tres
vueltas?» y «¿esto ya lo intentamos?».

## Antes de esta bitácora

Hasta el bloque BC (2026-09-14) ese papel lo hicieron las entradas T de
`.agents/context/18-siguientes-ventanas.md` (T1 a T168). Siguen ahí hasta que E6 disuelva el
18. **Desde la entrada 0001 no se escriben entradas T nuevas** (ADR 0002).

## Convención

`NNNN-titulo-en-kebab-case.md`, correlativos. La escribe el arquitecto **al cerrar** la tarea,
con el reporte del coder en mano; por eso suele commitearse junto con la tarea siguiente. Cada
entrada nombra el bloque (letras) y los mensajes (`#NNN`) que abarca. Plantilla:
`.agents/skills/arquitecto-coder/plantillas/bitacora-plantilla.md`.

## Índice

| # | Tarea | Bloque | Fecha |
| --- | --- | --- | --- |
| [0001](0001-arranque-del-modelo-arquitecto-coder.md) | Arranque del modelo arquitecto-coder y herencia de la campaña | — | 2026-09-14 |
| [0002](0002-bc-linea-base-y-censo.md) | La línea base dice una cifra, y el censo deja de exagerar | BC | 2026-09-14 |
| [0003](0003-lote-0b-orden-de-directorios.md) | Una razón de ser por carpeta | 0b | 2026-09-14 |
| [0004](0004-lote-0a-dev-en-los-paquetes.md) | `dev` en los cuatro paquetes | 0a | 2026-09-14 |
| [0005](0005-bd-analizadores-nivelados.md) | Los paquetes miden con el mismo analizador que el framework | BD | 2026-09-14 |
| [0009](0009-lote-4-escapestring-primer-bloque.md) | Lote 4, bloque 1: 17 de 22 usos de `escapeString()` por marcador; `@deprecated`, pendiente | BL | 2026-09-14 |
| [0008](0008-lote-3-subidas.md) | Las subidas dejan de servirse a cualquiera: Publications como arquetipo y la comprobación 29 | BH, BJ, BK | 2026-09-14 |
| [0007](0007-lote-3a-busquedas-por-marcador.md) | ⚠ Las búsquedas de los listados paginados van por marcador: siete vías, tres de ellas públicas | BI | 2026-09-14 |
| [0006](0006-lote-2-identificadores-de-sql.md) | Identificadores de SQL: un censo y un trinquete en vez de una lista blanca; cierre de BD | BE, BF, BG | 2026-09-14 |
