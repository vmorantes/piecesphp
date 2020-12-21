<?php

use PiecesPHP\Core\Routing\RequestResponsePiecesPHP;

$container_configurations = [
    'settings' => [
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => true,
    ],
    'foundHandler' => function ($c) {

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
