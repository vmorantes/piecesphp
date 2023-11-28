<?php

use PiecesPHP\Core\Routing\InvocationStrategyPiecesPHP;
use PiecesPHP\Core\Routing\RequestRoutePiecesPHP;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP;
use PiecesPHP\Core\Routing\Slim3Compatibility\Http\StatusCode;
use PiecesPHP\CSSVariables;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;

$container_configurations = [
    'foundHandler' => function (RequestRoutePiecesPHP $request, RequestHandlerInterface $handler) {

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
        InvocationStrategyPiecesPHP::appendBeforeCallMethod(function () {
            set_config('lock_assets', true);
        });

        //Después de ejecutar el método de la ruta
        InvocationStrategyPiecesPHP::appendAfterCallMethod(function () {
            set_config('lock_assets', false);
        });

        $response = null;

        try {
            $response = $handler->handle($request);
        } catch (\Error $e) {
            if ($response instanceof ResponseRoutePiecesPHP) {
                throw $e;
            }
        }

        if (!($response instanceof ResponseRoutePiecesPHP)) {
            $response = new ResponseRoutePiecesPHP();
        }

        return $response;
    },
    'notFoundHandler' => function (HttpNotFoundException $notFoundError) {

        /**
         * @var RequestRoutePiecesPHP $request
         */
        $request = $notFoundError->getRequest();
        $response = new ResponseRoutePiecesPHP(StatusCode::HTTP_NOT_FOUND);

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
    },
];
