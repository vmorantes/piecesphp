# Avisos del sistema y mantenimiento

!!! warning "«Mantenimiento» aquí no es «sitio en mantenimiento»"
    Esta página es el **panel de tareas** de `root`: limpiar caché, borrar enlaces rotos. El sitio
    sigue funcionando para todo el mundo.

    Para dejar el sitio fuera de servicio y que responda 503, ve a
    [Modo mantenimiento](maintenance-mode.md).


El módulo del núcleo `PiecesPHP\SystemStatus` (`src/app/classes/PiecesPHP/SystemStatus/`) reúne en un sitio lo que el
sistema necesita que alguien revise, y las tareas de mantenimiento que antes solo existían por terminal.

## Para quien usa el panel

**Avisos del sistema** (menú de la barra superior; root y administrador general). Una tabla con los avisos activos:
gravedad (grave, atención, información), el mensaje, su estado y, si el aviso lo trae, un botón «Cómo arreglarlo» que
lleva a donde se arregla. Algunos avisos salen además como **aviso flotante** en la parte inferior del panel.

- **Root puede ocultar** los avisos que admiten ocultarse. Un aviso oculto deja de salir como flotante, pero **sigue en
  la tabla, marcado «Oculto»**: ocultar no lo resuelve. Desde ahí mismo se vuelve a mostrar.
- El aviso de la `app_key` de relleno se puede ocultar también, como siempre, desde «Seguridad e IA».

**Mantenimiento** (solo root):

- **Estado:** cuántos enlaces hay en `statics/server-delegated` y cuántos están rotos (con los 50 primeros), el tamaño de
  las cachés de WebP y de Publicaciones, y la marca de estáticos.
- **Borrar enlaces rotos:** borra solo los enlaces que apuntan a un archivo que ya no existe, y las carpetas que queden
  vacías. Nunca sigue un enlace ni toca su destino. El panel lo hace como el usuario del servidor web, así que no hace
  falta ningún permiso de administrador del sistema. Queda en el registro de acciones.
- **Limpiar toda la caché:** la misma limpieza que «Limpiar caché» de la barra superior (marca de estáticos, cachés de
  WebP y de Publicaciones, y todos los enlaces de `server-delegated`, que se vuelven a crear al servirse).

## Los avisos que trae el framework

`SystemStatusRoutes::registerCoreAlerts()` registra **13 avisos**. Ninguno es ocultable salvo el primero.

| Clave | Gravedad | Quién lo ve | Flotante | Cuándo salta | Dónde se arregla |
| :-- | :-- | :-- | :-- | :-- | :-- |
| `app-key-placeholder` | grave | root y administrador general | sí | La `app_key` es la de relleno. **Ocultable.** | `configurations-system-security` («Seguridad») |
| `legacy-extensions-folder` | atención | root y administrador general | sí | Existe la carpeta vieja `app/config/final-configurations-includes`; se sigue cargando. | Moverla a `app/config/extensions` |
| `backup-overdue` | atención | root | sí | La política de respaldos está encendida y el último respaldo es más viejo que dos veces el intervalo configurado, o no hay ninguno. | `configurations-system-backups` («Respaldos»); revisar que corran las tareas programadas |
| `server-delegated-broken-links` | atención | root | no | Hay enlaces rotos en `statics/server-delegated`. | `system-status-maintenance` («Estado y cachés») |
| `mail-test-mode` | info en local, atención fuera | root | no | El correo está retenido (modo de pruebas activo). | `configurations-integrations-mail` («Correo») |
| `mail-sin-declarar` | grave | root | no | La instalación no declara su entorno, así que el correo se retiene. | Crear `app/config/environment.php`, o declarar la entrega en «Correo» |
| `mail-retenido-en-produccion` | grave | root | no | Entorno de producción con el correo retenido: nadie recibe nada. | «Correo»: poner la entrega en «Real» |
| `mail-real-en-local` | grave | root | no | Máquina local con el correo saliendo de verdad por el SMTP. | «Correo»: poner la entrega en «Retenido» |
| `mail-con-fallos` | grave | root | sí | Hay correos que no llegaron a ningún sitio en las últimas 24 horas (umbral: 1 fallo). Se apaga solo al pasar la ventana. | `system-status-mail-log` («Registro de correos»); `bin/cli mail-doctor` |
| `mail-log-sin-tabla` | grave | root | sí | Falta la tabla del registro de correos: no se anota ningún envío. | Aplicar el archivo de actualización que nombra el propio aviso |
| `mail-log-sin-cuerpo` | atención | root | sí | La tabla del registro existe pero le falta la columna del cuerpo. | Aplicar el archivo de actualización que nombra el propio aviso |
| `invalid-route-pattern` | grave | root | sí | Se descartaron rutas por tener un patrón que no se puede analizar. | Corregir el patrón (están en el log) |
| `environment-not-configured` | info | root | no | No existe `src/app/config/environment.php`: la instalación funciona como producción. | Crearlo desde `environment.example.php` |

«Gravedad» sigue el campo `severity` del código (`DANGER` = grave, `WARNING` = atención, `INFO` = información). Los
avisos de correo no se pueden ocultar: un fallo de correo que se puede silenciar vuelve a ser un fallo que nadie ve.

## Desde la terminal: `bin/cli system-alerts`

Los mismos avisos, sin entrar al panel. Útil al instalar o desplegar, o en un servidor sin navegador:

```bash
bin/cli system-alerts          # en un servidor
bin/cli system-alerts --local  # en tu máquina de desarrollo
```

```text
[PELIGRO] app-key-placeholder — La app_key es la de relleno: … → ruta: configurations-system-security
[INFO] environment-not-configured — El entorno no está configurado: la instalación funciona como producción. …
Total: 2 aviso(s) activo(s).
```

- Enseña **todos los avisos activos**, sean para quien sean, con su gravedad (`PELIGRO`, `ATENCIÓN`, `INFO`).
- Un aviso oculto en el panel también sale, marcado con «(oculto en el panel)»: ocultarlo no lo arregla.
- `→ ruta:` es el nombre de la ruta del panel donde se arregla.
- Sin avisos, dice «Sin avisos activos.». Es solo lectura: no oculta ni muestra nada.
- Solo root. Un aviso cuya comprobación falla no sale, y su error va al log.

## Para quien desarrolla: registrar un aviso

Un módulo registra sus avisos una vez, en el `routes()` de su clase `…Routes`:

```php
\PiecesPHP\SystemStatus\SystemAlertRegistry::register(new \PiecesPHP\SystemStatus\SystemAlert(
    'mi-modulo-sin-configurar',                                   // clave en kebab-case, única
    \PiecesPHP\SystemStatus\SystemAlert::SEVERITY_WARNING,         // INFO, WARNING o DANGER
    fn() => __(MiModuloLang::LANG_GROUP, 'Falta configurar la clave de la API.'),
    fn() => get_config('mi_modulo_api_key') === null,             // ¿está activo?
    true,                                                          // root puede ocultarlo
    [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL], // quién lo ve
    true,                                                          // aviso flotante
    'mi-modulo-configuracion',                                     // ruta donde se arregla (opcional)
    __(MiModuloLang::LANG_GROUP, 'Configurar')                     // texto del botón (opcional)
));
```

Reglas:

- **La comprobación de un aviso flotante se ejecuta en cada página del panel:** tiene que ser barata (leer una
  configuración, no recorrer carpetas ni consultar tablas grandes). Un aviso caro va sin flotante: solo se evalúa en la
  página de avisos.
- **Si la comprobación lanza una excepción, el aviso cuenta como inactivo** y el error va al log: un aviso roto no tumba
  el panel.
- La clave repetida es un error de programación, que salta al registrar.
- Los avisos ocultos se guardan en la configuración (`system_alerts_hidden`).

## Referencia

- `SystemAlert`: `key()`, `severity()`, `message()`, `isDismissible()`, `audience()`, `showAsNag()`, `fixRoute()`,
  `fixLabel()`.
- `SystemAlertRegistry`: `register()`, `all()`, `get()`, `visibleFor(int $tipo)`, `nagsFor(int $tipo)`, `isActive()`,
  `hidden()`, `isHidden()`, `hide()`, `show()`.
- `ServerDelegatedLinks`: `scan()`, `deleteBroken()`, `directorySize()`.
- Tarea de terminal: `Terminal\Tasks\SystemAlertsTask` (`system-alerts`); `lines()` devuelve las líneas sin imprimir.
- Rutas, bajo `<zona administrativa>/system-status/`:
  - `system-status-alerts` (GET; root y administrador general) y `system-status-alerts-toggle` (POST, root);
  - `system-status-maintenance` (GET, root), `system-status-maintenance-broken-links` (POST, root) y
    `system-status-maintenance-clean` (POST, root);
  - `system-status-site-maintenance` (GET, root) y `system-status-site-maintenance-save` (POST, root): el modo
    «Sitio en mantenimiento», descrito en [Modo mantenimiento](maintenance-mode.md);
  - `system-status-mail-log` (GET, root), `system-status-mail-log-datatables` (GET, root) y
    `system-status-mail-log-body` (GET, root, con `{id}`): el registro de correos. El cuerpo tiene su propio nombre
    porque es otro permiso que el del listado.
