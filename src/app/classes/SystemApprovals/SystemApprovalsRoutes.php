<?php

/**
 * SystemApprovalsRoutes.php
 */

namespace SystemApprovals;

use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;
use SystemApprovals\Controllers\SystemApprovalsController;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\SystemApprovalManager;

/**
 * SystemApprovalsRoutes.
 *
 * @package     SystemApprovals
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class SystemApprovalsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = SYSTEM_APPROVALS_MODULE;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {

        SystemApprovalManager::init();

        if (self::ENABLE) {

            $sqlCreate = [
                (new \PiecesPHP\Core\Database\SchemeCreator(new SystemApprovalsMapper()))->getSQL(),
            ];
            $showSQL = false;
            //$showSQL = true;
            if ($showSQL) {
                header('Content-Type: text/sql');
                echo strReplaceTemplate(implode("\r\n", $sqlCreate), [
                    'createdBy` int' => 'createdBy` bigint',
                    'approvalBy` int' => 'approvalBy` bigint',
                ]);
                exit;
            }

            $groupAdministration = SystemApprovalsController::routes($groupAdministration);

            self::staticResolver($groupAdministration);

            SystemApprovalsLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return [
            'groupAdministration' => $groupAdministration,
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

            /**
             * @category AddToBackendSidebarMenu
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_sidebar_menu();

            $sidebar->addItem(new MenuGroup([
                'name' => __(SystemApprovalsLang::LANG_GROUP, 'Aprobaciones'),
                'icon' => 'check square outline',
                'href' => SystemApprovalsController::routeName('list'),
                'visible' => SystemApprovalsController::allowedRoute('list'),
                'asLink' => true,
                'position' => 50,
            ]));

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
            return $server->compileScssServe($request, $response, $args, __DIR__ . '/Statics', [], self::staticRoute());
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
            new Route('system-approval/statics/globals-vars.css', $cssGlobalVariables, SystemApprovalsRoutes::class . '-global-vars'),
            new Route('system-approval/statics/[{params:.*}]', $callableHandler, SystemApprovalsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
