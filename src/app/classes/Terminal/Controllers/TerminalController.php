<?php

/**
 * TerminalController.php
 */

namespace Terminal\Controllers;

use App\Controller\AdminPanelController;
use App\Controller\AppConfigController;
use App\Model\UsersModel;
use Ifsnop\Mysqldump\Mysqldump;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRouteFactory;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\TerminalData;

/**
 * TerminalController.
 *
 * @package     Terminal\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class TerminalController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = '';
    /**
     * @var string
     */
    protected static $baseRouteName = 'terminal';

    public function __construct()
    {
        parent::__construct();
        $this->model = new BaseModel();
    }

    /**
     * Respaldar toda la base de datos
     *
     * @return void
     */
    public function dbBackup()
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'gz',
                true,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? mb_strtolower(clean_string($value)) === 'yes' : $value === true;
                }
            ),
        ]);

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(TerminalData::getInstance()->arguments());

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        //Mensajes de respuesta
        $responseText = "asfasffas";

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información de los parámetros
            /**
             * @var string $gz
             */
            $gz = $expectedParameters->getValue('gz');

            $db = $this->model->getDatabase();
            $dbName = $db->getDatabaseName();
            $dbHost = $db->getHost();
            $dbUser = $db->getUsername();
            $dbPassword = $db->getPassword();
            $dbPassword = $dbPassword !== null ? $dbPassword : '';

            $dumpSettingsDefault = array(
                'compress' => $gz ? Mysqldump::GZIP : Mysqldump::NONE,
                'add-drop-table' => true,
                'default-character-set' => Mysqldump::UTF8,
                'routines' => true,
                'single-transaction' => true,
                'skip-definer' => true,
                'disable-foreign-keys-check' => true,
            );

            if ($dbUser !== null) {

                $dump = new Mysqldump("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPassword, $dumpSettingsDefault);
                $fileName = date('d-m-Y_H-i-s-A') . ($gz ? '.sql.gz' : '.sql');
                $dumpDirectory = basepath("dumps");
                $htaccess = "{$dumpDirectory}/.htaccess";

                if (!file_exists($dumpDirectory)) {
                    mkdir($dumpDirectory, 0777, true);
                }

                if (!file_exists($htaccess)) {
                    $htaccessContent = "<IfVersion > 2.4>\r\n";
                    $htaccessContent .= "\tDeny from All\r\n";
                    $htaccessContent .= "</IfVersion>\r\n";
                    $htaccessContent .= "<IfVersion <= 2.4>\r\n";
                    $htaccessContent .= "\tRequire all denied\r\n";
                    $htaccessContent .= "</IfVersion>";
                    @file_put_contents($htaccess, $htaccessContent);
                }

                try {

                    $output = "{$dumpDirectory}/{$fileName}";
                    $changePermissions = !file_exists($output);
                    $dump->start($output);

                    if ($changePermissions) {
                        chmod($output, 0777);
                    }

                    $responseText = "Operación exitosa\r\n";

                } catch (\Exception $e) {
                    $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
                    log_exception($e);
                }

            } else {
                $responseText = "No se pudo seleccionar ningún usuario para la conexión a la base de datos.\r\n";
            }

        } catch (MissingRequiredParamaterException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (ParsedValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (\Exception $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        }

        echoTerminal($responseText);
    }

    /**
     * @return void
     */
    public function bundle()
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'app',
                false,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? mb_strtolower(clean_string($value)) === 'yes' : $value === true;
                }
            ),
            new Parameter(
                'statics',
                false,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? mb_strtolower(clean_string($value)) === 'yes' : $value === true;
                }
            ),
            new Parameter(
                'all',
                false,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? mb_strtolower(clean_string($value)) === 'yes' : $value === true;
                }
            ),
            new Parameter(
                'zip',
                false,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return is_string($value) ? mb_strtolower(clean_string($value)) === 'yes' : $value === true;
                }
            ),
        ]);

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(TerminalData::getInstance()->arguments());

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        //Mensajes de respuesta
        $responseText = "";

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información de los parámetros
            /**
             * @var bool $app
             * @var bool $statics
             * @var bool $all
             * @var bool $zip
             */
            $app = $expectedParameters->getValue('app');
            $statics = $expectedParameters->getValue('statics');
            $all = $expectedParameters->getValue('all');
            $zip = $expectedParameters->getValue('zip');

            $bundleDirectory = basepath("bundle");

            if (!file_exists($bundleDirectory)) {
                mkdir($bundleDirectory, 0777, true);
            } else {
                $bundleDirectoryObject = new DirectoryObject($bundleDirectory);
                $bundleDirectoryObject->process();
                $bundleDirectoryObject->delete(false);
            }

            $appDirectory = new DirectoryObject(basepath('app'));
            $staticsDirectory = new DirectoryObject(basepath('statics'));
            $hasOutput = false;

            if ($all) {
                $app = true;
                $statics = true;
            }

            if ($app) {
                $ignore = new FilesIgnore([]);
                $ignore->addRegExpr("sass$");
                $ignore->addRegExpr("app/cache$");
                $ignore->addRegExpr("app/logs$");
                $appDirectory->process($ignore);
                $appDirectory->copyContentTo(append_to_url($bundleDirectory, 'app'));
                $hasOutput = true;
            }

            if ($statics) {
                $ignore = new FilesIgnore([]);
                $ignore->addRegExpr("sass$");
                $ignore->addRegExpr("statics/plugins$");
                $ignore->addRegExpr("statics/uploads$");
                $ignore->addRegExpr("statics/filemanager$");
                $staticsDirectory->process($ignore);
                $staticsDirectory->copyContentTo(append_to_url($bundleDirectory, 'statics'));
                $hasOutput = true;
            }

            if ($zip) {
                $bundleDirectoryObject = new DirectoryObject($bundleDirectory);
                $bundleDirectoryObject->process();
                $bundleDirectoryObject->copyTo(basepath('bundle'), true);
                $bundleDirectoryObject->delete(false);
            }

            if (!$hasOutput) {
                echoTerminal('No se procesó ninguna información');
            }

            try {

                $responseText = "\r\nOperación finalizada\r\n";

            } catch (\Exception $e) {
                $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
                log_exception($e);
            }

        } catch (MissingRequiredParamaterException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (ParsedValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (\Exception $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        }

        echoTerminal($responseText);
    }

    /**
     * @return void
     */
    public function cleanCache()
    {

        //Mensajes de respuesta
        $responseText = "";

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            try {

                $controllerConfig = new AppConfigController();
                $response = $controllerConfig->recreateStaticCacheStamp(RequestRouteFactory::createFromGlobals(), new ResponseRoute());
                $responseJSON = json_decode($response->getLastWriteBodyData(), true);
                $responseMessage = $responseJSON['message'];

                $responseText = "{$responseMessage}\r\n";

            } catch (\Exception $e) {
                $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
                log_exception($e);
            }

        } catch (MissingRequiredParamaterException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (ParsedValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        } catch (\Exception $e) {

            $responseText = "Ha ocurrido un error: {$e->getMessage()}\r\n";
            log_exception($e);

        }

        echoTerminal($responseText);
    }

    /**
     * @return string
     */
    public function help()
    {

        //Mensajes de respuesta
        $responseText = "";

        //Tareas disponibles
        $terminalTaskAvailables = get_config('terminalTaskAvailablesVerbose');

        if (is_array($terminalTaskAvailables)) {

            $message = [
                "*** Tareas disponibles ***",
            ];
            foreach ($terminalTaskAvailables as $task) {
                $name = array_key_exists('name', $task) ? $task['name'] : null;
                $description = array_key_exists('description', $task) ? $task['description'] : null;
                if (is_string($name) && is_string($description)) {
                    $message[] = "Tarea: {$name}";
                    $message[] = "  Descripción: {$description}";
                }
            }

            if (count($message) > 1) {
                echoTerminal(implode("\r\n", $message));
            }
        }

        return $responseText;
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @return bool
     */
    public static function allowedRoute(string $name)
    {
        $route = self::routeName($name, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route)
    {

        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'sample') { //do something
            }

        }

        return $allow;
    }

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, bool $silentOnNotExists)
    {

        $simpleName = !is_null($name) ? $name : '';
        $name = self::routeID($name);

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                [],
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route);

        return $allow ? $route : '';
    }

    /**
     * @param string $name
     * @return string
     */
    public static function routeID(string $name)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        return !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (TerminalData::getInstance()->isTerminal()) {

            $routes = [];
            $terminalTaskAvailables = [];

            $groupSegmentURL = $group->getGroupSegment();

            $lastIsBar = last_char($groupSegmentURL) == '/';
            $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

            $classname = self::class;

            //Permisos
            $onlyRoot = [
                UsersModel::TYPE_USER_ROOT,
            ];
            $routesData = [
                //──── GET ───────────────────────────────────────────────────────────────────────────────
                [
                    'description' => [
                        "Respalda la base de datos por defecto.\r\n",
                        "\tParámetros:\r\n",
                        "\t  gz (yes|no) define si se comprime o no. Por defecto: yes",
                    ],
                    'route' => "{$startRoute}/db-backup[/]",
                    'controller' => $classname . ':dbBackup',
                    'name' => self::$baseRouteName . '-db-backup',
                    'method' => 'GET',
                    'requireLogin' => true,
                    'alias' => null,
                    'rolesAllowed' => $onlyRoot,
                    'defaultParamsValues' => [],
                    'middlewares' => [],
                ],
                [
                    'description' => [
                        "Empaqueta la aplicación.\r\n",
                        "\tParámetros:\r\n",
                        "\t  app (yes|no) solo carpeta app. Por defecto: no\r\n",
                        "\t  statics (yes|no) solo carpeta static. Por defecto: no\r\n",
                        "\t  all (yes|no) app y statics. Por defecto: no\r\n",
                        "\t  zip (yes|no) define si solo copia los archivos o los comprime como zip. Por defecto: no",
                    ],
                    'route' => "{$startRoute}/bundle[/]",
                    'controller' => $classname . ':bundle',
                    'name' => self::$baseRouteName . '-bundle',
                    'method' => 'GET',
                    'requireLogin' => true,
                    'alias' => null,
                    'rolesAllowed' => $onlyRoot,
                    'defaultParamsValues' => [],
                    'middlewares' => [],
                ],
                [
                    'description' => [
                        "Fuerza la limpieza de caché de estáticos mediante la renovación del token.\r\n",
                        "\tParámetros:\r\n",
                        "\t  N/A\r\n",
                    ],
                    'route' => "{$startRoute}/clean-cache[/]",
                    'controller' => $classname . ':cleanCache',
                    'name' => self::$baseRouteName . '-clean-cache',
                    'method' => 'GET',
                    'requireLogin' => true,
                    'alias' => null,
                    'rolesAllowed' => $onlyRoot,
                    'defaultParamsValues' => [],
                    'middlewares' => [],
                ],
                //Rutas de ayuda
                [
                    'description' => null,
                    'route' => "{$startRoute}/" . uniqid() . "[/]",
                    'controller' => $classname . ':help',
                    'name' => self::$baseRouteName . '-help',
                    'method' => 'GET',
                    'requireLogin' => true,
                    'alias' => null,
                    'rolesAllowed' => $onlyRoot,
                    'defaultParamsValues' => [],
                    'middlewares' => [],
                ],
                [
                    'description' => null,
                    'route' => "{$startRoute}/" . uniqid() . "[/]",
                    'controller' => $classname . ':help',
                    'name' => self::$baseRouteName . '-h',
                    'method' => 'GET',
                    'requireLogin' => true,
                    'alias' => null,
                    'rolesAllowed' => $onlyRoot,
                    'defaultParamsValues' => [],
                    'middlewares' => [],
                ],
            ];

            foreach ($routesData as $routeData) {
                $descriptionRoute = $routeData['description'];
                $routePathRoute = $routeData['route'];
                $controllerRoute = $routeData['controller'];
                $nameRoute = $routeData['name'];
                $methodRoute = $routeData['method'];
                $requireLoginRoute = $routeData['requireLogin'];
                $aliasRoute = $routeData['alias'];
                $rolesAllowedRoute = $routeData['rolesAllowed'];
                $defaultParamsValuesRoute = $routeData['defaultParamsValues'];
                $middlewaresRoute = $routeData['middlewares'];
                $routes[] = new Route(
                    $routePathRoute,
                    $controllerRoute,
                    $nameRoute,
                    $methodRoute,
                    $requireLoginRoute,
                    $aliasRoute,
                    $rolesAllowedRoute,
                    $defaultParamsValuesRoute,
                    $middlewaresRoute
                );

                if (is_array($descriptionRoute)) {
                    $descriptionRoute = implode('', $descriptionRoute);
                }
                $terminalTaskAvailables[] = [
                    'name' => str_replace(self::$baseRouteName . '-', '', $nameRoute),
                    'description' => $descriptionRoute,
                ];
            }

            set_config('terminalTaskAvailablesVerbose', $terminalTaskAvailables);

            $group->register($routes);

        }

        return $group;
    }
}
