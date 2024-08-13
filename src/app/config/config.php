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

$config['base_url'] = str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
$config['base_url'] = (isset($_SERVER['HTTPS']) ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/" . mb_substr($config['base_url'], 1);

$config['default_lang'] = "es";
$config['cache_stamp_render_files'] = true;

$config['title_app'] = "Nombre Plataforma";
$config['owner'] = "Nombre Plataforma";

$config['keywords'] = [
    'Website',
    'Application',
];

$config['description'] = "Descripción de la página.";

//Colors
$config['main_brand_color'] = "#6435C9";
$config['second_brand_color'] = "#A333C8";
$config['font_color_one'] = "#1B1C1D";
$config['font_color_two'] = "#FFFFFF";
$config['menu_color_background'] = "#6435C9";
$config['menu_color_mark'] = "rgba(255, 255, 255, 0.2)";
$config['menu_color_font'] = "#FFFFFF";
$config['meta_theme_color'] = "#6435C9";
$config['bg_tools_buttons'] = "#E2ECEF";
$config['body_gradient'] = "transparent linear-gradient(180deg, #D1CCBD40 0%, #D1CCBD00 100%) 0% 0% no-repeat padding-box";

$config['osTicketAPI'] = "";
$config['osTicketAPIKey'] = "";

//──── Seguridad ─────────────────────────────────────────────────────────────────────────

$config['app_key'] = 'TODO:secret';
$config['check_aud_on_auth'] = true;

//──── Statics ───────────────────────────────────────────────────────────────────────────
$config['statics_path'] = __DIR__ . '/../../statics';

//──── Extras ────────────────────────────────────────────────────────────────────────────

$config['mailjet'] = [
    'email' => 'correo@correo.com',
    'name' => 'Name',
    'apiKey' => 'API_KEY',
    'secretKey' => 'SECRET_KEY',
];

//──── Azure ─────────────────────────────────────────────────────────────────────────────
$config['Azure'] = [
    'BASE_STORAGE_ACCOUNT_NAME' => '',
    'BASE_STORAGE_ACCOUNT_KEY' => '',
    //Asociada al contenedor: X
    'BASE_STORAGE_ACCESS_QUERY_PARAMS' => '',
];

//======Información complementaria para mostrar en la aplicación========

//Desarrollador
$config['developer'] = 'Lorem ipsum';
