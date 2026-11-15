# PiecesPHP Framework

## La guía

- **[Estructura del framework](./content/structure.md)**: las carpetas del proyecto y qué va en cada una.
- **[Despliegue](./content/general.md)**: el entorno de desarrollo, sus dependencias y lo que conviene saber antes de publicar.
- **[Permisos y propiedad](./content/permissions.md)**: los permisos de archivos y carpetas en el servidor.
- **[Sistema de rutas](./content/routing.md)**: rutas y grupos sobre Slim 4, donde el nombre de la ruta es su permiso.
- **[Mappers y base de datos](./content/mappers.md)**: el mapeo de entidades y sus tablas.
- **[Tareas Gulp](./content/gulp.md)**: compilar los estilos y el TypeScript.
- **[Terminal](./content/terminal.md)**: `bin/cli` y sus acciones de administración y mantenimiento.
- **[Crear un módulo](./content/modules.md)**: dónde vive el código de un módulo y cómo se arma.
- **[El panel por dentro](./content/panel.md)**: cómo está hecho el panel de administración y qué usar en una vista suya.
- **[Mantener el framework](./content/maintain.md)**: para quien cambia el propio PiecesPHP, no solo un proyecto que parte de él.

## Características avanzadas

- **[Sistema de Colas](./new-features/queues.md)**: Procesamiento asíncrono de tareas pesadas.
- **[FreezeRequest](./new-features/freeze-request.md)**: Persistencia de contexto HTTP para ejecución diferida.
- **[HttpClient](./new-features/http-client.md)**: Cliente HTTP moderno y robusto.
- **[Sistema de Eventos](./new-features/events.md)**: Desacoplamiento de lógica mediante observadores.
- **[CronJobs](./new-features/cronjobs.md)**: Automatización de tareas recurrentes.
- **[Pruebas Unitarias (CLI)](./new-features/unit-testing.md)**: Suites que corren desde la terminal, y `bin/cli gates`.
- **[Archivos Protegidos](./new-features/protected-files.md)**: Subidas que solo se sirven con permiso.
- **[Exportador BBDD Nativo](./new-features/database-exporter.md)**: Volcados sin `mysqldump`.
- **[Importar y exportar datos](./new-features/data-transfer.md)**: Importadores y exportadores por definición, todo o nada, con credenciales de entrega única.
