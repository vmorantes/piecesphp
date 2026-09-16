# 0017 — El framework actualiza sus paquetes `piecesphp/*` con Composer cuando un lote lo pide

- **Estado:** Aceptada
- **Fecha:** 2026-09-16
- **Decide:** Product Owner («haz lo que debas con 4d», 2026-09-16), con el alcance del arquitecto
- **Estructural:** sí (amplía una excepción de la guarda sobre dependencias del producto)

## En cristiano

El framework usa cuatro paquetes que también son del propietario. Cuando uno de ellos se corrige y
saca versión nueva, el framework tiene que actualizarse para recibir el arreglo. Hasta ahora la
guarda de los agentes lo impedía siempre, porque en el framework el `composer.lock` se versiona y
cambiarlo es cambiar una dependencia del producto. Esta decisión deja actualizar esos cuatro
paquetes, y solo esos, cuando la instrucción lo nombra; cualquier otra dependencia sigue bloqueada.

## Contexto

- El lote `4d` corrige el escape doble del texto en `piecesphp/database` y lo publica como `v5.0.0`.
  El framework lo pide con `"piecesphp/database": "^4.0"` y lo instala desde Packagist:
  `src/composer.json` no declara repositorios (medido el 2026-09-16).
- La guarda (`guardia.py`) deja `composer update piecesphp/*` solo dentro de un paquete hermano
  (ADR 0008), cuyo lock no se versiona. En el framework lo bloquea a propósito.
- El último cambio de restricción de los paquetes en el framework (`1534be5c`, 2026-08-28) fue
  anterior a la guarda (ADR 0003, 2026-09-14).
- El PO decidió el 2026-09-16 que `4d` se haga entero, framework incluido, y que no se le consulte
  cada ruptura de la campaña.
- `src/composer.json` define `post-update-cmd` → `TasksManager::task`, que corre `composer install`
  en `bin/tools` y puede descargar `phpstan-src` con `wget`. No tiene nada que ver con actualizar un
  paquete del producto.

## Decisión

1. La guarda permite `composer update` **en el framework** (`--working-dir` = `<raíz>/src`) cuando
   **todos** los paquetes nombrados son `piecesphp/*`.
2. Sigue bloqueado, también con `piecesphp/*`:
   - `-w`, `-W`, `--with-dependencies` y `--with-all-dependencies`: arrastrarían dependencias de
     terceros;
   - `require`, `remove`, `install`, `upgrade` y `global`;
   - mezclar un `piecesphp/*` con cualquier otro paquete;
   - el framework sin `--working-dir` explícito.
3. La restricción de versión en `src/composer.json` la cambia el coder con una edición de texto,
   cuando la instrucción lo dicta. El `composer.lock` nunca se edita a mano (`00-core.md`).
4. Las instrucciones dictan la actualización con `--no-scripts`, y enumeran lo que cambia en el lock.
5. **Cuándo:** solo si un lote nombrado por el PO lo necesita, y siempre después de que el PO haya
   empujado la versión nueva del paquete, porque Composer la busca en Packagist.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Que el PO corra la actualización a mano | Le devuelve trabajo mecánico, y el PO pidió no ser consultado por cada paso de la campaña |
| Un repositorio `path` hacia `/var/www/html/vicsen/database` en `src/composer.json` | Cambiaría cómo instalan TODOS los clones, que no tienen ese directorio |
| Permitir `composer update` en el framework sin acotar | Abriría la puerta a subir cualquier dependencia de terceros, que sigue siendo del PO con alternativas delante |
| Esquivar la guarda | Prohibido: la guarda se ajusta con su prueba |

## Consecuencias

- **Lo bueno:** un arreglo en un paquete del PO llega al framework en la misma campaña, con la guarda
  activa.
- **Lo malo:**
  - un `composer update piecesphp/database` puede mover en el lock las dependencias PROPIAS de ese
    paquete si su restricción lo exige. Por eso la instrucción enumera el diff del lock y se para si
    cambia algo que no sea `piecesphp/*`;
  - depende de la red (Packagist y GitHub) y de que el PO haya empujado.

## Reversión

1. En `guardia.py`, volver a limitar `piecesphp/*` a `--working-dir` dentro de `HERMANOS`.
2. Quitar sus casos de `probar_guardia.py` y correrlo.
3. Quitar la excepción de `40-salvaguardas.md` §3.

Es completa. Las versiones ya instaladas en el framework no se revierten con esto: se revierten con
un commit que devuelva `composer.json` y `composer.lock` a su estado anterior.

## Verificación

- `probar_guardia.py`: permite `composer update piecesphp/database --working-dir=src --no-scripts`
  y bloquea `-W`, la mezcla con un tercero, `require` y la forma sin `--working-dir`.
- Provocación: sin la excepción nueva, el caso permitido cae; sin la exclusión de `-w`/`-W`, caen los
  bloqueos que la llevan.
- **Un caso previo cambia a propósito:** el de la época del ADR 0008 que bloqueaba la actualización
  simple de `piecesphp/datastructures` con `--working-dir=<raíz>/src` pasa a exigir el bloqueo de la
  misma orden con `--with-dependencies`, porque la forma simple es justo la que este ADR permite.
  Medido el 2026-09-16: 216/216.
