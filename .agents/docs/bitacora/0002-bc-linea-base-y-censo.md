# 0002 — BC: la línea base dice una cifra, y el censo deja de exagerar

- **Fecha:** 2026-09-14
- **Pedido por:** Product Owner, dentro de la campaña. La instrucción la redactó el arquitecto
  anterior el 2026-09-13; en `.agents/HERENCIA.md` está su contenido.
- **ADR relacionados:** ninguno
- **Bloque:** BC. **Mensajes:** el reporte llegó como `#002`; la instrucción es anterior al
  contador.
- **Commit:** `0c1af05a chore(instrumental): la linea base dice una cifra, y el censo deja de exagerar`
  (12 archivos, +635 −60)

## Qué se pidió

Dos instrumentos que exageraban y una deuda de registro. Ni una línea de producción.

1. La línea base de PHPStan tenía tres cifras distintas (749, 747 y 744). Tenía que quedar una
   sola, con una comprobación que lo vigile.
2. `asignaciones()` del censo de SQL contaba como paréntesis el `(` de un literal.
3. El CHANGELOG llevaba cinco bloques sin rupturas.
4. El censo de interpolación había que declararlo con su cota, sin cablearlo.

## Qué se encontró al explorar

Lo encontró el coder, y el arquitecto entrante lo comprobó leyendo el commit:

- **La instrucción tenía errores del propio registro.** El origen de los tres errores que
  murieron era `getReferencesAliases()`, no `getContentTypes()` (T167 lo tenía mal y el recuadro
  lo copió). Los repartos sí estaban escritos. Y las rupturas eran 11, no 12.
- **La comprobación 26 salió roja contra la línea base real antes de tocar nada**: «747 y 744»,
  «749 y 747». Es la mejor prueba de que mira lo que dice mirar.
- **La primera provocación no provocó nada.** Un `\R`, que no existe en el módulo `re` de
  Python, abortó sin escribir, y la puerta salió verde. El coder lo detectó porque imprimió el
  valor provocado, y repitió la provocación (memoria del coder, «Una provocación comprueba que
  provocó»).
- **El trinquete de declaradas no falla si hay menos de las declaradas** (H3): con `count` 5 y 3
  vistas, dos concatenaciones nuevas habrían entrado gratis. Queda cerrado para este caso; la
  forma general sigue abierta.

## Qué se instruyó

La instrucción de BC del arquitecto anterior, íntegra en su chat. Resumen en `HERENCIA.md`.

## Qué reportó el coder

**Estado: completado con desviaciones.** Todo con salidas reales guardadas de la ejecución de BC;
nada se volvió a ejecutar el 2026-09-14.

- **Línea base**: 744 en el campo, igual al reparto más reciente; la cabecera ya no nombra cifra.
  La comprobación 26 salió verde después y roja en tres provocaciones (campo a 745, cabecera con
  749, archivo ausente), con el mismo `sha256` antes y después.
- **Censo**: CONFIRMADO 0 → 0 · DECLARADO 10 → 8 (no es una mejora: era una cifra inflada) ·
  REVISAR 103 → 105 · DESCARTADO 135 → 135. Canario nuevo (cara 21), provocado. El trinquete
  sigue en 0.
- **CHANGELOG**: rupturas 12 (`contact-forms-general` sin el registro SMTP), 13 (`MySpace` sin
  usuarios no aprobados) y 14 (`bin/tools` desde su lock), más siete entradas. La guarda de AP
  retirada **no** es ruptura, con su motivo.
- **Censo de interpolación**: A 223 · B 38 · C 6 · indecisas 16 · 126 salen del método (57 %).
  Declarado en `sql-concat-baseline.json`, sin cablear.
- **Puertas**: `verify-integrity` con 26 comprobaciones en verde; `gates` con 25 suites, 0
  fallos y 2 no corridas por declarar efectos externos; PHPStan 2.2.12 en 744, igual que la
  línea base.
- **Cuatro números**: previsto 12, cambiado 13, añadido 12, pendientes 1.

**Desviaciones, todas justificadas:**

- **D1**: un pendiente, no dos. `tags.txt` desapareció; era una demostración del PO.
- **D2**: no reescribió el origen del reparto, porque el recuadro lo tenía mal.
- **D3**: añadió un canario no pedido, necesario para probar el arreglo.
- **D4**: reconcilió también la línea base del censo, que estaba rancia.

**Contradicciones con el modelo nuevo** (el coder las declaró solo):

- escribió documentación (T168, el CHANGELOG);
- mezcló documentación y código en un commit;
- no usó `bin/guarda-add`, sino una guarda en shell escrita a mano.

Las dos primeras eran lo que pedía la instrucción de BC; la tercera, no.

## Qué quedó fuera

Registrado en `files/dev/PENDIENTES.md` (hallazgos de BC):

- **H2**: el bloque «CAMBIOS INCOMPATIBLES» del CHANGELOG está partido (`---` en las líneas 193
  y 221, entre la ruptura 8 y la 9). Es trabajo de documentación del arquitecto.
- **H3**: el trinquete de declaradas (`bin/censo-sql-concatenado`, bucle
  `foreach ($declaradas as $clave)`) solo falla por exceso. Encaja con el lote de
  identificadores.
- **H6**: el mecanismo de la LEY 33 («nada declarado abierto puede faltar en `PENDIENTES.md`»)
  no existe como comprobación.
- **H7**: `files/dev/integrity-signatures.json` está en LF con `eol=crlf` declarado. Git lo
  normaliza; `bin/normaliza-eol` lo señala.
- **Visto por el arquitecto al leer el código**: la comprobación 26 lee la cabecera solo dentro
  de la sección `[NOTA DE MEDICIÓN]`. Si esa sección desapareciera, no miraría nada e informaría
  igual «la cabecera no nombra cifra». Hoy existe (línea 1 de la línea base): es latente.

## Aprendido

- **El registro también se equivoca, y la instrucción hereda sus errores.** El coder que
  contrasta antes de ejecutar (D2) vale más que el que ejecuta al pie de la letra.
- **Una puerta que sale roja contra el estado real antes de arreglarlo** es la demostración más
  barata de que mira lo que dice (LEY 23).
- **Un trinquete de cotas tiene dos lados.** El que solo mira el exceso deja pasar lo que cabe en
  la holgura.
