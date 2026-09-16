# Mantener el framework

Para quien cambia el propio PiecesPHP (su núcleo, sus módulos incluidos o sus herramientas), no solo un proyecto
que parte de él. Cada instalación es una copia del framework: **un cambio que rompe algo en silencio se copia a todos
los proyectos que clonen después**.

## Antes de dar un cambio por bueno

```bash
bin/phpstan                 # análisis estático; no puede subir el total de la línea base
bin/cli verify-integrity    # veintinueve comprobaciones de estructura
bin/cli gates               # todas las suites de pruebas; falla si alguna no corrió
```

- **`bin/phpstan`** compara contra `PHPStanResult.Summary.baseline.txt`. Si el total sube, falla. Si baja, la línea
  base se actualiza **a mano y en el mismo commit**, con la línea `[REPARTO]` que explica de dónde sale la diferencia
  (arreglos, supresiones, errores que murieron con código borrado).
- **Supresiones de PHPStan** (`bin/phpstan.neon`): van en dos listas, permanentes y temporales. Una temporal lleva
  la condición para retirarla. **El orden importa**: un error lo consume la primera entrada que casa.
- **`bin/cli gates`** no corre por defecto las suites que salen a la red o envían correo; `with=external` las incluye.
- **`bin/cli verify-integrity`** vigila, entre otras cosas, que ninguna carpeta de subidas quede sin proteger, que no
  crezcan las concatenaciones de SQL con valores de la petición y que la versión instalada de cada paquete
  `piecesphp/*` sea la última etiquetada.

## Reglas que no se negocian

- Rutas solo con `Route`/`RouteGroup`; URLs con `routeName()`; visibilidad con `allowedRoute()`.
- Todo método de ruta devuelve un `Response`.
- Un valor de la petición entra al SQL por marcador, nunca concatenado.
- Las tablas salen de los mappers con `bin/cli scheme-create`.
- No se edita `src/vendor/` ni los paquetes `piecesphp/*` desde aquí: son repositorios aparte, y un cambio en un
  paquete no llega al framework hasta que el paquete se versiona.
- Código e identificadores en inglés; textos visibles en español dentro de `__()`.

## Qué cambió para quien clona: `CHANGELOG.md`

Todo cambio que afecte a un proyecto que actualice su copia va al `CHANGELOG.md` de la raíz:

- las **rupturas**, numeradas, con «Qué hacer»;
- las correcciones y eliminaciones;
- en una versión mayor en curso, la sección **«CÓMO ACTUALIZAR — LEER ANTES DE FUSIONAR»**.

## Versiones y ramas

- `master` recibe las pre-versiones etiquetadas (`vX.Y.Z-alpha.N`, `-beta.N`, `-rc.N`).
- `last-stable` apunta **siempre** a la última versión estable. Quien quiera estabilidad clona esa rama.
- Una etiqueta no se mueve ni se borra nunca.

## Documentación que acompaña al código

- **Para agentes de IA:** `AGENTS.md` y `.agents/` (arquitectura, recetas, decisiones y estado del trabajo).
- **Para desarrolladores:** esta documentación (`source-docs/project/`) y la de la API (`source-docs/api/`).

Si un cambio hace falso un documento de cualquiera de las dos, se corrige **en el mismo commit**.
