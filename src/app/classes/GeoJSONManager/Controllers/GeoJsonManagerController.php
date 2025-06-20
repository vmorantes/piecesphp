<?php

/**
 * GeoJsonManagerController.php
 */

namespace GeoJSONManager\Controllers;

use ApplicationCalls\Mappers\ApplicationCallsMapper;
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
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
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
        } else if ($featuresType == FeaturesTypes::APPLICATION_CALLS->value) {
            $geometries = self::withApplicationCalls($geometries, $queryParams);
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
         * @var int[]|null $researchAreas
         * @var int[]|null $organizations
         */
        $search = array_key_exists('search', $params) ? $params['search'] : null;
        $researchAreas = array_key_exists('researchAreas', $params) ? $params['researchAreas'] : null;
        $organizations = array_key_exists('organizations', $params) ? $params['organizations'] : null;

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $where = [];
        $having = [
            "userStatus != " . UsersModel::STATUS_USER_INACTIVE,
        ];
        $table = UserProfileMapper::TABLE;

        $approvedValue = SystemApprovalsRoutes::ENABLE ? SystemApprovalsMapper::STATUS_APPROVED : null;
        if ($approvedValue !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "systemApprovalStatus = '{$approvedValue}'";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if ($search !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = [
                "UPPER(fullname) LIKE UPPER('%{$search}%')",
                "UPPER(fullLocation) LIKE UPPER('%{$search}%')",
                "UPPER(interestResearhAreasNames) LIKE UPPER('%{$search}%')",
            ];
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($researchAreas)) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = [];
            foreach ($researchAreas as $researchArea) {
                $critery[] = "JSON_CONTAINS(JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.interestResearhAreas')), {$researchArea})";
            }
            $critery = implode(' OR ', $critery);
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($organizations)) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = [];
            foreach ($organizations as $organization) {
                $critery[] = "organizationID = {$organization}";
            }
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }
        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $model = UserProfileMapper::model();
        $model->select(UserProfileMapper::fieldsToSelect());
        if ($whereString !== null) {
            $model->where($whereString);
        }
        if ($havingString !== null) {
            $model->having($havingString);
        }
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
         * @var int[]|null $researchAreas
         * @var int[]|null $organizations
         */
        $search = array_key_exists('search', $params) ? $params['search'] : null;
        $researchAreas = array_key_exists('researchAreas', $params) ? $params['researchAreas'] : null;
        $organizations = array_key_exists('organizations', $params) ? $params['organizations'] : null;

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $where = [
            "id != " . OrganizationMapper::INITIAL_ID_GLOBAL,
            'AND status IN (' . implode(',', [
                OrganizationMapper::ACTIVE,
                OrganizationMapper::PENDING_APPROVAL,
            ]) . ')',
        ];
        $having = [];
        $table = OrganizationMapper::TABLE;

        $approvedValue = SystemApprovalsRoutes::ENABLE ? SystemApprovalsMapper::STATUS_APPROVED : null;
        if ($approvedValue !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "systemApprovalStatus = '{$approvedValue}'";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if ($search !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = [
                "UPPER(name) LIKE UPPER('%{$search}%')",
                "UPPER(fullLocation) LIKE UPPER('%{$search}%')",
                "UPPER(interestResearhAreasNames) LIKE UPPER('%{$search}%')",
            ];
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($researchAreas)) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = [];
            foreach ($researchAreas as $researchArea) {
                $critery[] = "JSON_CONTAINS(JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.interestResearhAreas')), {$researchArea})";
            }
            $critery = implode(' OR ', $critery);
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($organizations)) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = [];
            foreach ($organizations as $organization) {
                $critery[] = "id = {$organization}";
            }
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }
        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $model = OrganizationMapper::model();
        $model->select(OrganizationMapper::fieldsToSelect());
        if ($whereString !== null) {
            $model->where($whereString);
        }
        if ($havingString !== null) {
            $model->having($havingString);
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

    public static function withApplicationCalls(FeaturesCollection $geometries, array $params = [])
    {

        /**
         * @var string|null $search
         * @var int[]|null $researchAreas
         * @var int[]|null $organizations
         * @var string[]|null $contentType
         * @var string[]|null $financingType
         * @var \DateTime|null $startDate
         * @var \DateTime|null $endDate
         */
        $search = array_key_exists('search', $params) ? $params['search'] : null;
        $researchAreas = array_key_exists('researchAreas', $params) ? $params['researchAreas'] : null;
        $organizations = array_key_exists('organizations', $params) ? $params['organizations'] : null;
        $contentType = array_key_exists('contentType', $params) ? $params['contentType'] : null;
        $financingType = array_key_exists('financingType', $params) ? $params['financingType'] : null;
        $startDate = array_key_exists('startDate', $params) ? $params['startDate'] : null;
        $endDate = array_key_exists('endDate', $params) ? $params['endDate'] : null;

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $where = [];
        $having = [];
        $table = ApplicationCallsMapper::TABLE;
        $fields = ApplicationCallsMapper::fieldsToSelect();

        //NOTE: Validación de criterios extraída de ApplicationCallsController::_all()
        $approvedValue = SystemApprovalsRoutes::ENABLE ? SystemApprovalsMapper::STATUS_APPROVED : null;
        if ($approvedValue !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "systemApprovalStatus = '{$approvedValue}'";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if ($search !== null) {
            $beforeOperator = !empty($having) ? $and : '';
            $titleField = ApplicationCallsMapper::fieldCurrentLangForSQL('title');
            $contentField = ApplicationCallsMapper::fieldCurrentLangForSQL('content');
            $fields[] = "{$titleField} AS titleForQuerySearch";
            $fields[] = "{$contentField} AS contentForQuerySearch";
            $critery = [
                "UPPER(titleForQuerySearch) LIKE UPPER('%{$search}%')",
                "UPPER(contentForQuerySearch) LIKE UPPER('%{$search}%')",
                "TRIM(UPPER(targetCountriesNames)) COLLATE utf8_general_ci LIKE TRIM(UPPER('%{$search}%'))",
            ];
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($researchAreas)) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = [];
            foreach ($researchAreas as $researchArea) {
                $critery[] = "JSON_CONTAINS(JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.interestResearhAreas')), {$researchArea})";
            }
            $critery = implode(' OR ', $critery);
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($organizations)) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = [];
            foreach ($organizations as $organization) {
                $critery[] = "organizationID = {$organization}";
            }
            $critery = implode(' OR ', $critery);
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($contentType)) {
            $beforeOperator = !empty($where) ? $and : '';
            $contentType = implode("','", $contentType);
            $critery = "{$table}.contentType IN ('{$contentType}')";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($financingType)) {
            $beforeOperator = !empty($where) ? $and : '';
            $financingType = implode("','", $financingType);
            $critery = "{$table}.financingType IN ('{$financingType}')";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        $startDateStr = $startDate !== null ? $startDate->format('Y-m-d 00:00:00') : '';
        $endDateStr = $endDate !== null ? $endDate->format('Y-m-d 00:00:00') : '';
        if ($startDate !== null && $endDate !== null) {
            $beforeOperator = !empty($where) ? $and : '';
            $startDateCritery = "DATE({$table}.startDate) >= '{$startDateStr}'";
            $endDateCritery = "DATE({$table}.endDate) <= '{$endDateStr}'";
            $critery = "({$startDateCritery}) AND ({$endDateCritery})";
            $where[] = "{$beforeOperator} ({$critery})";
        } else {
            if ($startDate !== null) {
                $beforeOperator = !empty($where) ? $and : '';
                $critery = "DATE({$table}.endDate) >= '{$startDateStr}' AND '{$startDateStr}' <= DATE({$table}.startDate)";
                $where[] = "{$beforeOperator} ({$critery})";
            }
            if ($endDate !== null) {
                $beforeOperator = !empty($where) ? $and : '';
                $critery = "DATE({$table}.startDate) <= '{$endDateStr}' AND DATE({$table}.endDate) >= '{$endDateStr}'";
                $where[] = "{$beforeOperator} ({$critery})";
            }
        }

        $now = \DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:00'));
        $now = $now->getTimestamp();
        $unixNowDate = "FROM_UNIXTIME({$now})";
        $startDateSQL = "{$table}.startDate";
        $endDateSQL = "{$table}.endDate";

        $beforeOperator = !empty($where) ? $and : '';
        $critery = "{$startDateSQL} <= {$unixNowDate} OR {$table}.startDate IS NULL";
        $where[] = "{$beforeOperator} ({$critery})";

        $beforeOperator = !empty($where) ? $and : '';
        $critery = "{$endDateSQL} > {$unixNowDate} OR {$table}.endDate IS NULL";
        $where[] = "{$beforeOperator} ({$critery})";

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }
        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $model = ApplicationCallsMapper::model();
        $model->select($fields);
        if ($whereString !== null) {
            $model->where($whereString);
        }
        if ($havingString !== null) {
            $model->having($havingString);
        }
        $model->execute();
        $result = $model->result();
        $usersProfilesByUserID = [];
        foreach ($result as $record) {

            if (!array_key_exists($record->createdBy, $usersProfilesByUserID)) {
                $usersProfilesByUserID[$record->createdBy] = UserProfileMapper::getProfile($record->createdBy);
            }
            $userProfile = $usersProfilesByUserID[$record->createdBy];

            if ($userProfile !== null) {
                $lat = $userProfile->latitude;
                $lng = $userProfile->longitude;
                if ($lat !== null && $lng !== null) {

                    $applicationCallMapper = new ApplicationCallsMapper($record->id);

                    //Agrego datos a la feature
                    $featureProperties = [
                        'name' => $applicationCallMapper->currentLangData('title'),
                        'pointHTML' => ContentNavigationHubController::view('contents/map-elements/profile-application-call-point', [
                            'mapper' => $applicationCallMapper,
                        ], false),
                        'cardHTML' => ContentNavigationHubController::view('contents/map-elements/profile-application-call-card', [
                            'mapper' => $applicationCallMapper,
                        ], false),
                    ];

                    //Crear feature
                    $point = new Point($lng, $lat);
                    $feature = GeoJSONFactory::getFeatureFromGeometry(new GeometryPackage($point), $featureProperties);

                    //Añadir feature
                    $geometries->append($feature);
                }
            }

        }

        return $geometries;
    }

    /**
     * Maneja la solicitud de características GeoJSON para contenidos
     *
     * Este método procesa los parámetros de la solicitud para filtrar y obtener
     * características GeoJSON basadas en:
     * - Términos de búsqueda
     * - Áreas de investigación seleccionadas
     * - Organizaciones específicas
     * - Tipos de contenido
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
                'researchAreas',
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
         * @var int[] $researchAreas
         * @var int[] $organizations
         * @var string[] $contentType
         * @var string[] $financingType
         * @var \DateTime|null $startDate
         * @var \DateTime|null $endDate
         */
        $search = $expectedParameters->getValue('search');
        $researchAreas = $expectedParameters->getValue('researchAreas');
        $organizations = $expectedParameters->getValue('organizations');
        $contentType = $expectedParameters->getValue('contentType');
        $financingType = $expectedParameters->getValue('financingType');
        $startDate = $expectedParameters->getValue('startDate');
        $endDate = $expectedParameters->getValue('endDate');

        return [
            'search' => $search,
            'researchAreas' => $researchAreas,
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

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = isset($params[$paramName]) ? $params[$paramName] : null;
            $paramValue = $paramValue !== null ? $paramValue : (isset($_GET[$paramName]) ? $_GET[$paramName] : null);
            $paramValue = $paramValue !== null ? $paramValue : (isset($_POST[$paramName]) ? $_POST[$paramName] : null);
            return $paramValue;
        };

        $allow = strlen($route) > 0;

        if ($allow) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;

                if ($name == 'SAMPLE') {
                    $allow = false;
                }

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
}
