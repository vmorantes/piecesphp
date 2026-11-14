# Salud de la instalación

Una pantalla del panel que dice **cómo está esta instalación**: versiones, entorno, base de datos, tareas y carpetas. Es
la primera de **Configuración › Sistema** y **solo la ve root** (ruta `system-status-health`, módulo
`PiecesPHP\SystemStatus`).

## Las dos reglas que la gobiernan

1. **Cada fila sale de algo que ya existe y dice de dónde**: la columna «Origen» nombra la constante, la función o el
   archivo del que sale el dato. Ninguna fila se escribe a mano. Lo comprueba la suite `core/system-health`.
2. **Hechos, nunca valores**: dice que una llave está definida o que una carpeta se puede escribir, pero no enseña llaves,
   contraseñas, el servidor ni el usuario de la base. La suite busca en la página la `app_key` y la contraseña del correo,
   y falla si aparecen.

## Qué enseña

**En pestañas**: la primera, **«Resumen»**, reúne solo lo que avisa o falta de todos los bloques (y dice «Todo en
orden.» si no hay nada); después, una pestaña por bloque.

| Bloque | Qué | De dónde sale |
| :-- | :-- | :-- |
| Framework | Versión, fecha, commit y su origen; de qué distribución salió; el entorno | `AppVersion`, la marca `.piecesphp-distribution`, `AppEnvironment` |
| Paquetes PHP | Cada dependencia de `src/composer.json`: restricción, versión instalada y licencia | `PiecesPHP\Core\InstalledPackages` (lo instalado por Composer) |
| PHP | Versión contra la exigida, cada extensión exigida cargada o no, límites, OPcache, ffmpeg y ffprobe | `composer.json`, `extension_loaded()`, `ini_get()`, `FfmpegAudioAdapter` |
| Base de datos | Motor y versión, juego de caracteres, número de tablas y las tablas declaradas que faltan | La conexión, `information_schema` y los mappers |
| Respaldos y tareas | El último respaldo y el siguiente; la última ejecución de cada tarea programada | La política de respaldos y el libro de tareas |
| Carpetas | Si cada carpeta que debe escribirse se puede escribir | `src/app/config/writable-dirs.txt` y `secure-keys/` |
| Seguridad | Si las carpetas de subidas niegan PHP (con Apache; con otro servidor, «depende del servidor»), la lista de dominios permitidos y el entorno | El `.htaccess` del subsistema (`ProtectedUploads`), `$config['allowed_hosts']`, `AppEnvironment` |

## Lo que todavía no enseña

Los resultados de las verificaciones, cuánto se ha desviado el clon del framework, la última versión publicada, las
versiones de las librerías de JavaScript y las actualizaciones SQL aplicadas, entre otras: no tienen todavía una fuente
en una instalación. La lista está en el registro del framework, como candidatos a crecer.

## Para un clon

`InstalledPackages` sirve también a «Acerca de»: las dos pantallas leen las versiones y licencias de lo instalado, así
que no hay que mantener una lista a mano. Una carpeta escribible nueva se añade a `writable-dirs.txt` y la pantalla la
mira sola.
