<?php

use PiecesPHP\Core\Routing\DependenciesInjector;
use PiecesPHP\Core\Routing\InvocationStrategy;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Http\StatusCode;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;

$container_configurations = [
    'foundHandler' => function (RequestRoute $request, RequestHandlerInterface $handler) {

        /**
         * @var DependenciesInjector $container
         */
        $container = get_router()->getDI();

        //Variables CSS globales
        $metaThemeColor = get_config('meta_theme_color');
        $mainBrandColor = get_config('main_brand_color');
        $secondBrandColor = get_config('second_brand_color');
        $bgToolsButtons = get_config('bg_tools_buttons');
        $fontColorOne = get_config('font_color_one');
        $fontColorTwo = get_config('font_color_two');
        $menuColorBackground = get_config('menu_color_background');
        $menuColorMark = get_config('menu_color_mark');
        $menuColorFont = get_config('menu_color_font');
        $bodyGradient = get_config('body_gradient');
        $loaderBgColor = "rgba(255, 255, 255, 1)";
        $loaderBorderColor = "rgb(150, 150, 150)";
        $loaderBorderMarkColor = $mainBrandColor;
        $loaderLogoBorderRadius = "100%";

        $cssGlobalVariables = CSSVariables::instance('global');
        $cssGlobalVariables->setVariable('meta-theme-color', $metaThemeColor);
        $cssGlobalVariables->setVariable('main-brand-color', $mainBrandColor);
        $cssGlobalVariables->setVariable('main-brand-color-opacity', $mainBrandColor . opacityToHex(0.075));
        $cssGlobalVariables->setVariable('bg-tools-buttons', $bgToolsButtons);
        $cssGlobalVariables->setVariable('second-brand-color', $secondBrandColor);
        $cssGlobalVariables->setVariable('font-color-one', $fontColorOne);
        $cssGlobalVariables->setVariable('font-color-two', $fontColorTwo);
        $cssGlobalVariables->setVariable('menu-color-background', $menuColorBackground);
        $cssGlobalVariables->setVariable('menu-color-background-opacity', $menuColorBackground . opacityToHex(0.741));
        $cssGlobalVariables->setVariable('menu-color-mark', $menuColorMark);
        $cssGlobalVariables->setVariable('menu-color-font', $menuColorFont);
        $cssGlobalVariables->setVariable('body-gradient', $bodyGradient);
        $cssGlobalVariables->setVariable('main-brand-color-0-5', $mainBrandColor . opacityToHex(0.502));
        $cssGlobalVariables->setVariable('main-brand-color-0-8', $mainBrandColor . opacityToHex(0.8));

        //--Loader
        $cssGlobalVariables->setVariable('loader-bg-color', $loaderBgColor);
        $cssGlobalVariables->setVariable('loader-border-color', $loaderBorderColor);
        $cssGlobalVariables->setVariable('loader-border-mark-color', $loaderBorderMarkColor);
        $cssGlobalVariables->setVariable('loader-logo-border-radius', $loaderLogoBorderRadius);

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

        /* Ejecución antes de procesar la ruta: */
        //Do something

        $response = null;
        try {
            //Lanza excepción si el manejador no está devolviendo su respectivo objeto "response"
            $response = $handler->handle($request);
        } catch (\Error $e) {
            if ($response instanceof ResponseRoute) {
                throw $e;
            }
        }

        if (!($response instanceof ResponseRoute)) {
            $response = new ResponseRoute();
        }

        /* Ejecución después de procesar la ruta: */

        //Cabeceras CORS para peticiones desde otros orígenes
        if (API_MODULE) {
            if ($container instanceof DependenciesInjector) {
                $response = $container->get('cors')($request, $response);
                $response = $response;
            } else {
                $response = $response
                    ->withHeader('Access-Control-Allow-Origin', '*')
                    ->withHeader('Access-Control-Allow-Methods', '*')
                    ->withHeader('Access-Control-Allow-Headers', '*');
            }
        }

        return $response;
    },
    'notFoundHandler' => function (HttpNotFoundException $notFoundError, array $extraData = []) {

        /**
         * @var DependenciesInjector $container
         */
        $container = get_router()->getDI();

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
                if ($container instanceof DependenciesInjector) {
                    $response = $container->get('cors')($request, $response);
                    return $response;
                } else {
                    return $response
                        ->withHeader('Access-Control-Allow-Origin', '*')
                        ->withHeader('Access-Control-Allow-Methods', '*')
                        ->withHeader('Access-Control-Allow-Headers', '*')
                        ->withStatus(204);
                }
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
    'staticRouteModulesResolver' => function (string $classQualifiedName, string $segment = '', string $sourcePath = '', bool $enable = false) {
        if ($enable) {
            $route = get_route($classQualifiedName);
            if (is_string($route)) {
                //Obtener la ruta del recurso
                $resourcePath = str_replace('/[{params:.*}]', '', $route);
                //Verificar si tiene enlace simbólico
                $delegatedUrl = ServerStatics::getSymbolicLink([
                    'params' => $segment,
                ], $sourcePath);
                //Ajustar ruta final si no hay enlace simbólico
                $resourcePath = append_to_url($resourcePath, $segment);
                //Devolver la ruta final
                return $delegatedUrl ?? $resourcePath;
            } else {
                return $segment;
            }
        } else {
            return '';
        }
    },
    'cors' => function (RequestRoute $request, ResponseRoute $response) {
        $origin = $request->getHeaderLine('Origin') ?: '*';
        $requestedHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        $allowedHeaders = $requestedHeaders ?: 'Content-Type, Authorization, isWebApp, isExternalLogin, JWTAuth';
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS, TRACE, CONNECT')
            ->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
            ->withHeader('Vary', 'Origin');
        if ($request->getMethod() == 'OPTIONS') {
            $response = $response->withStatus(204);
        }
        return $response;
    },
];
