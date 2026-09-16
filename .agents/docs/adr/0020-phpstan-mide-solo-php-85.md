# 0020 — PHPStan mide solo PHP 8.5 en los cinco repositorios

- **Estado:** Aceptada
- **Fecha:** 2026-09-16
- **Decide:** Arquitecto, por la delegación del PO sobre la instrumentación de análisis («Toda la instrumentación
  de análisis en desarrollo está en tus manos», 2026-09-02)
- **Reemplaza en parte:** la decisión de las dos pasadas (8.4 y 8.5) con unión, registrada en
  `.agents/context/18-siguientes-ventanas.md` y en `19-leyes.md:797`
- **Estructural:** sí (cambia el instrumental común de los cinco repositorios y sus líneas base)

## En cristiano

El análisis estático corría dos veces: como si el código fuera a ejecutarse en PHP 8.4 y en PHP 8.5, y juntaba los
errores de las dos. Se hacía porque el framework aceptaba 8.4. Ya no lo acepta: los cinco repositorios exigen PHP 8.5.
Los errores que solo existen en 8.4 describen un servidor que no puede ejecutar este código, así que la pasada de 8.4
se retira y el análisis mide solo 8.5.

## Contexto

- **Medido el 2026-09-16:** `src/composer.json` exige `"php": ">=8.5 <8.6"`, y los cuatro paquetes fijan
  `config.platform.php` en `8.5.0` con el mismo rango.
- `bin/phpstan` corre dos pasadas (`phpVersion` 80400 y 80500) y el total de la línea base es la unión
  (`bin/phpstan:40-103`). La unión nació porque un RANGO en `phpVersion` reporta la intersección y cegó toda
  deprecación exclusiva de 8.5 (`19-leyes.md:797`). Aquella lección sigue en pie: **nunca un rango**.
- `files/dev/shared-toolchain.json` exige a los cinco las marcas `80400.generated.neon`, `80500.generated.neon` y
  «DOS PASADAS Y LA UNIÓN»; la comprobación 7 de `verify-integrity` falla si un repositorio se desvía.
- Los cuatro paquetes llevan además un `bin/phpstan-process-result.php` anterior a la puerta de patrones sin casar
  (lote 10, ronda C).

## Decisión

1. `bin/phpstan` corre **una sola pasada**, con `phpVersion` fijo en 80500 derivado del `.neon`, en los cinco
   repositorios. Nunca un rango.
2. El bloque `phpVersion` del `.neon` queda en `min: 80500`, `max: 80500`, con su comentario al día.
3. `bin/phpstan-process-result.php` lee solo la pasada de 8.5, y los cuatro paquetes reciben la puerta de patrones sin
   casar.
4. Cada línea base baja lo que solo existía en 8.4, con su `[REPARTO]` en «murieron» y una nota de medición que dice
   que es la pasada retirada, no código borrado.
5. `shared-toolchain.json` cambia sus marcas a las de la pasada única.

## Alternativas descartadas

- **Dejar las dos pasadas.** Barato hoy, pero la línea base arrastra errores de un PHP que no puede ejecutar el
  código, y quien la lea cree que son deuda real.
- **Un rango `{min: 80500, max: 80599}`.** Un rango reporta la intersección: es la trampa que motivó la unión.
- **Retirarla solo en `piecesphp`.** La comprobación 7 exige el mismo instrumental en los cinco: divergir es lo que
  esa puerta existe para impedir.

## Consecuencias

- Buenas: la cifra de cada línea base describe solo el PHP soportado; `bin/phpstan` tarda la mitad.
- Malas: cinco líneas base se mueven a la vez, y la historia de cifras deja de ser comparable antes y después de este
  cambio (se dice en la nota de cada una). Si algún día se vuelve a soportar un segundo PHP, hay que reponer la unión.

## Reversión

1. En los cinco repositorios, restaurar `bin/phpstan` y `bin/phpstan-process-result.php` a la forma de dos pasadas
   (el commit anterior a este cambio en cada uno).
2. Devolver `phpVersion` a `min: 80400` y las marcas de `shared-toolchain.json`.
3. Correr `bin/phpstan` y fijar cada línea base con su `[REPARTO]` en «destapados».

## Verificación

- `bin/phpstan` en los cinco: una pasada, sale 0, total igual a su línea base nueva, sin «was not matched».
- `bin/cli verify-integrity` (comprobación 7) en verde con las marcas nuevas.
- Provocación: una marca vieja en un paquete hace fallar la comprobación 7.
