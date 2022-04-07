<?php

/**
 * EntryPointController.php
 */

namespace PiecesPHP\BuiltIn\DynamicImages;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\DynamicImages\Informative\Controllers\HeroController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;

/**
 * EntryPointController.
 *
 * @package     PiecesPHP\BuiltIn\DynamicImages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class EntryPointController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'dynamic-images/private';

    /**
     * @var string
     */
    protected static $baseRouteName = 'built-in-dynamic-images-private-entry';

    /**
     * @var string
     */
    protected static $title = 'Opciones';

    /**
     * @var array
     */
    private $options = [];

    const BASE_VIEW_DIR = 'built-in/dynamic-images';
    const BASE_JS_DIR = 'statics/js/built-in/dynamic-images';
    const LANG_GROUP = 'bi-dynamic-images';

    /**
     * @return static
     */
    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        set_title(self::$title);

        $this->options = [
            [
                'title' => __(self::LANG_GROUP, 'Imágenes principales'),
                'link' => HeroController::routeName('list'),
            ],
        ];
    }

    /**
     * @return void
     */
    public function optionsView()
    {

        $backLink = get_route('admin');

        $options = $this->getAllowedOptions();

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
     * @return \stdClass[]
     */
    protected function getAllowedOptions()
    {

        $options = [];

        foreach ($this->options as $option) {
            $option = (object) $option;
            $allowedOption = mb_strlen($option->title) > 0;
            $allowedOption = $allowedOption && mb_strlen($option->link) > 0;
            if ($allowedOption) {
                $options[] = $option;
            }
        }

        return $options;

    }

    /**
     * @param string $name
     * @param array $data
     * @param bool $mode
     * @param bool $format
     * @return void|string
     */
    public static function view(string $name, array $data = [], bool $mode = true, bool $format = true)
    {
        return (new EntryPointController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
    }

    /**
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {

        $route = self::routeName($name, $params, true);
        $allow = mb_strlen($route) > 0;

        if ($allow) {

            if ($name == 'options') {

                $allow = !empty((new EntryPointController)->getAllowedOptions());

            }

        }

        return $allow;
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = mb_strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            $routeResult = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            return is_string($routeResult) ? $routeResult : '';
        } else {
            return '';
        }
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
        $all_roles = array_keys(UsersModel::TYPES_USERS);
        $roles_view_options = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN,
            UsersModel::TYPE_USER_GENERAL,
        ];

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
                $roles_view_options
            ),

        ];

        $group->register($routes);

        return $group;
    }
}
