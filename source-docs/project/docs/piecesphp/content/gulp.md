# Tareas Gulp en PiecesPHP

> **Nota:** Se recomienda NodeJS v22.12.x para máxima compatibilidad con las tareas de Gulp.

## Introducción
Gulp es un sistema de automatización de tareas para Node.js. PiecesPHP utiliza Gulp para compilar estilos, scripts y otras tareas de desarrollo.

---

## Instrucciones básicas

Desde la carpeta `src`, ejecuta:

```bash
gulp <tarea>
```

Por ejemplo:

```bash
gulp sass-all
```

---

## Tareas disponibles

Las que compilan terminan; las `*:watch` observan y no terminan (se cortan con `Ctrl+C`).

**Estilos del proyecto** (`src/statics/sass/` → `src/statics/css/`, sin `imports/`):

- `sass` y `sass:init` — Compilan los SASS del proyecto.
- `sass:watch` — Observa `src/statics/sass/`.

**Estilos del núcleo** (`src/statics/core/` y `src/statics/login-and-recovery/`):

- `sass-compile-own-plugins` — Plugins propios (`statics/core/own-plugins/sass` → `own-plugins/css`).
- `sass-compile-general` — Estilos generales del panel (`statics/core/sass` → `statics/core/css`).
- `sass-compile-users` — Acceso y recuperación (`statics/login-and-recovery/sass` → `.../css`).
- `sass-vendor:init` — Las tres anteriores a la vez.
- `sass-vendor:watch` — Observa esas tres carpetas y recompila solo la que cambió.

**Estilos de los módulos** (`src/app/classes/**/sass/**/*.scss` → la carpeta `css/` hermana):

- `sass-modules` y `sass-modules:init` — Compilan los SASS de todos los módulos.
- `sass-modules:watch` — Observa los de los módulos y los parciales del panel que incluyen.

**Todos los estilos:**

- `sass-all` — Compila los cuatro grupos de arriba (plugins propios, generales, acceso y recuperación, proyecto) y
  los módulos.
- `sass-all:watch` — Observa los mismos (`sass:watch`, `sass-modules:watch` y `sass-vendor:watch`).

**Scripts del núcleo:**

- `ts-vendor` — Compila el TypeScript de `src/statics/core/ts/` a JavaScript en `src/statics/core/js/`.
- `ts-vendor:watch` — Observa ese TypeScript y vuelve a compilarlo.
- `js-vendor` — Concatena y minifica los JavaScript del núcleo en `src/statics/core/js/configurations.min.js`.
- `js-vendor:watch` — Observa esos JavaScript.

**Utilidades:**

- `clean-cache` — Ejecuta `../bin/cli clean-cache`: fuerza que todos los visitantes descarguen los estáticos de nuevo
  (ver más abajo).
- `api-build` — Genera la documentación de la API: `cd ../source-docs/api && mkdocs build --clean` (necesita
  `mkdocs` instalado).

**Conjuntos:**

- `init-project` — Ejecuta en paralelo `ts-vendor`, `js-vendor` y `sass-all`. **No** incluye `clean-cache`,
  `api-build` ni ninguna tarea `*:watch`.
- `init-project:watch` — Ejecuta `init-project` y deja observando `sass-all:watch` y `js-vendor:watch`. **No**
  observa el TypeScript: para eso, `ts-vendor:watch` aparte.

---

## Cómo compila SASS

Con un adaptador propio (`sassCompileAdapter()` en `src/gulpfile.js`) sobre el paquete `sass` y su API moderna. **No
reescribe un `.css` ni su `.map` si su contenido no cambió**, así que su versión (y la caché de los visitantes) se
conserva. Un error de Sass enseña archivo y línea, compila las demás hojas y hace fallar la tarea.

## Compilar no limpia la caché

Cada estático lleva la versión de su propio archivo: al compilar, el CSS o el JS que cambia cambia de
versión solo, y el navegador lo vuelve a pedir; lo demás sigue en caché. **Compilar ya no renueva la marca global.**
Para forzar que todos los visitantes descarguen todo de nuevo: `gulp clean-cache` o `bin/cli clean-cache` (renueva la
marca, borra las imágenes optimizadas y los accesos directos de `statics/server-delegated` que el usuario que la
ejecuta pueda borrar: desde el terminal, los que creó el servidor web suelen quedarse), o el botón equivalente en
**Sistema → Estado y cachés**.

## Tips
- `gulp --tasks` lista las tareas que define `src/gulpfile.js`.
- Si tienes errores de dependencias, ejecuta `npm install` nuevamente.
