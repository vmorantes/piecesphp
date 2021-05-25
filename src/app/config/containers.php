<?php

use PiecesPHP\Core\Routing\RequestResponsePiecesPHP;
use PiecesPHP\CSSVariables;

$container_configurations = [
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
    ],
    'foundHandler' => function ($c) {

        //Variables CSS globales
        $cssGlobalVariables = CSSVariables::instance('global');
        $cssGlobalVariables->setVariable('bg-color', get_config('admin_menu_color'));
        $cssGlobalVariables->setVariable('emphasis-color', get_config('emphasis_color_admin_area'));
        $cssGlobalVariables->setVariable('over-emphasis-color', get_config('emphasis_over_color_admin_area'));

        //CSS de variables globales del área administrativa
        add_global_required_asset(get_route('admin-global-variables-css'), 'css');

        //Antes de ejecutar el método de la ruta
        RequestResponsePiecesPHP::appendBeforeCallMethod(function ($name) {
            set_config('lock_assets', true);
        });

        //Después de ejecutar el método de la ruta
        RequestResponsePiecesPHP::appendAfterCallMethod(function () {
            set_config('lock_assets', false);
        });

        return new \PiecesPHP\Core\Routing\RequestResponsePiecesPHP;
    },
    'errorHandler' => function ($c) {
        return new \PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler($c);
    },
    'phpErrorHandler' => function ($c) {
        return new \PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler($c);
    },
    'notFoundHandler' => function ($c) {
        return function ($request, $response) use ($c) {

            $response = $response->withStatus(404);

            if (!$request->isXhr()) {
                $controller = new PiecesPHP\Core\BaseController(false);
                $controller->render('pages/404');
            } else {
                $response = $response->withJson("404 Not Found");
            }

            return $response;

        };
    },
];
