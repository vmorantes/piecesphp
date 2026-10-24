<?php

/**
 * Locations.php
 */

namespace PiecesPHP\App\Locations\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\App\Locations\LocationsLang;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * Locations.
 *
 * Controlador de ubicaciones
 *
 * @package     PiecesPHP\App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Locations extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $baseRouteName = 'locations';

    /**
     * @var string
     */
    protected static $prefixEntity = 'locations';

    /**
     * @var string
     */
    public static $title = 'Ubicaciones';

    protected HelperController $helperController;

    /**
     * @return static
     */
    public function __construct()
    {
        $this->helperController = new HelperController();
        self::$title = __(LOCATIONS_LANG_GROUP, self::$title);

        parent::__construct();
        set_title(self::$title);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function indexView(Request $request, Response $response)
    {
        //Las tarjetas de la portada son piezas de estadísticas, que no traen su hoja.
        add_global_asset('statics/core/css/graphs-and-statistics.css', 'css');

        $data = [
            'pieces' => $this->helperController,
            'title' => self::$title,
            'breadcrumbs' => [
                __(ADMIN_MENU_LANG_GROUP, 'Inicio') => [
                    'url' => AdminPanelController::routeName(''),
                ],
                self::$title,
            ],
        ];

        $this->helperController->render('panel/layout/header');
        $this->helperController->localRender(self::$prefixEntity . '/main', $data);
        $this->helperController->render('panel/layout/footer');
        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function generateLangFile(Request $request, Response $response)
    {
        $regionsController = new Region();
        $countriesController = new Country();
        $statesController = new State();
        $citiesController = new City();
        $pointsController = new Point();

        $regions = $regionsController->regions($request, $response);
        $countries = $countriesController->countries($request, $response);
        $states = $statesController->states($request, $response);
        $cities = $citiesController->cities($request, $response);
        $points = $pointsController->points($request, $response);

        $data = [
            'regions' => $regions->getRawJsonDataInserted(),
            'countries' => $countries->getRawJsonDataInserted(),
            'states' => $states->getRawJsonDataInserted(),
            'cities' => $cities->getRawJsonDataInserted(),
            'points' => $points->getRawJsonDataInserted(),
        ];

        $dataToTranslation = [];

        foreach ($data as $type => $elements) {
            foreach ($elements as $element) {
                $name = $element->name;
                $description = property_exists($element, 'description') && is_string($element->description) ? $element->description : null;
                if (is_string($name)) {
                    $dataToTranslation[$name] = $name;
                    if ($description !== null) {
                        $dataToTranslation[$description] = $description;
                    }
                } else {
                    continue;
                }
            }
        }

        $strToPHPFile = "<?php\n";
        $strToPHPFile .= "return [\n";
        foreach ($dataToTranslation as $key => $value) {
            $strToPHPFile .= "\t'{$key}' => '{$value}',\n";
        }
        $strToPHPFile .= "];\n";

        $response = $response->withHeader('Content-Type', 'text/plain');
        $response = $response->write($strToPHPFile);

        return $response;
    }

    /**
     * Las opciones de un desplegable, con la clave y el texto escapados: `array_to_html_options()` los pinta en crudo y
     * aquí vienen de la base. Lo seleccionado se le pasa escapado igual (`escape_html()`), o su `in_array` deja de
     * casarlo y el formulario de edición pierde la selección.
     *
     * @param array<int|string,mixed> $options
     * @return array<int|string,string>
     */
    public static function escapeOptions(array $options): array
    {
        $escaped = [];
        foreach ($options as $value => $text) {
            if (is_scalar($text)) {
                $escaped[escape_html($value)] = escape_html($text);
            }
        }
        return $escaped;
    }

    /**
     * La dirección de un estático del módulo (Statics/).
     *
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, dirname(__DIR__) . '/Statics', LOCATIONS_ENABLED);
    }

    /**
     * @param RouteGroup $groupAdministration Las rutas con sesión: la gestión, en el backoffice (P51).
     * @param RouteGroup|null $groupPublic Las rutas sin sesión: las `ajax-all`, públicas desde el 2026-08-26.
     * @return RouteGroup[] Con los índices groupAdministration y groupPublic
     */
    public static function routes(RouteGroup $groupAdministration, ?RouteGroup $groupPublic = null)
    {
        //Opcional porque `AdminPanelController::routes()` recibe un grupo: sin el público, todo va al único.
        $groupPublic ??= $groupAdministration;
        $groupAdministration->active(LOCATIONS_ENABLED);
        $groupPublic->active(LOCATIONS_ENABLED);
        LocationsLang::injectLang();

        //Cada ruta va al grupo que dice su `require_login`: así una ruta nueva no se equivoca de zona.
        $registrar = function (array $rutas) use ($groupAdministration, $groupPublic): void {
            foreach ($rutas as $ruta) {
                ($ruta->requireLogin() === true ? $groupAdministration : $groupPublic)->register([$ruta]);
            }
        };

        $routes = [];

        $groupSegmentURL = $groupAdministration->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        $permisos_traducciones = [
            UsersModel::TYPE_USER_ROOT,
        ];

        $permisos_gestion = [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];

        $permisos_listado = [
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ROOT,
        ];

        //Los estáticos del módulo (Statics/), sin sesión como los de los demás módulos: los pide Locations::staticRoute().
        $staticsHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, dirname(__DIR__) . '/Statics');
        };

        //General
        $routes[] = new Route("{$startRoute}statics/[{params:.*}]", $staticsHandler, self::class, 'GET');
        $routes[] = new Route("[$startRoute]", static::class . ':indexView', self::$prefixEntity, 'GET', true, null, $permisos_listado);
        $routes[] = new Route("/generate-lang-file[/]", static::class . ':generateLangFile', self::$prefixEntity . '-generate-lang-file', 'GET', true, null, $permisos_traducciones);

        $registrar($routes);

        //Regiones
        $registrar(
            self::genericManageRoutesRegions($startRoute, self::$prefixEntity, Region::class, 'regions', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //Country
        $registrar(
            self::genericManageRoutes($startRoute, self::$prefixEntity, Country::class, 'countries', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //State
        $registrar(
            self::genericManageRoutes($startRoute, self::$prefixEntity, State::class, 'states', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //City
        $registrar(
            self::genericManageRoutes($startRoute, self::$prefixEntity, City::class, 'cities', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //Point
        $registrar(
            self::genericManageRoutes($startRoute, self::$prefixEntity, Point::class, 'points', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );

        return [
            'groupAdministration' => $groupAdministration,
            'groupPublic' => $groupPublic,
        ];
    }

    /**
     * @param string $startRoute
     * @param string $namePrefix
     * @param string $handler
     * @param string $uriPrefix
     * @param array $rolesAllowed
     * @return Route[]
     */
    protected static function genericManageRoutes(string $startRoute, string $namePrefix, string $handler, string $uriPrefix, array $rolesAllowed = [])
    {
        $namePrefix .= '-' . $uriPrefix;
        $startRoute .= $uriPrefix;

        $editPermissions = $rolesAllowed['edit'];
        $listPermissions = $rolesAllowed['list'];

        return [
            new Route(
                "{$startRoute}[/]",
                "{$handler}:{$uriPrefix}",
                "{$namePrefix}-ajax-all",
                'GET'
            ),
            new Route(
                "{$startRoute}/datatables[/]",
                "{$handler}:{$uriPrefix}DataTables",
                "{$namePrefix}-datatables",
                'GET',
                true,
                null,
                $listPermissions
            ),
            new Route(
                "{$startRoute}/list[/]",
                "{$handler}:list",
                "{$namePrefix}-list",
                'GET',
                true,
                null,
                $listPermissions
            ),
            new Route(
                "{$startRoute}/forms/add[/]",
                "{$handler}:addForm",
                "{$namePrefix}-forms-add",
                'GET',
                true,
                null,
                $editPermissions
            ),
            new Route(
                "{$startRoute}/action/add[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-add",
                'POST',
                true,
                null,
                $editPermissions
            ),
            new Route(
                "{$startRoute}/forms/edit/{id}[/]",
                "{$handler}:editForm",
                "{$namePrefix}-forms-edit",
                'GET',
                true,
                null,
                $editPermissions
            ),
            new Route(
                "{$startRoute}/action/edit[/]",
                "{$handler}:action",
                "{$namePrefix}-actions-edit",
                'POST',
                true,
                null,
                $editPermissions
            ),
            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                "{$handler}:all",
                "{$namePrefix}-ajax-all2",
                'GET'
            ),
            new Route( //Búsqueda
                "{$startRoute}/search[/]",
                "{$handler}:search",
                "{$namePrefix}-ajax-search",
                'GET',
                true, //requireLogin: decision del PROPIETARIO. Las `-ajax-all` siguen publicas.
                null,
                //Sin roles declarados NO hay permiso: el catálogo de roles se rellena con esto al arrancar, así que
                //`requireLogin` a secas dejaba la ruta en 403 para todos, root incluido.
                $listPermissions
            ),
        ];
    }

    /**
     * @param string $startRoute
     * @param string $namePrefix
     * @param string $handler
     * @param string $uriPrefix
     * @param array $rolesAllowed
     * @return Route[]
     */
    protected static function genericManageRoutesRegions(string $startRoute, string $namePrefix, string $handler, string $uriPrefix, array $rolesAllowed = [])
    {
        $namePrefix .= '-' . $uriPrefix;
        $startRoute .= $uriPrefix;

        $editPermissions = $rolesAllowed['edit'];
        $listPermissions = $rolesAllowed['list'];

        return [
            new Route(
                "{$startRoute}[/]",
                "{$handler}:{$uriPrefix}",
                "{$namePrefix}-ajax-all",
                'GET'
            ),
            //JSON
            new Route( //JSON con todos los elementos
                "{$startRoute}/all[/]",
                "{$handler}:all",
                "{$namePrefix}-ajax-all2",
                'GET'
            ),
            new Route( //Búsqueda
                "{$startRoute}/search[/]",
                "{$handler}:search",
                "{$namePrefix}-ajax-search",
                'GET',
                true, //requireLogin: decision del PROPIETARIO. Las `-ajax-all` siguen publicas.
                null,
                //Sin roles declarados NO hay permiso: el catálogo de roles se rellena con esto al arrancar, así que
                //`requireLogin` a secas dejaba la ruta en 403 para todos, root incluido.
                $listPermissions
            ),
        ];
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
