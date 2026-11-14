<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: el nombre, la zona horaria, los colores, la llave de la aplicación, los destinatarios, la seguridad y las claves de tu instalación. Lo que calcula el dominio y las rutas de estáticos es del framework.
 */

defined('BASEPATH') or die();

//========================================================================================
/*                                                                                      *
 *                           CONFIGURACIONES DE LA APLICACIÓN                           *
 *                                                                                      */
//========================================================================================
/**
 * Las claves de este archivo, su tipo y su efecto: source-docs/project/docs/piecesphp/content/configuration.md, «config.php».
 */

//──── Generales ─────────────────────────────────────────────────────────────────────────

//── Del clon ──
date_default_timezone_set('America/Bogota');

/**
 * @var array $config
 * */

//── Del framework: no lo edites ──
$config['domain'] = $_SERVER['HTTP_HOST'];
$config['domain_protocol'] = (isset($_SERVER['HTTPS']) ? "https://" : "http://");
$config['base_domain_path'] = str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
$config['base_domain_url'] = $config['domain_protocol'] . $config['domain'];
$config['base_url'] = $config['base_domain_url'] . $config['base_domain_path'];

//── Del clon ──
$config['default_lang'] = "es";
//Locale de Open Graph por idioma (og:locale). Un idioma sin entrada emite su código solo.
$config['og_locales'] = ['es' => 'es_CO', 'en' => 'en_US'];
$config['cache_stamp_render_files'] = true;

$config['title_app'] = "Nombre Plataforma";
$config['owner'] = "Nombre Plataforma";

$config['keywords'] = [
    'Website',
    'Application',
];

$config['description'] = "Descripción de la página.";

//Colors and fonts
$config['main_brand_color'] = "#356DCA";
$config['second_brand_color'] = "#7A5AF8";
$config['third_brand_color'] = "#C11574";
$config['meta_theme_color'] = "#356DCA";
//Vacío, no se publica y manda el tema: la serie 5 es light-dark(), y fijarla rompería el modo oscuro.
$config['data_color_2'] = "";
$config['data_color_3'] = "";
$config['data_color_4'] = "";
$config['data_color_5'] = "";
$config['theme_font'] = "inter";
$config['theme_font_size'] = 14;
$config['theme_radius'] = 8;
$config['accessibility_text_size'] = 'normal';
$config['accessibility_theme_mode'] = 'auto';

//Varios
$config['osTicketAPI'] = "";
$config['osTicketAPIKey'] = "";
//Destinatarios del formulario de contacto, del correo de «otros problemas» (si osTicket falta o falla) y del formulario
//de soporte del panel. Vacíos: no se envía. Nunca una dirección real en el código: todo clon la heredaría.
$config['contact_form_recipients'] = [];
$config['other_problems_recipients'] = [];
//La de soporte es de EJEMPLO (dominio reservado, RFC 2606: nadie la recibe) para que el botón se vea; un aviso pide
//cambiarla (support-recipients-placeholder). Vacía, el botón de soporte no sale.
$config['support_form_recipients'] = ['help@example.org'];
$config['LabsMobileAPIKey'] = "correo@domain.tld:API_KEY";
$config['LabsMobileSendInLocal'] = true;
//La llave del cron (CronJobKey) no va aquí: la pone secure-keys/cronjob (core/extensions/api-keys.php). Sin ella, la ruta
//del cron no acepta ninguna.

//──── Seguridad ─────────────────────────────────────────────────────────────────────────

$config['app_key'] = 'TODO:secret';
//El nombre de la sesión (cookie y cabecera) de ESTA instalación: letras, dígitos y _, hasta 64. Cambiarlo cierra todas las
//sesiones abiertas. Lo propone y lo escribe `bin/cli session-name` (@codigo-comentado: la plantilla, debajo).
//$config['session_token_name'] = 'JWTAuth';
$config['check_aud_on_auth'] = true;
$config['hide_app_key_warning'] = false;
//Orígenes exactos —esquema, host y puerto, como `https://app.ejemplo.com:8443`— que reciben CORS CON credenciales.
//Cada uno recibe las cookies del usuario en sus peticiones: el propio de la instalación ya va incluido.
$config['cors_credentials_origins'] = [];
//Límite de intentos de generate-otp, check-totp, two-factor-auth-status, del segundo factor y de la contraseña del login (OTPRateLimiter).
//uniformResponse: generate-otp responde lo mismo exista o no el usuario. oneUseCodeMinutes: la validez del código de un uso.
$config['otp_security'] = [
    'maxFailuresPerUser' => 5,
    'maxFailuresPerIP' => 20,
    'windowMinutes' => 15,
    'lockMinutes' => 15,
    'uniformResponse' => true,
    'oneUseCodeMinutes' => 20,
    //Login con contraseña: una IP con tantos fallos en windowMinutes queda frenada lockMinutes, sin mirar el usuario.
    'loginMaxFailuresPerIP' => 5,
    //La cuenta solo se bloquea (con failedAttempts al tope) si sus fallos vienen de al menos tantas IP distintas…
    'loginAccountDistinctIPs' => 3,
    //…dentro de esta ventana, en minutos.
    'loginAccountWindowMinutes' => 1440,
];

//── Del framework: no lo edites ──
//──── Statics ───────────────────────────────────────────────────────────────────────────
$config['statics_path'] = __DIR__ . '/../../statics';
//Sufijo en disco de lo privado de uploads (ProtectedUploads): foto.jpg.protected. La URL nunca lo lleva.
$config['protected_uploads_suffix'] = '.protected';

//── Del clon ──
//──── Extras ────────────────────────────────────────────────────────────────────────────

$config['mailjet'] = [
    'email' => 'correo@correo.com',
    'name' => 'Name',
    'apiKey' => 'API_KEY',
    'secretKey' => 'SECRET_KEY',
];

//Claves de reCAPTCHA v3 de PRUEBA del propietario: solo valen en sus dominios de prueba y en localhost.
//En producción, las reales van en las claves seguras (ver api-keys.php), que tienen prioridad.
$config['GoogleReCaptchaV3TestSiteKey'] = '6Le1Zb8tAAAAAOmAjR_AV6Pinr4D3OTRqAh_7ws-';
$config['GoogleReCaptchaV3TestSecretKey'] = '6Le1Zb8tAAAAACveNPehd0WXtX6NkK7P915giuWG';

//Inteligencia artificial
$config['OpenAIApiKey'] = "";
$config['MistralAIApiKey'] = "";
$config['GroqAPIKey'] = "";
$config['translationAI'] = "";
$config['translationAIEnable'] = true;
$config['modelOpenAI'] = "gpt-3.5-turbo-0125";
$config['modelMistral'] = "mistral-medium";

//──── Azure ─────────────────────────────────────────────────────────────────────────────
$config['Azure'] = [
    'BASE_STORAGE_ACCOUNT_NAME' => '',
    'BASE_STORAGE_ACCOUNT_KEY' => '',
    //Asociada al contenedor: X
    'BASE_STORAGE_ACCESS_QUERY_PARAMS' => '',
    //Speech to text
    'SPEECH_SUBSCRIPTION_KEY' => '',
    'SPEECH_REGION' => 'eastus',
];

//──── Geolozalización ───────────────────────────────────────────────────────────────────
//GeoIP
$config['GEO_IP'] = [
    'custom_directory' => realpath(__DIR__ . '/../../geoip'),
];
ini_set('geoip.custom_directory', $config['GEO_IP']['custom_directory']);

//======Información complementaria para mostrar en la aplicación========

//Desarrollador: el crédito del pie del menú del panel (P263). Fijo por clon, aquí y no en el panel; vacío no se pinta.
//Ejemplo: 'developer' => 'Mi Empresa S.A.S.', 'developer_logo' => 'statics/images/mi-empresa.svg', 'developer_url' => 'https://mi-empresa.example'
$config['developer'] = 'Nombre Desarrollador';
$config['developer_logo'] = 'statics/images/developer-logo.png';
$config['developer_url'] = '';
//Las preguntas frecuentes del proyecto, si las tiene; vacío, la entrada no sale.
$config['help_faq_url'] = '';
//El manual de usuario del panel (ADR 0072): si lo ven las cuentas que aún no están aprobadas.
$config['manual_for_pending'] = true;

//Listeners
\PiecesPHP\Core\BaseEventDispatcher::defaultListen(\PiecesPHP\Core\BaseEventDispatcher::EVENT_INIT_ROUTES_NAME, function () {
    //Cuando se registran las rutas
    //Do something
});
