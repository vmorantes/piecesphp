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

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Perzonalizaciones
        $group->register([

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
            //Manejadores de imágenes
            new Route(
                "{$startRoute}images/add/images[/]",
                $classname . ':actionImages',
                'configurations-customization-images',
                'POST',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ),

        ]);
        $group->active(APP_CONFIGURATION_MODULE);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }
}
