# 0007 — Excepción: los agentes actualizan las herramientas de análisis con Composer

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Product Owner (delegó la instrumentación el 2026-09-02) · Arquitecto (el alcance)
- **Estructural:** sí (excepción a una salvaguarda)

## En cristiano

Los agentes pueden actualizar, con Composer, las herramientas que analizan el código:
PHPStan, sus reglas de deprecaciones y Rector. Solo esas, y nombrándolas una a una. Todo lo
demás que toca dependencias sigue siendo del PO: instalar, añadir, quitar o actualizar
cualquier otra cosa. Existe porque el PO delegó en el arquitecto la instrumentación de
análisis, y la guarda impedía ejercer esa delegación.

## Contexto

- **La regla general** (`00-core.md`) prohíbe añadir dependencias sin preguntar, y la guarda
  (ADR 0003) bloquea todo `composer update|install|require|remove`.
- **La delegación del PO**, el 2026-09-02 a las 17:42: *«Todo la instrumentación de análisis en
  desarrollo está en tus manos»*. Recogida en la regla 30, «Cuándo se detiene…».
- **El lote BD** del mapa: igualar PHPStan en los cuatro paquetes (hoy 2.1.42 y 2.1.44) con el
  de `piecesphp` (2.2.12). Medido el 2026-09-14:
  - en los paquetes, la herramienta viene de `require-dev` con `^2.1`;
  - su `composer.lock` no se versiona, porque son librerías;
  - la restricción ya admite la 2.2.12.
  Solo cambia el `vendor/` local. Hace falta `composer update`.
- **La orden del PO**, el 2026-09-14: «Trabaja. Adelante.»

## Decisión

La guarda deja pasar `composer update` cuando **todos** los paquetes nombrados están en esta
lista cerrada: `phpstan/phpstan`, `phpstan/phpstan-deprecation-rules` y `rector/rector`.
- **Versión fija.** Se admite con la forma `paquete:versión` (por ejemplo,
  `phpstan/phpstan:2.2.12`). Es la forma recomendada: sin ella, Composer trae la última
  versión de Packagist y el repositorio se desnivela de los demás.
- **Opciones.** Se admiten `--with-dependencies` o `-W` y `--working-dir=<ruta>`.
- **Composer lanzado a través de PHP** (`php8.5 /usr/bin/composer …`) se trata igual. Antes
  del ADR, la guarda no lo veía.

Sigue bloqueado:
- `composer update` sin paquetes;
- actualizar cualquier otro paquete;
- `require`, `install`, `remove`, `upgrade` y `global`.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Que el PO ejecute él los `composer update` | Contradice su delegación y para el tramo en cada nivelación de herramientas |
| Permitir cualquier `composer update` | Es más de lo delegado: la delegación es sobre análisis, no sobre dependencias del producto |
| Admitir la forma `-d <ruta>` | Deja un argumento suelto que la guarda no distingue de un paquete. Con `--working-dir=` el valor va pegado |
| Quitar la guarda de Composer | La regla sigue viva para todo lo demás, y la red es barata |

## Consecuencias

- **Lo bueno:** las nivelaciones de herramientas (BD y las que vengan) no necesitan al PO.
- **Lo malo:**
  - un `composer update` de esas herramientas descarga de Packagist, así que es una conexión
    de red saliente. No es un servidor del PO;
  - con `--with-dependencies` puede arrastrar dependencias de esas herramientas, como
    `nikic/php-parser`. Es aceptable porque solo afecta al entorno de desarrollo. El
    reporte tiene que listarlas;
  - en `piecesphp`, `bin/tools/composer.lock` sí se versiona: el cambio llega al commit y queda
    a la vista.

## Reversión

1. En `guardia.py`, volver a bloquear todo `composer update`: quitar `HERRAMIENTAS_DE_ANALISIS`
   y su rama.
2. Quitar sus casos de `probar_guardia.py`.
3. Quitar la excepción de la regla 40 §3.

Reversión completa. Las versiones ya actualizadas se quedan.

## Verificación

`python3 -B .agents/scripts/guardas/probar_guardia.py`. Cada caso permitido tiene al lado su
bloqueo: `update` sin paquetes, un paquete ajeno, una mezcla de herramienta y paquete ajeno, y
`require` de una herramienta.
