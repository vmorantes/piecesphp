<?php

/**
 * LogsController.php
 */

namespace PiecesPHP\Logs\Controllers;

use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\AdminPanel\DashboardCardRegistry;
use PiecesPHP\AdminPanel\DashboardCard;
use PiecesPHP\AdminPanel\DashboardCardBody;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Logs\ErrorLogReader;
use PiecesPHP\Logs\NewErrorsAlert;
use PiecesPHP\Logs\LogsLang;
use PiecesPHP\Logs\LogsRoutes;
use PiecesPHP\Logs\LogTab;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * LogsController - La pantalla «Registros» y la pestaña de errores, que aporta este mismo módulo.
 *
 * @package     PiecesPHP\Logs\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class LogsController extends AdminPanelController
{

    use ControllerRoutingTrait;

    const LANG_GROUP = LogsLang::LANG_GROUP;

    const ERRORS_TAB = 'errors';

    /**
     * @var string
     */
    protected static $URLDirectory = 'logs';

    /**
     * @var string
     */
    protected static $baseRouteName = 'logs-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../Views/');
    }

    /**
     * La pantalla: SOLO se pinta la pestaña activa; cada pestaña es un enlace a esta misma ruta con otro ?tab.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function index(Request $request, Response $response)
    {
        $user = getLoggedFrameworkUserOrFail();
        $visible = LogTabRegistry::visibleFor((int) $user->type);
        if ($visible === []) {
            return throw403($request);
        }

        $requested = $request->getQueryParam('tab', null);
        if (is_string($requested) && $requested !== '') {
            //Una pestaña que existe pero no es suya y una que no existe responden igual: no se delata cuáles hay.
            if (!array_key_exists($requested, $visible)) {
                return throw403($request);
            }
            $active = $visible[$requested];
        } else {
            $active = reset($visible);
        }

        $tabs = [];
        foreach ($visible as $key => $tab) {
            $tabs[] = [
                'key' => $key,
                'title' => $tab->title(),
                'icon' => $tab->icon(),
                'url' => self::tabURL($key),
                'active' => $key === $active->key(),
            ];
        }

        //Antes de la cabecera: la pestaña añade sus estáticos al pintarse.
        $content = $active->render($request, self::tabURL($active->key()));

        $title = __(self::LANG_GROUP, 'Registros');
        set_title($title . ' - ' . $active->title());

        $this->helpController->render('panel/layout/header');
        $this->render('index', [
            'langGroup' => self::LANG_GROUP,
            'title' => $title,
            'description' => __(self::LANG_GROUP, 'Lo que el sistema anota, en las pestañas que su rol puede ver.'),
            'breadcrumbs' => [
                __(self::LANG_GROUP, 'Inicio') => [
                    'url' => AdminPanelController::routeName(''),
                ],
                $title,
            ],
            'helpController' => $this->helpController,
            'scope' => self::scopeNotice($user),
            'tabs' => $tabs,
            'content' => $content,
        ]);
        $this->helpController->render('panel/layout/footer');

        return $response;
    }

    /**
     * Los errores en JSON, con los mismos filtros que la pestaña: ?ref= o text, type, day y page.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function errorsData(Request $request, Response $response)
    {
        $reader = new ErrorLogReader();
        $reference = $request->getQueryParam('ref', null);
        if (is_string($reference) && $reference !== '') {
            return $response->withJson([
                'success' => true,
                'reference' => $reference,
                'result' => $reader->findByReference($reference),
            ]);
        }
        $result = $reader->page(self::errorFilters($request), self::pageNumber($request));
        return $response->withJson(['success' => true] + $result);
    }

    /**
     * La pestaña «Errores».
     *
     * @param Request $request
     * @param string $tabURL
     * @return string
     */
    public static function errorsTab(Request $request, string $tabURL): string
    {
        add_global_asset(LogsRoutes::staticRoute('css/logs.css'), 'css');
        add_global_asset(LogsRoutes::staticRoute('js/errors.js'), 'js');

        $reader = new ErrorLogReader();
        $file = $request->getQueryParam('file', null);
        $file = is_string($file) && array_key_exists($file, ErrorLogReader::PLAIN_FILES) ? $file : null;
        $reference = $request->getQueryParam('ref', null);
        $reference = is_string($reference) ? trim($reference) : '';
        $filters = self::errorFilters($request);
        $page = self::pageNumber($request);

        //Abrir la pestaña apaga el aviso de errores nuevos (ADR 0060 §4). Solo root la ve.
        $currentUser = getLoggedFrameworkUser();
        if ($currentUser !== null && (int) $currentUser->type === UsersModel::TYPE_USER_ROOT) {
            NewErrorsAlert::markVisited((int) $currentUser->id);
        }

        $data = [
            'langGroup' => self::LANG_GROUP,
            'tabKey' => self::ERRORS_TAB,
            'tabURL' => $tabURL,
            'filters' => $filters,
            'reference' => $reference,
            'file' => $file,
            'plainFiles' => array_keys(ErrorLogReader::PLAIN_FILES),
            'plainLines' => ErrorLogReader::PLAIN_LINES,
            'types' => $reader->types(),
            'levels' => ErrorLogReader::LEVELS,
            'pageSize' => ErrorLogReader::PAGE_SIZE,
        ];

        if ($file !== null) {
            $data['plainContent'] = (string) $reader->tail($file);
        } elseif ($reference !== '') {
            $data['found'] = $reader->findByReference($reference);
        } else {
            $data['result'] = $reader->page($filters, $page);
        }

        return (string) (new HelperController())->setInstanceViewDir(__DIR__ . '/../Views/')->render('errors', $data, false);
    }

    /**
     * @return void
     */
    public static function registerLogTab(): void
    {
        LogTabRegistry::register(new LogTab(
            self::ERRORS_TAB,
            __(self::LANG_GROUP, 'Errores'),
            'bug',
            self::$baseRouteName . '-errors',
            fn(Request $request, string $tabURL): string => self::errorsTab($request, $tabURL),
            10
        ));
    }

    /**
     * Qué alcance tiene lo que se ve, cuando está recortado por la jerarquía de P133: todos menos root.
     *
     * @param UserDataPackage $user
     * @return string Vacío si ve todo
     */
    protected static function scopeNotice(UserDataPackage $user): string
    {
        $type = (int) $user->type;
        if ($type === UsersModel::TYPE_USER_ROOT) {
            return '';
        }
        if ($type !== UsersModel::TYPE_USER_ADMIN_ORG || !CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']) {
            return __(self::LANG_GROUP, 'Solo sus registros y los de los usuarios de menor rango.');
        }
        //`??` y no isset(): `organization` es mágica y la clase no implementa __isset().
        $organizationID = $user->organization ?? null;
        $organization = is_numeric($organizationID) ? OrganizationMapper::getBy((int) $organizationID) : null;
        $name = $organization !== null && isset($organization->name) && is_string($organization->name) ? $organization->name : '';
        return $name !== ''
            ? sprintf(__(self::LANG_GROUP, 'Solo sus registros y los de los usuarios de menor rango de %s.'), $name)
            : __(self::LANG_GROUP, 'Solo sus registros y los de los usuarios de menor rango de su organización.');
    }

    /**
     * @param string $key
     * @return string
     */
    protected static function tabURL(string $key): string
    {
        return self::routeName('index') . '?' . http_build_query(['tab' => $key]);
    }

    /**
     * @param Request $request
     * @return array{text:string,type:string,day:string,level:string}
     */
    protected static function errorFilters(Request $request): array
    {
        $value = function (string $name) use ($request): string {
            $raw = $request->getQueryParam($name, '');
            return is_string($raw) ? mb_substr(trim($raw), 0, 200) : '';
        };
        return [
            'text' => $value('text'),
            'type' => $value('type'),
            'day' => ErrorLogReader::dayKey($value('day')) !== null ? $value('day') : '',
            'level' => in_array($value('level'), ErrorLogReader::LEVELS, true) ? $value('level') : '',
        ];
    }

    /**
     * @param Request $request
     * @return int
     */
    protected static function pageNumber(Request $request): int
    {
        $page = $request->getQueryParam('page', '1');
        return is_string($page) && preg_match('/^[1-9][0-9]{0,5}$/', $page) === 1 ? (int) $page : 1;
    }

    /**
     * La tarjeta «Errores y avisos» del inicio, de root: nace PLEGADA y, plegada, no calcula nada (ni cifras ni rojo: una
     * instalación de muestra no debe alarmar a quien mira). Desplegada, los errores nuevos y los avisos activos.
     *
     * @return void
     */
    public static function registerDashboardCard(): void
    {
        DashboardCardRegistry::register(new DashboardCard(
            'errors-alerts',
            __(self::LANG_GROUP, 'Errores y avisos'),
            'bug',
            self::$baseRouteName . '-errors',
            fn(UserDataPackage $user): DashboardCardBody => DashboardCardBody::numbers(
                [
                    ['value' => NewErrorsAlert::state((int) $user->id, (int) $user->type)['count'], 'concept' => __(self::LANG_GROUP, 'errores nuevos desde tu última visita'), 'icon' => 'bug'],
                    ['value' => count(SystemAlertRegistry::visibleFor((int) $user->type)), 'concept' => __(self::LANG_GROUP, 'avisos del sistema activos'), 'icon' => 'bell outline'],
                ],
                __(self::LANG_GROUP, 'Ver los errores'),
                self::errorsTabURL()
            ),
            40,
            collapsible: true,
            collapsedByDefault: true,
            collapsedFooterCaption: __(self::LANG_GROUP, 'Ver los errores'),
            collapsedFooterUrl: fn(): string => self::errorsTabURL(),
            help: __(self::LANG_GROUP, 'Los errores nuevos desde tu última visita y los avisos del sistema activos. Nace plegada para que no se vea en una demostración.')
        ));
    }

    /**
     * @return string La pestaña de errores de Registros
     */
    protected static function errorsTabURL(): string
    {
        return self::routeName('index') . '?' . http_build_query(['tab' => self::ERRORS_TAB]);
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $startRoute = (last_char($group->getGroupSegment()) == '/' ? '' : '/') . self::$URLDirectory;

        $group->register([
            //La ruta de la pantalla la piden los tres roles que ven alguna pestaña; la visibilidad real es por pestaña.
            new Route("{$startRoute}[/]", self::class . ':index', self::$baseRouteName . '-index', 'GET', true, null, [
                UsersModel::TYPE_USER_ROOT,
                UsersModel::TYPE_USER_ADMIN_GRAL,
                UsersModel::TYPE_USER_ADMIN_ORG,
            ]),
            //Los errores llevan trazas, rutas del servidor y datos de quien lo provocó: solo root.
            new Route("{$startRoute}/errors[/]", self::class . ':errorsData', self::$baseRouteName . '-errors', 'GET', true, null, [
                UsersModel::TYPE_USER_ROOT,
            ]),
        ]);

        $group->addMiddleware(function (Request $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
    }
}
