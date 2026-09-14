# 0005 — BD: los paquetes miden con el mismo analizador que el framework

- **Fecha:** 2026-09-14
- **Pedido por:** Product Owner. Lote 1 del mapa, con la orden «Trabaja. Adelante.». La
  instrumentación de análisis está delegada en el arquitecto desde el 2026-09-02 (P16).
- **ADR relacionados:** 0007, que deja a los agentes actualizar con Composer las herramientas de
  análisis y solo esas.
- **Bloque:** BD. **Mensajes:** `#016`–`#019`
- **Commits en piecesphp:**
  - `11fb9d06` `b0fdbcc3` `7332f4b9`: documentación y guarda, en `#016`;
  - `ca82802d` `4fc682ea` `43311b58` `7b45c761`, en `#018`.
- **Commits en los paquetes**, todos en `dev`:
  - database: `edacf6c` `e3e55d1`;
  - datastructures: `c12c067`;
  - geojson: `7a5ca38`;
  - html: `68aca08`.

## Qué se pidió

Nivelar los analizadores de los cuatro paquetes: pasar de phpstan 2.1.42 o 2.1.44 a la 2.2.12
de piecesphp. Una cifra medida con otro analizador no es comparable, y la comprobación 7 de
`verify-integrity` compara la versión instalada con la declarada.

## Qué se encontró al explorar

- **La guarda bloqueaba todo `composer update`.** Hizo falta el ADR 0007.
  - Excepción cerrada: tres herramientas nombradas, con versión fija.
  - De paso se cerró un hueco: la guarda no veía Composer lanzado a través de PHP.
- **En los paquetes, las herramientas viven en la raíz**, en `require-dev` con `^2.1`, y
  `vendor/` y `composer.lock` no se versionan. El mapa decía `bin/tools`, y se corrigió.
- **Los `04-desarrollo.md` de los cuatro paquetes son el mismo archivo**, el de database. La
  regla 30 los daba como fuente de verificación, y se corrigió.

## Qué se instruyó

- **`#016`.**
  - Commitear lo del arquitecto.
  - En cada paquete: `switch dev`, luego
    `composer update phpstan/phpstan:2.2.12 rector/rector:2.6.6 --working-dir=<paquete>`, y
    medir contra la línea base.
  - Registrar las versiones en `files/dev/shared-toolchain.json` solo si las cuatro cifras
    quedaban iguales.
- **`#018`**, tras un `#017` parcial.
  - Arreglar la guarda.
  - Igualar el bloque del trinquete del procesador de resultados de los paquetes con el de
    piecesphp.
  - Llevar la línea base de database a 18, con «3 murieron».
  - Registrar las versiones, con html pendiente.

## Qué reportó el coder

**`#017`: parcial.**
- datastructures y geojson, sin cambio: cero errores.
- database bajó de 21 a 18 sin cambiar el código: phpstan 2.2.12 deja de reportar tres
  `function.alreadyNarrowedType` de `ActiveRecord.php`. El coder se detuvo sin commitear, como
  se le pedía.
- html: Composer no resolvió. Su lock local, de antes de su 3.0.0, tiene
  `piecesphp/datastructures` v3.1.0 contra el `^4.0` de su `composer.json`.
- La guarda tomaba una redirección (`> f 2>&1`) por un paquete y bloqueaba el comando
  legítimo.

**`#019`: completado.**
- El bloque del trinquete es idéntico en los cinco repositorios.
- La línea base de database dice 18, con
  `[REPARTO] 18 <- 21 = 0 arreglos + 0 supresiones + 0 destapados + 3 murieron`.
- La provocación se vio fallar: con «2 murieron», «EL REPARTO NO CUADRA» y salida 1.
- El registro declara 2.2.12 en database, datastructures y geojson, y 2.1.42 en html.
- `verify-integrity` sin fallos; `gates` con 25 suites, 0 fallos y 2 no corridas por efectos
  externos.

## Qué quedó fuera

- **html** sigue con phpstan 2.1.42 y mide contra datastructures 3.1.0 (P23). Actualizar
  `piecesphp/datastructures` no es herramienta de análisis.
- **El procesador de los paquetes**: solo se igualó el bloque del trinquete. El resto del
  archivo difiere del de piecesphp.
- **El mensaje del trinquete** dice «murieron con el código borrado» también cuando es el
  analizador el que deja de verlos.
- **Ni fusión a `master` ni etiqueta** en los paquetes: los cambios son de instrumental y no
  llegan al framework.

## Aprendido

- **Subir el analizador puede bajar la cifra.** El instrumento tiene que poder decirlo sin
  llamarlo arreglo. Un trinquete que solo conoce «arreglos + supresiones» obliga a mentir o a
  reiniciar la cifra de partida. En piecesphp esto estaba resuelto desde T139; en los paquetes,
  no.
- **Un lock local que no se versiona puede quedar anclado a una versión que el propio
  `composer.json` ya no admite.** Nadie lo ve hasta que se intenta actualizar algo.
- **Instrucción con condiciones**: el PASO 3 de `#016` («solo si las cuatro cifras son
  iguales») evitó registrar una versión sobre una línea base que no cuadraba.
