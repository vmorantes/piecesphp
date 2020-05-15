<?php

/**
 * EntryPointController.php
 */

namespace PiecesPHP\BuiltIn\Shop;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Shop\Brand\Controllers\BrandController;
use PiecesPHP\BuiltIn\Shop\Category\Controllers\CategoryController;
use PiecesPHP\BuiltIn\Shop\Product\Controllers\ProductController;
use PiecesPHP\BuiltIn\Shop\SubCategory\Controllers\SubCategoryController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * EntryPointController.
 *
 * @package     PiecesPHP\BuiltIn\Shop
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class EntryPointController extends AdminPanelController
{

    /**
     * $URLDirectory
     *
     * @var string
     */
    protected static $URLDirectory = 'shop/private';

    /**
     * $baseRouteName
     *
     * @var string
     */
    protected static $baseRouteName = 'built-in-shop-private-entry';

    /**
     * $title
     *
     * @var string
     */
    protected static $title = 'Opciones';

    const BASE_VIEW_DIR = 'built-in/shop';
    const BASE_JS_DIR = 'statics/js/built-in/shop';
    const LANG_GROUP = 'bi-shop';

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.

        self::$title = __(self::LANG_GROUP, self::$title);
        set_title(self::$title);
    }

    /**
     * optionsView
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return void
     */
    public function optionsView(Request $request, Response $response, array $args)
    {

        $backLink = get_route('admin');

        $options = [
            [
                'title' => __(self::LANG_GROUP, 'Categorías'),
                'link' => CategoryController::routeName('list'),
            ],
            [
                'title' => __(self::LANG_GROUP, 'Subcategorías'),
                'link' => SubCategoryController::routeName('list'),
            ],
            [
                'title' => __(self::LANG_GROUP, 'Marcas'),
                'link' => BrandController::routeName('list'),
            ],
            [
                'title' => __(self::LANG_GROUP, 'Productos'),
                'link' => ProductController::routeName('list'),
            ],
        ];

        foreach ($options as $key => $option) {

            $option = (object) $option;

            if (strlen($option->title) > 0) {
                $options[$key] = $option;
            } else {
                unset($options[$key]);
            }

        }

        $data = [];
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = self::$title;
        $data['backLink'] = $backLink;
        $data['options'] = $options;

        $this->render('panel/layout/header');
        self::view('private/options', $data);
        $this->render('panel/layout/footer');

    }

    /**
     * view
     *
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        return (new static )->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
    }

    /**
     * routeName
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            return get_route(
                $name,
                $params,
                $silentOnNotExists
            );
        } else {
            return '';
        }
    }

    /**
     * routes
     *
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

        $all_roles = array_keys(UsersModel::TYPES_USERS);

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────

            //HTML
            new Route( //Vista del listado de opciones de este módulo
                "{$startRoute}/options[/]",
                $classname . ':optionsView',
                self::$baseRouteName . '-options',
                'GET',
                true,
                null,
                $all_roles
            ),

        ];

        $group->register($routes);

        return $group;
    }
}
