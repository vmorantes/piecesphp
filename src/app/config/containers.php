<?php

/**
 * @pcsphp-config framework
 * Qué conviene editar aquí: nada: son los intermediarios que corren en cada petición.
 */

use PiecesPHP\Core\Routing\DependenciesInjector;
use PiecesPHP\Core\Routing\InvocationStrategy;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Http\StatusCode;
use PiecesPHP\Core\SessionDataHandler;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\CSSVariables;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\TerminalData;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;

$container_configurations = [
    'foundHandler' => function (RequestRoute $request, RequestHandlerInterface $handler) {

        /**
         * @var DependenciesInjector $container
         */
        $container = get_router()->getDI();

        //Variables CSS globales: los tokens del contrato del tema que escribe la instalación (H106). Las hojas propias leen
        //esos tokens y los de lectura (--theme-*); la zona pública y las páginas de error no leen nada de aquí.
        $cssGlobalVariables = CSSVariables::instance('global');
        foreach (SettingsController::themeTokens() as $themeToken => $themeTokenValue) {
            $cssGlobalVariables->setVariable($themeToken, $themeTokenValue);
        }

        //CSS de variables globales del área administrativa
        //Con la huella de su contenido como versión: cambia al cambiar un color, y con ella se guarda un año.
        add_global_required_asset(CSSVariables::globalVersionedURL(), 'css');
        $themeFontStylesheet = SettingsController::themeFontStylesheet();
        if ($themeFontStylesheet !== null) {
            add_global_required_asset(base_url($themeFontStylesheet), 'css');
        }

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

        //P61: NAVEGAR NO ESCRIBE. Antes, a cualquier usuario con organización nula se le asignaba aquí la global
        //(-10) y se guardaba, en cada petición; eso contradice P39 y tapaba el defecto en vez de enseñarlo.
        $loggedUser = getLoggedFrameworkUser();
        if (
            $loggedUser !== null
                && $loggedUser->organization === null
                && !in_array($loggedUser->type, UsersModel::TYPES_USER_DONT_REQUIRE_ORGANIZATION)
        ) {
            //Un usuario dentro sin la organización que su tipo exige es un DEFECTO DE DATOS: se anota una vez por
            //sesión —si la hay— y se deja como está. Nunca se arregla solo.
            $primeraVez = true;
            if (session_status() === PHP_SESSION_ACTIVE) {
                $avisos = new SessionDataHandler('pcsphp_user_without_organization');
                $primeraVez = $avisos->getData('logged') === null;
                $avisos->addData('logged', true);
            }
            if ($primeraVez) {
                log_exception(new \RuntimeException(
                    'El usuario ' . $loggedUser->id . ', de tipo ' . $loggedUser->type . ', está dentro sin organización'
                        . ' y su tipo la requiere. No se le asigna ninguna: es un defecto de datos (P61).'
                ));
            }
        }

        $response = null;
        try {
            //Lanza excepción si el manejador no está devolviendo su respectivo objeto "response"
            $response = $handler->handle($request);
        } catch (\Error $e) {
            $missingResponseError = preg_match("/^.*must be of type Psr\\\\Http\\\\Message\\\\ResponseInterface.*$/", $e->getMessage());
            if ($response instanceof ResponseRoute) {
                throw $e;
            } elseif (!$missingResponseError) {
                throw $e;
            }
        }

        if (!($response instanceof ResponseRoute)) {
            $response = new ResponseRoute();
        }

        /* Ejecución después de procesar la ruta: */

        //Cabeceras CORS para peticiones desde otros orígenes (Solo en "modo API")
        if (API_MODULE) {
            if ($container instanceof DependenciesInjector) {
                $response = $container->get('cors')($request, $response);
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

        $url = $extraData['url'] ?? null;
        $url = is_string($url) && mb_strlen($url) > 0 ? $url : null;

        //Cabeceras CORS para peticiones desde otros orígenes (Solo en "modo API")
        if (API_MODULE) {
            if ($container instanceof DependenciesInjector) {
                $response = $container->get('cors')($request, $response);
            }
        }

        $requestTypeIsJSON = mb_strtolower($request->getHeaderLine('Accept')) == 'application/json';

        if ($request->isXhr() || $requestTypeIsJSON || TerminalData::getInstance()->isTerminal()) {
            $response = $response->withJson("404 Not Found");
        } else {
            $dataController = $extraData;
            $dataController['url'] = $url;
            $controller = new PiecesPHP\Core\BaseController(false);
            $controller->render('pages/404', $dataController);
        }

        return $response;
    },
    'forbiddenHandler' => function (HttpForbiddenException $forbiddenError, bool $throw = false): ResponseRoute {

        //Controlar casos en donde el flujo no se detuvo por no hacer return de throw403. Se lanza la excepción
        $errorDescription = $forbiddenError->getDescription();
        if ($throw && mb_strpos($errorDescription, '(REPEATED_THROW)') === false) {
            $forbiddenError->setDescription($errorDescription . ' (REPEATED_THROW)');
            throw $forbiddenError;
        }

        /**
         * @var DependenciesInjector $container
         */
        $container = get_router()->getDI();

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
            $adminRoute = \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('');
            if ($requireLogin && !array_key_exists('url', $extraData)) {
                $extraData['url'] = $adminRoute;
            }
        }

        $url = $extraData['url'] ?? null;
        $url = is_string($url) && mb_strlen($url) > 0 ? $url : null;
        $line = $extraData['line'] ?? null;
        $file = $extraData['file'] ?? null;

        $requestTypeIsJSON = mb_strtolower($request->getHeaderLine('Accept')) == 'application/json';

        //Cabeceras CORS para peticiones desde otros orígenes (Solo en "modo API")
        if (API_MODULE) {
            if ($container instanceof DependenciesInjector) {
                $response = $container->get('cors')($request, $response);
            }
        }

        if ($request->isXhr() || $requestTypeIsJSON || TerminalData::getInstance()->isTerminal()) {
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
        $origin = $request->getHeaderLine('Origin');
        $requestedHeaders = $request->getHeaderLine('Access-Control-Request-Headers');
        $allowedHeaders = $requestedHeaders ?: 'Content-Type, Authorization, isWebApp, isExternalLogin, ' . \PiecesPHP\Core\SessionToken::tokenName();
        //CREDENCIALES SOLO PARA EL PROPIO ORIGEN Y LOS DECLARADOS: a cualquier otro, el navegador no le manda las cookies
        //ni le deja leer la respuesta de una petición con ellas. El token por la cabecera sigue valiendo para todos.
        $declared = get_config('cors_credentials_origins');
        $withCredentials = cors_origin_allows_credentials($origin, is_array($declared) ? $declared : [], url_origin((string) get_config('base_url')));
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin !== '' ? $origin : '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS, TRACE, CONNECT')
            ->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
            //AÑADE, no sustituye: corre tras la ruta, y ServerStatics ya puede traer su Vary (Cookie, Accept…).
            ->withAddedHeader('Vary', 'Origin');
        if ($withCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        if ($request->getMethod() == 'OPTIONS') {
            $response = $response->withStatus(204);
        }
        return $response;
    },
];
