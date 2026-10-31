# Los archivos de configuración

Esta página explica **el formato de cada archivo de `src/app/config/`**: qué claves acepta, de qué tipo, cuáles son
obligatorias y qué pasa si faltan. Los archivos mismos traen poca explicación; lo que sigue sale de leer el código que
los consume.

Para el mapa de carpetas y la lista corta de archivos, ver [Estructura](./structure.md). Para las rutas en sí,
[Rutas](./routing.md); para los permisos por ruta, [Permisos de ruta](../new-features/permisos-de-ruta.md).

## Lo que vale para todos los archivos

### Qué se edita y qué no

Cada archivo abre con un docblock `@pcsphp-config framework|clon|ambos` y una línea «Qué conviene editar aquí».
`bin/cli verify-integrity` falla si falta alguna de las dos. Dentro de un archivo `ambos`, los marcadores
`//── Del clon ──` y `//── Del framework: no lo edites ──` delimitan los tramos. Edita los tuyos; los del framework los
actualiza una versión nueva y tu cambio sería un conflicto.

### En qué orden se cargan

El orden importa porque **un archivo no puede usar lo que otro define después**:

| Paso | Archivo | Obligatorio | Ya existe cuando corre |
| :-- | :-- | :-- | :-- |
| 1 | `critical-definitions.php` | sí | nada: ni siquiera las funciones del framework |
| 2 | `environment.php` (no versionado) | no | `AppEnvironment::load()` lo lee; sin él, la instalación cuenta como producción |
| 3 | `config.php` | sí | las funciones de `Utilities.php` (`is_local()`, `app_environment()`) |
| 4 | `database.php`, `cookies.php`, `roles.php` | sí | además la clase `Config` |
| 5 | `lang.php` | sí | además `AppHelpers.php` (`get_config()`, `set_config()`, `getKeyFromSecureKeys()`…) |
| 6 | `Config::init()` | — | aquí se aplican la base de datos, las cookies, los roles y el idioma |
| 7 | `functions.php`, `constants.php`, `autoloads.php` | **no**: se cargan solo si existen | — |
| 8 | `assets.php`, `containers.php` | sí | lo anterior |
| 9 | las opciones guardadas en la base (ver abajo) | — | — |
| 10 | `routes.php` | sí | — |
| 11 | `final-configurations.php` y, desde él, las [extensiones](#extensiones-del-nucleo) | sí | todo |

Consecuencias que muerden:

- **Las constantes de `constants.php` no existen todavía en `config.php`, `database.php`, `cookies.php`, `roles.php` ni
  `lang.php`.** Por eso `roles.php` consulta `CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']` y no
  `ORGANIZATIONS_MODULE`: `critical-definitions.php` es el único archivo que corre antes que todos.
- **Las funciones de `functions.php` tampoco.** Y en `database.php` todavía no hay nada de `AppHelpers.php`:
  `get_config()` y `getKeyFromSecureKeys()` aparecen después.

### Los archivos que devuelven un array y los que rellenan `$config`

`config.php`, `database.php`, `cookies.php` y `roles.php` **rellenan el array `$config`**. Al terminar, el arranque
recorre `$config` y llama a `Config::set_config()` con cada clave y su valor
(`bootstrap.php`). Desde ese momento cada clave se lee con `get_config()`, que devuelve `false` si no existe: por ejemplo,
`get_config('title_app')`.

`autoloads.php` es distinto: **devuelve** un array (`return [...]`). `lang.php` y `assets.php` no usan `$config`: llaman
a `set_config()` directamente.

### Lo guardado en el panel gana sobre `config.php`

En cada petición, el arranque lee la tabla de opciones (la que edita el panel en Configuración) y llama a
`set_config()` por cada fila, **después** de haber cargado `config.php`. Dos consecuencias:

- **Una opción guardada en el panel pisa el valor de `config.php`.** Cambiar `owner`, `title_app`, `description`,
  `keywords` o `meta_theme_color` en el archivo después del primer arranque **no tiene efecto** mientras la opción siga
  en la base.
- **`config.php` solo siembra la tabla cuando está vacía**, y solo estas claves: `title_app`, `mail`, `owner`,
  `description`, `keywords`, `meta_theme_color`, `check_aud_on_auth`, `osTicketAPI` y `osTicketAPIKey`, más las
  imágenes por defecto (`favicon`, `favicon-back`, `logo`, `backgrounds`, `backgoundProblems`, `partners`,
  `partnersVertical`, `open_graph_image`). Una instalación con la tabla ya poblada no recibe claves nuevas por esta vía.

Esto se apaga con la constante `APP_CONFIGURATION_MODULE` (ver [`constants.php`](#constantsphp)).

### Las opciones que la base puede dar se declaran

Cada opción que la tabla de opciones puede dar la **declara el módulo que la guarda**, una vez, en el mismo sitio
donde registra sus rutas, con `ConfigOptions::declare()` (`PiecesPHP\Core\ConfigHelpers\ConfigOptions`):

```php
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;

//En el routes() de tu módulo, fuera de cualquier if que apague sus rutas: la fila existe aunque la pantalla no.
ConfigOptions::declare('mi_modulo_color', MiModuloController::class, ConfigOptions::SHAPE_COLOR);
```

- El segundo argumento es el dueño (la clase que la escribe); el tercero, su forma (`SHAPE_TEXT`, `SHAPE_INTEGER`,
  `SHAPE_BOOL`, `SHAPE_COLOR`, `SHAPE_JSON`, `SHAPE_SEALED`) o `null` si no se sabe.
- Declarar el mismo nombre con otro dueño, o con otra forma, lanza: cada opción tiene una sola fuente.
- **Lo que no está declarado se avisa**: si la base tiene una fila con un nombre que nadie declaró, el registro de
  errores lo dice una vez por nombre (sin el valor). **En esta versión se sigue aplicando**; la siguiente dejará de
  hacerlo, así que una opción legítima de tu clon tiene que estar declarada antes (ADR 0055).
- `bin/cli config-options` lista el catálogo (nombre, dueño y forma) y las filas de la base que no lo cumplen, solo
  por su nombre.

### Las llaves de servicios no van en `config.php`

Las llaves de OpenAI, Mistral, Groq, la del cron y las dos de reCAPTCHA v3 las lee `src/app/core/extensions/api-keys.php`
de la carpeta `secure-keys/` (ignorada por git) con `set_configs_from_secure_keys()`. Con `'override' => true`, la
llave segura **sustituye** lo que haya en `config.php` o en el panel, **incluso cuando el archivo de la llave no
existe: entonces el valor queda vacío** (`getKeyFromSecureKeys()` devuelve `''`). Las de tus propios servicios van en
`src/app/config/extensions/api-keys.php`. La `app_key` tiene su propio mecanismo:
[La clave de la aplicación](../new-features/clave-de-la-aplicacion.md).

---

## `config.php`

La configuración general de la instalación. Se carga el tercero y rellena `$config`.

### Lo que calcula el framework (no lo edites)

| Clave | Valor |
| :-- | :-- |
| `domain` | `$_SERVER['HTTP_HOST']` |
| `domain_protocol` | `https://` si existe `$_SERVER['HTTPS']`, si no `http://` |
| `base_domain_path` | la ruta del script sin el nombre del archivo |
| `base_domain_url` | protocolo + dominio |
| `base_url` | `base_domain_url` + `base_domain_path` |
| `statics_path` | `src/statics`: la carpeta base de la ruta `statics-files` (`ServerStatics`) |
| `protected_uploads_suffix` | `'.protected'`: el sufijo **en disco** de las subidas privadas (`foto.jpg.protected`); la URL nunca lo lleva |

`app_lang` (el idioma de la petición) **no está en `config.php`**: lo calcula `Config` en cada petición (ver
[`lang.php`](#langphp)). Tampoco `title`: es el título de la sección actual, y se maneja con `set_title()` y
`get_title()`.

### Identidad y aspecto

| Clave | Tipo | De fábrica | Para qué |
| :-- | :-- | :-- | :-- |
| `default_lang` | `string` | `"es"` | Idioma por defecto: el de las visitas que no eligen otro y el que no lleva prefijo en la URL. |
| `og_locales` | `array<string,string>` | `['es' => 'es_CO', 'en' => 'en_US']` | `og:locale` por idioma. Un idioma sin entrada emite su propio código. |
| `cache_stamp_render_files` | `bool` | `true` | Prende o apaga `add_cache_stamp_to_url()` (las vistas lo usan para sus propias imágenes y estáticos). Los `<script>` y `<link>` de las librerías (`load_js()`, `load_css()`) llevan su `cacheStamp` siempre. |
| `title_app` | `string` | `"Nombre Plataforma"` | Título de la aplicación. Si no es una cadena, `Config` usa `"Title App"`. |
| `owner` | `string` | `"Nombre Plataforma"` | Titular: pie de página, correos, etiquetas SEO. |
| `keywords` | `string[]` | `['Website', 'Application']` | Palabras clave. |
| `description` | `string` | `"Descripción de la página."` | Descripción. |
| `main_brand_color` | `string` | `"#356DCA"` | El color de marca: el panel y el acceso lo publican como `--accent`, el token del tema; el correo lo lee por PHP. Se edita en Configuración › Colores, como todas las de esta tabla. |
| `second_brand_color` | `string` | `"#7A5AF8"` | Primer color de realce: se publica como `--accent-2`. |
| `third_brand_color` | `string` | `"#C11574"` | Segundo color de realce: se publica como `--accent-3`. |
| `data_color_2` … `data_color_5` | `string` | `""` | Las series 2 a 5 de las gráficas (`--data-2` a `--data-5`). Vacías, valen las del tema, que en la serie 5 cambian con el modo oscuro. |
| `meta_theme_color` | `string` | `"#356DCA"` | El color de la barra del navegador en el móvil (`<meta name="theme-color">`): el acento o el neutro oscuro `#1F2430`. |
| `theme_font` | `string` | `"inter"` | La fuente del panel y del acceso: `inter`, `poppins` o `public-sans`. |
| `theme_font_size` | `int` | `14` | El tamaño base en píxeles (14, 15 o 16). Escala textos, espaciados y controles. |
| `theme_radius` | `int` | `8` | El radio general en píxeles (0, 4, 6, 8 o 12). |

La zona pública y las páginas de error no leen estas opciones: llevan sus colores y su fuente fijos en su hoja.

### Seguridad

| Clave | Tipo | De fábrica | Para qué |
| :-- | :-- | :-- | :-- |
| `app_key` | `string` | `'TODO:secret'` | **Solo de respaldo** desde la 9.0.0: la fuente es el llavero `secure-keys/app-key.json`. Ver [La clave de la aplicación](../new-features/clave-de-la-aplicacion.md). |
| `session_token_name` | `string` | comentada (`'JWTAuth'`) | Nombre de la sesión (cookie y cabecera). `/^[A-Za-z0-9_]{1,64}$/`; uno inválido se ignora, se registra un error una vez y se usa `JWTAuth`. **Cambiarlo cierra todas las sesiones.** Lo escribe `bin/cli session-name`. |
| `check_aud_on_auth` | `bool` | `true` | La comprobación de origen del token de sesión al iniciar sesión. El panel la llama «Usar IP del usuario para encriptar el token de sesión». |
| `hide_app_key_warning` | `bool` | `false` | Marca antigua que oculta el aviso de `app_key` de relleno; se gestiona desde Avisos del sistema ([ver](../new-features/system-status.md)). |
| `cors_credentials_origins` | `string[]` | `[]` | Orígenes exactos (esquema, host y puerto: `https://app.ejemplo.com:8443`) que reciben CORS **con** credenciales. El propio origen de la instalación va incluido. |
| `otp_security` | `array` | ver abajo | Límite de intentos del OTP, del segundo factor y de la contraseña del acceso. |

**CORS.** Con `API_MODULE` activo el framework **refleja** el `Origin` recibido en `Access-Control-Allow-Origin` para
cualquier origen (o `*` si no hay); lo que `cors_credentials_origins` controla es solo `Access-Control-Allow-Credentials`.
La comparación es exacta: un origen con ruta, con barra final o con comodín no casa. Si la lista contiene algo que no sea
una cadena, cuenta como vacía.

**`otp_security`**, con los valores por omisión que usa `OTPRateLimiter` cuando una clave falta o no es válida:

| Subclave | Tipo | Por omisión | Efecto |
| :-- | :-- | :-- | :-- |
| `maxFailuresPerUser` | `int` > 0 | 5 | Fallos por usuario dentro de la ventana antes de bloquear. |
| `maxFailuresPerIP` | `int` > 0 | 20 | Igual, por IP. |
| `windowMinutes` | `int` > 0 | 15 | La ventana en que se cuentan los fallos. |
| `lockMinutes` | `int` > 0 | 15 | Cuánto dura el bloqueo. |
| `uniformResponse` | `bool` | `true` | `generate-otp` responde igual exista o no el usuario. |
| `oneUseCodeMinutes` | `int` > 0 | 20 | Validez del código de un solo uso. |
| `loginMaxFailuresPerIP` | `int` > 0 | 5 | Fallos de acceso con contraseña (o con un usuario que no existe) desde una IP, dentro de `windowMinutes`, antes de frenar esa IP `lockMinutes`. Frenada, el acceso responde 429 sin mirar el usuario. |
| `loginAccountDistinctIPs` | `int` > 0 | 3 | IP distintas de las que tienen que venir los fallos de una cuenta para bloquearla (además de los 4 fallos de `UsersController::MAX_ATTEMPTS`). |
| `loginAccountWindowMinutes` | `int` > 0 | 1440 | La ventana, en minutos, en que se cuentan esas IP. |

Un valor que no sea un `int` de PHP mayor que cero (`'5'`, `0`, `-1`) se sustituye por el de la tabla.

La IP es `REMOTE_ADDR`. **Detrás de un proxy inverso que no la pase**, todos los clientes comparten la del proxy y el
freno de una IP los frena a todos; el framework no lee `X-Forwarded-For`.

### Contacto y servicios

| Clave | Tipo | De fábrica | Para qué |
| :-- | :-- | :-- | :-- |
| `contact_form_recipients` | `string[]` | `[]` | Destinatarios del formulario de contacto. |
| `other_problems_recipients` | `string[]` | `[]` | Destinatarios del correo de «otros problemas» (si osTicket falta o falla). |
| `osTicketAPI`, `osTicketAPIKey` | `string` | `""` | URL y llave de osTicket. Vacías, no se usa. |
| `CronJobKey` | `string` | no está en `config.php` | La pone **solo** la llave segura `cronjob` de `secure-keys/` (`core/extensions/api-keys.php`). Sin ella, la ruta del cron rechaza toda llave. |
| `mailjet` | `array` | `email`, `name`, `apiKey`, `secretKey` de relleno | Valores de relleno: la configuración real de Mailjet se guarda **cifrada** en la opción `mailjet` (ver [La clave de la aplicación](../new-features/clave-de-la-aplicacion.md)). |
| `GoogleReCaptchaV3TestSiteKey`, `GoogleReCaptchaV3TestSecretKey` | `string` | claves de prueba del propietario del framework | Se usan **solo si faltan las reales** (`recaptcha-v3-site`, `recaptcha-v3-secret` en `secure-keys/`). |
| `OpenAIApiKey`, `MistralAIApiKey`, `GroqAPIKey` | `string` | `""` | Las sustituye `secure-keys/` si hay llave. |
| `translationAI`, `translationAIEnable`, `modelOpenAI`, `modelMistral` | `string`, `bool`, `string`, `string` | `""`, `true`, `"gpt-3.5-turbo-0125"`, `"mistral-medium"` | Traducción automática. Si `translationAI` queda vacío, el framework lo pone en OpenAI. |
| `translationAIUserTypes` | `int[]` | no está en `config.php`: sin valor, los seis tipos | Quién puede traducir con IA. Solo la escribe el panel (Configuración › IA). Root siempre puede; un tipo desconocido se descarta. Regla en `APIController::translationUserTypes()`. |
| `Azure` | `array` | vacío salvo `SPEECH_REGION` = `'eastus'` | `BASE_STORAGE_ACCOUNT_NAME`, `BASE_STORAGE_ACCOUNT_KEY`, `BASE_STORAGE_ACCESS_QUERY_PARAMS`, `SPEECH_SUBSCRIPTION_KEY`, `SPEECH_REGION`. |
| `GEO_IP` | `array` | `custom_directory` = `realpath()` de `src/geoip` | Se aplica a `geoip.custom_directory` de PHP. `realpath()` da `false` si esa carpeta no existe. |

**Dos trampas con los destinatarios.** Los dos campos **fallan cerrado**: si la lista está vacía **o si una sola
dirección no es válida**, el resultado es una lista vacía y no se envía a nadie. Ninguna dirección debe viajar por
defecto en el código: todo clon la heredaría.

```php
$config['contact_form_recipients'] = ['contacto@tu-dominio.example'];
$config['other_problems_recipients'] = ['soporte@tu-dominio.example'];
```

**Claves de prueba de reCAPTCHA.** `config.php` trae un par de **prueba que no es tuyo**: no vale en tu dominio. En
producción pon las reales en `secure-keys/` (ver arriba); tienen prioridad. Con las dos vacías, el formulario no crea el
adaptador de reCAPTCHA.

!!! warning "Claves que ningún código lee"
    `LabsMobileAPIKey`, `LabsMobileSendInLocal` y `developer` están en `config.php`, pero **ningún código
    de `src/` (fuera de `vendor/`) las consulta** (búsqueda en el repositorio del 2026-10-23, por nombre de clave).
    Rellenarlas no activa nada. `Azure` y `mailjet` sí tienen consumidor.

### Un listener a la medida

Al final, `config.php` registra un oyente por omisión del evento de arranque de rutas, vacío:

```php
\PiecesPHP\Core\BaseEventDispatcher::defaultListen(
    \PiecesPHP\Core\BaseEventDispatcher::EVENT_INIT_ROUTES_NAME,
    function () {
        //Cuando se registran las rutas
    }
);
```

Los oyentes de tu proyecto van mejor en `config/extensions/event-listeners.php` (ver [Eventos](../new-features/events.md)).

La zona horaria también se fija aquí (`date_default_timezone_set('America/Bogota')`); cámbiala por la tuya.

---

## `database.php`

Las conexiones a base de datos. Rellena `$config['database']` con la forma
`$config['database'][GRUPO][CLAVE]`.

| Clave | Tipo | Por omisión si falta |
| :-- | :-- | :-- |
| `driver` | `string` | `'mysql'` |
| `host` | `string` | `'localhost'` |
| `db` | `string` | **`''`** (vacío) cuando declaras el grupo; `'piecesphp'` solo si no declaras ningún grupo |
| `user` | `string` | `'root'` |
| `password` | `string` | `''` |
| `charset` | `string` | `'utf8'` |

Esas seis son **todas** las claves que lee `Config`; no existe una clave `port`. El grupo que usan los mappers y los modelos es **`'default'`** (el parámetro `$db_group` de `BaseEntityMapper` y
`$database_group` de `BaseModel`). Puedes declarar más grupos y elegirlos al construir el modelo.

```php
if (is_local()) {
    $config['database']['default']['driver'] = 'mysql';
    $config['database']['default']['db'] = 'mi_app';
    $config['database']['default']['user'] = 'mi_usuario_local';
    $config['database']['default']['password'] = 'mi_clave_local';
    $config['database']['default']['host'] = 'localhost';
    $config['database']['default']['charset'] = 'utf8mb4';
} else {
    $config['database']['default']['driver'] = 'mysql';
    $config['database']['default']['db'] = 'mi_app_produccion';
    $config['database']['default']['user'] = 'mi_usuario';
    $config['database']['default']['password'] = 'LA_CLAVE';
    $config['database']['default']['host'] = 'localhost';
    $config['database']['default']['charset'] = 'utf8mb4';
}
```

Qué rama se lee la decide `is_local()`, que **no mira el dominio**: lo decide `src/app/config/environment.php`
(`'local'` o `'production'`; sin el archivo, producción). Desde la terminal manda la bandera `--local` que `bin/cli`
añade solo (ver [Terminal](./terminal.md)).

!!! warning "Trampas"
    - **Solo cuentan los valores de tipo cadena.** Un entero o un `null` se ignora y queda el valor por omisión
      (salvo `db`, que queda vacío).
    - **Un grupo no válido descarta los anteriores.** Si un grupo tiene nombre vacío o no es un array con contenido,
      `Config` tira lo que había procesado hasta ahí y deja un único grupo `''` con los valores por omisión; si
      `'default'` estaba antes, deja de existir. Declara cada grupo completo.
    - **El archivo se versiona.** La contraseña de producción que escribas aquí queda en el repositorio. No hay un
      mecanismo de `secure-keys/` para las credenciales de la base (la lista de llaves del framework no las incluye),
      así que decide cómo despliegas ese archivo.

---

## `cookies.php`

Las opciones de las cookies. Rellena `$config['cookies']`.

| Clave | Tipo | De fábrica | Efecto |
| :-- | :-- | :-- | :-- |
| `lifetime` | `int` | `0` | `0`: cookie de sesión del navegador. **Ver la trampa de abajo.** |
| `path` | `string` | `'/'` | |
| `domain` | `string` | `$_SERVER['HTTP_HOST']` | |
| `secure` | `bool` | `false` | **En producción con HTTPS, `true`.** |
| `httponly` | `bool` | `false` | |
| `samesite` | `string` | `'Lax'` | `'Lax'`, `'Strict'` o `'None'` (sin importar mayúsculas). Cualquier otro valor da `Lax`, y `'None'` sin `secure` también da `Lax`: el navegador descartaría la cookie. |

Qué cookies pasan por aquí, según el código:

- **La cookie de sesión de PHP** (la que usan los mensajes flash): `lifetime`, `path`, `domain`, `secure` y `httponly`
  se aplican con `ini_set('session.cookie_*')`, y solo si la sesión aún no estaba iniciada. **`samesite` no se aplica a
  esta cookie.**
- **Las cookies que escribe `setCookieByConfig()`**: la del idioma (`PREFER_LANG_BY_COOKIE`, ver [`lang.php`](#langphp)).
  Lleva `samesite`.
- **La de «conectarse como otro usuario»** (`asUserID`): la escribe `ImpersonationController` con las mismas opciones
  (`cookie_options_by_config()`), `samesite` incluido, pero siempre `httponly` y con la caducidad de la suplantación.

Un valor `null` o ausente en `lifetime`, `path`, `domain`, `secure` o `httponly` se sustituye por el de fábrica.

!!! warning "`lifetime` no significa lo mismo en las dos vías"
    Para la cookie de sesión de PHP es «segundos de vida». Para las cookies de `setCookieByConfig()` el valor se pasa
    **tal cual como `expires`**, una marca de tiempo absoluta: con `lifetime = 3600`, esas cookies salen ya caducadas
    (1970). Déjalo en `0` mientras el código no lo sume a la hora actual. En esas cookies, además, `secure` y
    `httponly` solo valen si son exactamente `true`, y un `lifetime` que no sea `int` cuenta como `0`.

---

## `roles.php`

Los roles y sus permisos, y el control de acceso por login. Rellena `$config['roles']`, `$config['control_access_login']`
y `$config['admin_url']`.

### `$config['roles']`

| Clave | Tipo | Efecto |
| :-- | :-- | :-- |
| `active` | `bool` | Con `false` **no se registra ningún rol** y el framework no comprueba permisos de rol: toda ruta con `require_login` queda abierta a cualquier usuario con sesión. |
| `types` | `array` | La lista de roles (abajo). |
| `baseInitialSegmentedPermissions` | `array` | `generals`, `administratives`, `superiors`: las tres listas de rutas. `UsersModel::restrictRoutes()` lee `generals`: a un usuario pendiente de aprobación, rechazado o bloqueado se le recortan sus rutas a las generales más las que `keepsWhenRestricted()` conserva. |

Cada rol de `types`:

| Clave | Tipo | Obligatoria | Detalle |
| :-- | :-- | :-- | :-- |
| `name` | `string` | **sí** | Único. |
| `allowed_routes` | `string[]` | **sí** | Los **nombres de ruta** permitidos. Lo que no sea cadena se descarta. |
| `code` | `int` | no | Único. Si falta, el framework le pone `uniqid('pcs_roles')` (una cadena, no un número). Un valor que no sea entero lanza `RoleMalformedException`. |
| `all` | `bool` | no (`false`) | `true`: acceso a todas las rutas. Hoy ningún rol del framework lo usa. |

Un nombre o un código repetido lanza `RoleDuplicateException`.

El archivo trae siete roles, y cada uno se arma a partir de tres listas **anidadas**:

```
$permisosGenerales  ⊂  $permisosAdministrativos  ⊂  $permisosSuperiores
```

| Rol (`UsersModel`) | Código | Lista |
| :-- | :-- | :-- |
| `TYPE_USER_ROOT` | 0 | superiores |
| `TYPE_USER_ADMIN_GRAL` | 1 | administrativos |
| `TYPE_USER_ADMIN_ORG`, `TYPE_USER_GENERAL`, `TYPE_USER_INSTITUCIONAL`, `TYPE_USER_COMUNICACIONES`, `TYPE_USER_GOOGLE_PLAY` | 12, 2, 3, 4, 50 | generales |

`name` sale de `UsersModel::TYPES_USERS`: **un rol cuyo tipo no esté ahí se descarta** (así queda fuera
`TYPE_USER_GOOGLE_PLAY`, que está comentado). Con `CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']` en `false` se descarta
también el administrador de organización.

**Añadir los permisos de un módulo** es poner los nombres de ruta al final de la lista que corresponda, en el hueco
`//── Del clon ──`:

```php
$permisosGenerales = [
    //Generales
    'admin',
    //...
    //── Del clon ──
    'mi-modulo-list',        //todos los roles, root incluido
];

$permisosAdministrativos = array_unique(array_merge($permisosGenerales, [
    //...
    //── Del clon ──
    'mi-modulo-form-edit',   //administradores y root
]));
```

Poner una ruta en `generales` la da a **todos** los roles, y también la conserva un usuario restringido (pendiente,
rechazado o bloqueado); en `administrativos`, a administrador general y root.
Cómo se combina esto con el `rolesAllowed` de la propia ruta (se **unen**, no se restringen) está en
[Permisos de ruta](../new-features/permisos-de-ruta.md).

### `control_access_login` y `admin_url`

| Clave | Tipo | De fábrica | Efecto |
| :-- | :-- | :-- | :-- |
| `control_access_login` | `bool` | `true` | Con `false`, el arranque **no aplica ninguna** de las comprobaciones: ni sesión para las rutas con `require_login`, ni roles, ni la redirección de `admin_url`. Apágalo solo si implementas tu propio sistema de usuarios. |
| `admin_url['url']` | `string` | `''` | Adónde se redirige a un usuario **con sesión** que pide el formulario de acceso. También la lee el JavaScript del panel. |
| `admin_url['relative']` | `bool` | `true` | `true`: `url` es relativa a la URL base (`baseurl($url)`); `false`: se usa tal cual. |

### La marca global de sesión

Ningún token nacido antes de cierta fecha vale, **para nadie**. La fecha por omisión es `2026-03-02 00:00:00`
(`SessionToken::MINIMUM_DATE_DEFAULT`); se cambia con la clave de configuración `session_minimum_date`, que acepta una
fecha como `'2026-03-02 00:00:00'` (un número suelto no vale; si es inválida, se registra un error una vez y se usa la de
por omisión). Moverla hacia adelante **cierra todas las sesiones**.

---

## `routes.php`

Aquí se **crean los grupos de rutas y se registran los módulos**. La mecánica de `Route`, `RouteGroup`, el nombre de la
ruta como permiso y `routeName()` está en [Rutas](./routing.md); esto es lo propio del archivo.

El motor es **Slim 4** (`slim/slim ^4.0` en `src/composer.json`).

### La firma de `Route`

```php
new Route(
    $route,                // string: el segmento, dentro del grupo
    $controller,           // string 'Clase::class . ":metodo"' o un callable
    $name,                 // ?string: si es null, uniqid()
    $method = 'GET',       // 'GET', 'POST'… o varios: 'GET|POST'
    $requireLogin = false,
    $alias = null,         // IGNORADO: pasa null
    $rolesAllowed = [],    // int[]|string[]
    $defaultParamsValues = [],
    $middlewares = []      // callables o clases
);
```

### Dónde va tu módulo

```php
//── Del clon ──
//Tus módulos: TusRutas::routes($zona_administrativa, $zona_publica);
MiModuloRoutes::routes($zona_administrativa, $zona_publica);
```

Va en el hueco «Del clon», **antes** de `PublicAreaController::routes($zona_publica)`, que es la última línea.
Para el contenido del `MiModuloRoutes`, ver [Crear un módulo](./modules.md).

### El orden importa

Slim atiende la primera ruta que casa. `RouteGroup` registra los grupos en el orden de su **primer** `register()` (y
`PublicAreaController` termina con rutas comodín como `/{name}`), así que:

- Declara de lo más específico a lo menos específico, tanto los grupos como las rutas dentro de un grupo.
- Los servicios y el backoffice van antes que la zona pública.
- Un grupo al que nunca llamas `register()` no aporta rutas. Dos grupos con el mismo prefijo son dos entradas
  distintas.

El prefijo de idioma (`$prefix_lang`: `''` o `'/en'`) viene de [`lang.php`](#langphp); los grupos lo anteponen.

!!! warning "Un patrón que no analiza"
    En local, una ruta cuyo patrón no se puede analizar lanza una excepción. Fuera de local **no se registra** en lugar
    de tumbar la aplicación, y esa pantalla simplemente no existe. Compruébalo antes de subir con `bin/check-routes`.

---

## `assets.php`

El **catálogo de librerías front**. No carga nada por sí mismo: describe qué archivos forman cada librería, y las
funciones `import_*()` los activan cuando una vista los necesita.

### El formato

```php
$assets['mi_libreria']['css'] = [
    'statics/plugins/mi-libreria/mi-libreria.min.css',
];
$assets['mi_libreria']['js'] = [
    'statics/plugins/mi-libreria/mi-libreria.min.js',
];
$assets['mi_libreria']['plugins'] = [
    'miPlugin' => [
        'css' => ['statics/plugins/mi-libreria/mi-plugin.css'],
        'js'  => ['statics/plugins/mi-libreria/mi-plugin.js'],
    ],
];
```

| Clave | Tipo | Obligatoria | Detalle |
| :-- | :-- | :-- | :-- |
| `css`, `js` | `string[]` | no | Rutas **relativas a `src/`**. Una clave ausente se ignora. |
| `font` | `string[]` | no | Igual, para fuentes. |
| `asModules` | `string[]` | no | Fragmentos de ruta: todo `<script>` cuya ruta **contenga** uno se emite con `type='module'`. |
| `plugins` | `array` | no | `nombre => ['css' => [], 'js' => [], 'font' => []]`. |

Las rutas son relativas porque las páginas del panel declaran `<base href="<URL base>">`. En el HTML final cada archivo
lleva además `?cacheStamp=<versión>`: la calculada con la fecha de modificación del archivo si existe bajo `src/`, o la
marca global si no.

Todo queda guardado en la configuración `default_assets`. Al final del archivo se inicializan también `global_assets`,
`custom_assets`, `global_requireds_assets`, `imported_assets`, `as_modules_assets` y `lock_assets`: son del framework.

### Usarlas desde un controlador

```php
public function listView(Request $request, Response $response)
{
    import_datatables();              //las tres extensiones por omisión
    import_cropper();                 //sin argumentos: la librería y TODOS sus plugins
    import_app_front_libraries();     //tu código del front
    // ...
}
```

`import_<nombre>($plugins = [...], $all = ...)` existe para casi cada librería del catálogo y llama a
`import_front_library($nombre, $plugins, $all)`. Con `$all = true` se importan todos los plugins. El valor por omisión
de `$all` **depende de la función**: `import_datatables()` y `import_semantic()` traen una lista de plugins y `false`;
`import_cropper()`, `import_mapbox()` y otras traen `true`.

Para una librería propia, usa `import_front_library('mi_libreria', ['miPlugin'])` directamente.

!!! warning "Trampas"
    - **Un nombre que no está en el catálogo se ignora en silencio** con `import_front_library()`. Con
      `import_datatables()`, el plugin que no existe tampoco se carga, pero queda constancia en el registro de errores
      (desde la `10.0.0`, porque RowReorder y ColReorder dejaron de venir con DataTables 3).
    - **Importar de nuevo con otra lista de plugins quita los que ya no pides.** Si llamas `import_datatables()` (hoy,
      solo `responsive`) y luego `import_datatables([])`, `responsive` se retira. Un archivo que otro plugin importado sigue
      pidiendo no se retira.
    - **Instanciar un `BaseController` vacía las librerías ya importadas.** Su constructor limpia los assets globales,
      las importaciones y el título salvo que `lock_assets` sea `true`. El framework lo pone en `true` mientras corre el
      método de una ruta; fuera de ese momento (un script, una vista que crea un controlador para llamar un método),
      tenlo en cuenta.
    - **Nunca un `<script src>` suelto en la vista**: usa `add_global_asset()`, `set_custom_assets()` o `import_*()`.

### Lo que puedes editar y lo que no

El tramo editable es `app_front_libraries`, para el CSS/JS de tu proyecto. `PublicAreaController` lo importa en la zona
pública:

```php
$assets['app_front_libraries']['css'] = [
    'statics/css/mi-proyecto.css',
];
$assets['app_front_libraries']['js'] = [
    'statics/js/CustomNamespace.js',
    'statics/js/mi-proyecto.js',
];
$assets['app_front_libraries']['plugins'] = [];
```

`app_libraries` (los `helpers.css`, `configurations.min.js` y estilos del panel, con los plugins `adminStyle`,
`baseStyle`, `formJsonSchema` y `attachmentPlaceholder`) y las librerías de terceros las actualiza el framework.
`baseStyle` y `adminStyle` comparten `ui-pcs.css` a propósito.

### El editor de texto enriquecido

`defaultRichEditor` es un alias de la librería que nombra `$defaultRichEditorName` (de fábrica `'ckeditor'`); se activa
con `import_default_rich_editor()`. Es esa variable la que decide a qué librería apunta el alias.

CKEditor trae el **español dentro de `ckeditor.js`**; `$ckeditorLangs` lista solo los idiomas **adicionales** (`en`,
`fr`, `de`, `it`, `pt`) y añade el archivo del idioma actual (`Config::get_lang()`). Un idioma que no esté ahí no añade
nada. Para un idioma nuevo, ver [`lang.php`](#langphp).

El botón «Insertar imagen o archivo» del editor abre el explorador de archivos (elFinder). Sus dos direcciones las da el
servidor: el módulo FileManager las publica al frente como `richEditorFileManagerURLs` (`configuration` y `explorer`,
hechas con `routeName()`), y el adaptador (`RichEditorAdapterComponent.js`) las lee de ahí; no se escriben a mano. Si el
usuario no tiene permiso para el explorador, llegan vacías y el botón no aparece. Un adaptador concreto puede usar otras
con sus opciones `fileManagerConfigURL` y `fileManagerUIURL`.

### Dos detalles del catálogo

- **`jqueryui`** es una compilación **sin el widget `accordion`** (la palabra no aparece en ningún archivo de su
  carpeta). `elfinder` y `jqueryui` comparten el mismo `jquery-ui.min.js`; el framework no añade dos veces un archivo
  que ya está en la lista global.
- **`locations`**: `import_locations()` importa también `mapbox` si pides el plugin `autoInit` o `$all`.

---

## `autoloads.php`

Cargadores automáticos propios, **complementarios a Composer**. El archivo debe `return` un array de entradas; si no
devuelve un array, no se registra ningún cargador. La carpeta `src/app/classes` ya está registrada, así que **un módulo
nuevo no necesita tocar este archivo** (ver [Crear un módulo](./modules.md)).

| Clave | Tipo | Por omisión | Efecto |
| :-- | :-- | :-- | :-- |
| `path` | `string` | — | **Obligatoria.** Carpeta raíz absoluta. Una entrada sin `path` (o sin cadena) **corta la búsqueda**: las entradas siguientes no se consultan. |
| `psr4` | `bool` | `true` | Ver abajo. |
| `namespaces` | `string` o `string[]` | `[]` | Prefijos de espacio de nombres que atiende la entrada. |

**Cómo resuelve el cargador**, que no es PSR-4 estricto:

- **Sin `namespaces`** (la entrada del framework): el espacio de nombres completo de la clase se convierte en carpetas
  bajo `path`: la clase `Publications\Controllers\PublicationsController` con `path` en `src/app/classes/`, se busca en
  `src/app/classes/Publications/Controllers/PublicationsController.php`.
- **Con `namespaces`**: el prefijo se **quita** del nombre de la clase para formar el nombre del archivo, pero **se
  conserva como carpetas** bajo `path`. Una clase `Servicio` del espacio `Mi\Espacio`, con `'namespaces' => 'Mi\\Espacio'`,
  se busca dentro de `path`, en las carpetas Mi y Espacio, en el archivo Servicio.php.
- **Con `psr4 => false`**: un archivo directamente en `path`, con el nombre de la clase sin el prefijo, sin carpetas de
  espacio de nombres.

```php
return [
    //── Del framework: no lo edites ──
    [
        'psr4' => true,
        'path' => app_basepath('classes'),
    ],
    //── Del clon ──
    [
        'namespaces' => 'Mi\\Espacio',
        'psr4' => true,
        'path' => app_basepath('mis_clases'),   //src/app/mis_clases/Mi/Espacio/Servicio.php
    ],
];
```

!!! warning "Trampas"
    - **El nombre del archivo es el de la clase, más `.php`.** Una clase `Servicio` vive en `Servicio.php`.
    - **Con `namespaces`, un subespacio no se resuelve.** `Mi\Espacio\Sub\X` produciría un archivo llamado
      `Sub\X.php` (con la barra invertida en el nombre). Registra el subespacio como otra entrada, o usa la forma sin
      `namespaces`.
    - **Se prueban las entradas en orden y gana la primera que encuentra el archivo.**

---

## `constants.php`

Constantes globales con `define()`. **Es opcional** (se carga solo si existe) y corre **después** de `Config::init()`
y de `functions.php`: puedes usar las funciones del framework, pero lo definido aquí no existe en `config.php`,
`database.php`, `cookies.php`, `roles.php` ni `lang.php`.

Casi todo son banderas de módulos, en `true` o `false`. Cada módulo las lee en su clase `*Routes` (por ejemplo
`const ENABLE = PUBLICATIONS_MODULE;`) y, con la bandera apagada, **no registra rutas**.

| Constante | Controla |
| :-- | :-- |
| `PUBLICATIONS_MODULE`, `NEWS_MODULE`, `NEWSLETTER_MODULE`, `EVENTS_LOG_MODULE`, `FILE_MANAGER_MODULE` | los módulos del mismo nombre |
| `DOCUMENTS_MODULE_ENABLE`, `FORMS_MODULE_ENABLE`, `FORMS_MODULE_CATEGORIES_ENABLE`, `FORMS_MODULE_DOCUMENTS_TYPES_ENABLE` | Documentos y Formularios. Dependen entre sí: las categorías exigen también `FORMS_MODULE_ENABLE`; los tipos de documento exigen además `DOCUMENTS_MODULE_ENABLE` y `FORMS_MODULE_ENABLE`; Documentos exige que los tipos estén activos. |
| `DATA_IMPORT_EXPORT_MODULE` | [Importar y exportar datos](../new-features/data-transfer.md) |
| `PIECES_PHP_DYNAMIC_IMAGES_ENABLE` | el módulo de imágenes integrado |
| `LOCATIONS_ENABLED` | Ubicaciones |
| `SYSTEM_APPROVALS_MODULE` | Aprobaciones |
| `ORGANIZATIONS_MODULE` | Organizaciones. **Se copia de `CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']`** en `critical-definitions.php`; se cambia allí. |
| `API_MODULE`, `API_CRONJOBS`, `API_USERS`, `API_REPORTS` | La API y sus partes. `API_CRONJOBS` enciende la ruta del cron aunque las demás estén apagadas. `API_MODULE` también gobierna las cabeceras CORS. |
| `API_TRANSLATION_MODULE`, `API_AI_TRANSLATIONS_ACTIVE` | La traducción automática de los formularios. |
| `ACTIVE_TIMER` | El temporizador en la barra superior del panel. |
| `HOME_IMAGE_BANNER` | Una ruta de edición de `GenericContentController` (el banner de inicio). |
| `APP_CONFIGURATION_MODULE` | La pantalla de Configuración **y** la lectura de las opciones de la base (ver arriba). |
| `PUBLIC_AREA_VIEWS` | La portada, el contacto, la **baja de suscripción** y las vistas genéricas de la zona pública. Apagarla se lleva por delante el enlace de baja de los correos. |
| `PUBLIC_AREA_CONTACT_FORMS` | El envío del formulario de contacto. |

El tramo «Del framework» (rutas de logs, los grupos de idioma `*_LANG_GROUP`, y las listas de modelos
de IA `AI_MODELS` y `TRANSLATION_AI_LIST`) **no se edita**. `GLOBAL_LANG_GROUP` es de tu proyecto.

Para añadir la bandera de tu módulo:

```php
//Mi módulo
define('MI_MODULO_ENABLE', true);
```

!!! warning "`define()` no se puede repetir"
    Definir dos veces la misma constante no la cambia: el segundo `define()` da un aviso. No copies una constante del
    framework «para cambiarla»; edítala donde está.

---

## `functions.php`

Funciones globales de ayuda. **Es opcional** y corre después de `Config::init()`. Las del framework (arriba) no se
tocan; las tuyas van **al final**, en el hueco `//── Del clon ──`.

| Función | Qué hace |
| :-- | :-- |
| `array_to_html_options(array $values, $selected = null, bool $multiple = false, bool $key_as_value = true): string` | Convierte `[valor => texto]` en etiquetas `<option>`, con el valor y el texto escapados. |
| `addElementInPosition(array $array, $key, $element, int $position): array` | Inserta un elemento en una posición de un array asociativo. |
| `genericViewRoute(string $name, ?string $folder = null, bool $silentOnNotExists = false)` | La URL de una vista genérica; lanza `RouteNotExistsException` si la vista no existe (salvo `$silentOnNotExists`). |
| `genericViewRouteExists(string $name, ?string $folder = null): bool` | Si la vista genérica existe. |
| `getAllUsers(array $ignoreTypes = [])` | Los usuarios no eliminados, sin los tipos indicados. |
| `getAllUsersForSelect(...)` | Lo anterior listo para `array_to_html_options()`. |

```php
//Un <select> de estados; el segundo argumento marca el seleccionado
echo '<select name="status">'
    . array_to_html_options(['1' => 'Activo', '2' => 'Inactivo'], '2')
    . '</select>';
//<option value='1'>Activo</option>
//<option selected value='2'>Inactivo</option>
```

!!! note "`array_to_html_options()` escapa, sin escapar dos veces"
    Escapa el valor y el texto de cada opción, así que los datos de usuario se le pasan tal cual. Lo que ya llega
    escapado no se dobla (no sale `&amp;amp;`), y una entidad puesta a propósito (`&nbsp;`) se conserva; por lo mismo,
    lo que un usuario escriba con forma de entidad (`&amp;`) se pinta como su carácter. El seleccionado
    se compara con la clave tal como llega. Con `$multiple` en `false`, solo se marca la **primera** opción que
    coincida.

Una función propia, con su guarda para no chocar con otra del mismo nombre:

```php
if (!function_exists('mi_helper')) {
    function mi_helper(string $texto): string
    {
        return trim($texto);
    }
}
```

---

## `lang.php`

Los idiomas de la aplicación. Se carga con `require` (es obligatorio) y rellena la configuración con `set_config()`.

### El interruptor: `allowed_langs`

```php
set_config('allowed_langs', [
    'es',
    'en',
    //'fr',
    //'de',
]);
```

`array<string>`; si falta o no es un array, `Config` usa `['es']`. **Lo que no esté aquí no existe**: ni `?i18n=`, ni la
URL, ni el navegador pueden elegirlo. El idioma por defecto (`default_lang`, en `config.php`) se coloca primero.

Cada código tiene su archivo en `src/app/lang/`, con el código por nombre (`en.php` para el inglés), que **devuelve** el array `grupo => [texto => traducción]`
(ver `en.php`). El cargador **ignora en silencio** un idioma permitido sin archivo, y esa es la causa habitual de
«añadí un idioma y sigue en español». `src/app/lang/default.php` aporta lo que no tenga el idioma pedido.

**Para añadir un idioma**, de los que ya traen tablas (`fr`, `de`, `it`, `pt`):

1. Descomenta su línea en `allowed_langs`.
2. Crea su archivo en `src/app/lang/` (por ejemplo `fr.php`) y el mismo en la carpeta `lang/` de cada módulo que lo
   necesite.

Para un código que **no** esté en las tablas de abajo, añade también sus entradas: sin ellas, la bandera es `''`, el
formato de fecha es `Y-m-d`, y no se fija ni el locale del sistema ni el de MySQL.

### Cómo se elige el idioma de cada petición

Tres estrategias, cada una con su interruptor booleano (solo un `true` estricto las enciende):

| Clave | Qué hace |
| :-- | :-- |
| `lang_by_url` | El **primer segmento** de la URL, si es un idioma permitido: `https://sitio/en`. Sin segmento válido, el idioma por defecto. |
| `lang_by_browser` | El idioma preferido de la cabecera `Accept-Language` que esté permitido (si ninguno, el por defecto). Fija el idioma **actual** (`app_lang`), no `default_lang`. |
| `lang_by_cookie` | El valor de una cookie, cuyo nombre es `cookie_lang_definer` (de fábrica `'PREFER_LANG_BY_COOKIE'`). |

Si hay más de una, la prioridad es:

1. **Cookie**, si existe y es un idioma permitido. Gana a todas, incluso a un idioma explícito en la URL.
2. **Navegador**, salvo que `lang_by_url` esté activo y la URL traiga un idioma explícito **distinto del por defecto**,
   que entonces gana.
3. **URL**.
4. El idioma por defecto.

**La cookie se pone con el parámetro `i18n`**: `https://sitio/?i18n=en` la crea con ese idioma (con las opciones de
[`cookies.php`](#cookiesphp)), y `?i18n=default` la borra. Un valor que no sea idioma permitido no se usa. Si
`cookie_lang_definer` no es una cadena no vacía, el nombre sale de `uniqid()` en cada petición y la cookie nunca
persiste.

El prefijo de la URL (`/en`) solo existe para los idiomas distintos del por defecto.

### Revisar las traducciones faltantes

| Clave | Tipo | Efecto |
| :-- | :-- | :-- |
| `no_scan_langs` | `string[]` | Idiomas que **no** se revisan en busca de textos sin traducir. |
| `no_scan_lang_groups` | `string[]` | Grupos de traducción que no se revisan. |
| `additional_langs_to_scan` | `string[]` | Idiomas que **sí** se revisan aunque no estén en `allowed_langs`. |

Los avisos se escriben en `src/app/lang/missing-lang-messages/` **solo en local** (`is_local()`), y los consume
`bin/cli scan-missing-lang`, que revisa los idiomas de `allowed_langs` que no estén en `no_scan_langs`. Con los valores
de fábrica solo se revisa `en`.

### Las tablas por idioma (del framework)

No hace falta tocarlas salvo que añadas un código nuevo.

| Clave | Forma | La usa |
| :-- | :-- | :-- |
| `locale_langs` | `idioma => string[]` (locales del sistema, probados en orden) | `setlocale()` en cada petición |
| `lc_time_names_mysql` | `idioma => string[]` | `SET lc_time_names` una vez por proceso, con la primera conexión; el primero que acepte el servidor. Si ninguno lo acepta, registra un error. |
| `format_date_lang`, `format_date_lang_sql` | `idioma => string` | `get_default_format_date()`: formato PHP y formato MySQL. Sin entrada, `Y-m-d` y `%Y-%m-%d`. |
| `get_fomantic_flag_by_lang` | `callable(string $lang, string $size = '', ?float $opacity = null): string` | El `<i class='… flag'>` del selector de idioma; `''` si el código no existe. |
| `DYNAMIC_TRANSLATIONS` | `array` | Traducciones editables desde el panel. No se edita. |
| `autoTranslateFromLangGroupHTMLIgnoreLangs` | `string[]` | Se pasa al JavaScript como configuración del front: idiomas que `autoTranslateFromLangGroupHTML` no traduce. |

---

## Extensiones del núcleo

`final-configurations.php` carga **todos** los archivos `.php` de `src/app/core/extensions/` (framework) y luego los de
`src/app/config/extensions/` (tuyos). Los del núcleo **no se editan**. Dos de ellos merecen una explicación:

### `queues.php`

Registra el manejador de **prueba** de la cola (`TestQueueRequest::QUEUE_NAME`). Construye el manejador con
`QueueTask::make($nombre, $callable)` y lo registra con `QueueTask::addQueueHandlers($lista)`. Los manejadores se guardan
**por nombre**: registrar otro con el mismo nombre sustituye al anterior.

Los tuyos van en `src/app/config/extensions/queues.php`. El manejador devuelve `QueueHandlerResponse::success()`,
`::fail()` o `::wait()`. El formato completo está en [Sistema de colas](../new-features/queues.md).

```php
use PiecesPHP\Terminal\QueueHandlerResponse;
use PiecesPHP\Terminal\QueueTask;

QueueTask::addQueueHandlers([
    QueueTask::make('mi-cola', function ($data) {
        return QueueHandlerResponse::success('Hecho');
    }),
]);
```

## La ayuda, el crédito y el alta pública

- **`$config['help_faq_url']`** (`src/app/config/config.php`): el enlace de «Preguntas frecuentes» del centro de ayuda de la barra. Vacío, no sale.
- **`$config['developer']`, `$config['developer_logo']` y `$config['developer_url']`**: el crédito del pie del menú. El framework trae marcadores: cámbialos en tu clon.
- **`API_PUBLIC_SIGNUP`** (`src/app/config/constants.php`): el alta de usuarios sin sesión por la API. De fábrica, `false`: la ruta responde 404.
