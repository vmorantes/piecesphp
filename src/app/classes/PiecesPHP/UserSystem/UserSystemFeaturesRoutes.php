<?php

/**
 * UserSystemFeaturesRoutes.php
 */

namespace PiecesPHP\UserSystem;

use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\CSSVariables;
use PiecesPHP\UserSystem\Controllers\ImpersonationController;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\Controllers\UserTokensController;

/**
 * UserSystemFeaturesRoutes.
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class UserSystemFeaturesRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic Recibe también los estáticos del módulo, que se piden sin sesión.
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            UserSystemFeaturesController::routes($groupAdministration, $groupPublic);
            //Las sesiones abiertas (ADR 0026, fases B y C): cerrar sesión, cerrar una, extender y su listado.
            UserSessionsController::routes($groupAdministration);
            //Actuar como otro usuario (P204 a): empezar y terminar, solo por POST desde el panel.
            ImpersonationController::routes($groupAdministration);
            //Los tokens y códigos vigentes y su historia (Registros); ver su valor, invalidarlos y restablecer el doble factor.
            UserTokensController::routes($groupAdministration);

            self::staticResolver($groupPublic);

            UserSystemFeaturesLang::injectLang();

            //La pestaña «Accesos» de Registros: sus informes y su estática viven en este módulo.
            LoginAttemptsController::registerLogTab();
            UserSessionsController::registerLogTab();
            UserTokensController::registerLogTab();
            //Sus tarjetas del inicio del panel (DashboardCardRegistry).
            UserSessionsController::registerDashboardCard();
            LoginAttemptsController::registerDashboardCard();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

            //routes() corre en cada petición: aquí no va ninguna escritura ni consulta de datos.

        }

        return [
            'groupAdministration' => $groupAdministration,
            'groupPublic' => $groupPublic,
        ];
    }

    /**
     * @return void|null
     */
    public static function init()
    {

        if (!self::$init) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser === null) {
                return null;
            }

            $currentUserType = (int) $currentUser->type;

        }

        self::$init = true;

    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, __DIR__ . '/Statics', self::ENABLE);
    }

    /**
     * @param RouteGroup $group
     * @return void
     */
    protected static function staticResolver(RouteGroup $group)
    {

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, __DIR__ . '/Statics');
        };

        /**
         * @param Request $request
         * @param Response $response
         * @return Response
         */
        $cssGlobalVariables = function (Request $request, Response $response) {
            $css = CSSVariables::instance('global');
            return $css->toResponse($request, $response, false);
        };

        $routeStatics = [
            new Route('user-system-features/statics/globals-vars.css', $cssGlobalVariables, UserSystemFeaturesRoutes::class . '-global-vars'),
            new Route('user-system-features/statics/[{params:.*}]', $callableHandler, UserSystemFeaturesRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
