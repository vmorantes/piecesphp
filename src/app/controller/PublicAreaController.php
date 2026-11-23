<?php

/**
 * PublicAreaController.php
 */

namespace App\Controller;

use Newsletter\Controllers\NewsletterController;
use Newsletter\NewsletterRoutes;
use PiecesPHP\BuiltIn\Banner\BuiltInBannerRoutes;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerPublicController;
use PiecesPHP\Components\Controllers\ComponentProvider;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\ProjectStart;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use PiecesPHP\GoogleReCaptchaV3\GoogleReCaptchaV3Routes;
use PiecesPHP\MailCatalog\Definitions\ContactFormMail;
use PiecesPHP\MailCatalog\MailCatalog;
use PiecesPHP\PublicSite\PublicSite;
use PiecesPHP\PublicSite\SiteRenderer;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
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
class PublicAreaController extends BaseController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'public';

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
        import_fomantic();
        import_app_libraries();
        import_app_front_libraries();
        if (GoogleReCaptchaV3Routes::ENABLE) {
            import_google_captcha_v3_adapter();
            //La real la carga api-keys.php; si falta, la de prueba de config.php; vacías las dos, el formulario no crea el adaptador.
            $siteKey = get_config('GoogleReCaptchaV3SiteKey');
            if (!is_string($siteKey) || mb_strlen(trim($siteKey)) === 0) {
                $siteKey = get_config('GoogleReCaptchaV3TestSiteKey');
            }
            add_to_front_configurations('GoogleReCaptchaV3SiteKey', is_string($siteKey) ? $siteKey : '');
        }
    }

    /**
     * La portada sin zona pública: al acceso, o adonde lleva el acceso si ya hay sesión.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function closedIndexView(Request $req, Response $res)
    {
        $url = getLoggedFrameworkUser() !== null ? ProjectStart::afterLoginURL(null) : UsersController::routeName('form-login');
        return $res->withRedirect($url);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function indexView(Request $req, Response $res)
    {
        $sitePage = $this->publicSitePage('index', $res);
        if ($sitePage !== null) {
            return $sitePage;
        }

        set_title(__(LANG_GROUP, 'Inicio'));

        //El «Título para compartir» de «Identidad y SEO» es el de la tarjeta de la portada.
        $shareTitle = get_config(SettingsController::SEO_OPTION_SHARE_TITLE);
        if (is_string($shareTitle) && trim($shareTitle) !== '') {
            MetaTags::setShareTitle(trim($shareTitle));
        }

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
        $sitePage = $this->publicSitePage('contact', $res);
        if ($sitePage !== null) {
            return $sitePage;
        }

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
     * Una página del sitio público hecho con marcas (ADR 0084): la de la ruta public-site-<página>.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function publicSiteView(Request $req, Response $res)
    {
        $routeName = (string) $req->getRoute()?->getName();
        $page = str_starts_with($routeName, self::$prefixNameRoutes . '-') ? PublicSite::pageFromRouteSuffix(substr($routeName, strlen(self::$prefixNameRoutes) + 1)) : null;
        $response = $page !== null ? $this->publicSitePage($page, $res) : null;
        if ($response === null) {
            throw new NotFoundException($req, $res);
        }
        return $response;
    }

    /**
     * La página del sitio activo, o null si no hay sitio o no tiene esa página. Solo carga los estáticos del diseñador:
     * los CSS del framework no van, y su JavaScript solo si la página lleva un formulario (pcs-form).
     *
     * @param string $page
     * @param Response $res
     * @return Response|null
     */
    private function publicSitePage(string $page, Response $res): ?Response
    {
        $site = PublicSite::activeSite();
        if ($site === null || !in_array($page, PublicSite::pages($site), true)) {
            return null;
        }
        $renderer = new SiteRenderer($site);
        $info = $renderer->pageInfo($page);
        if ($info === null) {
            return null;
        }
        if ($info['title'] !== '') {
            set_title(__(PublicSite::langGroup($site), $info['title']));
        }
        if ($info['description'] !== '') {
            MetaTags::setDescription(__(PublicSite::langGroup($site), $info['description']));
        }
        set_global_assets([], 'css');
        if (!$info['usesForm']) {
            set_global_assets([], 'js');
        }
        set_custom_assets($info['css'], 'css');
        set_custom_assets($info['js'], 'js');
        return $res->write($renderer->renderPage($page));
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
     * Las rutas públicas del framework que el proyecto sustituye, por nombre: `$config['public_area_ignore_routes']` (H371).
     * Lo que no sea una lista de cadenas no quita nada y queda en el registro de errores.
     *
     * @return string[]
     */
    public static function projectIgnoredRoutes(): array
    {
        $names = get_config('public_area_ignore_routes');
        if ($names === false || $names === null || $names === []) {
            return [];
        }
        if (!is_array($names) || !array_is_list($names) || count(array_filter($names, 'is_string')) !== count($names)) {
            log_exception(new \RuntimeException("\$config['public_area_ignore_routes'] tiene que ser una lista de nombres de ruta: se ignora."));
            return [];
        }
        return $names;
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
        if (mb_strlen(self::$startSegmentRoutes) > 0) {
            $startRoute .= self::$startSegmentRoutes;
        } else {
            $startRoute = '';
        }

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Sin páginas públicas (P399, 'headless' o false): la portada lleva al acceso y la baja de los correos sigue.
        if (!ProjectStart::publicPages()) {
            $closedRoutes = array_filter([
                new Route("{$startRoute}[/]", self::class . ":closedIndexView", "{$namePrefix}-index", 'GET'),
                new Route("{$startRoute}/unsubscribe/{identifier}[/]", self::class . ":unsubscribeView", "{$namePrefix}-unsubscribe", 'GET'),
            ], fn(Route $route): bool => !in_array($route->name(), self::projectIgnoredRoutes(), true));
            if (PUBLIC_AREA_VIEWS) {
                $group->register($closedRoutes);
            }
            //El envío del formulario de contacto es un dato: con 'headless' lo usa el front de fuera.
            if (PUBLIC_AREA_CONTACT_FORMS && ProjectStart::publicData()) {
                MailCatalog::register(new ContactFormMail(), 'Zona pública');
                $group = ContactFormsController::routes($group);
            }
            return $group;
        }

        //Generales
        $ignoreRoutes = array_merge([
            //Vistas para ignorar
            "{$namePrefix}-SAMPLE",
            //Sin formularios no hay a dónde enviar: la página de contacto no se registra (H464.1).
             ...(PUBLIC_AREA_CONTACT_FORMS ? [] : ["{$namePrefix}-contact"]),
        ], self::projectIgnoredRoutes());
        //Las páginas del sitio público (ADR 0084), antes del comodín; index y contact usan las rutas de arriba.
        $siteRoutes = [];
        $site = PublicSite::activeSite();
        $sitePages = PublicSite::registrablePages($site !== null ? PublicSite::pages($site) : [], $groupSegmentURL, $site !== null ? PublicSite::takenPaths() : []);
        foreach ($sitePages['skipped'] as $page => $routeName) {
            PublicSite::logUnique(new \RuntimeException("Sitio público «{$site}»: la página «{$page}» no se sirve porque su dirección ya es de la ruta {$routeName}."));
        }
        foreach ($sitePages['pages'] as $page) {
            $siteRoutes[] = new Route(
                "{$startRoute}/{$page}[/]",
                self::class . ":publicSiteView",
                "{$namePrefix}-" . PublicSite::routeSuffix($page),
                'GET'
            );
        }
        $routes = array_filter([
             ...$siteRoutes,
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

        if (PUBLIC_AREA_VIEWS) {
            $group->register($routes);
        }
        //──── POST ─────────────────────────────────────────────────────────────────────────

        //Otros controladores asociados

        if (PUBLIC_AREA_CONTACT_FORMS) {
            //El formulario de contacto, en el catálogo de correos.
            MailCatalog::register(new ContactFormMail(), 'Zona pública');
            $group = ContactFormsController::routes($group);
        }

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
            //El id no viaja a la zona pública: ninguna vista lo usa (ADR 0061 §10).
            unset($this->user->id, $this->user->password, $this->user->meta, $this->user->failedAttempts, $this->user->sessionsValidFrom);
        }

        $this->setVariables($view_data);

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
