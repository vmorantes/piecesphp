<?php

/**
 * ComponentProvider.php
 */

namespace PiecesPHP\Components\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Components\ComponentProviderLang;
use PiecesPHP\Components\ComponentProviderRoutes;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;

/**
 * ComponentProvider.
 *
 * @package     PiecesPHP\Components\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class ComponentProvider extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = '';
    /**
     * @var string
     */
    protected static $baseRouteName = 'components-provider';

    const BASE_JS_DIR = 'js';
    const LANG_GROUP = ComponentProviderLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function provide(Request $request, Response $response)
    {

        //EL GRUPO ACABA EN UN require: solo pasa uno que exista en Views/ y con forma de nombre, sin depender de que el
        //servidor normalice los «..». Sin try alrededor del render: un 404 o un fallo de la vista no se vuelven un 200.
        $requestedGroup = $request->getAttribute('group');
        if (!is_string($requestedGroup) || preg_match('/^[A-Za-z0-9_-]+$/', $requestedGroup) !== 1 || !in_array($requestedGroup, $this->availableGroups(), true)) {
            throw new NotFoundException($request, $response);
        }

        $output = $this->render("{$requestedGroup}/components", [], false);
        $response = $response->write(is_string($output) ? $output : '');

        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * Los grupos que se pueden servir: los directorios de Views/ con un components.php, y con forma de nombre.
     *
     * @return string[]
     */
    public function availableGroups(): array
    {
        $groups = [];
        $directory = $this->getInstanceViewDir();
        foreach (scandir($directory) ?: [] as $entry) {
            if (is_string($entry) && preg_match('/^[A-Za-z0-9_-]+$/', $entry) === 1 && is_file($directory . $entry . '/components.php')) {
                $groups[] = $entry;
            }
        }
        return $groups;
    }

    /**
     * @return string
     */
    public static function pathJSModule()
    {
        return ComponentProviderRoutes::staticRoute('js/ComponentsProvider.js');
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

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos
        $provide = $allRoles;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Las piezas HTML de un grupo: Views/<grupo>/components.php
                "{$startRoute}/provide/{group}[/]",
                $classname . ':provide',
                self::$baseRouteName . '-provide',
                'GET',
                false,
                null,
                $provide
            ),
        ];

        $group->register($routes);

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
