<?php

/**
 * TerminalController.php
 */

namespace Terminal\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use Ifsnop\Mysqldump\Mysqldump;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\TerminalData;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

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
        parent::__construct(false); //No cargar ningún modelo automáticamente.
        $this->model = new BaseModel();
    }

    /**
     * Respaldar toda la base de datos
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function dbBackup(Request $request, Response $response)
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
        $responseText = "";

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información de los parámetros
            /**
             * @var string $table
             */;
            $gz = $expectedParameters->getValue('gz');

            $db = $this->model->getDatabase();
            $dbName = $db->getDatabaseName();
            $dbHost = $db->getHost();
            $dbUser = $db->getUsername();
            $dbPassword = $db->getPassword();

            $dumpSettingsDefault = array(
                'compress' => $gz ? Mysqldump::GZIP : Mysqldump::NONE,
                'add-drop-table' => true,
                'default-character-set' => Mysqldump::UTF8,
                'routines' => true,
                'single-transaction' => true,
                'skip-definer' => true,
                'disable-foreign-keys-check' => true,
            );

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

        return $responseText;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function bundle(Request $request, Response $response)
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
             * @var bool $all
             */;
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

        return $responseText;
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route, array $params = [])
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
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = $name;

        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (TerminalData::getInstance()->isTerminal()) {

            $routes = [];

            $groupSegmentURL = $group->getGroupSegment();

            $lastIsBar = last_char($groupSegmentURL) == '/';
            $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

            $classname = self::class;

            //Permisos
            $doBackup = [
                UsersModel::TYPE_USER_ROOT,
            ];
            $routes = [

                //──── GET ───────────────────────────────────────────────────────────────────────────────
                new Route(
                    "{$startRoute}/db-backup[/]",
                    $classname . ':dbBackup',
                    self::$baseRouteName . '-db-backup',
                    'GET',
                    true,
                    null,
                    $doBackup
                ),
                new Route(
                    "{$startRoute}/bundle[/]",
                    $classname . ':bundle',
                    self::$baseRouteName . '-bundle',
                    'GET',
                    true,
                    null,
                    $doBackup
                ),
            ];

            $group->register($routes);

        }

        return $group;
    }
}
