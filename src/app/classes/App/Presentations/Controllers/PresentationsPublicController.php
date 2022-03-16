<?php

/**
 * PresentationsPublicController.php
 */

namespace App\Presentations\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use App\Presentations\Mappers\PresentationCategoryMapper;
use App\Presentations\Mappers\PresentationMapper;
use App\Presentations\PresentationsLang;
use App\Presentations\PresentationsRoutes;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Pagination\PageQuery;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use Slim\Exception\NotFoundException;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * PresentationsPublicController.
 *
 * @package     App\Presentations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class PresentationsPublicController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'presentations';

    /**
     * @var string
     */
    protected static $baseRouteName = 'app-presentations';

    /**
     * @var string
     */
    protected static $title = 'Presentación';
    /**
     * @var string
     */
    protected static $pluralTitle = 'Presentaciones';
    /**
     * @var BaseController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = PresentationsController::BASE_VIEW_DIR;
    const BASE_JS_DIR = PresentationsController::BASE_JS_DIR;
    const BASE_CSS_DIR = PresentationsController::BASE_CSS_DIR;
    const LANG_GROUP = PresentationsLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        self::$title = __(self::LANG_GROUP, self::$title);
        self::$pluralTitle = __(self::LANG_GROUP, self::$pluralTitle);

        $this->model = (new PresentationMapper())->getModel();

        set_title(self::$title);

        $this->helpController = (function ($user) {
            set_config('lock_assets', true);
            $c = new class extends BaseController
            {

                public $user;

                public function __construct()
                {
                    parent::__construct(false);
                }

            };
            $c->user = $user;
            set_config('lock_assets', false);
            return $c;
        })($this->user);

        $this->helpController->setVariables($this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(PresentationsRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function listView(Request $request, Response $response)
    {

        try {

            $category = new Parameter('category');
            $category->setDefaultValue(null)->setOptional(true);
            $category->setValidator(function ($value) {
                return ctype_digit($value) || is_int($value);
            });
            $category->setParser(function ($value) {
                return (int) $value;
            });
            $category->setValue($request->getQueryParam('category', null));
            $category = $category->getValue();

            $backLink = get_route('admin');

            $title = self::$pluralTitle;

            $ajaxURL = self::routeName('ajax-all') . ($category !== null ? "?category={$category}" : '');
            $currentURL = get_current_url();
            $categoriesOptions = array_to_html_options(PresentationCategoryMapper::allForSelect(), $category);

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = $title;
            $data['ajaxURL'] = $ajaxURL;
            $data['currentURL'] = $currentURL;
            $data['categoriesOptions'] = $categoriesOptions;

            set_custom_assets([
                PresentationsRoutes::staticRoute(self::BASE_CSS_DIR . '/presentation-style.css'),
            ], 'css');

            set_custom_assets([
                PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/AppPresentations.js'),
                PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/public/list.js'),
            ], 'js');

            $this->helpController->render('panel/layout/header');
            self::view('public/list', $data);
            $this->helpController->render('panel/layout/footer');

        } catch (\Exception $e) {
            throw new NotFoundException($request, $response);
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function singleView(Request $request, Response $response)
    {

        $slug = $request->getAttribute('slug', '');
        $slugID = PresentationMapper::extractIDFromSlug($slug);
        $element = new PresentationMapper($slugID);

        if ($element->id !== null) {

            $backLink = PresentationsPublicController::routeName('list');

            $title = $element->currentLangData('name');

            $ajaxURL = self::routeName('ajax-all') . "?category={$element->category->id}&ignore={$element->id}";
            $titleCategory = $element->currentLangData('category')->currentLangData('name');

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['backLink'] = $backLink;
            $data['title'] = $title;
            $data['ajaxURL'] = $ajaxURL;
            $data['titleCategory'] = $titleCategory;
            $data['element'] = $element;

            import_fancybox3();

            set_custom_assets([
                PresentationsRoutes::staticRoute(self::BASE_CSS_DIR . '/presentation-style.css'),
            ], 'css');

            set_custom_assets([
                PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/AppPresentations.js'),
                PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/public/list.js'),
                PresentationsRoutes::staticRoute(self::BASE_JS_DIR . '/public/single.js'),
            ], 'js');

            $this->helpController->render('panel/layout/header');
            self::view('public/single', $data);
            $this->helpController->render('panel/layout/footer');

        } else {
            throw new NotFoundException($request, $response);
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function all(Request $request, Response $response)
    {

        $expectedParameters = new Parameters([
            new Parameter(
                'page',
                1,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'per_page',
                10,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'category',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
            new Parameter(
                'ignore',
                null,
                function ($value) {
                    return ctype_digit($value) || is_int($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var int $id
         * @var int $perPage
         * @var int $category
         * @var int $ignore
         */
        $page = $expectedParameters->getValue('page');
        $perPage = $expectedParameters->getValue('per_page');
        $category = $expectedParameters->getValue('category');
        $ignore = $expectedParameters->getValue('ignore');

        $table = PresentationMapper::TABLE;
        $fields = PresentationMapper::fieldsToSelect();
        $jsonExtractExists = PresentationMapper::jsonExtractExistsMySQL();

        $whereString = null;
        $where = [];

        if ($category !== null) {
            $where[] = "category = {$category}";
        }

        if ($ignore !== null) {
            if (!empty($where)) {
                $where[] = "AND id != {$ignore}";
            } else {
                $where[] = "id != {$ignore}";
            }
        }

        if (!empty($where)) {
            $whereString = implode(' ', $where);
        }

        $fields = implode(', ', $fields);
        $sqlSelect = "SELECT {$fields} FROM {$table}";
        $sqlCount = "SELECT COUNT({$table}.id) AS total FROM {$table}";

        if ($whereString !== null) {
            $sqlSelect .= " WHERE {$whereString}";
            $sqlCount .= " WHERE {$whereString}";
        }

        $sqlSelect .= " ORDER BY " . implode(', ', PresentationMapper::ORDER_BY_PREFERENCE);

        $pageQuery = new PageQuery($sqlSelect, $sqlCount, $page, $perPage, 'total');

        $parser = function ($element) {
            $element = PresentationMapper::objectToMapper($element);
            $element = PresentationsPublicController::view('public/util/item', [
                'element' => $element,
            ], false, false);
            return $element;
        };
        $each = !$jsonExtractExists ? function ($element) {
            $element = PresentationMapper::translateEntityObject($element);
            return $element;
        } : null;

        $pagination = $pageQuery->getPagination($parser, $each);

        return $response->withJson($pagination);
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
        return (new static )->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
    }

    /**
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {

        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'SAMPLE') { //do something
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
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user !== false) {
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

        $permisos_listado = $all_roles;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado
                "{$startRoute}/single/{slug}[/]",
                $classname . ':singleView',
                self::$baseRouteName . '-single',
                'GET',
                true,
                null,
                $permisos_listado
            ),
            //HTML
            new Route( //Vista del listado
                "{$startRoute}/list[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list',
                'GET',
                true,
                null,
                $permisos_listado
            ),
            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                $classname . ':all',
                self::$baseRouteName . '-ajax-all',
                'GET'
            ),

        ];

        $group->register($routes);

        return $group;
    }
}
