<?php

/**
 * DataImportExportUtilityRoutes.php
 */

namespace DataImportExportUtility;

use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\CSSVariables;

/**
 * DataImportExportUtilityRoutes.
 *
 * @package     DataImportExportUtility
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DataImportExportUtilityRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    /**
     * Importadores registrados, por key.
     *
     * @var array<string,class-string<ImportDefinition>>
     */
    private static $importers = [];

    const ENABLE = DATA_IMPORT_EXPORT_MODULE;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $groupAdministration = DataTransferController::routes($groupAdministration);
            $groupAdministration = self::importer($groupAdministration, UsersImportDefinition::class);

            self::staticResolver($groupAdministration);

            DataImportExportUtilityLang::injectLang();

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
                'name' => __(DataImportExportUtilityLang::LANG_GROUP, 'Importar y exportar'),
                'asLink' => true,
                'icon' => 'file excel',
                'href' => DataTransferController::routeName('hub'),
                'visible' => DataTransferController::allowedRoute('hub'),
                'position' => 8000,
            ]));

        }

        self::$init = true;

    }

    /**
     * Registra un importador: formulario, acción y plantilla, cada uno con su nombre de ruta, que es su permiso.
     *
     * @param RouteGroup $group
     * @param string $definitionClass Clase que extiende ImportDefinition
     * @return RouteGroup
     * @throws \InvalidArgumentException si la clase no es una ImportDefinition, su key no es kebab-case o ya está registrada
     */
    public static function importer(RouteGroup $group, string $definitionClass): RouteGroup
    {
        if (!class_exists($definitionClass) || !is_subclass_of($definitionClass, ImportDefinition::class)) {
            throw new \InvalidArgumentException("{$definitionClass} no extiende " . ImportDefinition::class . '.');
        }

        /** @var ImportDefinition $definition */
        $definition = new $definitionClass();
        $key = $definition->key();

        //La key forma parte del nombre de la ruta y de la URL.
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) !== 1) {
            throw new \InvalidArgumentException("La key «{$key}» de {$definitionClass} no está en kebab-case.");
        }
        if (array_key_exists($key, self::$importers)) {
            throw new \InvalidArgumentException("Ya hay un importador registrado con la key «{$key}».");
        }

        if (!self::ENABLE) {
            return $group;
        }

        self::$importers[$key] = $definitionClass;
        return DataTransferController::importerRoutes($group, $definitionClass, $key, $definition->allowedUserTypes());
    }

    /**
     * @return array<string,class-string<ImportDefinition>>
     */
    public static function importers(): array
    {
        return self::$importers;
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
            new Route('data-import-export-utility/statics/globals-vars.css', $cssGlobalVariables, DataImportExportUtilityRoutes::class . '-global-vars'),
            new Route('data-import-export-utility/statics/[{params:.*}]', $callableHandler, DataImportExportUtilityRoutes::class),
        ];
        $group->register($routeStatics);

    }

}
