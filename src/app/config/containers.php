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
        $cssGlobalVariables->setVariable('meta-theme-color', get_config('meta_theme_color'));
        $cssGlobalVariables->setVariable('main-brand-color', get_config('main_brand_color'));
        $cssGlobalVariables->setVariable('main-brand-color-opacity', get_config('main_brand_color') . '13');
        $cssGlobalVariables->setVariable('second-brand-color', get_config('second_brand_color'));
        $cssGlobalVariables->setVariable('third-brand-color-text', get_config('third_brand_color_text'));
        $cssGlobalVariables->setVariable('color-text-over-main-brand-color', get_config('color_text_over_main_brand_color'));

        $cssGlobalVariables->setVariable('main-brand-color-0-5', get_config('main_brand_color') . '80');
        $cssGlobalVariables->setVariable('main-brand-color-0-8', get_config('main_brand_color') . 'CC');

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

            //if ($request->getMethod() == 'OPTIONS') {
            //    return $response->withStatus(200);
            //}

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
