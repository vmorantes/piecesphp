<?php

/**
 * PublicAreaController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use App\Model\UsersModel;
use Components\Controllers\ComponentProvider;
use GoogleReCaptchaV3\GoogleReCaptchaV3Routes;
use Newsletter\Controllers\NewsletterController;
use Newsletter\NewsletterRoutes;
use PiecesPHP\BuiltIn\Banner\BuiltInBannerRoutes;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerPublicController;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use Publications\Controllers\PublicationsController;
use Publications\Controllers\PublicationsPublicController;
use Publications\PublicationsRoutes;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

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
     * @var string
     */
    private static $prefixNameRoutes = 'public';

    /**
     * @var string
     */
    private static $startSegmentRoutes = '';

    /**
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    const LANG_REPLACE_GENERIC_TITLES = 'REPLACE_GENERIC_TITLES';

    /**
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente

        $this->init();

        import_jquery();
        import_izitoast();
        import_semantic();
        import_app_libraries();
        import_app_front_libraries();
        if (GoogleReCaptchaV3Routes::ENABLE) {
            import_google_captcha_v3_adapter();
        }
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function indexView(Request $req, Response $res)
    {

        set_title(__(LANG_GROUP, 'Inicio'));

        set_custom_assets([
            'statics/css/style.css',
        ], 'css');

        $assetsJS = [
            PublicationsController::pathFrontPublicationsAdapter(),
            BuiltInBannerController::pathFrontBuiltInBannerAdapter(),
            BuiltInBannerRoutes::staticRoute('js/public/home.js'),
            PublicationsRoutes::staticRoute('js/publications/public/listing.js'),
            'statics/js/main.js',
        ];
        $assetsJS = array_filter($assetsJS, function ($e) {return mb_strlen($e) > 0;});
        if (!empty($assetsJS)) {
            set_custom_assets($assetsJS, 'js');
        }

        $data = [
            'componentsProviderURL' => ComponentProvider::routeName('provide', ['group' => '{GROUP}'], true),
            'ajaxPublicationsURL' => PublicationsPublicController::routeName('ajax-all', [], true),
            'sliderAjax' => BuiltInBannerPublicController::routeName('ajax-all', [], true),
            'addSuscriberURL' => NewsletterController::routeName('add', [], true),
            'suscriberEnable' => NewsletterRoutes::ENABLE,
            'langGroup' => LANG_GROUP,
        ];

        $this->setVariables($data);

        $this->render('layout/header');
        $this->render('layout/menu');
        $this->render('pages/home', []);
        $this->render('layout/footer');

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function contactView(Request $req, Response $res)
    {

        set_title(__(LANG_GROUP, 'Contacto'));

        set_custom_assets([
            'statics/css/style.css',
        ], 'css');

        set_custom_assets([
            'statics/js/main.js',
            'statics/js/contact-form.js',
        ], 'js');

        $data = [
            'contactURL' => ContactFormsController::routeName('general'),
            'langGroup' => LANG_GROUP,
        ];

        $this->setVariables($data);
        $this->render('layout/header');
        $this->render('layout/menu');
        $this->render('pages/contact', []);
        $this->render('layout/footer');

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function unsubscribeView(Request $req, Response $res)
    {
        set_title(__(LANG_GROUP, 'Unsubscribe'));
        echo "OK";
        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function genericViews(Request $req, Response $res, array $args)
    {

        $folder = $req->getAttribute('folder', null);
        $name = $req->getAttribute('name', null);

        $folder = is_string($folder) && mb_strlen(trim($folder)) > 0 ? trim($folder) : null;
        $name = mb_strlen(trim($name)) > 0 ? __(self::LANG_REPLACE_GENERIC_TITLES, trim($name)) : null;

        if ($folder !== null) {
            $name = "{$folder}/{$name}";
        }

        $css = [
            'statics/css/style.css',
        ];
        $js = [
            PublicationsController::pathFrontPublicationsAdapter(),
            'statics/js/CustomNamespace.js',
            'statics/js/main.js',
        ];

        $data = [
            'withSocialBar' => true,
            'withRecents' => true,
            'req' => $req,
            'res' => $res,
            'args' => $args,
            'langGroup' => LANG_GROUP,
            'ajaxPublicationsURL' => PublicationsPublicController::routeName('ajax-all', [], true),
            'componentsProviderURL' => ComponentProvider::routeName('provide', ['group' => '{GROUP}'], true),
        ];

        $availableView = self::genericViewsConfigurations();

        $viewHeader = 'layout/header';
        $viewMenu = 'layout/menu';
        $viewFooter = 'layout/footer';

        if (is_string($name) && array_key_exists($name, $availableView)) {

            $viewConfig = $availableView[$name];
            $directory = isset($viewConfig['directory']) && $viewConfig['directory'] !== null ? $viewConfig['directory'] : 'pages/generic-views';
            $file = isset($viewConfig['file']) && $viewConfig['file'] !== null ? $viewConfig['file'] : $name;
            $viewHeader = isset($viewConfig['header']) && $viewConfig['header'] !== null ? $viewConfig['header'] : $viewHeader;
            $viewMenu = isset($viewConfig['menu']) && $viewConfig['menu'] !== null ? $viewConfig['menu'] : $viewMenu;
            $viewFooter = isset($viewConfig['footer']) && $viewConfig['footer'] !== null ? $viewConfig['footer'] : $viewFooter;
            $viewTitle = isset($viewConfig['title']) && $viewConfig['title'] !== null ? $viewConfig['title'] : null;
            $viewData = isset($viewConfig['data']) && $viewConfig['data'] !== null ? $viewConfig['data'] : [];
            $prependAssets = isset($viewConfig['prependAssets']) ? $viewConfig['prependAssets'] : [];
            $appendAssets = isset($viewConfig['appendAssets']) ? $viewConfig['appendAssets'] : [];
            $executeBeforeViews = isset($viewConfig['executeBeforeViews']) && is_callable($viewConfig['executeBeforeViews']) ? $viewConfig['executeBeforeViews'] : function () {};
            $executeAfterViews = isset($viewConfig['executeAfterViews']) && is_callable($viewConfig['executeAfterViews']) ? $viewConfig['executeAfterViews'] : function () {};
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

            if (isset($file) && $file !== null) {

                set_custom_assets($css, 'css');
                set_custom_assets($js, 'js');

                $this->setVariables($data);

                ($executeBeforeViews)();
                $this->render($viewHeader);
                $this->render($viewMenu);
                $this->render("{$directory}/{$file}");
                $this->render($viewFooter);
                ($executeAfterViews)();

            } else {
                throw new NotFoundException($req, $res);
            }

        } else {
            throw new NotFoundException($req, $res);
        }

        return $res;
    }

    /**
     * Obtiene las configuraciones de las vistas genéricas
     *
     * @return array Arreglo con las configuraciones de las vistas genéricas
     */
    public static function genericViewsConfigurations()
    {
        $currentUser = getLoggedFrameworkUser();

        $baseConfiguration = [
            'header' => null,
            'menu' => null,
            'footer' => null,
            'directory' => null,
            'file' => null,
            'appendAssets' => [
                'css' => [
                ],
                'js' => [
                ],
            ],
            'data' => [
                'dataWfPage' => '',
                'dataWfSite' => '',
                'bodyClasses' => '',
            ],
            'executeBeforeViews' => function () {},
            'executeAfterViews' => function () {},
        ];
        $getConfiguration = fn(array $config = []): array=> array_merge($baseConfiguration, $config);
        $baseAssetsConfig = fn(array $assets = []): array=> array_merge($baseConfiguration['appendAssets'], [
            'css' => array_merge($baseConfiguration['appendAssets']['css'], $assets['css'] ?? []),
            'js' => array_merge($baseConfiguration['appendAssets']['js'], $assets['js'] ?? []),
        ]);
        $baseDataConfig = function (array $data = [], bool $overwrite = false) use ($baseConfiguration): array {
            return $overwrite ? $data : array_merge($baseConfiguration['data'], $data);
        };

        $genericViewConfigurations = [
            'tabs-sample' => $getConfiguration([
                'title' => __(LANG_GROUP, 'Ejemplo de tabs'),
                'appendAssets' => $baseAssetsConfig([
                    'js' => [
                        'statics/js/generic-views/tabs.js',
                    ],
                ]),
                'data' => $baseDataConfig([]),
            ]),
            'elements' => $getConfiguration([
                'title' => __(LANG_GROUP, 'Elementos'),
                'appendAssets' => $baseAssetsConfig([
                    'js' => [
                        'statics/js/generic-views/elements.js',
                    ],
                ]),
                'data' => $baseDataConfig([]),
            ]),
        ];

        if ($currentUser !== null) {

            if ($currentUser->type == UsersModel::TYPE_USER_ROOT) {

                //NOTE: Vistas adicionales para pruebas o disponibles según criterios
                $genericViewConfigurations['SOMETHING' . uniqid()] = [];

            }

        }

        return $genericViewConfigurations;
    }

    /**
     * Verificar si una vista genérica existe
     * @param string $name
     * @param string|null $folder
     * @return bool
     */
    public static function genericViewExists(string $name, ?string $folder = null)
    {

        $folder = is_string($folder) && mb_strlen(trim($folder)) > 0 ? trim($folder) : null;
        $name = mb_strlen(trim($name)) > 0 ? __(self::LANG_REPLACE_GENERIC_TITLES, trim($name)) : null;
        if ($folder !== null) {
            $name = "{$folder}/{$name}";
        }
        $exists = false;
        $availableView = self::genericViewsConfigurations();

        if (is_string($name) && array_key_exists($name, $availableView)) {
            $viewConfig = $availableView[$name];
            $directory = isset($viewConfig['directory']) && $viewConfig['directory'] !== null ? $viewConfig['directory'] : 'pages/generic-views';
            $file = isset($viewConfig['file']) && $viewConfig['file'] !== null ? $viewConfig['file'] : $name;
            $path = append_to_path_system((new BaseController())->getViewDir(), "{$directory}/{$file}.php");
            $exists = file_exists($path);
        }

        return $exists;
    }

    /**
     * Verificar si una ruta es permitida
     *
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {
        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;
        return $allow;
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    private static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'SAMPLE') { //do something
            }

        }

        return $allow;
    }

    /**
     * Obtener URL de una ruta
     *
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {

        $simpleName = !is_null($name) ? $name : '';

        if (!is_null($name)) {
            $name = trim($name);
            $name = mb_strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$prefixNameRoutes . $name : self::$prefixNameRoutes;

        $allowed = false;
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        $route = '';

        if ($allowed) {
            $route = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            $route = !is_string($route) ? '' : $route;
        }

        $allow = self::_allowedRoute($simpleName, $route, $params);

        return $allow ? $route : '';
    }

    /**
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
        //self::$startSegmentRoutes = uniqid(); //Para ocultar este controlador
        if (mb_strlen(self::$startSegmentRoutes) > 0) {
            $startRoute .= self::$startSegmentRoutes;
        } else {
            $startRoute = '';
        }

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $ignoreRoutes = [
            //Vistas para ignorar
            "{$namePrefix}-SAMPLE",
        ];
        $routes = array_filter([
            new Route(
                "{$startRoute}[/]",
                self::class . ":indexView",
                "{$namePrefix}-index",
                'GET'
            ),
            new Route(
                "{$startRoute}/contact[/]",
                self::class . ":contactView",
                "{$namePrefix}-contact",
                'GET'
            ),
            new Route(
                "{$startRoute}/unsubscribe/{identifier}[/]",
                self::class . ":unsubscribeView",
                "{$namePrefix}-unsubscribe",
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
        ], function ($e) use ($ignoreRoutes) {
            return !in_array($e->name(), $ignoreRoutes);
        });

        $group->register($routes);
        //──── POST ─────────────────────────────────────────────────────────────────────────

        //Otros controladores asociados

        $group = ContactFormsController::routes($group);

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
            $this->user->avatar = AvatarModel::getAvatar($this->user->id);
            $this->user->hasAvatar = !is_null($this->user->avatar);
            $this->user->id = BaseHashEncryption::encrypt(base64_encode($this->user->id), self::class);
            unset($this->user->password);
        }

        $this->setVariables($view_data);

    }
}
