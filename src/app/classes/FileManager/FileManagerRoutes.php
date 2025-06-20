<?php

/**
 * FileManagerRoutes.php
 */

namespace FileManager;

use App\Model\UsersModel;
use FileManager\Controllers\FileManagerController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;

/**
 * FileManagerRoutes.
 *
 * @package     FileManager
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class FileManagerRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const FILE_MANAGER_ENABLE = FILE_MANAGER_MODULE;

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (self::FILE_MANAGER_ENABLE) {

            $group = FileManagerController::routes($group);

            self::staticResolver($group);

            FileManagerLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return $group;
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

            if ($currentUserType === UsersModel::TYPE_USER_ROOT) {

                $sidebar->addItem(new MenuGroup(
                    [
                        'name' => __(FileManagerLang::LANG_GROUP, 'Gestor de archivos'),
                        'visible' => FileManagerController::allowedRoute('filemanager'),
                        'href' => FileManagerController::routeName('filemanager'),
                        'asLink' => true,
                        'position' => 1980,
                        'icon' => 'folder open',
                    ]
                ));

            }
        }

        self::$init = true;

    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, __DIR__ . '/Statics', self::FILE_MANAGER_ENABLE);
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

        $routeStatics = new Route('filemanager/statics-resolver/[{params:.*}]', $callableHandler, FileManagerRoutes::class);
        $group->register([$routeStatics]);

    }

}
