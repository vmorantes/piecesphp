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
