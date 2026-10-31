<?php

/**
 * DirectoryController.php
 */

namespace Directory\Controllers;

use Directory\DirectoryLang;
use Directory\DirectoryRoutes;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * DirectoryController.
 *
 * @package     Directory\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DirectoryController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'directory';
    /**
     * @var string
     */
    protected static $baseRouteName = 'directory-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const LANG_GROUP = DirectoryLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(DirectoryRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function listView(Request $request, Response $response)
    {

        $title = __(self::LANG_GROUP, 'Directorio');
        $description = __(self::LANG_GROUP, 'Las personas y las organizaciones con su perfil público visible.');

        set_title($title);

        $processTableLink = AllProfilesController::routeName('datatables');

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['processTableLink'] = $processTableLink;
        $data['helpController'] = $this->helpController;
        $data['breadcrumbs'] = [
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
            ],
            $title,
        ];

        set_custom_assets([
            DirectoryRoutes::staticRoute(self::BASE_JS_DIR . '/list.js'),
        ], 'js');

        $this->helpController->render('panel/layout/header');
        $this->render('list', $data);
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(trim($name, '/'), $data, $mode, $format);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        //Permisos: los cinco roles del panel que ven personas y organizaciones
        $list = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ADMIN_ORG,
            UsersModel::TYPE_USER_GENERAL,
            UsersModel::TYPE_USER_INSTITUCIONAL,
        ];
        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado: actores
                "{$startRoute}[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list',
                'GET',
                true,
                null,
                $list
            ),
        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * PUNTO DE VARIACIÓN DEL MÓDULO. Aquí, y en ningún otro sitio, van las reglas de negocio
     * que oculten una ruta que los roles SÍ permiten. Está vacío a propósito: es la plantilla,
     * y su presencia dice dónde se escribe la regla el día que aparezca.
     *
     * Devolver `false` ESTRECHA lo que ya concedieron los roles; nunca ensancha. `routeName()`
     * llama a este método SIEMPRE, y `allowedRoute()` no hace más que preguntarle a
     * `routeName()` si devolvió cadena.
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {
        return true;
    }
}
