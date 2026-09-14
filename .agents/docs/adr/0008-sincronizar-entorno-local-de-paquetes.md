# 0008 — Excepción: los agentes sincronizan el entorno local de los paquetes hermanos

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Arquitecto, por delegación expresa del PO: «Resuelve P22 y P23 como tu prefieras»
  (2026-09-14)
- **Estructural:** sí (excepción a una salvaguarda)

## En cristiano

En los cuatro paquetes hermanos, un agente puede actualizar con Composer las dependencias
`piecesphp/*`, además de las herramientas de análisis del ADR 0007. Solo dentro de esos cuatro
paquetes, porque ahí el `composer.lock` no se versiona: lo que cambia es el entorno local de
desarrollo, no algo que se entregue. En `piecesphp`, cualquier dependencia sigue siendo del PO.
Existe porque el lock local de `html` se quedó atrás de su propio `composer.json` y lo medía todo
contra `piecesphp/datastructures` 3.1.0 cuando pide `^4.0` (P23).

## Contexto

- El ADR 0007 deja actualizar tres herramientas de análisis y nada más.
- En `#017`, `composer update phpstan/phpstan:2.2.12 rector/rector:2.6.6 --working-dir=…/html`
  no resolvió:
  - el lock local (ignorado por `.gitignore:2`, fechado el 2026-08-26) fija
    `piecesphp/datastructures` v3.1.0;
  - su `composer.json` pide `^4.0` desde `7350039`.
  Composer exige incluir `piecesphp/datastructures` en el update.
- En los cuatro paquetes, `vendor/` y `composer.lock` están ignorados (medido en `#016` con
  `git check-ignore -v`). Actualizar ahí no cambia ningún archivo versionado.
- Consecuencia de no hacerlo: el `bin/phpstan` de html analiza contra una API de
  `datastructures` que su `composer.json` ya no admite. Su cifra (3) no dice lo que parece.

## Decisión

La guarda deja pasar `composer update` cuando se cumplen las dos condiciones:
1. **Los paquetes.** Todos los nombrados (con o sin `:versión`) están en `HERRAMIENTAS_DE_ANALISIS`
   (ADR 0007) o son uno de `piecesphp/database`, `piecesphp/datastructures`, `piecesphp/geojson`
   y `piecesphp/html`.
2. **El directorio.** Si hay alguno de `piecesphp/*`, el comando lleva `--working-dir=<ruta>` y
   esa ruta resuelve a uno de los cuatro paquetes hermanos.

Sin `--working-dir`, o apuntando a `piecesphp`, un `piecesphp/*` sigue bloqueado.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Que el PO ejecute el comando | Delegó la decisión; devolverle la ejecución contradice la delegación |
| `-W` en el update de herramientas | Deja que Composer mueva cualquier dependencia de cualquier árbol, sin nombrarla |
| Permitir `piecesphp/*` en cualquier directorio | En `piecesphp`, `src/composer.lock` se versiona: sería cambiar la versión de una dependencia del producto, que es punto serio (regla 30) |
| Borrar el lock y `composer install` | Destruye algo no versionado y reinstala todo, no solo lo que hace falta |

## Consecuencias

- **Lo bueno:**
  - `html` se nivela.
  - Un lock local desfasado deja de bloquear las nivelaciones siguientes.
- **Lo malo:**
  - Es una conexión saliente a Packagist o al VCS de los paquetes, igual que en el ADR 0007.
  - Un update de `piecesphp/*` en un paquete puede mover la cifra de PHPStan, porque se analiza
    contra otra API. Eso es medición, no regresión: se registra con su reparto.

## Reversión

1. En `guardia.py`, quitar `PAQUETES_HERMANOS_COMPOSER` y la rama de `--working-dir`.
2. Quitar sus casos de `probar_guardia.py`.
3. Quitar la excepción de la regla 40 §3.

Reversión completa. Los locks ya actualizados se quedan: no están versionados.

## Verificación

`python3 -B .agents/scripts/guardas/probar_guardia.py`. Casos permitidos y, al lado, su
bloqueo:
- `piecesphp/datastructures` sin `--working-dir`;
- `piecesphp/datastructures` con `--working-dir` a `piecesphp` o a una ruta ajena;
- `piecesphp/datastructures` junto a un paquete de terceros.
