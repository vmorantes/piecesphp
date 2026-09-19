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

- `sass:init` — Compila los archivos SASS en `src/statics/sass`.
- `sass:watch` — Observa cambios en los archivos SASS.
- `sass-vendor:init` — Compila estilos del área administrativa.
- `sass-vendor:watch` — Observa estilos del área administrativa.
- `sass-all` — Compila todos los estilos y limpia caché.
- `sass-all:watch` — Observa todos los estilos.
- `ts-vendor` — Compila TypeScript del core del framework.
- `js-vendor` — Compila JavaScript del core del framework.
- `js-vendor:watch` — Observa cambios en JavaScript del core.
- `init-project` — Ejecuta todas las tareas anteriores.
- `init-project:watch` — Ejecuta y observa todas las tareas anteriores.

---

## Tips
- Usa `gulp <tarea> --help` para ver opciones adicionales.
- Si tienes errores de dependencias, ejecuta `npm install` nuevamente.
