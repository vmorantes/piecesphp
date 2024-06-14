# PiecesPHP Framework

## Tareas Gulp

### Instrucciones

Dentro de la carpeta src, ejecutar:
```bash
gulp TAREA
```
i.e.:
```bash 
gulp sass-all
```

### Tareas
- sass:init (para compilar los archivos en src/statics/sass)
- sass:watch (para observar los archivos en src/statics/sass)
- sass-vendor:init (para compilar los archivos de estilo del área administrativa)
- sass-vendor:watch (para observar los archivos de estilo del área administrativa)
- sass-all (compila todos los anteriores, borra caché automáticamente al cambiar el token de caché)
- sass-all:watch (observa todos los anteriores)
- ts-vendor (Compila los TypesScript del core del framework)
- js-vendor:watch (Compila y observa los TypesScript del core del framework)
- js-vendor (Compila los Javascript del core del framework)
- js-vendor:watch (Compila y observa los Javascript del core del framework)
- init-project (Ejecuta todas las tareas anteriores)
- init-project:watch (Ejecuta y observa todas las tareas anteriores)
