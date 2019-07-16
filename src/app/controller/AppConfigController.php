<?php

/**
 * AppConfigController.php
 */

namespace App\Controller;

use App\Model\AppConfigModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * AppConfigController.
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class AppConfigController extends AdminPanelController
{
    const PARSE_TYPE_STRING = 'string';
    const PARSE_TYPE_BOOL = 'bool';
    const PARSE_TYPE_INT = 'int';
    const PARSE_TYPE_FLOAT = 'float';
    const PARSE_TYPE_DOUBLE = 'double';
    const PARSE_TYPE_JSON_ENCODE = 'json_encode';
    const PARSE_TYPE_JSON_DECODE = 'json_decode';

    /**
     * $mapper
     *
     * @var AppConfigModel
     */
    protected $mapper;

    public function __construct()
    {
        parent::__construct();
        $this->mapper = new AppConfigModel();
        $this->model = $this->mapper->getModel();
    }

    /**
     * routesView
     *
     * Vista de configuraciones de las rutas y los permisos
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function routesView(Request $req, Response $res, array $args)
    {
        $this->render('panel/layout/header');
        $this->render('panel/pages/app_configurations/routes', [
            'routes' => get_routes(),
        ]);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * configurationsView
     *
     * Vista de configuraciones
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function configurationsView(Request $req, Response $res, array $args)
    {
        $this->render('panel/layout/header');
        $this->render('panel/pages/app_configurations/configurations');
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * customizationView
     *
     * Vista de personalización
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function customizationView(Request $req, Response $res, array $args)
    {
        $this->render('panel/layout/header');
        $this->render('panel/pages/app_configurations/customization');
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * actionGeneric
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function actionGeneric(Request $req, Response $res, array $args)
    {
        $operation_name = 'Configuración';

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = 'Guardado.';
        $message_unknow_error = 'Ha ocurrido un error inesperado.';

        $parametersExcepted = new Parameters([
            new Parameter(
                'name',
                null,
                function ($value) {
                    return is_string($value);
                }
            ),
            new Parameter(
                'value',
                null,
                function ($value) {
                    return is_string($value) || is_array($value) || is_object($value);
                }
            ),
            new Parameter(
                'parse',
                null,
                function ($value) {

                    if (is_array($value)) {

                        $valid = true;

                        array_map(function ($v) use (&$valid) {
                            if (!is_string($v)) {
                                $valid = false;
                            }
                        }, $value);

                        return $valid;

                    } else {
                        return is_string($value);
                    }

                },
                true
            ),
        ]);

        $parametersExcepted->setInputValues($req->getParsedBody());

        try {

            $parametersExcepted->validate();

            $name = $parametersExcepted->getValue('name');
            $value = $parametersExcepted->getValue('value');
            $parse = $parametersExcepted->getValue('parse');

            $option = new AppConfigModel($name);

            $value = self::processGenericInputValues($value, $parse);

            $option->value = $value;

            if (!is_null($option->id)) {

                $success = $option->update();

            } else {

                $option->name = $name;
                $success = $option->save();

            }

            if ($success) {

                $result
                    ->setMessage($message_create)
                    ->operation($operation_name)
                    ->setSuccess(true);

            } else {

                $result
                    ->setMessage($message_unknow_error)
                    ->operation($operation_name);

            }

        } catch (\PDOException $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);

        } catch (\Exception $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);

        }

        return $res->withJson($result);
    }

    /**
     * actionOsTicket
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function actionOsTicket(Request $req, Response $res, array $args)
    {
        $operation_name = 'Configuración OsTicket';

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = 'Guardado.';
        $message_unknow_error = 'Ha ocurrido un error inesperado.';

        $parametersExcepted = new Parameters([
            new Parameter(
                'url',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return rtrim($value, '/');
                }
            ),
            new Parameter(
                'key',
                null,
                function ($value) {
                    return is_string($value);
                }
            ),
        ]);

        $parametersExcepted->setInputValues($req->getParsedBody());

        try {

            $parametersExcepted->validate();

            $url = new AppConfigModel('osTicketAPI');
            $key = new AppConfigModel('osTicketAPIKey');

            $url->value = $parametersExcepted->getValue('url');
            $key->value = $parametersExcepted->getValue('key');

            $successUrl = $url->update();
            $successKey = $key->update();
            $success = $successUrl || $successKey;

            if ($success) {

                $result
                    ->setMessage($message_create)
                    ->operation($operation_name)
                    ->setSuccess(true);

            } else {

                $result
                    ->setMessage($message_unknow_error)
                    ->operation($operation_name);

            }

        } catch (\PDOException $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);

        } catch (\Exception $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);

        }

        return $res->withJson($result);
    }

    /**
     * actionImages
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function actionImages(Request $req, Response $res, array $args)
    {

        $valid_images_names = [
            'favicon' => [
                'name' => 'favicon',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
            ],
            'logo' => [
                'name' => 'logo',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
            ],
            'logo-login' => [
                'name' => 'logo-login',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
            ],
            'logo-sidebar-top' => [
                'name' => 'logo-sidebar-top',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
            ],
            'logo-sidebar-bottom' => [
                'name' => 'logo-sidebar-bottom',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
            ],
            'logo-mailing' => [
                'name' => 'logo-mailing',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
            ],
            'background-1' => [
                'name' => 'bg1',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
            ],
            'background-2' => [
                'name' => 'bg2',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
            ],
            'background-3' => [
                'name' => 'bg3',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
            ],
            'background-4' => [
                'name' => 'bg4',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
            ],
            'background-5' => [
                'name' => 'bg5',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
            ],
        ];

        $name_image_form = '';
        $name_image = '';
        $extension = '';
        $folder = '';
        $params_ok = false;

        foreach ($valid_images_names as $valid_image_name => $config) {
            $params_ok = isset($_FILES[$valid_image_name]);
            if ($params_ok) {
                $name_image_form = $valid_image_name;
                $name_image = $config['name'];
                $extension = $config['extension'];
                $folder = $config['folder'];
                break;
            }
        }

        $operation_name = 'Guardar imagen';
        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = 'Imagen guardada.';
        $message_unknow_error = 'Ha ocurrido un error inesperado.';
        $message_unexpected_or_missing_params = 'Información faltante o inesperada.';

        if ($params_ok) {

            $fileHandler = new FileUpload($name_image_form, [
                $extension == 'png' ? FileValidator::TYPE_PNG : FileValidator::TYPE_JPG,
            ], 5);

            if ($fileHandler->validate()) {

                $route = $fileHandler->moveTo($folder, $name_image, $extension);

                if (count($route) > 0) {
                    $result->setValue('reload', true);
                    $result
                        ->setMessage($message_create)
                        ->operation($operation_name)
                        ->setSuccess(true);
                } else {
                    $result
                        ->setMessage($message_unknow_error)
                        ->operation($operation_name);
                }

            } else {

                $result
                    ->setMessage(implode('<br>', $fileHandler->getErrorMessages()))
                    ->operation($operation_name);

            }

        } else {
            $result
                ->setMessage($message_unexpected_or_missing_params)
                ->operation($operation_name);
        }

        return $res->withJson($result);
    }

    /**
     * processGenericInputValues
     *
     * @param mixed $input
     * @param string|array $parse
     * @return mixed
     */
    private static function processGenericInputValues($input, $parse = null)
    {

        if (is_array($input)) {

            $parseIsArray = is_array($parse);

            foreach ($input as $index => $value) {

                if ($parseIsArray) {

                    if (!is_array($value)) {

                        if (array_key_exists($index, $parse)) {

                            $parseType = $parse[$index];

                            if (is_string($parseType)) {
                                $input[$index] = self::parseTo($value, $parseType);
                            }

                        }

                    } else {

                        if (array_key_exists($index, $parse)) {
                            $input[$index] = self::processGenericInputValues($value, $parse[$index]);
                        }
                    }
                }

            }

        } else {
            $parseType = is_string($parse) ? $parse : '';
            $input = self::parseTo($input, $parse);
        }

        return $input;

    }

    /**
     * parseTo
     *
     * @param mixed $value
     * @param string $to
     * @return mixed
     */
    public static function parseTo($value, string $to = null)
    {
        $to = is_null($to) ? self::PARSE_TYPE_STRING : $to;

        switch ($to) {

            case self::PARSE_TYPE_STRING:

                if (is_scalar($value)) {
                    return (string) $value;
                }
                return $value;

                break;

            case self::PARSE_TYPE_BOOL:

                if (is_scalar($value)) {
                    $toFalse = [0, '0', 'off', 'no', 'false', null, 'null'];
                    $toTrue = [1, '1', 'on', 'yes', 'si', 'sí', 'true'];
                    if (is_string($value)) {
                        $value = strtolower($value);
                    }
                    foreach ($toTrue as $i) {
                        if ($i === $value) {
                            return true;
                        }
                    }
                    foreach ($toFalse as $i) {
                        if ($i === $value) {
                            return false;
                        }
                    }
                }
                return $value;

                break;

            case self::PARSE_TYPE_INT:

                if (is_scalar($value) && is_numeric($value)) {
                    return (int) $value;
                }
                return $value;

                break;

            case self::PARSE_TYPE_FLOAT:
            case self::PARSE_TYPE_DOUBLE:

                if (is_scalar($value) && is_numeric($value)) {
                    return (double) $value;
                }
                return $value;

                break;

            case self::PARSE_TYPE_JSON_ENCODE:
                $parsedValue = json_encode($value);
                if (json_last_error() == \JSON_ERROR_NONE) {
                    return $parsedValue;
                } else {
                    return $value;
                }
                break;

            case self::PARSE_TYPE_JSON_DECODE:
                $parsedValue = json_decode($value);
                if (json_last_error() == \JSON_ERROR_NONE) {
                    return $parsedValue;
                } else {
                    return $value;
                }
                break;

            default:

                return $value;

                break;
        }
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;

        $group->active(APP_CONFIGURATION_MODULE);

        //Perzonalizaciones
        $group->register([

            //──── GET ─────────────────────────────────────────────────────────────────────────
            //Vista de personalización de imágenes
            new Route(
                "{$startRoute}images[/]",
                $classname . ':customizationView',
                'configurations-customization',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ),
            //Vista de configuración general
            new Route(
                "{$startRoute}generals[/]",
                $classname . ':configurationsView',
                'configurations-generals',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ),
            //Vista de configuración de rutas y permisos
            new Route(
                "{$startRoute}routes[/]",
                $classname . ':routesView',
                'configurations-routes',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                ]
            ),

            //──── POST ────────────────────────────────────────────────────────────────────────
            //Manejadores de imágenes
            new Route(
                "{$startRoute}images/add/images[/]",
                $classname . ':actionImages',
                'configurations-customization-images-action',
                'POST',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ),
            //OsTicket
            new Route(
                "{$startRoute}images/config/osticket[/]",
                $classname . ':actionOsTicket',
                'configurations-generals-osticket-action',
                'POST',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ),
            //General
            new Route(
                "{$startRoute}images/config/generic[/]",
                $classname . ':actionGeneric',
                'configurations-generals-generic-action',
                'POST',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ),

        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }
}
