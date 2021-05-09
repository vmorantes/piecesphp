<?php

/**
 * PublicationsRoutes.php
 */

namespace Publications;

use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Menu\MenuItem;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestResponsePiecesPHP;
use PiecesPHP\Core\ServerStatics;
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsController;
use Publications\Controllers\PublicationsPublicController;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * PublicationsRoutes.
 *
 * @package     Publications
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class PublicationsRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = true;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            //echo (new \PiecesPHP\Core\Database\SchemeCreator(new \Publications\Mappers\PublicationCategoryMapper()))->getSQL();exit;
            //echo (new \PiecesPHP\Core\Database\SchemeCreator(new \Publications\Mappers\PublicationMapper()))->getSQL();exit;

            $groupAdministration = PublicationsController::routes($groupAdministration);
            $groupAdministration = PublicationsCategoryController::routes($groupAdministration);
            $groupPublic = PublicationsPublicController::routes($groupPublic);

            self::staticResolver($groupAdministration);

            PublicationsLang::injectLang();

            $groupAdministration->addMiddleware(function (\Slim\Http\Request $request, \Slim\Http\Response $response, callable $next) {

                return $next($request, $response);
            });

            RequestResponsePiecesPHP::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return [
            'groupAdministration' => $groupAdministration,
            'groupPublic' => $groupPublic,
        ];
    }

    /**
     * @return void
     */
    public static function init()
    {

        if (!self::$init) {

            $currentUser = get_config('current_user');

            if (!($currentUser instanceof \stdClass)) {
                return null;
            }

            $currentUserType = (int) $currentUser->type;

            /**
             * @var MenuGroupCollection $sidebar
             */
            $sidebar = get_config('menus')['sidebar'];

            $sidebar->addItem(new MenuGroup([
                'name' => __(PublicationsLang::LANG_GROUP, 'Publicaciones'),
                'items' => [
                    new MenuItem([
                        'text' => __(PublicationsLang::LANG_GROUP, 'Publicaciones'),
                        'href' => PublicationsController::routeName('list'),
                        'visible' => PublicationsController::allowedRoute('list'),
                    ]),
                    new MenuItem([
                        'text' => __(PublicationsLang::LANG_GROUP, 'Categorías'),
                        'href' => PublicationsCategoryController::routeName('list'),
                        'visible' => PublicationsCategoryController::allowedRoute('list'),
                    ]),
                ],
                'position' => 3,
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
        return append_to_url(str_replace('/[{params:.*}]', '', get_route(self::class)), $segment);
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
            return $server->compileScssServe($request, $response, $args, __DIR__ . '/Statics');
        };

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        $cssGlobalVariables = function (Request $request, Response $response, array $args) {

            $css = [
                "--emphasis-color:" . '#282828' . ";",
                "--over-emphasis-color:" . 'white' . ";",
            ];

            $css = implode("\n", $css);

            $css = ":root {\n{$css}\n}";

            $lastModification = \DateTime::createFromFormat('d-m-Y h:i A', '01-05-2021 04:22 PM');
            $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification, $css);

            foreach ($headersAndStatus['headers'] as $header => $value) {
                $response = $response->withHeader($header, $value);
            }

            return $response
                ->write($css)
                ->withStatus($headersAndStatus['status'])
                ->withHeader('Content-Type', 'text/css');

        };

        $routeStatics = [
            new Route('publications/statics/globals-vars.css', $cssGlobalVariables, PublicationsRoutes::class . '-global-vars'),
            new Route('publications/statics/[{params:.*}]', $callableHandler, PublicationsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
