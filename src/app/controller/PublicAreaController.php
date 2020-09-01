<?php

/**
 * PublicAreaController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleController;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
use PiecesPHP\BuiltIn\DynamicImages\Informative\Controllers\HeroController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * PublicAreaController.
 *
 * Controlador del área pública
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class PublicAreaController extends \PiecesPHP\Core\BaseController
{

    /**
     * $prefixNameRoutes
     *
     * @var string
     */
    private static $prefixNameRoutes = 'public';

    /**
     * $startSegmentRoutes
     *
     * @var string
     */
    private static $startSegmentRoutes = '';

    /**
     * $user
     *
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    /**
     * __construct
     *
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente

        $this->init();

        import_jquery();
        import_izitoast();
        import_app_libraries();
    }

    /**
     * indexView
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function indexView(Request $req, Response $res, array $args)
    {

        set_title(__(LANG_GROUP, 'Home'));

        set_custom_assets([
            'statics/css/style.css',
        ], 'css');

        set_custom_assets([
            'statics/js/CustomNamespace.js',
            ArticleControllerPublic::JS_FOLDER . '/BuiltInArticle.js',
            HeroController::BASE_JS_DIR . '/public/main.js',
            'statics/js/main.js',
            'statics/js/default-template.js',
        ], 'js');

        $data = [
            'withSocialBar' => true,
            'withRecents' => true,
            'ajaxArticlesGlobalURL' => ArticleController::routeName('ajax-all'),
            'sliderAjax' => HeroController::routeName('ajax-all'),
            'req' => $req,
            'res' => $res,
            'args' => $args,
        ];

        $this->setVariables($data);

        $this->render('layout/header');
        $this->render('pages/home', []);
        $this->render('layout/footer');

        return $res;
    }

    /**
     * genericViews
     *
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function genericViews(Request $req, Response $res, array $args)
    {

        $folder = $req->getAttribute('folder', null);
        $name = $req->getAttribute('name', null);

        $folder = is_string($folder) && strlen(trim($folder)) > 0 ? trim($folder) : null;
        $name = strlen(trim($name)) > 0 ? trim($name) : null;

        if ($folder !== null) {
            $name = "{$folder}/{$name}";
        }

        $css = [
            'statics/css/style.css',
        ];
        $js = [
            'statics/js/CustomNamespace.js',
            'statics/js/generic-views/generic-views.js',
            'statics/js/default-template.js',
        ];

        $data = [
            'withSocialBar' => true,
            'withRecents' => true,
            'req' => $req,
            'res' => $res,
            'args' => $args,
        ];

        $availableView = [
            'tabs-sample' => [
                'title' => __(LANG_GROUP, 'Ejemplo de tabs'),
            ],
            'about-us' => [
                'title' => __(LANG_GROUP, 'Quiénes somos'),
            ],
        ];

        $viewHeader = 'layout/header';
        $viewFooter = 'layout/footer';

        if (is_string($name) && array_key_exists($name, $availableView)) {

            $viewConfig = $availableView[$name];
            $file = isset($viewConfig['file']) && $viewConfig['file'] !== null ? $viewConfig['file'] : $name;
            $viewHeader = isset($viewConfig['header']) ? $viewConfig['header'] : $viewHeader;
            $viewFooter = isset($viewConfig['footer']) ? $viewConfig['footer'] : $viewFooter;
            $viewTitle = isset($viewConfig['title']) ? $viewConfig['title'] : null;
            $viewData = isset($viewConfig['data']) ? $viewConfig['data'] : [];
            $prependAssets = isset($viewConfig['prependAssets']) ? $viewConfig['prependAssets'] : [];
            $appendAssets = isset($viewConfig['appendAssets']) ? $viewConfig['appendAssets'] : [];
            $prependCss = isset($prependAssets['css']) ? $prependAssets['css'] : [];
            $prependJs = isset($prependAssets['js']) ? $prependAssets['js'] : [];
            $appendCss = isset($appendAssets['css']) ? $appendAssets['css'] : [];
            $appendJs = isset($appendAssets['js']) ? $appendAssets['js'] : [];

            foreach ($viewData as $k => $i) {
                $data[$k] = $i;
            }
            foreach ($prependCss as $i) {
                array_unshift($css, $i);
            }
            foreach ($prependJs as $i) {
                array_unshift($js, $i);
            }
            foreach ($appendCss as $i) {
                array_push($css, $i);
            }
            foreach ($appendJs as $i) {
                array_push($js, $i);
            }

            if ($viewTitle !== null) {
                set_title($viewTitle);
            }

        }

        set_custom_assets($css, 'css');
        set_custom_assets($js, 'js');

        $this->setVariables($data);

        if (isset($file) && $file !== null) {

            $this->render($viewHeader);
            $this->render("pages/generic-views/{$file}");
            $this->render($viewFooter);

        } else {
            throw new NotFoundException($req, $res);
        }

        return $res;
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

        $name = !is_null($name) ? self::$prefixNameRoutes . $name : self::$prefixNameRoutes;

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

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        //Otras rutas
        $namePrefix = self::$prefixNameRoutes;

        if (strlen(self::$startSegmentRoutes) > 0) {
            $startRoute .= self::$startSegmentRoutes;
        } else {
            $startRoute = '';
        }

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $group->register([
            new Route(
                $lastIsBar ? "" : "[/]",
                self::class . ":indexView",
                "{$namePrefix}-index",
                'GET'
            ),
            new Route(
                "{$startRoute}/{name}[/]",
                self::class . ":genericViews",
                "{$namePrefix}-generic",
                'GET'
            ),
            new Route(
                "{$startRoute}/{folder}/{name}[/]",
                self::class . ":genericViews",
                "{$namePrefix}-generic-2",
                'GET'
            ),
        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        //Otros controladores asociados

        $group = ContactFormsController::routes($group);

        return $group;
    }

    /**
     * init
     *
     * @return void
     */
    protected function init()
    {
        $api_url = get_config('osTicketAPI');
        $api_key = get_config('osTicketAPIKey');

        OsTicketAPI::setBaseURL($api_url);
        OsTicketAPI::setBaseAPIKey($api_key);

        $view_data = [];
        $this->user = get_config('current_user');

        if ($this->user instanceof \stdClass) {
            $view_data['user'] = $this->user;
            $this->user->avatar = AvatarModel::getAvatar($this->user->id);
            $this->user->hasAvatar = !is_null($this->user->avatar);
            $this->user->id = BaseHashEncryption::encrypt(base64_encode($this->user->id), self::class);
            unset($this->user->password);
        }

        $this->setVariables($view_data);

    }
}
