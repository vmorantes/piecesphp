<?php

/**
 * Locations.php
 */

namespace App\Locations\Controllers;

use App\Controller\AdminPanelController;
use App\Locations\LocationsLang;
use App\Model\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * Locations.
 *
 * Controlador de ubicaciones
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Locations extends AdminPanelController
{
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
        $this->helperController->render('panel/layout/header');
        $this->helperController->localRender(self::$prefixEntity . '/main');
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
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $group->active(LOCATIONS_ENABLED);
        LocationsLang::injectLang();

        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        $permisos_traducciones = [
            UsersModel::TYPE_USER_ROOT,
        ];

        $permisos_gestion = [
            UsersModel::TYPE_USER_ROOT,
        ];

        $permisos_listado = [
            UsersModel::TYPE_USER_ADMIN_GRAL,
            UsersModel::TYPE_USER_ROOT,
        ];

        //General
        $routes[] = new Route("[$startRoute]", static::class . ':indexView', self::$prefixEntity, 'GET', true, null, $permisos_listado);
        $routes[] = new Route("/generate-lang-file[/]", static::class . ':generateLangFile', self::$prefixEntity . '-generate-lang-file', 'GET', true, null, $permisos_traducciones);

        $group->register($routes);

        //Regiones
        $group->register(
            self::genericManageRoutesRegions($startRoute, self::$prefixEntity, Region::class, 'regions', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //Country
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, Country::class, 'countries', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //State
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, State::class, 'states', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //City
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, City::class, 'cities', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );
        //Point
        $group->register(
            self::genericManageRoutes($startRoute, self::$prefixEntity, Point::class, 'points', [
                'edit' => $permisos_gestion,
                'list' => $permisos_listado,
            ])
        );

        return $group;
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
                'GET'
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
                'GET'
            ),
        ];
    }

}
