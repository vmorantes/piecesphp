# Estructura del Framework

PiecesPHP sigue una organización modular y profesional para separar la lógica de negocio, el núcleo del sistema, los activos estáticos y las herramientas de mantenimiento.

## Directorios Principales

- **`bin/`**: Ejecutables de desarrollo: `bin/cli` (la terminal del framework), `bin/phpstan`, `bin/rector`, y
  guardas de verificación.

- **`databases/`**: Scripts SQL de vistas, funciones y datos iniciales. Las **tablas** no se escriben aquí a mano:
  salen de los mappers con `bin/cli scheme-create` (ver [Mappers](./mappers.md)).

- **`docs/`**: Salida de `mkdocs build` de esta documentación (`site_dir` en `source-docs/project/mkdocs.yml`). No existe
  hasta que se construye; no se versiona ni se edita a mano.

- **`files/`**: Recursos auxiliares: `dev/` guarda datos de máquina de las herramientas de verificación (líneas base,
  firmas); también hay traducciones públicas e instrucciones de Webflow. La colección de Postman **no** está aquí,
  sino en `source-docs/api/`.

- **`secure-keys/`**: Claves de servicios del producto de cada instalación (por ejemplo, la llave de los cronjobs).
  Ignoradas por git: nunca se versionan ni se comparten.

- **`source-docs/`**: Fuentes de la documentación en Markdown y su configuración de MkDocs: `project/` (esta guía) y
  `api/` (la API y la colección de Postman).

- **`.agents/`, `.claude/`, `AGENTS.md`, `CLAUDE.md`**: Documentación y reglas para los agentes de IA que trabajan en
  el repositorio (para desarrollar sobre PiecesPHP, lo útil está en `.agents/context/`). No forman parte de la
  aplicación.

- **`src/`**: **Raíz de la aplicación web.**

    - `index.php`: Punto de entrada único (Front Controller).

    - `.htaccess`: Configuración de Apache para enrutamiento, cabeceras de seguridad (CSP, HSTS, `X-Frame-Options`…),
      caché de los estáticos y compresión. **No lleva CORS**: ese lo ponen `src/app/config/containers.php` y
      `src/app/core/bootstrap.php`.

    - **`app/`**: Lógica interna de la aplicación.
        - `classes/`: Módulos PSR-4 (Controllers, Mappers, Views).
        - `config/`: Archivos de configuración de la instancia (BD, rutas, menú).
        - `controller/`: Solo dos controladores sueltos: `ContactFormsController` y `PublicAreaController`. El panel y
          los usuarios viven como módulos en `classes/PiecesPHP/` (`AdminPanelController` en
          `classes/PiecesPHP/AdminPanel/Controllers/`; `UsersController` y `UsersModel`, los usuarios y sus intentos de
          acceso, en `classes/PiecesPHP/UserSystem/`). No existe `src/app/model/`.
        - `core/`: Núcleo del framework (Bootstrap, clases base, suites de pruebas en `system-controllers/local-tests/`).
        - `lang/`: Directorios de idiomas globales.
        - `view/`: Vistas de sistema y layouts base (`view/panel/layout/`: el esqueleto del panel).
        - `cache/` y `logs/`: generados en ejecución; necesitan permiso de escritura.

    - `statics/`: Recursos estáticos públicos (JS, CSS, Imágenes, Plugins; `uploads/` para lo subido).
    - `dumps/`: Volcados de `bin/cli db-backup`.
    - `tmp/`: Temporales de ejecución.
    - `vendor/`, `composer.json`, `composer.lock`: dependencias de Composer. `vendor/` no se edita.
    - `gulpfile.js`: tareas de compilación de SASS y JS (ver [Gulp](./gulp.md)).

- **`tasks/`**: Contiene el `TasksManager.php`. Las tareas de mantenimiento del framework están en la terminal
  (`bin/cli help`; ver [Terminal](./terminal.md)).

---

## Detalle de Configuraciones (`src/app/config/`)

Los archivos en este directorio definen el comportamiento y las constantes de la aplicación:

- **`assets.php`**: Registra recursos estáticos globales cargados en el sistema (ej. SweetAlert, jQuery, CSS de marca). Gestiona librerías front-end y sus dependencias (plugins/adaptadores).

*Ejemplo de registro de librería:*

```php
<?php
$assets['nombre_lib']['css'] = ['statics/path/style.css'];
$assets['nombre_lib']['js'] = ['statics/path/script.js'];
$assets['nombre_lib']['plugins'] = [
    'plugin_name' => [
        'js' => ['statics/path/plugin.js']
    ]
];
```

- **`autoloads.php`**: Define cargadores automáticos adicionales complementarios a Composer. Permite mapear namespaces PSR-4 a rutas específicas del proyecto.

*Ejemplo de registro PSR-4:*

```php
<?php
return [
    [
        'namespaces' => "Mi\\Namespace\\Ejemplo",
        'psr4' => true,
        'path' => app_basepath('mi_carpeta'),
    ],
];
```

- **`config.php`**: Configuración maestra que incluye el nombre de la app, dominio, llaves de seguridad, zona horaria y paleta de colores de la interfaz.

- **`constants.php`**: Define constantes de sistema, rutas lógicas y banderas (`flags`) de activación para los módulos integrados (News, Publications, Forms, etc.).

- **`containers.php`**: Definición de servicios (DI) para Slim: manejadores de errores 404/403, lógica CORS y variables CSS que se inyectan dinámicamente desde la configuración global.

- **`cookies.php`**: Configuración de seguridad y persistencia de las cookies de sesión y usuario, gestionando atributos como `Secure`, `HttpOnly` y `SameSite`.

- **`critical-definitions.php`**: Es el primer archivo cargado por el sistema. Contiene definiciones de constantes críticas que determinan el modo de operación básico del framework.

- **`database.php`**: Define múltiples perfiles de conexión a bases de datos (host, usuario, clave, nombre), permitiendo manejar diferentes conexiones de forma simultánea.

- **`final-configurations.php`**: Punto de inyección de grupos de traducciones y cargador automático para scripts adicionales en `extensions/`.

- **`functions.php`**: Declaración de funciones globales de ayuda (`helpers`). Por ejemplo, funciones para procesar DataTables o generar selectores de usuarios.

- **`lang.php`**: Configura los lenguajes soportados (`es`, `en`, etc.), el idioma predeterminado y la ubicación de los archivos de traducción.

- **`menu.php`**: Construcción programática de los menús (sidebar) mediante colecciones de `MenuGroup` y `MenuItem`, con validación de visibilidad basada en roles.

- **`roles.php`**: Configura el sistema de permisos, definiendo qué rutas de Slim son accesibles para cada tipo de usuario (Root, Admin, General).

*Ejemplo de definición de rol:*

```php
<?php
$config['roles']['types'][] = [
    'code' => 1,
    'name' => 'ADMIN',
    'allowed_routes' => ['users-list', 'admin-error-log']
];
```

- **`routes.php`**: Orquestador central de todas las rutas. Utiliza las clases `Route` y `RouteGroup` para envolver la lógica de Slim 4, facilitando el control de acceso automático.

*Ejemplo de ruta protegida:*

```php
<?php
new PiecesRoute('/perfil', '\MiModulo\Controllers\MiModuloController:index', 'mi-perfil', 'GET', true);
```

---

### Inclusiones Adicionales (`src/app/config/extensions/`)

Archivos cargados al final del ciclo de configuración para extender la funcionalidad:

- **`add-dynamic-translations.php`**: carga y asocia traducciones dinámicas (por ejemplo, desde la base de datos o
  desde la lógica de un módulo) en los grupos de idiomas. **Ojo: este archivo vive en
  `src/app/core/extensions/`, no en la carpeta del clon**; se nombra aquí porque se carga en el mismo punto del
  ciclo. Los archivos que un clon edita son los ocho que lista el recuadro de abajo.

- **`api-keys.php`**: Centraliza las llaves de servicios externos (Mapbox, GeoIP, reCAPTCHA, etc.) para uso en backend y frontend.

- **`cronjobs.php`**: Registro de tareas programadas (CronJobs).

*Ejemplo:*

```php
<?php
$cronjobs[] = CronJobTask::make('Mi Tarea', function() {
    return ['success' => true, 'message' => 'OK'];
})->dailyAt("00:00");
```

- **`event-listeners.php`**: Define acciones automáticas ante eventos (ej. `InitRoutes`).

*Ejemplo:*

```php
<?php
BaseEventDispatcher::listen('NombreEvento', function($data) {
    // Lógica
});
```

!!! danger "El SMTP NO se configura aquí, y hasta el 2026-10-03 esta página decía que sí"

    Esta página describía un archivo `mailing.php` en esta carpeta con «parámetros de conexión
    SMTP» y un ejemplo que guardaba host, usuario y **contraseña en claro** en una opción llamada
    `mailing_settings`. **Las dos cosas eran falsas**: no existe ningún
    `src/app/config/extensions/mailing.php`, y `mailing_settings` no se lee en ningún sitio del
    framework. Quien siguiera esa receta creaba un archivo que sí se carga, dejaba ahí su
    contraseña SMTP en texto plano y el correo seguía sin salir.

    **Cómo se configura el correo de verdad:** desde el panel, en **Integraciones → Correo**. Se
    guarda en la opción **`mail`**, como JSON **comprimido y cifrado**, y la lee
    `MailConfig::loadConfigutarion()`
    (`src/app/core/psr4/PiecesPHP/Core/ConfigHelpers/MailConfig.php:329`). **No hay ningún archivo
    de configuración donde escribir las credenciales del SMTP, y es a propósito.**

    **La entrega** (si el correo sale de verdad o se retiene) es una opción **aparte**,
    `mail_delivery`, para que la pantalla del SMTP no pueda machacarla al guardar. El diagnóstico
    completo se obtiene con `bin/cli mail-doctor`.

!!! info "Los ocho archivos que un clon edita en `src/app/config/extensions/`"

    `api-keys.php`, `cli-actions.php`, `cronjobs.php`, `event-listeners.php`, `mail-log.php`,
    `protected-files.php`, `queues.php` y `set-additional-configurations.php`. **No hay ningún otro**,
    y en particular no hay `mailing.php`.

    **`mail-log.php`** (desde el 2026-10-05) decide qué se guarda del **cuerpo** de cada correo en el
    registro de correos. Por omisión se guarda entero y cifrado con la clave de la aplicación; el
    archivo trae, comentados, los dos ajustes habituales: no guardar ningún cuerpo, o tachar un dato
    antes de guardarlo. Los adjuntos no se guardan nunca, ni con este archivo.

    El framework tiene su propia carpeta, `src/app/core/extensions/`, que se carga **antes** y que
    **no se edita**: `add-dynamic-translations.php`, `api-keys.php`, `cli-actions.php`,
    `cronjobs.php`, `mailing.php`, `patches_composer_dependencies.php`, `protected-files.php`,
    `queues.php` y `set-additional-configurations.php`. El orden de carga está en
    `src/app/config/final-configurations.php:45`.

!!! note "Hay un `mailing.php`, pero es del framework y hace otra cosa"

    `src/app/core/extensions/mailing.php` existe y **prepara el logo de los correos**: redimensiona
    el logo del sitio y lo deja en `statics/images/mailing-logo.png`. Está marcado
    `@pcsphp-config framework`, es decir, **no conviene editarlo**. No tiene nada que ver con el
    SMTP.

- **`cli-actions.php`**: Acciones de terminal propias del proyecto (`CliActions::make()`; ver [Terminal](./terminal.md)).

- **`protected-files.php`**: Declaración de las carpetas de subida protegidas (ver [Archivos protegidos](../new-features/protected-files.md)).

- **`patches_composer_dependencies.php`**: Parches sobre dependencias de Composer que se aplican al cargar.

- **`queues.php`**: Registro de manejadores (handlers) para el procesamiento asíncrono.

*Ejemplo:*

```php
<?php
$queueHandlers[] = QueueTask::make('mi-cola', function($data) {
    return QueueHandlerResponse::success();
});
```

- **`set-additional-configurations.php`**: Capa final para realizar ajustes menores o parches de configuración sin alterar los archivos principales.

---

## El Punto de Entrada (`src/index.php`)

El archivo `index.php` actúa como el **Front Controller**. Sus responsabilidades incluyen:
1.  **Bootstrap:** Carga de constantes y autoloader (`bootstrap.php`).

2.  **Configuración:** Carga de archivos de configuración y sobrescritura desde la base de datos.

3.  **Middleware Global:** Manejo de sesiones, seguridad, internacionalización (i18n) y control de acceso.

4.  **Enrutamiento:** Despacho de la solicitud al controlador correspondiente mediante Slim Framework.

## Configuración de Servidor (`src/.htaccess`)

El archivo `.htaccess` es fundamental para el funcionamiento del framework en Apache:

- **Routing:** Redirige todas las peticiones que no son archivos o carpetas reales hacia `index.php`.

- **Seguridad:** Implementa cabeceras de seguridad modernas y niega el acceso a archivos sensibles: por extensión (`.sass`, `.scss`, `.htaccess`, `.php`, `.gitignore`, `.zip`,
  `.ts`, `.sh`) y por nombre (`composer.json`, `composer.lock` y `gulpfile.js`). **No bloquea los `.json` en general.**
  Solo `index.php` se puede ejecutar.

- **Optimización:** Habilita la compresión Gzip para mejorar la velocidad de carga.
