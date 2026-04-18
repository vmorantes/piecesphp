<?php

/**
 * BundleTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * BundleTask.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class BundleTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        //Procesar entrada
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'bundle';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Empaqueta la aplicación.\r\n",
            "\tParámetros:\r\n",
            "\t  app (yes|no) solo carpeta app. Por defecto: no\r\n",
            "\t  statics (yes|no) solo carpeta statics (sin filemanager, uploads ni plugins). Por defecto: no\r\n",
            "\t  all (yes|no) app y statics. Por defecto: no\r\n",
            "\t  zip (yes|no) define si solo copia los archivos o los comprime como zip. Por defecto: no",
        ]);
        $this->route = "{$startRoute}/bundle[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
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
                mkdir($bundleDirectory, 0755, true);
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

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new BundleTask($startRoute, $namePrefix);
        $route = new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
        return $route;
    }

}
