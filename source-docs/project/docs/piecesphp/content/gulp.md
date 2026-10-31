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

**Estilos del núcleo** (`src/statics/core/`):

- `sass-compile-own-plugins` — Plugins propios (`statics/core/own-plugins/sass` → `own-plugins/css`).
- `sass-compile-general` — Estilos generales del panel (`statics/core/sass` → `statics/core/css`).
- `sass-vendor:init` — Las dos anteriores a la vez.
- `sass-vendor:watch` — Observa esas dos carpetas y recompila solo la que cambió.

Las hojas de acceso y recuperación están en su módulo (`src/app/classes/PiecesPHP/UserSystem/Statics/sass/`) y las compila
`sass-modules`.

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

### Direcciones dentro del SCSS: `static-url()`

Una dirección a un archivo propio dentro de una hoja (una imagen de fondo, otra hoja importada) se escribe con
`static-url()`, con la ruta relativa a donde queda el `.css` compilado, como un `url()` normal:

```scss
.error-page {
    background-image: url(static-url('../images/errors/masc.svg'));
}
```

Una hoja de CSS importada va igual, dentro de `@import url(…)`, y sale como un `@import` de CSS con su huella:

```scss
@import url(static-url('../fonts/poppins/poppins.css'));
```

Al compilar, la dirección sale con la huella del contenido del archivo (`…/masc.svg?cacheStamp=<16 caracteres>`): si la
imagen cambia, cambia la hoja, su versión, y el navegador pide las dos. Con la huella, el servidor les da un año de
caché; sin ella, el navegador pregunta en cada visita. **Si el archivo no existe, esa hoja no compila** y la tarea falla
con su nombre. Lo externo (`https://…`, `//…`) y `data:` se escriben con `url()` normal. `bin/cli verify-integrity`
señala un `url()` o un `@import` de un `.scss` propio que apunte a un archivo local sin `static-url()` (ADR 0067).

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
