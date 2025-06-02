<?php

use PiecesPHP\Core\Routing\InvocationStrategy;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Http\StatusCode;
use PiecesPHP\CSSVariables;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;

$container_configurations = [
    'foundHandler' => function (RequestRoute $request, RequestHandlerInterface $handler) {

        //Variables CSS globales
        $cssGlobalVariables = CSSVariables::instance('global');
        $cssGlobalVariables->setVariable('meta-theme-color', get_config('meta_theme_color'));
        $cssGlobalVariables->setVariable('main-brand-color', get_config('main_brand_color'));
        $cssGlobalVariables->setVariable('main-brand-color-opacity', get_config('main_brand_color') . '13');
        $cssGlobalVariables->setVariable('bg-tools-buttons', get_config('bg_tools_buttons'));
        $cssGlobalVariables->setVariable('second-brand-color', get_config('second_brand_color'));
        $cssGlobalVariables->setVariable('font-color-one', get_config('font_color_one'));
        $cssGlobalVariables->setVariable('font-color-two', get_config('font_color_two'));
        $cssGlobalVariables->setVariable('menu-color-background', get_config('menu_color_background'));
        $cssGlobalVariables->setVariable('menu-color-background-opacity', get_config('menu_color_background') . 'BD');
        $cssGlobalVariables->setVariable('menu-color-mark', get_config('menu_color_mark'));
        $cssGlobalVariables->setVariable('menu-color-font', get_config('menu_color_font'));
        $cssGlobalVariables->setVariable('body-gradient', get_config('body_gradient'));
        $cssGlobalVariables->setVariable('main-brand-color-0-5', get_config('main_brand_color') . '80');
        $cssGlobalVariables->setVariable('main-brand-color-0-8', get_config('main_brand_color') . 'CC');

        $fontFamilyGlobal = get_config('font_family_global');
        if (is_string($fontFamilyGlobal) && mb_strlen($fontFamilyGlobal) > 0) {
            $cssGlobalVariables->setVariable('font-family-global', $fontFamilyGlobal);
        }
        $fontFamilySidebars = get_config('font_family_sidebars');
        if (is_string($fontFamilySidebars) && mb_strlen($fontFamilySidebars) > 0) {
            $cssGlobalVariables->setVariable('font-family-sidebars', $fontFamilySidebars);
        }

        //CSS de variables globales del área administrativa
        add_global_required_asset(get_route('admin-global-variables-css'), 'css');

        //Antes de ejecutar el método de la ruta
        InvocationStrategy::appendBeforeCallMethod(function () {
            set_config('lock_assets', true);
        });

        //Después de ejecutar el método de la ruta
        InvocationStrategy::appendAfterCallMethod(function () {
            set_config('lock_assets', false);
        });

        $response = null;

        try {
            $response = $handler->handle($request);
        } catch (\Error $e) {
            if ($response instanceof ResponseRoute) {
                throw $e;
            }
        }

        if (!($response instanceof ResponseRoute)) {
            $response = new ResponseRoute();
        }

        return $response;
    },
    'notFoundHandler' => function (HttpNotFoundException $notFoundError) {

        /**
         * @var RequestRoute $request
         */
        $request = $notFoundError->getRequest();
        $response = new ResponseRoute(StatusCode::HTTP_NOT_FOUND);
        $extraDataKey = 'information404';
        $extraData = $request->getAttribute($extraDataKey, []);
        $extraData = is_array($extraData) ? $extraData : [];

        $url = array_key_exists('url', $extraData) ? $extraData['url'] : null;
        $url = is_string($url) && mb_strlen($url) > 0 ? $url : null;

        if (API_MODULE) {
            if ($request->getMethod() == 'OPTIONS') {
                return $response->withStatus(200);
            }
        }

        $requestTypeIsJSON = mb_strtolower($request->getHeaderLine('Accept')) == 'application/json';

        if ($request->isXhr() || $requestTypeIsJSON) {
            $response = $response->withJson("404 Not Found");
        } else {
            $dataController = $extraData;
            $dataController['url'] = $url;
            $controller = new PiecesPHP\Core\BaseController(false);
            $controller->render('pages/404', $dataController);
        }

        return $response;
    },
    'forbiddenHandler' => function (HttpForbiddenException $forbiddenError) {

        /**
         * @var RequestRoute $request
         */
        $request = $forbiddenError->getRequest();
        $route = $request->getRoute();
        $extraDataKey = 'information403';
        $response = new ResponseRoute(StatusCode::HTTP_FORBIDDEN);
        $extraData = $request->getAttribute($extraDataKey, []);
        $extraData = is_array($extraData) ? $extraData : [];

        if ($route !== null) {
            $routeName = $route->getName();
            $routeInformation = get_route_info($routeName);
            $requireLogin = $routeInformation['require_login'];
            //Definir el botón de volver en la ruta administrativa si no hay una url definida y la ruta requiere login
            $adminRoute = get_route('admin');
            if ($requireLogin && !array_key_exists('url', $extraData)) {
                $extraData['url'] = $adminRoute;
            }
        }

        $url = array_key_exists('url', $extraData) ? $extraData['url'] : null;
        $url = is_string($url) && mb_strlen($url) > 0 ? $url : null;
        $line = array_key_exists('line', $extraData) ? $extraData['line'] : null;
        $file = array_key_exists('file', $extraData) ? $extraData['file'] : null;

        $requestTypeIsJSON = mb_strtolower($request->getHeaderLine('Accept')) == 'application/json';

        if ($request->isXhr() || $requestTypeIsJSON) {
            $response = $response->withJson("403 Forbidden");
        } else {

            $dataController = $extraData;
            $dataController['url'] = $url;

            $controller = new PiecesPHP\Core\BaseController(false);
            $controller->render('pages/403', $dataController);

        }

        return $response;

    },
];
