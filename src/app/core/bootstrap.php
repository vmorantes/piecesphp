<?php

/**
 * boostrap.php - Inicio de la aplicación
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */

error_reporting(E_ALL);
ini_set('display_errors', isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost');
set_error_handler(function ($int_error_type, $string_error_message, $string_error_file, $int_error_line, $array_context) {
    if (error_reporting() & $int_error_type) {
        throw new \ErrorException($string_error_message, 0, $int_error_type, $string_error_file, $int_error_line);
    }
    return true;
});

$directories = [
    'vendor_autoload' => __DIR__ . "/../../vendor/autoload.php",
    'autoload' => __DIR__ . "/autoload.php",
    'utilities' => __DIR__ . "/Utilities.php",
    'config' => __DIR__ . "/../config/config.php",
    'database' => __DIR__ . "/../config/database.php",
    'mail' => __DIR__ . "/../config/mail.php",
    'cookies' => __DIR__ . "/../config/cookies.php",
    'roles' => __DIR__ . "/../config/roles.php",
    'config_class' => __DIR__ . "/Config.php",
    'app_helpers' => __DIR__ . "/AppHelpers.php",
    'config_lang' => __DIR__ . "/../config/lang.php",
    'custom_functions' => __DIR__ . "/../config/functions.php",
    'custom_constants' => __DIR__ . "/../config/constants.php",
    'custom_autoloads' => __DIR__ . "/../config/autoloads.php",
    'custom_autoloads_config' => __DIR__ . "/custom-autoloads-config.php",
];

require $directories['vendor_autoload'];
require $directories['autoload'];

use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ServerStatics;

if (!defined('BASEPATH')) {
    /**
     * @var string La ruta base de toda la aplicación
     */
    define('BASEPATH', realpath(__DIR__ . '/../../'));
}
if (!defined('APP_VERSION')) {
    /**
     * @var string Versión de la aplicación
     */
    define('APP_VERSION', '4.6');
}

require $directories['utilities'];

require $directories['config'];
require $directories['config_class'];
require $directories['database'];
require $directories['mail'];
require $directories['cookies'];
require $directories['roles'];

if (!isset($config) || !is_array($config)) {
    $config_pcs_php = [];
} else {
    $config_pcs_php = $config;
}

if (is_array($config_pcs_php)) {
    foreach ($config_pcs_php as $name => $value) {
        Config::set_config($name, $value);
    }
}

require $directories['app_helpers'];
require $directories['config_lang'];

Config::init();

if (file_exists($directories['custom_functions'])) {
    require $directories['custom_functions'];
}

if (file_exists($directories['custom_constants'])) {
    require $directories['custom_constants'];
}

if (file_exists($directories['custom_autoloads'])) {

    require $directories['custom_autoloads_config'];

}

if (get_config('statics_path') !== false && is_string(get_config('statics_path'))) {
    ServerStatics::setStaticPath(get_config('statics_path'));
}

//Configurar seguridad de tokens con la app_key general
BaseToken::setSecretKey(Config::app_key());

//Configurar seguridad de encriptación con la app_key general
BaseHashEncryption::setSecretKey(Config::app_key());

//Configurar directorio de vistas por defecto
BaseController::setViewDir(Config::app_path() . "/app/view/");
