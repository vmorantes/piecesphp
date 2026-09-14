# 0009 — Lote 4, bloque 1: 17 de 22 usos de `escapeString()` pasan a marcador

- **Fecha:** 2026-09-14
- **Pedido por:** el mapa, lote 4, en un tramo sin el PO.
- **ADR relacionados:** 0009 (`escapeString()` cede al marcador; no se toca `sql_mode`).
- **Bloque:** BL. **Mensajes:** `#036`–`#037`
- **Commits:** `13708e8b` `a47be2a3` `2dcea3ba` (documentación), `a5e5e231` y `69b48dc7`
  (artefactos de PHPStan)

## Qué se pidió

Migrar a marcador todos los usos de `escapeString()` y marcar la función `@deprecated` (ADR
0009). `escapeString()` es `addslashes(stripslashes())` y depende de un `sql_mode` que nadie
fija.

## Qué se encontró

- **Son 22 llamadas, no 23:** una de las apariciones que da `grep` es un docblock.
- **17 se migraron**, 16 de ellas a `WhereSegment`.
  - El login ya ligaba su valor por `where([...])`: allí `escapeString()` solo alteraba el
    valor.
  - En los `*MultipleCriteries`, el operador de cada criterio pasa a ser el `afterOperator` del
    anterior, con la misma precedencia.
- **Dos sitios no tienen vía directa a marcador:**
  - **`OrganizationMapper.php:664-667`** (4 llamadas). Un JSON literal de etiquetas traducidas
    dentro del `SELECT` de `fieldsToSelect()`, y `select()` no liga valores. El valor es del
    servidor; el riesgo es solo `sql_mode` con una comilla en una etiqueta. Salidas: pasar las
    etiquetas a PHP después de leer la fila, o un literal hexadecimal.
  - **`DataTablesHelper.php:1324`** (1 llamada). La búsqueda de las tablas del panel en
    `process()`. La vía existe (`generateHavingGroup()`), pero `process()` la mezcla con el
    `having_string` del llamador, y los 14 llamadores de `process()` siguen en cadena (la
    migración AQ del registro). Arreglarlo de verdad es migrarlos, lo que pasa de diez archivos:
    **regla de los diez**, el PO ve el plan antes.
- **`@deprecated` se queda sin poner.** Con los 5 usos vivos, PHPStan sube 5 («Call to
  deprecated function») y rompe el trinquete. Decisión del arquitecto: primero se resuelven los
  5 y luego se marca. No se sube la línea base ni se añaden supresiones.
- **⚠ El mensaje del commit `a5e5e231` dice «la funcion queda obsoleta», y NO es verdad.** No se
  reescribe la historia: esta entrada, el `CHANGELOG.md` y `pendientes.md` lo corrigen. La
  función sigue sin `@deprecated` y con su cuerpo de siempre.

## Qué reportó el coder

- **`sql-placeholders` pasa de 74 a 103.**
  - La comilla suelta, en los 17 sitios migrados.
  - **La sonda que discrimina es `stripslashes()`**: con el `sql_mode` por defecto, la comilla
    no delata a `escapeString()`, pero un valor que existe, con una barra metida, casa si se
    concatena y no casa si va ligado. Discrimina en 12 sitios; en los otros 5, «NO DISCRIMINA»
    por falta de filas.
  - Una comprobación de fuente, por tokens, exige los 5 usos que quedan, exactos.
- **Provocación:** 95/103 al restaurar los originales de tres mappers, y 103/103 tras
  devolverlos.
- **Cambios de comportamiento, solo en los bordes:**
  - un usuario con `'` o `\` en su nombre ahora casa literal en el login;
  - un nombre, código o NIT con `\` ya no da un duplicado falso;
  - el filtro de organización nula ya no es un error de SQL.

## Qué quedó fuera

- **Los dos sitios parados y el `@deprecated`:** el bloque 2 del lote 4.
- **Los comodines `%` y `_`** en la búsqueda del panel: se quedan como están, anotados.
- **`OrganizationMapper::getLogoURL()`**, sin llamadores: lote 10.

## Aprendido

- **Una sonda tiene que atacar lo que la función hace de verdad.** La comilla no delataba a
  `escapeString()` con el `sql_mode` por defecto; `stripslashes()`, sí.
- **Dictar un mensaje de commit que afirma el resultado es arriesgado.** Si el resultado cambia
  a mitad de la ronda, el mensaje queda mintiendo en la historia. Mejor un mensaje que describa
  el cambio, no la meta.
