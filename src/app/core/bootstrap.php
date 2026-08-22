<?php

/**
 * boostrap.php - Inicio de la aplicación
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */

use PiecesPHP\Cli;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\TerminalData;

//Constantes críticas (primer archivo que se carga)
require_once __DIR__ . "/../config/critical-definitions.php";

//Preparación para solicitudes desde la terminal
require_once __DIR__ . "/psr4/PiecesPHP/Cli.php"; //Incluye la clase Cli
$cliHandler = new Cli($argv ?? [], [
    'addLines' => false,
    'skipArgs' => 2, //El nombre del script y el comando cli
]);
/**
 * @var array{
 *  isTerminal:bool,
 *  arguments:array<string,mixed>,
 *  route:string,
 *  local:bool,
 *  cli:Cli,
 * }
 */
$_SERVER['PCSPHP_TERMINAL_DATA'] = [
    'isTerminal' => defined('STDIN'),
    'arguments' => [],
    'route' => '',
    'local' => true,
    'cli' => $cliHandler,
];
if (!isset($_SERVER['HTTP_HOST'])) {

    $fileEntry = basename($_SERVER['PHP_SELF']);

    if ($fileEntry == 'index.php') {

        $startScriptOK = $cliHandler->scriptName == $fileEntry;
        $commandIsCli = $cliHandler->getCommand() == 'cli';
        $isLocalMode = $cliHandler->getArgumentValue('--local') ?? false;
        $isValidCli = $startScriptOK && $commandIsCli;

        if ($isValidCli) {
            $cliHandler->removeArgument('--local'); //Eliminar el argumento --local para limpiar el array de argumentos
            $firstArgument = $cliHandler->getArgumentByPosition(0); //Obtener el primer argumento para usarlo como comando
            if ($firstArgument !== null) {
                //Eliminar el primer argumento para limpiar el array de argumentos y usarlo como comando
                $cliHandler->removeArgument($firstArgument['name']);
                $cliHandler->setCommand($firstArgument['name']);
            } else {
                $isValidCli = false; //No se especificó ningún comando
            }
        }

        if ($isValidCli) {

            $terminalData = $_SERVER['PCSPHP_TERMINAL_DATA'];
            $terminalData['local'] = $isLocalMode;
            $actionName = $cliHandler->getCommand();

            foreach ($cliHandler->getArguments() as $argument) {
                $terminalData['arguments'][$argument['name']] = $argument['value'];
            }

            $terminalData['route'] = $actionName;
            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['REQUEST_URI'] = '';
            $_SERVER['SCRIPT_NAME'] = '';
            $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalData;

        } else {
            Cli::systemOutFormatted('No se ha especificado ninguna acción.', [
                'color' => 'red',
                'newLine' => true,
            ]);
            exit;
        }
    }
}

//Manejo de errores
error_reporting(E_ALL);
$isLocalBootstrap = (
    isset($_SERVER['HTTP_HOST']) && (
        $_SERVER['HTTP_HOST'] === 'localhost' ||
        mb_substr($_SERVER['HTTP_HOST'], -10) === '.localhost'
    )
);
if ($cliHandler->isCliPlatform) {
    $isLocalBootstrap = $cliHandler->orginalArguments['--local'] ?? false;
}
ini_set('display_errors', $isLocalBootstrap);
set_error_handler(function ($int_error_type, $string_error_message, $string_error_file, $int_error_line) use ($isLocalBootstrap) {
    $errorLevelTypeReferencesByType = [
        E_ERROR => 'Fatal error',
        E_WARNING => 'Warning',
        E_PARSE => 'Compile-time',
        E_NOTICE => 'Notice -possible false positive-',
        E_DEPRECATED => 'Deprecated',
        //Niveles que la tabla anterior no cubría y que por tanto se descartaban
        //en silencio, incluido E_USER_ERROR: todo trigger_error() de una librería
        //—entre ellos el platform_check de Composer— se perdía sin dejar rastro.
        E_RECOVERABLE_ERROR => 'Recoverable error',
        E_USER_ERROR => 'Fatal error (trigger_error)',
        E_USER_WARNING => 'Warning (trigger_error)',
        E_USER_NOTICE => 'Notice (trigger_error)',
        E_USER_DEPRECATED => 'Deprecated (trigger_error)',
    ];

    //Niveles que abortan la ejecución.
    $stopExcutionErrors = [
        E_ERROR,
        E_WARNING,
        E_PARSE,
        E_NOTICE,
        E_RECOVERABLE_ERROR,
        E_USER_ERROR,
    ];

    //Las deprecaciones solo abortan en local, donde queremos enterarnos de
    //inmediato. En producción se registran y la petición continúa: una
    //deprecación es un aviso sobre una versión futura de PHP, no un fallo de
    //la petición en curso, y tumbar producción por ella es desproporcionado.
    //OJO: un cronjob lanzado sin --local cae en la rama de producción.
    if ($isLocalBootstrap) {
        $stopExcutionErrors[] = E_DEPRECATED;
        $stopExcutionErrors[] = E_USER_DEPRECATED;
    }

    //Silenciado con @ o fuera de error_reporting: se respeta la supresión.
    //Importa: bootstrap.php carga el autoload de bin/tools con @require_once
    //precisamente para que sea opcional.
    if (!(error_reporting() & $int_error_type)) {
        return true;
    }

    $message = $string_error_message;
    if (isset($errorLevelTypeReferencesByType[$int_error_type])) {
        $levelTypeError = $errorLevelTypeReferencesByType[$int_error_type];
        $message = "(Level: {$levelTypeError}) {$message}";
    }

    if (in_array($int_error_type, $stopExcutionErrors, true)) {
        throw new \ErrorException($message, 0, $int_error_type, $string_error_file, $int_error_line);
    }

    //No aborta: se deja constancia. Las deprecaciones van a un archivo propio
    //para poder vaciarlo como puerta de la migración; el resto, al log de PHP.
    $logLine = "[" . date('c') . "] {$message} en {$string_error_file}:{$int_error_line}" . PHP_EOL;
    $deprecationLevels = [E_DEPRECATED, E_USER_DEPRECATED];
    $writtenToOwnLog = false;
    if (in_array($int_error_type, $deprecationLevels, true) && defined('LOG_ERRORS_PATH')) {
        $deprecationsLog = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . DIRECTORY_SEPARATOR . 'deprecations.log';
        $writtenToOwnLog = @file_put_contents($deprecationsLog, $logLine, FILE_APPEND | LOCK_EX) !== false;
    }
    if (!$writtenToOwnLog) {
        error_log(rtrim($logLine));
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
        // Limpiar todos los buffers de salida previos
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

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

        //CORS para API
        if (defined('API_MODULE') && API_MODULE) {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS, TRACE, CONNECT');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, isWebApp, isExternalLogin, JWTAuth');
            header('Vary: Origin');
        }
        /**
         * `die($string)` IMPRIME Y SALE CON CÓDIGO CERO. En CLI eso hace que un proceso que
         * murió informe de que todo fue bien, y entonces cualquier puerta lanzada por
         * `bin/cli` deja de distinguir «pasé» de «no llegué a ejecutarme». Estuvo así toda
         * la campaña: `verify-integrity` con un archivo sin compilar daba salida 0.
         *
         * En HTTP el código de salida no lo lee nadie: quien manda es el 500 que ya se envió.
         */
        echo $content;
        exit(PHP_SAPI === 'cli' ? 1 : 0);
    };

    //Manejo de errores lanzados por throw
    if ($exception instanceof \Error) {
        $errorClass = $exception::class;
        $exception = new \ErrorException("({$errorClass}) " . $exception->getMessage(), $exception->getCode(), E_WARNING, $exception->getFile(), $exception->getLine(), $exception->getPrevious());
    }

    //Manejos de excepciones
    if (
        $exception instanceof \ErrorException  ||
        $exception instanceof \Exception  ||
        $exception instanceof \ClosedGeneratorException
    ) {
        ($stopAndShowError)($exception, $request);
    }

}

$directories = [
    'tools_vendor_autoload' => __DIR__ . "/../../../bin/tools/vendor/autoload.php",
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

if (@file_exists($directories['tools_vendor_autoload'])) {
    @require_once $directories['tools_vendor_autoload'];
}
require_once $directories['vendor_autoload'];
require_once $directories['autoload'];

//Se registra el manejador de excepciones global luego de que se hayan registrado los autoloads
set_exception_handler('global_custom_exception_handler');

if (!defined('BASEPATH')) {
    /**
     * La ruta base de toda la aplicación
     */
    define('BASEPATH', realpath(__DIR__ . '/../../'));
}
if (!defined('APP_VERSION')) {
    /**
     * Versión de la aplicación
     */
    define('APP_VERSION', 'v7.1.0');
    /**
     * Fecha de la versión de la aplicación
     */
    define('APP_VERSION_DATE', (new \DateTime('2026-08-20'))->format('Y-m-d'));
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
