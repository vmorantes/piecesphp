<?php

/**
 * AdminPanelController.php
 */

namespace PiecesPHP\AdminPanel\Controllers;

use PiecesPHP\Core\InstalledPackages;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\TicketsLogModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use MySpace\Controllers\MyProfileController;
use PiecesPHP\AdminPanel\DashboardCardRegistry;
use Publications\PublicationsRoutes;
use SystemApprovals\SystemApprovalsRoutes;
use SystemApprovals\Util\SystemApprovalManager;
use News\Controllers\NewsController;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Utilities\OsTicket\OsTicketAPI;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\CSSVariables;
use Publications\Controllers\PublicationsController;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * AdminPanelController.
 *
 * Controlador del panel administrativo
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class AdminPanelController extends BaseController
{

    /**
     * Las tres vistas propias de este armazón viven en el módulo, y las pide ESTE ayudante, que
     * lleva el directorio del módulo. En `$this` no puede ir: lo heredarían las 42 clases que
     * extienden de aquí, y las trece que no fijan el suyo buscarían TODAS sus vistas en este
     * módulo. Medido el 2026-09-26: dos pantallas en HTTP 500 y ninguna puerta lo vio.
     *
     * Se tipa por el ancestro común porque el árbol tiene 23 clases `HelperController` distintas y
     * ninguna es covariante con otra: con un tipo concreto, 31 errores de PHPStan.
     *
     * @var BaseController
     */
    protected $moduleViews = null;

    //Va el último de los controladores del sistema: sus hijos sin trait propio heredarían el prefijo «admin».
    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'admin';

    /**
     * Usuario logueado
     *
     * @var \stdClass
     */
    protected $user = null;

    /**
     * @var \PiecesPHP\Core\BaseModel
     */
    protected $modelUsers;

    const ADMIN_LANG_GROUP = 'adminZone';

    /**
     * @return static
     */
    public function __construct()
    {
        parent::__construct(false); //No cargar ningún modelo automáticamente
        $this->init();
        $this->moduleViews = (new HelperController($this->user, $this->getGlobalVariables()))
            ->setInstanceViewDir(__DIR__ . '/../Views/');
        set_title('Dashboard');
    }

    /**
     * Vista principal
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function indexView(Request $req, Response $res)
    {
        $currentUser = getLoggedFrameworkUserOrFail();
        $currentUserType = (int) $currentUser->type;

        //Los repartos de siempre, delante: comunicaciones va a su listado y quien debe tener perfil, a completarlo.
        if (SystemApprovalManager::getInstance()->isApproved(UsersModel::class, $currentUser->id)) {
            if ($currentUserType === UsersModel::TYPE_USER_COMUNICACIONES && PublicationsRoutes::ENABLE) {
                return (new PublicationsController())->listView($req, $res);
            }
            if (in_array($currentUserType, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE) && SystemApprovalsRoutes::ENABLE) {
                return $res->withRedirect(MyProfileController::routeName('my-profile'));
            }
        }

        //El inicio: las tarjetas que cada módulo registró y que este usuario puede ver (DashboardCardRegistry).
        $cards = [];
        foreach (DashboardCardRegistry::visibleFor($currentUserType) as $card) {
            $collapsed = DashboardCardRegistry::isCollapsed($card, (int) $currentUser->id);
            //Plegada, su contenido NO se calcula: ni cifras ni colores que enseñar.
            $body = $collapsed ? null : $card->content($currentUser);
            if (!$collapsed && $body === null) {
                continue;
            }
            $cards[] = ['card' => $card, 'collapsed' => $collapsed, 'body' => $body];
        }

        set_title(__(self::ADMIN_LANG_GROUP, 'Inicio'));
        add_global_asset('statics/core/css/graphs-and-statistics.css', 'css');
        set_custom_assets([self::staticRoute('js/dashboard.js')], 'js');

        $this->render('panel/layout/header');
        $this->moduleViews->render('panel/pages/home', [
            'langGroup' => self::ADMIN_LANG_GROUP,
            'helpController' => new HelperController($this->user, $this->getGlobalVariables()),
            'cards' => $cards,
            'collapseURL' => self::routeName('dashboard-card-collapsed'),
            'userName' => (string) $currentUser->firstname,
        ]);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Pliega o despliega una tarjeta del inicio para el usuario actual.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function dashboardCardCollapsed(Request $req, Response $res)
    {
        $key = $req->getParsedBodyParam('key', '');
        $collapsed = $req->getParsedBodyParam('collapsed', '');
        $user = getLoggedFrameworkUserOrFail();
        $card = is_string($key) ? DashboardCardRegistry::get($key) : null;
        //Solo una tarjeta que este usuario ve y que se puede plegar.
        $allowed = $card !== null && array_key_exists($card->key(), DashboardCardRegistry::visibleFor((int) $user->type)) && in_array($collapsed, ['yes', 'no'], true);
        $saved = $allowed && DashboardCardRegistry::setCollapsed($card->key(), (int) $user->id, $collapsed === 'yes');
        return $res->withJson(['success' => $saved], $saved ? 200 : 400);
    }

    //Medidas en la cabecera de cada archivo de statics/plugins/: al actualizar una librería, se actualiza aquí.
    const ABOUT_JS_LIBRARIES = [
        'ApexCharts Ver. 3.50.0 Licencia MIT',
        'CKEditor Ver. 27.1.0',
        'Cropper Ver. 1.6.2 Licencia MIT',
        'DataTables Ver. 2.3.7',
        'elFinder Ver. 2.1.57 Licencia BSD-3-Clause',
        'fancyBox Ver. 3.5.7 Licencia GPL-3.0',
        'Fomantic UI Ver. 2.9.4 Licencia MIT',
        'iziToast Ver. 1.4.0',
        'Jodit Ver. 3.1.95 Licencia GPL-2.0-or-later',
        'jQuery Ver. 3.7.0',
        'jQuery Mask Ver. 1.14.15',
        'jQuery UI Ver. 1.12.1',
        'Mapbox GL JS Ver. 3.19.0 Licencia Mapbox Terms of Service',
        'Mapbox GL Geocoder Ver. 2.3.0',
        'NProgress Licencia MIT',
        'OpenLayers Ver. 6.14.1',
        'QRCode',
        'Quill Ver. 1.3.6',
        'Spectrum Ver. 1.8.0 Licencia MIT',
        'SurveyJS Ver. 1.12.18 Licencia MIT',
        'SweetAlert2',
        'tidy-html5',
    ];

    /**
     * Acerca de
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function aboutFramework(Request $req, Response $res)
    {

        add_global_asset(self::staticRoute('css/about-framework.css'), 'css');

        set_title(__(AdminPanelController::ADMIN_LANG_GROUP, 'Acerca de'));

        $publicationsListLink = PublicationsController::routeName('list');

        //Las de PHP salen de lo instalado; Ver. y Licencia se dejan sin traducir, como el resto de la lista.
        $describe = fn(array $package): string => InstalledPackages::displayName($package['name'])
            . ($package['installed'] ? ' Ver. ' . $package['version'] : '')
            . ($package['license'] !== '' ? ' Licencia ' . $package['license'] : '');
        $ownPackages = [];
        $thirdPackages = [];
        foreach (InstalledPackages::required() as $package) {
            if ($package['own']) {
                $ownPackages[] = $describe($package);
            } else {
                $thirdPackages[] = $describe($package);
            }
        }

        $data = [];
        $data['publicationsListLink'] = $publicationsListLink;
        $data['ownPackages'] = $ownPackages;
        $data['thirdPackages'] = $thirdPackages;
        $data['jsLibraries'] = self::ABOUT_JS_LIBRARIES;
        $data['years'] = (new \DateTime('2018-01-01'))->diff(new \DateTime())->y;
        $data['breadcrumbs'] = [
            __(self::ADMIN_LANG_GROUP, 'Inicio') => [
                'url' => self::routeName(''),
            ],
            __(self::ADMIN_LANG_GROUP, 'Acerca de'),
        ];
        //Las piezas del panel se piden a un ayudante con la carpeta de vistas global: `moduleViews` mira la del módulo.
        $data['helpController'] = new HelperController($this->user, $this->getGlobalVariables());

        $this->render('panel/layout/header');
        $this->moduleViews->render('panel/pages/about-framework', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * La hoja de diseño: cada componente del panel con su ejemplo vivo y su código (Views/panel/pages/design-sheet/).
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function designSheet(Request $req, Response $res)
    {

        add_global_asset(self::staticRoute('css/design-sheet.css'), 'css');
        add_global_asset('statics/core/css/graphs-and-statistics.css', 'css');
        set_custom_assets([
            self::staticRoute('js/design-sheet/sheet.js'),
            self::staticRoute('js/design-sheet/formularios-desplegable.js'),
            self::staticRoute('js/design-sheet/formularios-casillas.js'),
            self::staticRoute('js/design-sheet/contenido-tabla.js'),
            self::staticRoute('js/design-sheet/contenido-acciones-fila.js'),
            self::staticRoute('js/design-sheet/contenido-pestanas.js'),
            self::staticRoute('js/design-sheet/mensajes-emergentes.js'),
            self::staticRoute('js/design-sheet/mensajes-confirmacion.js'),
            'statics/core/js/statistics-chart-legend.js',
            self::staticRoute('js/design-sheet/estadisticas-grafico.js'),
        ], 'js');
        //Los gráficos los dibuja ApexCharts.
        import_apexcharts();

        $title = __(self::ADMIN_LANG_GROUP, 'Hoja de diseño');
        set_title($title);

        //Cada ejemplo corre con un `$this` que tiene `helpController`, como la vista de un módulo: su código se copia tal cual.
        $helpController = new HelperController($this->user, $this->getGlobalVariables());
        $exampleContext = new class ($helpController) {
            public function __construct(public HelperController $helpController)
            {
            }
        };
        $runExample = \Closure::bind(function (string $file, string $langGroup): void {
            include $file;
        }, $exampleContext, null);

        $data = [];
        $data['langGroup'] = self::ADMIN_LANG_GROUP;
        $data['title'] = $title;
        $data['runExample'] = $runExample;
        $data['helpController'] = $helpController;
        //El panel lleva <base href>: un «#seccion» suelto saltaría a la portada.
        $data['sheetLink'] = self::routeName('design-sheet');
        $data['breadcrumbs'] = [
            __(self::ADMIN_LANG_GROUP, 'Inicio') => [
                'url' => self::routeName(''),
            ],
            $title,
        ];

        $this->render('panel/layout/header');
        $this->moduleViews->render('panel/pages/design-sheet/index', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Las páginas que la hoja de diseño enseña en un marco: la plantilla de correo y SurveyJS (creador y formulario).
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function designSheetResources(Request $req, Response $res)
    {
        $source = $req->getAttribute('source', null);
        $source = is_string($source) ? $source : '';
        $refererURL = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        $refererURL = is_string($refererURL) && mb_strlen($refererURL) > 0 ? $refererURL : null;

        if ($source == 'mail-users-template') {
            //La muestra llama a `$this->helpController`, como la vista de un módulo: el mismo contexto que los ejemplos de designSheet().
            $sampleContext = new class (new HelperController($this->user, $this->getGlobalVariables())) {
                public function __construct(public HelperController $helpController)
                {
                }
            };
            $renderSample = \Closure::bind(function (string $file, ?string $refererURL): void {
                include $file;
            }, $sampleContext, null);
            $renderSample(__DIR__ . '/../Views/panel/pages/design-sheet/resources/mail-sample.php', $refererURL);
        } elseif ($source == 'survey-js-creator') {
            $this->moduleViews->render('panel/pages/design-sheet/resources/survey-js-creator', []);
        } elseif ($source == 'survey-js-form') {
            $this->moduleViews->render('panel/pages/design-sheet/resources/survey-js-form', []);
        } else {
            throw new \PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException($req, $res);
        }

        return $res;
    }

    /**
     * Prueba del adaptador de CropperJS
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function cropperAdapterTestingView(Request $req, Response $res)
    {
        import_cropper();
        set_custom_assets([self::staticRoute('js/test-cropper.js')], 'js');

        $data = [];
        $data['langGroup'] = self::ADMIN_LANG_GROUP;
        $data['helpController'] = new HelperController($this->user, $this->getGlobalVariables());

        $this->render('panel/layout/header');
        $this->moduleViews->render('panel/pages/test-cropper', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Enviar mensaje al soporte
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function sendTicket(Request $req, Response $res)
    {
        $name = $req->getParsedBodyParam('name', null);
        $email = $req->getParsedBodyParam('email', null);
        $subject = $req->getParsedBodyParam('subject', null);
        $comments = $req->getParsedBodyParam('comments', null);

        $params_ok = !in_array(null, [
            $subject,
            $email,
            $name,
            $comments,
        ]);

        $operation_name = __(self::ADMIN_LANG_GROUP, 'Crear ticket de soporte');
        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = __(self::ADMIN_LANG_GROUP, 'Ticket de soporte creado.');
        $message_unknow_error = __(self::ADMIN_LANG_GROUP, 'Ha ocurrido un error inesperado.');
        $message_unexpected_or_missing_params = __(self::ADMIN_LANG_GROUP, 'Información faltante o inesperada.');

        if ($params_ok) {

            $osTicketApi = new OsTicketAPI();

            $subject = "$subject " . get_title();

            $success = $osTicketApi->createTicket($name, $email, $subject, $comments);
            $result->setValue('osTicketApi', [
                'url' => $osTicketApi->getHttpClient()->getRequestURI(),
                'reponse' => $osTicketApi->getHttpClient()->getResponseHeaders(),
            ]);

            $logRequest = new TicketsLogModel();
            $logRequest->created = new \DateTime();
            $logRequest->name = $name;
            $logRequest->email = $email;
            $logRequest->message = $comments;
            $logRequest->information = [
                'subject' => $subject,
                'email_sended' => $success,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ];
            $logRequest->type = (string) __(self::ADMIN_LANG_GROUP, 'Ticket soporte dentro del panel administrativo (osTicket).');
            $logRequest->save();

            if ($success) {
                $result->setValue('reload', true);
                $result
                    ->setMessage($message_create);
                //La operación se creó con este nombre en el constructor: si faltara, es un fallo de verdad.
                ($result->operation($operation_name) ?? throw new \LogicException('La operación del resultado no existe.'))->setSuccess(true);
            } else {
                $result
                    ->setMessage($message_unknow_error);
            }
        } else {
            $result
                ->setMessage($message_unexpected_or_missing_params);
        }

        return $res->withJson($result);
    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, dirname(__DIR__) . '/Statics', true);
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
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //Generales
        $group->register([
            new Route(
                $lastIsBar ? '' : '[/]',
                self::class . ':indexView',
                'admin',
                'GET',
                true
            ),
            new Route(
                "{$startRoute}dashboard/card-collapsed[/]",
                self::class . ':dashboardCardCollapsed',
                'admin-dashboard-card-collapsed',
                'POST',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}about[/]",
                self::class . ':aboutFramework',
                'admin-about-framework',
                'GET',
                true,
                null,
                $allRoles
            ),
            new Route(
                "{$startRoute}design-sheet[/]",
                self::class . ':designSheet',
                'admin-design-sheet',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN_GRAL,
                ]
            ),
            new Route(
                "{$startRoute}design-sheet/resources/{source}[/]",
                self::class . ':designSheetResources',
                'admin-design-sheet-resources',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN_GRAL,
                ]
            ),
            new Route(
                "{$startRoute}cropper-testing[/]",
                self::class . ':cropperAdapterTestingView',
                'admin-cropper-testing',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                ]
            ),
        ]);

        //Variables css globales
        $cssGlobalVariables = function (Request $request, Response $response) {
            $css = CSSVariables::instance('global');
            return $css->toResponse($request, $response, false);
        };

        //Los estáticos del módulo (Statics/): de momento, los de la hoja de diseño.
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, dirname(__DIR__) . '/Statics');
        };

        $routeStatics = [
            new Route(
                "{$startRoute}/global-variables/variables.css[/]",
                $cssGlobalVariables,
                'admin-global-variables-css',
                'GET'
            ),
            new Route(
                "{$startRoute}statics/[{params:.*}]",
                $callableHandler,
                self::class,
                'GET'
            ),
        ];
        $group->register($routeStatics);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        return $group;
    }

    /**
     * @param RouteGroup $groupAdministration Las rutas de usuarios con sesión (P51).
     * @param RouteGroup $groupPublic Las de acceso, recuperación y bloqueo.
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function usersRoutes(RouteGroup $groupAdministration, RouteGroup $groupPublic)
    {
        return UsersController::routes($groupAdministration, $groupPublic);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function ticketsRoutes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;
        /**
         * @var array<string>
         */
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //──── POST ─────────────────────────────────────────────────────────────────────────

        $group->register([
            new Route(
                "{$startRoute}create[/]",
                $classname . ':sendTicket',
                'admin-tickets-create',
                'POST',
                true,
                null,
                $all_roles
            ),
        ]);

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
            unset($this->user->password, $this->user->meta, $this->user->failedAttempts, $this->user->sessionsValidFrom);
        }

        $this->setVariables($view_data);

        /* JQuery */
        import_jquery();
        /* Semantic */
        import_semantic();
        /* DataTables */
        import_datatables();
        /* NProgress */
        import_nprogress();
        /* izitoast */
        import_izitoast();
        /* Cropper */
        import_cropper();
        /* Módulo de noticias */
        if (NEWS_MODULE) {
            add_global_asset(NewsController::pathFrontNewsAdapter(), 'js');
        }
        /* Librerías de la aplicación */
        import_app_libraries([
            'adminStyle',
            'attachmentPlaceholder',
        ]);
        add_global_asset(base_url(ADMIN_AREA_PATH_JS . '/main.js'), 'js');

        if (LOCATIONS_ENABLED) {
            import_locations([], true, true);
        }
    }

    /**
     * @return void
     */
    private function showPermissionsRoles()
    {
        $routes = get_routes();
        $total_routes = count($routes);
        $result = [];
        $typesUsers = UsersModel::getTypesUser();

        foreach ($typesUsers as $code => $name) {
            if (!isset($result[$name])) {
                $result[$name] = [
                    'total_routes' => $total_routes,
                    'total_allowed' => 0,
                    'total_restricted' => 0,
                    'allowed' => [],
                    'restricted' => [],
                ];
            }
            foreach (get_routes() as $route_info) {
                $route_name = $route_info['name'];
                $allowed = Roles::hasPermissions($route_name, (int) $code);
                if ($allowed) {
                    $result[$name]['allowed'][] = $route_name;
                } else {
                    $result[$name]['restricted'][] = $route_name;
                }
            }
            $result[$name]['total_allowed'] = count($result[$name]['allowed']);
            $result[$name]['total_restricted'] = count($result[$name]['restricted']);
        }
        header('content-type:application/json');
        echo json_encode($result);
        die;
    }
}
