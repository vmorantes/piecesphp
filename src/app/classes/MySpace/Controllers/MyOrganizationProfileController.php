<?php

/**
 * MyOrganizationProfileController.php
 */

namespace MySpace\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use MySpace\Exceptions\SafeException;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;

/**
 * MyOrganizationProfileController.
 *
 * @package     MySpace\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class MyOrganizationProfileController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'my-organization-profile';
    /**
     * @var string
     */
    protected static $baseRouteName = 'my-organization-profile-admin';

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = MySpaceLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/my-organization-profile');

        add_global_asset(MySpaceRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/my-organization-profile.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function myOrganizationProfileView(Request $request, Response $response, array $args)
    {

        $currentUser = getLoggedFrameworkUserOrFail();
        $adminUser = $currentUser;
        $organizationID = $adminUser->organization;
        $organizationMapper = null;

        //En true se muestra solo el segmento de asignación de administrador
        $setAdministratorForm = false;
        //Verificar si tiene privilegios superiores
        $hasSuperPrivileges = self::hasSuperPrivileges($currentUser);
        if ($hasSuperPrivileges) {
            $organizationMapper = self::organizationForSuperUser($args, $currentUser);
            //La organización sale de la URL, o es la propia sin ella: una que no existe es un 404, no un 500.
            if ($organizationMapper === null) {
                throw new NotFoundException($request, $response);
            }
            $organizationID = $organizationMapper->id;
            $organizationAdministratorID = self::administratorIDOf($organizationMapper);
            if ($organizationAdministratorID === null) {
                $setAdministratorForm = true;
            } else {
                $adminUser = new UserDataPackage($organizationAdministratorID);
            }
        }

        $title = __(self::LANG_GROUP, 'Perfil de organización');
        $description = __(self::LANG_GROUP, 'Gestionar');

        if ($organizationID !== null) {

            if (!$setAdministratorForm) {

                remove_imported_asset('locations');
                import_locations([], false, true);
                import_cropper();
                set_custom_assets([
                    MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/my-organization-profile.js'),
                ], 'js');

                //Se toma de la organización del administrado o de la ingresada por URL si es root (para organizaciones que fueron huérfanas de admin)
                $organizationMapper = $adminUser->type != UsersModel::TYPE_USER_ROOT ? $adminUser->organizationMapper : $organizationMapper;
                //Null solo sin organización, y el acceso ya la exige (_allowedRoute): aquí sería un fallo de verdad.
                if ($organizationMapper === null) {
                    throw new NotFoundException($request, $response);
                }
                $organizationAdminMapper = $organizationMapper->administrator;
                $organizationIDParam = $hasSuperPrivileges ? [
                    'organizationID' => $organizationID,
                ] : [];
                $action = self::routeName('actions-save-profile', $organizationIDParam);
                $actionChangeAdministrator = self::routeName('actions-change-administrator', $organizationIDParam);
                $optionsUsersAdministrators = array_to_html_options(array_map('escape_html', UsersModel::allOrganizationUsersCanBeAdminForSelect($organizationMapper->id)), self::administratorIDOf($organizationMapper));

                $data = [];
                $data['action'] = $action;
                $data['actionChangeAdministrator'] = $actionChangeAdministrator;
                $data['langGroup'] = self::LANG_GROUP;
                $data['organizationAdminMapper'] = $organizationAdminMapper;
                $data['organizationMapper'] = $organizationMapper;
                $data['optionsUsersAdministrators'] = $optionsUsersAdministrators;
                $data['title'] = $title;
                $data['description'] = $description;
                $data['breadcrumbs'] = get_breadcrumbs([
                    __(self::LANG_GROUP, 'Inicio') => [
                        'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                    ],
                    $title,
                ]);

                set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));
                $this->helpController->render('panel/layout/header');
                $this->render('my-organization-profile', $data);
                $this->helpController->render('panel/layout/footer');

            } else {

                remove_imported_asset('locations');
                set_custom_assets([
                    MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/my-organization-profile-assign-administrator.js'),
                ], 'js');

                $organizationIDParam = [
                    'organizationID' => $organizationID,
                ];
                $actionChangeAdministrator = self::routeName('actions-change-administrator', $organizationIDParam);
                $optionsUsersAdministratorsBase = UsersModel::allOrganizationUsersCanBeAdminForSelect($organizationID);
                $hasAdminOptions = count(array_filter(array_keys($optionsUsersAdministratorsBase), fn($e) => mb_strlen((string) $e) > 0)) > 0;
                $optionsUsersAdministrators = array_to_html_options(array_map('escape_html', $optionsUsersAdministratorsBase));

                $data = [];
                $data['organizationMapper'] = $organizationMapper;
                $data['actionChangeAdministrator'] = $actionChangeAdministrator;
                $data['langGroup'] = self::LANG_GROUP;
                $data['hasAdminOptions'] = $hasAdminOptions;
                $data['optionsUsersAdministrators'] = $optionsUsersAdministrators;
                $data['title'] = $title;
                $data['description'] = $description;
                $data['breadcrumbs'] = get_breadcrumbs([
                    __(self::LANG_GROUP, 'Inicio') => [
                        'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
                    ],
                    $title,
                ]);

                set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));
                $this->helpController->render('panel/layout/header');
                $this->render('my-organization-profile-assign-administrator', $data);
                $this->helpController->render('panel/layout/footer');
            }

            return $response;

        } else {
            return throw403($request);
        }

    }

    /**
     * Guardar datos de perfil
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function saveProfileAction(Request $request, Response $response, array $args)
    {
        $currentUser = getLoggedFrameworkUserOrFail();
        $adminUser = $currentUser;
        $organizationID = $currentUser->organization;
        //Verificar si tiene privilegios superiores
        if (self::hasSuperPrivileges($currentUser)) {
            $organizationMapper = self::organizationForSuperUser($args, $currentUser);
            if ($organizationMapper === null) {
                throw new NotFoundException($request, $response);
            }
            $organizationID = $organizationMapper->id;
            $organizationAdministratorID = self::administratorIDOf($organizationMapper);
            if ($organizationAdministratorID !== null) {
                $adminUser = new UserDataPackage($organizationAdministratorID);
            }
        }
        $organizationMapper = $adminUser->organizationMapper;
        if ($organizationID !== null && $organizationMapper !== null && self::administratorIDOf($organizationMapper) == $adminUser->id) {
            $parsedBody = $request->getParsedBody();
            $parsedBody['id'] = $organizationMapper->id;
            $request = $request->withParsedBody($parsedBody);
            $responseResult = (new OrganizationsController)->action($request, $response)->getRawJsonDataInserted();
            $responseResult->setValue('redirect', false);
            $responseResult->setValue('reload', false);
            return $response->withJson($responseResult);
        } else {
            return throw403($request);
        }
    }


    /**
     * Cambia el administrador de la organización mediante el cambio de tipo de usuario
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function changeOrganizationAdminAction(Request $request, Response $response, array $args)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'newUserAdminID',
                null,
                function ($value) {
                    return Validator::isInteger($value) && UsersModel::getBy($value) !== null;
                },
                false,
                function ($value) {
                    return ($value);
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Cambio de persona encargada'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);

        //Mensajes de respuesta
        $successEditMessage = __(self::LANG_GROUP, 'Datos guardados.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario
            /**
             * @var int $newUserAdminID
             */
            $newUserAdminID = $expectedParameters->getValue('newUserAdminID');
            $newAdminUserExists = UsersModel::getBy($newUserAdminID) !== null;

            $currentUser = getLoggedFrameworkUserOrFail();
            $adminUser = $currentUser;
            $organizationMapper = null;
            $organizationID = $adminUser->organization;
            $specialSuperiorPrivileges = self::hasSuperPrivileges($currentUser);

            //Verificar si tiene privilegios superiores
            if (!$specialSuperiorPrivileges) {

                $organizationMapper = $adminUser->organizationMapper;
                if ($organizationID !== null && $organizationMapper !== null && self::administratorIDOf($organizationMapper) == $adminUser->id) {
                    $organizationID = $organizationMapper->id;
                } else {
                    return throw403($request);
                }

            } else {
                $organizationMapper = self::getOrganizationFromParams($args);
                if ($organizationMapper === null) {
                    return throw403($request);
                }
            }

            if (!$newAdminUserExists) {
                throw new SafeException(__(self::LANG_GROUP, 'El usuario seleccionado como encargado no existe'));
            }

            try {

                $organizationMapper->administrator = $newUserAdminID;
                $updated = $organizationMapper->update();
                $resultOperation->setSuccessOnSingleOperation($updated);

                if ($updated) {

                    $resultOperation
                        ->setMessage($successEditMessage)
                        ->setValue('reload', true);

                } else {

                    $resultOperation->setMessage($unknowErrorMessage);

                }

            } catch (SafeException $e) {

                $resultOperation->setMessage($e->getMessage());

            } catch (\Exception $e) {
                $reference = log_exception($e);

                $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));

            }

        } catch (SafeException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (MissingRequiredParameterException | InvalidParameterValueException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        } catch (\Exception $e) {
            $reference = log_exception($e);

            $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));

        }

        return $response->withJson($resultOperation);
    }

    /**
     * Verifica si el usuario tiene privilegios de edición sobre cualquier perfil de organización
     * @param UserDataPackage $user
     * @return bool
     */
    public static function hasSuperPrivileges(UserDataPackage $user)
    {
        return in_array($user->type, OrganizationMapper::PROFILE_EDITOR_SUPER);
    }

    /**
     * El id del encargado de una organización, o null si no tiene. `administrator` puede llegar como el modelo,
     * como su id o vacío: el `->id` directo reventaba con los dos últimos.
     *
     * @param OrganizationMapper $organization
     * @return int|null
     */
    protected static function administratorIDOf(OrganizationMapper $organization): ?int
    {
        $administrator = $organization->administrator;
        if ($administrator instanceof UsersModel) {
            return $administrator->id !== null ? (int) $administrator->id : null;
        }
        return is_int($administrator) ? $administrator : null;
    }

    /**
     * La organización que abre quien puede editar cualquiera: la de la dirección y, sin ella, la suya propia
     * (pendientes.md 374.5). Null si la de la dirección no existe, o si no viene ninguna y él no tiene organización.
     *
     * @param array $args
     * @param UserDataPackage $user
     * @return OrganizationMapper|null
     */
    protected static function organizationForSuperUser(array $args, UserDataPackage $user): ?OrganizationMapper
    {
        if (($args['organizationID'] ?? null) !== null) {
            return self::getOrganizationFromParams($args);
        }
        $own = $user->organizationMapper;
        return $own !== null && $own->id !== null ? $own : null;
    }

    /**
     * Devuelve la organización a partir del parámetro organizationID
     * @param array $args
     * @return OrganizationMapper|null
     */
    public static function getOrganizationFromParams(array $args)
    {
        $organizationID = $args['organizationID'] ?? null;
        $organizationID = Validator::isInteger($organizationID) ? (int) $organizationID : -1;
        $organizationMapper = new OrganizationMapper($organizationID);
        return $organizationMapper->id !== null ? $organizationMapper : null;
    }

    /**
     * Devuelve el administrador de la organización
     * @param int $organizationID
     * @return int|null
     */
    public static function getOrganizationAdministratorID(int $organizationID)
    {
        $organizationAdministratorID = null;
        $organizationRecord = OrganizationMapper::getBy($organizationID, 'id');
        if ($organizationRecord !== null) {
            $organizationMeta = json_decode($organizationRecord->meta);
            $organizationMeta = is_object($organizationMeta) ? $organizationMeta : new \stdClass;
            $organizationAdministratorID = property_exists($organizationMeta, 'administrator') ? $organizationMeta->administrator : null;
        }
        return $organizationAdministratorID;
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(trim($name, '/'), $data, $mode, $format);
    }

    /**
     * Verificar si una ruta es permitida y determinar pasos para permitirla o no
     *
     * @param string $name
     * @param string $route
     * @param array $params
     * @return bool
     */
    protected static function _allowedRoute(string $name, string $route, array $params = [])
    {

        $getParam = function ($paramName) use ($params) {
            $_POST = isset($_POST) && is_array($_POST) ? $_POST : [];
            $_GET = isset($_GET) && is_array($_GET) ? $_GET : [];
            $paramValue = $params[$paramName] ?? null;
            $paramValue ??= $_GET[$paramName] ?? null;
            $paramValue ??= $_POST[$paramName] ?? null;
            return $paramValue;
        };

        $allow = $route !== '';

        if ($allow) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser !== null) {

                $organizationID = $currentUser->organization;
                $organizationMapper = $currentUser->organizationMapper;
                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;
                $isRoot = $currentUserType == UsersModel::TYPE_USER_ROOT;

                $adminProfileRoutes = [
                    'my-organization-profile',
                    'actions-save-profile',
                    'actions-change-administrator',
                ];

                if (in_array($name, $adminProfileRoutes)) {

                    $allow = false;

                    //Privilegios especiales
                    $currentUserCanEditAllProfiles = in_array($currentUserType, OrganizationMapper::PROFILE_EDITOR_SUPER);
                    if ($currentUserCanEditAllProfiles) {
                        $allow = true;
                        return $allow;
                    }

                    //Privilegios regulares
                    if ($organizationID === null) {
                        return false;
                    }

                    $currentUserCanEditProfile = in_array($currentUserType, OrganizationMapper::PROFILE_EDITOR);
                    $currentUserIsOrganizationAdministrator = $organizationMapper !== null && self::administratorIDOf($organizationMapper) == $currentUserID;

                    $allow = $currentUserIsOrganizationAdministrator;

                }

            }

        }

        return $allow;
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {

        //Rutas
        $routes = [];

        $groupSegmentURL = $group->getGroupSegment();

        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = ($lastIsBar ? '' : '/') . self::$URLDirectory;

        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);
        $saveProfile = $allRoles;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route(
                "{$startRoute}[/[{organizationID}[/]]]",
                $classname . ':myOrganizationProfileView',
                self::$baseRouteName . '-my-organization-profile',
                'GET',
                true,
                null,
                $allRoles
            ),
            //JSON
            //──── POST ──────────────────────────────────────────────────────────────────────────────
            new Route( //Acción guardar el perfil
                "{$startRoute}/action/profile/save[/[{organizationID}[/]]]",
                $classname . ':saveProfileAction',
                self::$baseRouteName . '-actions-save-profile',
                'POST',
                true,
                null,
                $saveProfile
            ),
            new Route( //Acción cambiar encargado de organización
                "{$startRoute}/action/change-organization-administrator[/[{organizationID}[/]]]",
                $classname . ':changeOrganizationAdminAction',
                self::$baseRouteName . '-actions-change-administrator',
                'POST',
                true,
                null,
                $saveProfile
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
