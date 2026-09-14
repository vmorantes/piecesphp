<?php

/**
 * GeoJsonManagerController.php
 */

namespace GeoJSONManager\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use ContentNavigationHub\Controllers\ContentNavigationHubController;
use GeoJSONManager\Enums\FeaturesTypes;
use GeoJSONManager\GeoJsonManagerLang;
use GeoJSONManager\GeoJsonManagerRoutes;
use GeoJSONManager\Util\FeaturesCollection;
use GeoJSONManager\Util\GeoJSONFactory;
use GeoJSONManager\Util\GeometryPackage;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\GeoJson\GeoJson;
use PiecesPHP\GeoJson\Geometry\Point;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;

/**
 * GeoJsonManagerController.
 *
 * @package     GeoJSONManager\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class GeoJsonManagerController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'geojson-manager-routes';
    /**
     * @var string
     */
    protected static $baseRouteName = 'geojson-manager-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_VIEW_DIR = '';
    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = GeoJsonManagerLang::LANG_GROUP;

    const ENABLE_CACHE = true;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(GeoJsonManagerRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function contentsGeoJsonFeatures(Request $request, Response $response)
    {
        $featuresType = $request->getQueryParam('featuresType');
        $queryParams = self::handleRequestContentsGeoJsonFeaturesParam($request);

        $geometries = new FeaturesCollection();
        if ($featuresType == FeaturesTypes::PROFILES->value) {
            $geometries = self::withPersonsProfiles($geometries, $queryParams);
            $geometries = self::withOrganizationsProfiles($geometries, $queryParams);
        }
        $geoJSON = GeoJSONFactory::getGeoJsonFromGeometries($geometries);

        return $response->withJson($geoJSON);
    }

    /**
     * Agrega perfiles de personas a la colección de geometrías
     *
     * Este método obtiene los perfiles de usuarios aprobados y los agrega como puntos
     * en la colección de geometrías. Para cada perfil:
     * - Verifica que tenga coordenadas válidas
     * - Aplica un offset aleatorio a las coordenadas para evitar superposición
     * - Agrega propiedades como nombre y HTML para visualización
     *
     * @param FeaturesCollection $geometries Colección de geometrías a la que se agregarán los perfiles
     * @param array $params
     * @return FeaturesCollection Colección actualizada con los perfiles agregados
     */
    public static function withPersonsProfiles(FeaturesCollection $geometries, array $params = [])
    {

        /**
         * @var string|null $search
         * @var int[]|null $organizations
         */
        $search = $params['search'] ?? null;
        $organizations = $params['organizations'] ?? null;

        $whereString = null;
        $where = [];
        $table = UserProfileMapper::TABLE;

        $havingCriteria = [
            new HavingItem('userStatus', HavingItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_INACTIVE, HavingItem::AND_OPERATOR),
            new HavingItem('userStatus', HavingItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_DELETED, HavingItem::AND_OPERATOR),
        ];

        $approvedValue = SystemApprovalsRoutes::ENABLE ? SystemApprovalsMapper::STATUS_APPROVED : null;
        if ($approvedValue !== null) {
            $havingCriteria[] = new HavingItem('systemApprovalStatus', HavingItem::EQUAL_OPERATOR, $approvedValue, HavingItem::AND_OPERATOR);
        }

        $havingSegment = new HavingSegment($havingCriteria);

        if ($search !== null) {
            //Valor de la petición: va por marcador.
            $havingSegment->addGroup(self::searchHavingGroup(['fullname', 'fullLocation'], $search));
        }

        if (!empty($organizations)) {
            $havingSegment->addGroup(self::idsHavingGroup('organizationID', $organizations));
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $model = UserProfileMapper::model();
        $model->select(UserProfileMapper::fieldsToSelect());
        if ($whereString !== null) {
            $model->where($whereString);
        }
        $model->having($havingSegment);
        $model->execute();
        $result = $model->result();
        foreach ($result as $profile) {
            $lat = $profile->latitude;
            $lng = $profile->longitude;
            if ($lat !== null && $lng !== null) {

                //Agrego datos a la feature
                $featureProperties = [
                    'name' => $profile->fullname,
                    'pointHTML' => ContentNavigationHubController::view('contents/map-elements/profile-person-point', [
                        'element' => $profile,
                    ], false),
                    'cardHTML' => ContentNavigationHubController::view('contents/map-elements/profile-person-card', [
                        'element' => $profile,
                    ], false),
                ];

                //Crear feature
                $point = new Point($lng, $lat);
                $feature = GeoJSONFactory::getFeatureFromGeometry(new GeometryPackage($point), $featureProperties);

                //Añadir feature
                $geometries->append($feature);
            }
        }

        return $geometries;
    }

    /**
     * Agrega perfiles de organizaciones a la colección de geometrías GeoJSON
     *
     * Este método obtiene las organizaciones aprobadas y las agrega como features
     * a la colección de geometrías proporcionada. Para cada organización:
     * - Verifica que tenga coordenadas válidas en sus metadatos
     * - Aplica un offset aleatorio a las coordenadas para evitar superposición
     * - Agrega propiedades como nombre y HTML para visualización
     *
     * @param FeaturesCollection $geometries Colección de geometrías GeoJSON donde se agregarán los puntos
     * @return FeaturesCollection La colección de geometrías con los puntos de organizaciones agregados
     */
    public static function withOrganizationsProfiles(FeaturesCollection $geometries, array $params = [])
    {

        /**
         * @var string|null $search
         * @var int[]|null $organizations
         */
        $search = $params['search'] ?? null;
        $organizations = $params['organizations'] ?? null;

        $whereString = null;
        $where = [
            "id != " . OrganizationMapper::INITIAL_ID_GLOBAL,
            'AND status IN (' . implode(',', [
                OrganizationMapper::ACTIVE,
                OrganizationMapper::PENDING_APPROVAL,
            ]) . ')',
        ];
        $table = OrganizationMapper::TABLE;

        $havingCriteria = [];

        $approvedValue = SystemApprovalsRoutes::ENABLE ? SystemApprovalsMapper::STATUS_APPROVED : null;
        if ($approvedValue !== null) {
            $havingCriteria[] = new HavingItem('systemApprovalStatus', HavingItem::EQUAL_OPERATOR, $approvedValue, HavingItem::AND_OPERATOR);
        }

        $havingSegment = new HavingSegment($havingCriteria);

        if ($search !== null) {
            //Valor de la petición: va por marcador.
            $havingSegment->addGroup(self::searchHavingGroup(['name', 'fullLocation'], $search));
        }

        if (!empty($organizations)) {
            $havingSegment->addGroup(self::idsHavingGroup('id', $organizations));
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $model = OrganizationMapper::model();
        $model->select(OrganizationMapper::fieldsToSelect());
        if ($whereString !== null) {
            $model->where($whereString);
        }
        if ($havingSegment->countCriteria() > 0) {
            $model->having($havingSegment);
        }
        $model->execute();
        $result = $model->result();
        foreach ($result as $profile) {
            $metaData = json_decode($profile->meta);
            $lat = property_exists($metaData, 'latitude') ? $metaData->latitude : null;
            $lng = property_exists($metaData, 'longitude') ? $metaData->longitude : null;
            if ($lat !== null && $lng !== null) {

                $orgMapper = new OrganizationMapper($profile->id);

                //Agrego datos a la feature
                $featureProperties = [
                    'name' => $orgMapper->currentLangData('name'),
                    'pointHTML' => ContentNavigationHubController::view('contents/map-elements/profile-org-point', [
                        'mapper' => $orgMapper,
                    ], false),
                    'cardHTML' => ContentNavigationHubController::view('contents/map-elements/profile-org-card', [
                        'mapper' => $orgMapper,
                    ], false),
                ];

                //Crear feature
                $point = new Point($lng, $lat);
                $feature = GeoJSONFactory::getFeatureFromGeometry(new GeometryPackage($point), $featureProperties);

                //Añadir feature
                $geometries->append($feature);
            }
        }

        return $geometries;
    }

    /**
     * `(UPPER(a) LIKE UPPER(:x) OR UPPER(b) LIKE UPPER(:y))`, con el valor de la búsqueda por marcador.
     *
     * @param string[] $fields
     * @param string $search
     * @return HavingItemGroup
     */
    protected static function searchHavingGroup(array $fields, string $search): HavingItemGroup
    {
        $criteria = [];
        foreach ($fields as $field) {
            $criteria[] = new HavingItem("UPPER({$field})", HavingItem::LIKE_OPERATOR, "%{$search}%", HavingItem::OR_OPERATOR, 'UPPER(' . HavingItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')');
        }
        //EL ULTIMO FIJA `AND`: un grupo se une a lo que venga detras con el operador de su ultimo criterio.
        $criteria[count($criteria) - 1]->setAfterOperator(HavingItem::AND_OPERATOR);
        return new HavingItemGroup($criteria);
    }

    /**
     * `(campo = :a OR campo = :b ...)` para una lista de ids que el parser ya dejo en enteros.
     *
     * @param string $field
     * @param int[] $ids
     * @return HavingItemGroup
     */
    protected static function idsHavingGroup(string $field, array $ids): HavingItemGroup
    {
        $criteria = [];
        foreach ($ids as $id) {
            $criteria[] = new HavingItem($field, HavingItem::EQUAL_OPERATOR, $id, HavingItem::OR_OPERATOR);
        }
        $criteria[count($criteria) - 1]->setAfterOperator(HavingItem::AND_OPERATOR);
        return new HavingItemGroup($criteria);
    }

    /**
     * Maneja la solicitud de características GeoJSON de perfiles
     *
     * Este método procesa los parámetros de la solicitud para filtrar y obtener
     * características GeoJSON basadas en:
     * - Términos de búsqueda
     * - Organizaciones específicas
     *
     * @param Request $request La solicitud HTTP con los parámetros de filtrado
     * @return mixed Las características GeoJSON filtradas según los parámetros
     */
    public static function handleRequestContentsGeoJsonFeaturesParam(Request $request)
    {
        $expectedParameters = new Parameters([
            new Parameter(
                'search',
                null,
                function ($value) {
                    return is_scalar($value) && mb_strlen((string) $value) > 0;
                },
                true,
                function ($value) {
                    return (string) $value;
                }
            ),
            new Parameter(
                'organizations',
                [],
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return is_array($value);
                },
                true,
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return array_map(fn($e) => is_scalar($e) ? (
                        Validator::isInteger($e) ? (int) $e : -1
                    ) : -1, $value);
                }
            ),
            new Parameter(
                'contentType',
                [],
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return is_array($value);
                },
                true,
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return array_map(fn($e) => is_scalar($e) ? (string) $e : '-1', $value);
                }
            ),
            new Parameter(
                'financingType',
                [],
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return is_array($value);
                },
                true,
                function ($value) {
                    $value = !is_array($value) ? [$value] : $value;
                    return array_map(fn($e) => is_scalar($e) ? (string) $e : '-1', $value);
                }
            ),
            new Parameter(
                'startDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y', $value);
                }
            ),
            new Parameter(
                'endDate',
                null,
                function ($value) {
                    return $value === null || Validator::isDate($value, 'd-m-Y');
                },
                true,
                function ($value) {
                    return $value === null ? $value : \DateTime::createFromFormat('d-m-Y', $value);
                }
            ),
        ]);

        $expectedParameters->setInputValues($request->getQueryParams());
        $expectedParameters->validate();

        /**
         * @var string $search
         * @var int[] $organizations
         * @var string[] $contentType
         * @var string[] $financingType
         * @var \DateTime|null $startDate
         * @var \DateTime|null $endDate
         */
        $search = $expectedParameters->getValue('search');
        $organizations = $expectedParameters->getValue('organizations');
        $contentType = $expectedParameters->getValue('contentType');
        $financingType = $expectedParameters->getValue('financingType');
        $startDate = $expectedParameters->getValue('startDate');
        $endDate = $expectedParameters->getValue('endDate');

        return [
            'search' => $search,
            'organizations' => $organizations,
            'contentType' => $contentType,
            'financingType' => $financingType,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        $name = mb_strlen(self::BASE_VIEW_DIR) > 0 ? self::BASE_VIEW_DIR . '/' . trim($name, '/') : trim($name, '/');
        return parent::render($name, $data, $mode, $format);
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
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        //Permisos
        $list = $allRoles;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //JSON
            new Route( //Features de perfiles
                "{$startRoute}/contents-geojson-features[/]",
                $classname . ':contentsGeoJsonFeatures',
                self::$baseRouteName . '-contents-geojson-features',
                'GET',
                true,
                null,
                $list
            ),
        ];

        $group->register($routes);

        $group->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoute $request, $handler) {
            return (new DefaultAccessControlModules(self::$baseRouteName . '-', function (string $name, array $params) {
                return self::routeName($name, $params);
            }))->getResponse($request, $handler);
        });

        return $group;
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
