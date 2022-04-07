<?php

/**
 * PublicationsPublicController.php
 */

namespace Publications\Controllers;

use App\Model\AvatarModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use Publications\PublicationsLang;
use Publications\PublicationsRoutes;
use Slim\Exception\NotFoundException;
use Slim\Http\Request as Request;
use Slim\Http\Response as Response;

/**
 * PublicationsPublicController.
 *
 * @package     Publications\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class PublicationsPublicController extends \PiecesPHP\Core\BaseController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'publications';

    /**
     * @var string
     */
    protected static $baseRouteName = 'publications';

    /**
     * @var BaseController
     */
    protected $helpController = null;

    /**
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    const BASE_VIEW_DIR = PublicationsController::BASE_VIEW_DIR;
    const BASE_JS_DIR = PublicationsController::BASE_JS_DIR;
    const BASE_CSS_DIR = PublicationsController::BASE_CSS_DIR;
    const LANG_GROUP = PublicationsLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente.
        $this->init();

        $this->model = (new PublicationMapper())->getModel();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        import_jquery();
        import_izitoast();
        import_semantic();
        import_app_libraries();
        import_app_front_libraries();

        add_global_asset(PublicationsRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function listView(Request $request, Response $response)
    {

        try {

            $category = $request->getAttribute('categorySlug', null);
            $categoryMapper = null;

            if (is_string($category)) {
                $category = PublicationCategoryMapper::extractIDFromSlug($category);
            }

            set_title(__(LANG_GROUP, 'Publicaciones'));
            $titleSection = __(LANG_GROUP, 'Publicaciones');

            if ($category !== null) {

                $categoryMapper = new PublicationCategoryMapper($category);

                if ($categoryMapper->id === null) {
                    throw new NotFoundException($request, $response);
                } else {
                    $titleSection .= ': ' . $categoryMapper->currentLangData('name');
                }
            }

            $ajaxURL = self::routeName('ajax-all') . ($category !== null ? "?category={$category}" : '');

            $data = [];
            $data['titleSection'] = $titleSection;
            $data['ajaxURL'] = $ajaxURL;

            set_custom_assets([
                'statics/css/style.css',
            ], 'css');

            set_custom_assets([
                PublicationsController::pathFrontPublicationsAdapter(),
                PublicationsRoutes::staticRoute(self::BASE_JS_DIR . '/public/list.js'),
                'statics/js/main.js',
            ], 'js');

            $this->helpController->render('layout/header');
            $this->helpController->render('layout/menu');
            self::view('public/list', $data);
            $this->helpController->render('layout/footer');

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
        $slugID = PublicationMapper::extractIDFromSlug($slug);
        $element = new PublicationMapper($slugID);
        $currentLang = Config::get_lang();

        $exists = $element->id !== null;
        $allowShow = false;

        if ($element->isDraft()) {

            if ($exists && $this->user instanceof \stdClass) {

                if (in_array($this->user->type, PublicationMapper::CAN_VIEW_DRAFT)) {
                    $allowShow = true;
                } else {
                    $allowShow = false;
                }

            } else {
                $allowShow = false;
            }

        } else {
            $allowShow = $exists && $element->status == PublicationMapper::ACTIVE && $element->isActiveByDates();
        }

        if ($allowShow) {

            set_custom_assets([
                'statics/css/style.css',
            ], 'css');

            set_custom_assets([
                PublicationsController::pathFrontPublicationsAdapter(),
                'statics/js/main.js',
            ], 'js');

            $title = $element->currentLangData('title');

            set_title($title);

            //Agregar visita
            if (!$element->isDraft()) {
                $element->addVisit();
            }

            //Configuraciones de SEO
            $seoDescription = $element->getLangData($currentLang, 'seoDescription', false, null);
            $seoDescription =
            is_string($seoDescription) && mb_strlen($seoDescription) > 0 ?
            $seoDescription :
            strip_tags($element->currentLangData('content'));

            $imageOpenGraph = $element->getLangData($currentLang, 'ogImage');
            $imageOpenGraph = is_string($imageOpenGraph) && mb_strlen($imageOpenGraph) > 0 ? $imageOpenGraph : $element->currentLangData('mainImage');

            MetaTags::setDescription($seoDescription);
            MetaTags::setImage(baseurl($imageOpenGraph));

            //URL alternativas según el idioma
            Config::set_config('alternatives_url', $element->getURLAlternatives());

            $data = [];
            $data['langGroup'] = self::LANG_GROUP;
            $data['element'] = $element;

            $this->helpController->render('layout/header');
            $this->helpController->render('layout/menu');
            self::view('public/single', $data);
            $this->helpController->render('layout/footer');

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
        return (new PublicationsController)->all($request, $response);
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
        return (new PublicationsPublicController)->render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Vista del listado
                "{$startRoute}/list[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list',
                'GET'
            ),
            new Route( //Vista del listado
                "{$startRoute}/list/{categorySlug}[/]",
                $classname . ':listView',
                self::$baseRouteName . '-list-by-category',
                'GET'
            ),
            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                $classname . ':all',
                self::$baseRouteName . '-ajax-all',
                'GET'
            ),
            //HTML
            new Route( //Vista del listado
                "{$startRoute}/{slug}[/]",
                $classname . ':singleView',
                self::$baseRouteName . '-single',
                'GET',
                false
            ),

        ];

        $group->register($routes);

        return $group;
    }

    /**
     * @return void
     */
    protected function init()
    {
        $api_url = get_config('osTicketAPI');
        $api_key = get_config('osTicketAPIKey');

        OsTicketAPI::setBaseURL($api_url);
        OsTicketAPI::setBaseAPIKey($api_key);

        $view_data = [];
        $currentUser = getLoggedFrameworkUser();
        if ($currentUser !== null) {
            $this->user = $currentUser->userStdClass;
        }

        if ($this->user instanceof \stdClass) {
            $view_data['user'] = $this->user;
            $this->user->avatar = AvatarModel::getAvatar((int) $this->user->id);
            $this->user->hasAvatar = !is_null($this->user->avatar);
            unset($this->user->password);
        }

        $this->setVariables($view_data);

    }

}
