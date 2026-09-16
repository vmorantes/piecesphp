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
  el repositorio. No forman parte de la aplicación.

- **`src/`**: **Raíz de la aplicación web.**

    - `index.php`: Punto de entrada único (Front Controller).

    - `.htaccess`: Configuración de Apache para enrutamiento, seguridad (CORS, CSP, HSTS) y compresión.

    - **`app/`**: Lógica interna de la aplicación.
        - `classes/`: Módulos PSR-4 (Controllers, Mappers, Views).
        - `config/`: Archivos de configuración de la instancia (BD, rutas, menú).
        - `controller/`: Controladores del sistema (`AdminPanelController`, `UsersController`…).
        - `model/`: Modelos del sistema (usuarios, intentos de acceso…).
        - `core/`: Núcleo del framework (Bootstrap, clases base, suites de pruebas en `system-controllers/local-tests/`).
        - `lang/`: Directorios de idiomas globales.
        - `view/`: Vistas de sistema y layouts base (`view/panel/layout/`: el esqueleto del panel).
        - `cache/` y `logs/`: generados en ejecución; necesitan permiso de escritura.

    - `statics/`: Recursos estáticos públicos (JS, CSS, Imágenes, Plugins; `uploads/` para lo subido).
    - `adminer/`: Adminer, de terceros. **Se borra al desplegar** (ver [Despliegue](./general.md)).
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

- **`final-configurations.php`**: Punto de inyección de grupos de traducciones y cargador automático para scripts adicionales en `final-configurations-includes/`.

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
new PiecesRoute('/perfil', '\App\MiControlador:index', 'mi-perfil', 'GET', true);
```

---

### Inclusiones Adicionales (`src/app/config/final-configurations-includes/`)

Archivos cargados al final del ciclo de configuración para extender la funcionalidad:

- **`add-dynamic-translations.php`**: Carga y asocia traducciones dinámicas (ej. desde BD o lógica de módulos) en los grupos de idiomas.

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

- **`mailing.php`**: Parámetros de conexión SMTP y configuración de remitentes.

*Ejemplo:*

```php
<?php
set_config('mailing_settings', [
    'host' => 'smtp.ejemplo.com',
    'port' => 587,
    'user' => 'usuario',
    'password' => 'clave',
]);
```

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

- **Seguridad:** Implementa cabeceras de seguridad modernas y restringe el acceso a archivos sensibles (`.php`, `.sass`, `.json`, etc.).

- **Optimización:** Habilita la compresión Gzip para mejorar la velocidad de carga.
