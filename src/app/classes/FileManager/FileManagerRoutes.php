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
use PiecesPHP\Core\Routing\RequestResponsePiecesPHP;
use PiecesPHP\Core\ServerStatics;
use Slim\Http\Request;
use Slim\Http\Response;

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

            $group->addMiddleware(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

                return $next($request, $response);
            });

            RequestResponsePiecesPHP::appendBeforeCallMethod(function () {
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
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_config('menus')['sidebar'];

            if ($currentUserType === UsersModel::TYPE_USER_ROOT) {

                $sidebar->addItem(new MenuGroup(
                    [
                        'name' => __(FileManagerLang::LANG_GROUP, 'Gestor de archivos'),
                        'visible' => FileManagerController::allowedRoute('filemanager'),
                        'href' => FileManagerController::routeName('filemanager'),
                        'asLink' => true,
                        'position' => 300,
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
        if (self::FILE_MANAGER_ENABLE) {
            $route = get_route(self::class);
            return is_string($route) ? append_to_url(str_replace('/[{params:.*}]', '', $route), $segment) : $segment;
        } else {
            return '';
        }
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
