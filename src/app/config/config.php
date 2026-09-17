<?php

defined('BASEPATH') or die();

//========================================================================================
/*                                                                                      *
 *                           CONFIGURACIONES DE LA APLICACIÓN                           *
 *                                                                                      */
//========================================================================================
/**
 *
 * $config['default_lang']: Lenguaje por defecto de la aplicación
 * $config['app_lang']: Lenguaje actual de la aplicación
 *
 * $config['title_app']: Título de la aplicación en general
 * $config['title']: Título de la sección actual (set_title() y get_title())
 *
 * $config['domain']: Dominio de la aplicación
 * $config['domain_protocol']: Protocolo de la aplicación
 * $config['base_domain_path']: Ruta base del dominio de la aplicación
 * $config['base_domain_url']: URL base del dominio de la aplicación
 * $config['base_url']: URL base de la aplicación
 *
 * $config['app_key']: Llave de la aplicación usada para la encriptación de todos los token y sesiones
 *
 * $['statics_path']: Este es el directorio por defecto donde buscará todas las solicitudes de archivos
 * estáticos. Si se quiere desactivar esta opción puede borrarse la ruta /statics/ de slim
 *
 */

//──── Generales ─────────────────────────────────────────────────────────────────────────

date_default_timezone_set('America/Bogota');

/**
 * @var array $config
 * */

$config['domain'] = $_SERVER['HTTP_HOST'];
$config['domain_protocol'] = (isset($_SERVER['HTTPS']) ? "https://" : "http://");
$config['base_domain_path'] = str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
$config['base_domain_url'] = $config['domain_protocol'] . $config['domain'];
$config['base_url'] = $config['base_domain_url'] . $config['base_domain_path'];

$config['default_lang'] = "es";
$config['cache_stamp_render_files'] = true;

$config['title_app'] = "Nombre Plataforma";
$config['owner'] = "Nombre Plataforma";

$config['keywords'] = [
    'Website',
    'Application',
];

$config['description'] = "Descripción de la página.";

//Colors and fonts
$config['main_brand_color'] = "#6435C9";
$config['second_brand_color'] = "#A333C8";
$config['font_color_one'] = "#1B1C1D";
$config['font_color_two'] = "#FFFFFF";
$config['menu_color_background'] = "#6435C9";
$config['menu_color_mark'] = "rgba(255, 255, 255, 0.2)";
$config['menu_color_font'] = "#FFFFFF";
$config['meta_theme_color'] = "#6435C9";
$config['bg_tools_buttons'] = "#E2ECEF";
$config['body_gradient'] = "transparent linear-gradient(180deg,rgba(100, 53, 201, 0.78) 0%, #D1CCBD00 100%) 0% 0% no-repeat padding-box";
$config['font_family_global'] = "'Poppins', sans-serif";
$config['font_family_sidebars'] = "'Public Sans', sans-serif";

//Varios
$config['osTicketAPI'] = "";
$config['osTicketAPIKey'] = "";
//Destinatarios del formulario de contacto y del correo de «otros problemas» (si osTicket falta o falla).
//Vacíos: no se envía. Nunca una dirección en el código: todo clon la heredaría.
$config['contact_form_recipients'] = [];
$config['other_problems_recipients'] = [];
$config['LabsMobileAPIKey'] = "correo@domain.tld:API_KEY";
$config['LabsMobileSendInLocal'] = true;
$config['CronJobKey'] = 'TODO:secret';

//──── Seguridad ─────────────────────────────────────────────────────────────────────────

$config['app_key'] = 'TODO:secret';
$config['check_aud_on_auth'] = true;
$config['hide_app_key_warning'] = false;
//Límite de intentos de generate-otp, check-totp, two-factor-auth-status y del segundo factor del login (OTPRateLimiter).
//uniformResponse: generate-otp responde lo mismo exista o no el usuario. oneUseCodeMinutes: la validez del código de un uso.
$config['otp_security'] = [
    'maxFailuresPerUser' => 5,
    'maxFailuresPerIP' => 20,
    'windowMinutes' => 15,
    'lockMinutes' => 15,
    'uniformResponse' => true,
    'oneUseCodeMinutes' => 20,
];

//──── Statics ───────────────────────────────────────────────────────────────────────────
$config['statics_path'] = __DIR__ . '/../../statics';
//Sufijo en disco de lo privado de uploads (ProtectedUploads): foto.jpg.protected. La URL nunca lo lleva.
$config['protected_uploads_suffix'] = '.protected';

//──── Extras ────────────────────────────────────────────────────────────────────────────

$config['mailjet'] = [
    'email' => 'correo@correo.com',
    'name' => 'Name',
    'apiKey' => 'API_KEY',
    'secretKey' => 'SECRET_KEY',
];

$config['SurveyJSKey'] = "";

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

//Desarrollador
$config['developer'] = 'Lorem ipsum';

//Listeners
\PiecesPHP\Core\BaseEventDispatcher::defaultListen(\PiecesPHP\Core\BaseEventDispatcher::EVENT_INIT_ROUTES_NAME, function () {
    //Cuando se registran las rutas
    //Do something
});
