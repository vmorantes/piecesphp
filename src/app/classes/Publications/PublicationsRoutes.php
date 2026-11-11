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
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Sitemap\Sitemap;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\CSSVariables;
use PiecesPHP\SystemStatus\CleanTarget;
use PiecesPHP\SystemStatus\CleanTargetRegistry;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\ServerDelegatedLinks;
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsController;
use Publications\Controllers\PublicationsPublicController;

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

    const ENABLE = PUBLICATIONS_MODULE;

    /**
     * @param RouteGroup $groupAdministration
     * @param RouteGroup $groupPublic
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        if (self::ENABLE) {

            $groupAdministration = PublicationsController::routes($groupAdministration);
            $groupAdministration = PublicationsCategoryController::routes($groupAdministration);
            $groupPublic = PublicationsPublicController::routes($groupPublic);
            Sitemap::registerProvider('publications', [PublicationsPublicController::class, 'sitemapItems']);
            self::registerCleanTarget();
            //El inicio de comunicaciones (aprobado) es su listado de publicaciones; un proyecto lo cambia con replace().
            \PiecesPHP\AdminPanel\HomeDestinationRegistry::register(\PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_COMUNICACIONES, fn(Request $request, Response $response): Response => (new PublicationsController())->listView($request, $response));

            self::staticResolver($groupAdministration);

            PublicationsLang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        return [
            'groupAdministration' => $groupAdministration,
            'groupPublic' => $groupPublic,
        ];
    }

    /**
     * Los listados guardados (la caché de Publications) en «Estado y cachés»: su botón y su fila. Los textos van en el
     * grupo de idioma de SystemStatus, donde ya estaban traducidos.
     *
     * @return void
     */
    public static function registerCleanTarget(): void
    {
        $lang = SystemStatusController::LANG_GROUP;
        $directory = basepath('app/cache/Publications');
        CleanTargetRegistry::register((new CleanTarget(
            'publications-cache',
            fn (): string => __($lang, 'Borrar listados guardados'),
            fn (): string => __($lang, '¿Borrar los listados guardados? Se vuelven a crear al pedirse.'),
            fn (): array => SystemStatusController::cleanCacheDirectory(
                $directory,
                __($lang, 'No se pudieron borrar los listados guardados: el sistema no tiene permiso sobre su carpeta.'),
                __($lang, 'Listados guardados borrados. Se vuelven a crear al pedirse.')
            ),
            30
        ))->withRow(
            30,
            fn (): string => __($lang, 'Listados de publicaciones guardados'),
            CleanTarget::UNIT_BYTES,
            ['publicationsCacheBytes'],
            fn (): array => ['publicationsCacheBytes' => ServerDelegatedLinks::directorySize($directory)],
            icon: 'list'
        ));
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
                'name' => __(PublicationsLang::LANG_GROUP, 'Publicaciones'),
                'icon' => 'newspaper',
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
                'position' => MenuGroupCollection::BAND_CONTENTS,
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
            new Route('publications/statics/globals-vars.css', $cssGlobalVariables, PublicationsRoutes::class . '-global-vars'),
            new Route('publications/statics/[{params:.*}]', $callableHandler, PublicationsRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
