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
 * $config['title_app']: Título de la aplicación
 * $config['title']: Título de la aplicación no afectado por set_title() ni get_title
 *
 * $config['base_url']: URL base de la aplicación
 *
 * $config['addons']: Nombres de los addons habilitados
 *     - Lista de addons disponibles:
 *         - blog
 *
 * $config['app_key']: Llave de la aplicación usada para la encriptación de todos los token y sesiones
 *
 * $['statics_path']: Este es el directorio por defecto donde buscará todas las solicitudes de archivos
 * estáticos. Si se quiere desactivar esta opción puede borrarse la ruta /statics/ de slim
 *
 */

//──── Generales ─────────────────────────────────────────────────────────────────────────

date_default_timezone_set('America/Bogota');

$config['default_lang'] = "es";
$config['app_lang'] = "es";
$config['title_app'] = "Nombre Plataforma";
$config['title'] = $config['title_app'];
$config['base_url'] = str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']);
$config['base_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/" . substr($config['base_url'], 1);

//──── AddOns ────────────────────────────────────────────────────────────────────────────
$config['addons'] = []; //TODO

//──── Seguridad ─────────────────────────────────────────────────────────────────────────

$config['app_key'] = 'secret';

//──── Statics ───────────────────────────────────────────────────────────────────────────
$config['statics_path'] = __DIR__ . '/../../statics';

//──── Extras ────────────────────────────────────────────────────────────────────────────
$config['osTicketAPI'] = 'http://ayuda.tejidodigital.com';
$config['osTicketAPIKey'] = 'E41DCA99FDBE099ECC0EC48E34F41136';
$config['developer'] = 'Vicsen Morantes - sir.vamb@gmail.com';
$config['favicon'] = append_to_url($config['base_url'], 'statics/images/favicon.ico');
$config['logo'] = append_to_url($config['base_url'], 'statics/images/logo.png');
$config['logo-login'] = append_to_url($config['base_url'], 'statics/images/logo-login.png');
$config['logo-sidebar-top'] = append_to_url($config['base_url'], 'statics/images/logo-sidebar-top.png');
$config['logo-sidebar-bottom'] = append_to_url($config['base_url'], 'statics/images/logo-sidebar-bottom.png');
$config['logo-mailing'] = append_to_url($config['base_url'], 'statics/images/logo-mailing.png');


//──── Locations ─────────────────────────────────────────────────────────────────────────
$config['locations-load-assets'] = true;
