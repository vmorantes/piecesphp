<?php

/**
 * boostrap.php - Inicio de la aplicación
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */

use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\TerminalData;

//Preparación para solicitudes desde la terminal
$_SERVER['PCSPHP_TERMINAL_DATA'] = [
    'isTerminal' => defined('STDIN'),
    'arguments' => [],
    'route' => [],
];
$_SERVER['argv'] = isset($_SERVER['argv']) ? $_SERVER['argv'] : [];
$_SERVER['argc'] = isset($_SERVER['argc']) ? $_SERVER['argc'] : count($_SERVER['argv']);

if (!isset($_SERVER['HTTP_HOST'])) {

    $fileEntry = basename($_SERVER['PHP_SELF']);
    /**
     * Genera una salida en la terminal
     *
     * @param string $text
     * @param boolean $newLine
     * @param string $newLineChars
     * @return void
     */
    $echoStd = function (string $text, bool $newLine = true, string $newLineChars = "\r\n") {
        fwrite(STDOUT, $text . ($newLine ? $newLineChars : ''));
        flush();
    };

    if ($fileEntry == 'index.php') {

        $_SERVER['SCRIPT_NAME'] = '';
        $argv = $_SERVER['argv'];
        $argc = $_SERVER['argc'];
        $firstArgument = $argc > 0 ? basename($argv[0]) : null;
        $firstArgumentValid = $firstArgument == $fileEntry;

        $secondArgumentRequired = 'cli';
        $secondArgument = $argc > 1 ? $argv[1] : null;
        $secondArgumentValid = $secondArgumentRequired === $secondArgument;

        if ($firstArgument !== null && $firstArgument) {
            unset($argv[0]);
            $argc--;
        }
        if ($secondArgumentValid) {
            unset($argv[1]);
            $argc--;
        }

        if ($argc > 0 && $firstArgumentValid && $secondArgumentValid) {

            $terminalData = $_SERVER['PCSPHP_TERMINAL_DATA'];
            $actionName = $argv[2];
            unset($argv[2]);

            foreach ($argv as $i) {

                $argParts = explode('=', $i);

                if (count($argParts) == 2) {

                    $argName = $argParts[0];
                    $argValue = $argParts[1];
                    if (is_string($argName) && is_string($argValue)) {
                        $terminalData['arguments'][$argName] = $argValue;
                    }

                }

            }

            $terminalData['route'] = $actionName;

            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['REQUEST_URI'] = '';
            $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalData;

        } else {
            $echoStd('No se ha especificado ninguna acción.');
            exit;
        }
    }
}

//Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] == 'localhost');
set_error_handler(function ($int_error_type, $string_error_message, $string_error_file, $int_error_line) {
    $errorLevelTypeReferencesByType = [
        E_ERROR => 'Fatal error',
        E_WARNING => 'Warning',
        E_PARSE => 'Compile-time',
        E_NOTICE => 'Notice -possible false positive-',
        E_DEPRECATED => 'Deprecated',
    ];
    $stopExcutionErrors = array_keys($errorLevelTypeReferencesByType);

    if (error_reporting() & $int_error_type) {

        $message = $string_error_message;
        if (isset($errorLevelTypeReferencesByType[$int_error_type])) {
            $levelTypeError = $errorLevelTypeReferencesByType[$int_error_type];
            $message = "(Level: {$levelTypeError}) {$message}";
        }
        $exception = new \ErrorException($message, 0, $int_error_type, $string_error_file, $int_error_line);;

        if (in_array($int_error_type, $stopExcutionErrors)) {
            throw $exception;
        }

    }
    return true;
});
/**
 * @param \Exception|\Throwable $exception
 * @param string $context Información sobre el lugar de donde fue manejado
 */
function global_custom_exception_handler($exception, string $context = 'set_exception_handler')
{

    $request = RequestRouteFactory::createFromGlobals();
    $stopAndShowError = function (\Exception $exception, RequestRoute $request) use ($context) {
        $customErrorHandler = new CustomSlimErrorHandler($exception, $context);
        $responseBody = $customErrorHandler->getResponse($request);
        $content = $responseBody->getLastWriteBodyData();
        $contentType = $responseBody->getHeaderLine('Content-Type');
        if ($contentType === 'application/json') {
            header('Content-Type: ' . $contentType);
            http_response_code(500);
        } else {
            header('Content-Type: text/html');
            http_response_code(500);
        }
        die($content);
    };

    //Manejo de errores lanzados por throw
    if ($exception instanceof \Error) {
        $errorClass = get_class($exception);
        $exception = new \ErrorException("({$errorClass}) " . $exception->getMessage(), $exception->getCode(), E_WARNING);
    }

    //Manejos de excepciones
    if (
        $exception instanceof \ErrorException ||
        $exception instanceof \Exception ||
        $exception instanceof \ClosedGeneratorException
    ) {
        ($stopAndShowError)($exception, $request);
    }

}

set_exception_handler('global_custom_exception_handler');

$directories = [
    'vendor_autoload' => __DIR__ . "/../../vendor/autoload.php",
    'autoload' => __DIR__ . "/autoload.php",
    'utilities' => __DIR__ . "/Utilities.php",
    'config' => __DIR__ . "/../config/config.php",
    'database' => __DIR__ . "/../config/database.php",
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
    define('APP_VERSION', '6.1.0');
    define('APP_VERSION_DATE', \DateTime::createFromFormat('d-m-Y', '18-12-2023')->format('Y-m-d'));
}

require $directories['utilities'];

require $directories['config'];
require $directories['config_class'];
require $directories['database'];
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

set_config('terminalData', TerminalData::getInstance()->setData($_SERVER['PCSPHP_TERMINAL_DATA']));
