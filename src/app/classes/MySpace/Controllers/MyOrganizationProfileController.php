<?php

/**
 * MyOrganizationProfileController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\Controllers\Util\OrganizationPreviousExperiencesController;
use MySpace\Exceptions\SafeException;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
use PiecesPHP\UserSystem\Profile\SubMappers\OrganizationPreviousExperiencesMapper;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * MyOrganizationProfileController.
 *
 * @package     MySpace\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class MyOrganizationProfileController extends AdminPanelController
{

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
     * @return void
     */
    public function myOrganizationProfileView(Request $request, Response $response, array $args)
    {

        $currentUser = getLoggedFrameworkUser();
        $adminUser = $currentUser;
        $organizationID = $adminUser->organization;
        $organizationMapper = null;

        //En true se muestra solo el segmento de asignación de administrador
        $setAdministratorForm = false;
        //Verificar si tiene privilegios superiores
        $hasSuperPrivileges = self::hasSuperPrivileges($currentUser);
        if ($hasSuperPrivileges) {
            $organizationMapper = self::getOrganizationFromParams($args);
            $organizationID = $organizationMapper !== null ? $organizationMapper->id : null;
            $organizationAdministratorID = $organizationMapper->administrator->id;
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
                    MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/experience-organization/delete-config.js'),
                    MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/my-organization-profile.js'),
                ], 'js');

                $organizationMapper = $adminUser->organizationMapper;
                $organizationAdminMapper = $organizationMapper->administrator;
                $organizationIDParam = $hasSuperPrivileges ? [
                    'organizationID' => $organizationID,
                ] : [];
                $action = self::routeName('actions-save-profile', $organizationIDParam);
                $actionExperience = self::routeName('actions-save-experience', $organizationIDParam);
                $actionChangeAdministrator = self::routeName('actions-change-administrator', $organizationIDParam);
                $dataTablesExperienceLink = self::routeName('datatables-experience', $organizationIDParam);
                $optionsUsersAdministrators = array_to_html_options(UsersModel::allOrganizationUsersCanBeAdminForSelect($organizationMapper->id), $organizationMapper->administrator->id);

                $data = [];
                $data['action'] = $action;
                $data['actionExperience'] = $actionExperience;
                $data['actionChangeAdministrator'] = $actionChangeAdministrator;
                $data['dataTablesExperienceLink'] = $dataTablesExperienceLink;
                $data['langGroup'] = self::LANG_GROUP;
                $data['organizationAdminMapper'] = $organizationAdminMapper;
                $data['organizationMapper'] = $organizationMapper;
                $data['optionsUsersAdministrators'] = $optionsUsersAdministrators;
                $data['title'] = $title;
                $data['description'] = $description;
                $data['breadcrumbs'] = get_breadcrumbs([
                    __(self::LANG_GROUP, 'Inicio') => [
                        'url' => get_route('admin'),
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
                $optionsUsersAdministrators = array_to_html_options($optionsUsersAdministratorsBase);

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
                        'url' => get_route('admin'),
                    ],
                    $title,
                ]);

                set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));
                $this->helpController->render('panel/layout/header');
                $this->render('my-organization-profile-assign-administrator', $data);
                $this->helpController->render('panel/layout/footer');
            }

        } else {
            throw403($request);
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
        $currentUser = getLoggedFrameworkUser();
        $adminUser = $currentUser;
        $organizationID = $currentUser->organization;
        //Verificar si tiene privilegios superiores
        if (self::hasSuperPrivileges($currentUser)) {
            $organizationMapper = self::getOrganizationFromParams($args);
            $organizationID = $organizationMapper !== null ? $organizationMapper->id : null;
            $organizationAdministratorID = $organizationMapper->administrator->id;
            if ($organizationAdministratorID !== null) {
                $adminUser = new UserDataPackage($organizationAdministratorID);
            }
        }
        if ($organizationID !== null && $adminUser->organizationMapper->administrator->id == $adminUser->id) {
            $organizationMapper = $adminUser->organizationMapper;
            $parsedBody = $request->getParsedBody();
            $parsedBody['id'] = $organizationMapper->id;
            $request = $request->withParsedBody($parsedBody);
            $responseResult = (new OrganizationsController)->action($request, $response)->getRawJsonDataInserted();
            $responseResult->setValue('redirect', false);
            $responseResult->setValue('reload', false);
            return $response->withJson($responseResult);
        } else {
            throw403($request);
        }
    }

    /**
     * Agregar experiencia
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function addExperienceAction(Request $request, Response $response, array $args)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'experienceType',
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'experienceName',
                null,
                function ($value) {
                    $isArray = is_array($value);
                    $valid = $isArray && !empty($value);
                    $allowedLangs = Config::get_allowed_langs();
                    if ($valid) {
                        foreach ($value as $lang => $text) {
                            if (!(is_string($lang) && is_string($text) && in_array($lang, $allowedLangs))) {
                                return false;
                            }
                        }
                    }
                    return $valid;
                },
                false,
                function ($value) {
                    $parsed = [];
                    $allowedLangs = Config::get_allowed_langs();
                    if (is_array($value)) {
                        foreach ($value as $lang => $text) {
                            if (is_string($lang) && is_string($text) && in_array($lang, $allowedLangs)) {
                                $parsed[$lang] = clean_string($text);
                            }
                        }
                    }
                    return $parsed;
                }
            ),
            new Parameter(
                'researchAreas',
                null,
                function ($value) {
                    $isArray = is_array($value);
                    $valid = $isArray && !empty($value);
                    if ($valid) {
                        foreach ($value as $i) {
                            if ($i instanceof InterestResearchAreasMapper) {
                                if ($i->id == null) {
                                    throw new SafeException(__(self::LANG_GROUP, 'El área de interés no es válida'));
                                }
                            } else if (!Validator::isInteger($i)) {
                                return false;
                            }
                        }
                    }
                    return $valid;
                },
                false,
                function ($value) {
                    return is_array($value) ? array_map(function ($e) {
                        if (Validator::isInteger($e)) {
                            return new InterestResearchAreasMapper($e);
                        }
                    }, $value) : [];
                }
            ),
            new Parameter(
                'institutionsParticipated',
                [],
                function ($value) {
                    $isArray = is_array($value);
                    $valid = $isArray || is_null($value);
                    if ($valid) {
                        foreach ($value as $i) {
                            if (!is_scalar($i)) {
                                return false;
                            }
                        }
                    }
                    return $valid;
                },
                true,
                function ($value) {
                    return is_array($value) ? array_map(function ($e) {
                        if (is_scalar($e)) {
                            return clean_string((string) $e);
                        } else {
                            return null;
                        }
                    }, $value) : [];
                }
            ),
            new Parameter(
                'country',
                null,
                function ($value) {
                    return Validator::isInteger($value);
                },
                false,
                function ($value) {
                    return Validator::isInteger($value) ? (int) $value : null;
                }
            ),
            new Parameter(
                'city',
                null,
                function ($value) {
                    return Validator::isInteger($value);
                },
                false,
                function ($value) {
                    return Validator::isInteger($value) ? (int) $value : null;
                }
            ),
            new Parameter(
                'startDate',
                null,
                function ($value) {
                    return Validator::isDate($value, 'Y-m-d');
                },
                false,
                function ($value) {
                    return $value instanceof \DateTime  ? $value : \DateTime::createFromFormat('Y-m-d', $value);
                }
            ),
            new Parameter(
                'endDate',
                null,
                function ($value) {
                    return Validator::isDate($value, 'Y-m-d');
                },
                false,
                function ($value) {
                    return $value instanceof \DateTime  ? $value : \DateTime::createFromFormat('Y-m-d', $value);
                }
            ),
            new Parameter(
                'description',
                null,
                function ($value) {
                    $isArray = is_array($value);
                    $valid = $isArray && !empty($value);
                    $allowedLangs = Config::get_allowed_langs();
                    if ($valid) {
                        foreach ($value as $lang => $text) {
                            if (!(is_string($lang) && is_string($text) && in_array($lang, $allowedLangs))) {
                                return false;
                            }
                        }
                    }
                    return $valid;
                },
                false,
                function ($value) {
                    $parsed = [];
                    $allowedLangs = Config::get_allowed_langs();
                    if (is_array($value)) {
                        foreach ($value as $lang => $text) {
                            if (is_string($lang) && is_string($text) && in_array($lang, $allowedLangs)) {
                                $parsed[$lang] = clean_string($text);
                            }
                        }
                    }
                    return $parsed;
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Experiencias'));
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
             * @var string $experienceType
             * @var array<string,string> $experienceName
             * @var InterestResearchAreasMapper[] $researchAreas
             * @var string[] $institutionsParticipated
             * @var int $country
             * @var int $city
             * @var \DateTime $startDate
             * @var \DateTime $endDate
             * @var array<string,string> $description
             */
            $experienceType = $expectedParameters->getValue('experienceType');
            $experienceName = $expectedParameters->getValue('experienceName');
            $researchAreas = $expectedParameters->getValue('researchAreas');
            $researchAreas = array_map(fn($e) => $e->id, $researchAreas);
            $institutionsParticipated = $expectedParameters->getValue('institutionsParticipated');
            $country = $expectedParameters->getValue('country');
            $city = $expectedParameters->getValue('city');
            $startDate = $expectedParameters->getValue('startDate');
            $endDate = $expectedParameters->getValue('endDate');
            $description = $expectedParameters->getValue('description');

            $currentUser = getLoggedFrameworkUser();
            $adminUser = $currentUser;
            $organizationID = $adminUser->organization;
            //Verificar si tiene privilegios superiores
            if (self::hasSuperPrivileges($currentUser)) {
                $organizationMapper = self::getOrganizationFromParams($args);
                $organizationID = $organizationMapper !== null ? $organizationMapper->id : null;
                $organizationAdministratorID = $organizationMapper->administrator->id;
                if ($organizationAdministratorID !== null) {
                    $adminUser = new UserDataPackage($organizationAdministratorID);
                }
            }

            if ($organizationID !== null && $adminUser->organizationMapper->administrator->id == $adminUser->id) {
                $organizationID = $adminUser->organizationMapper->id;
            } else {
                throw403($request);
            }

            try {

                $mapper = new OrganizationPreviousExperiencesMapper();
                $lang = Config::get_default_lang();

                $mapper->profile = $organizationID;
                $mapper->setLangData($lang, 'experienceType', $experienceType);
                $mapper->setLangData($lang, 'researchAreas', $researchAreas);
                $mapper->setLangData($lang, 'institutionsParticipated', $institutionsParticipated);
                $mapper->setLangData($lang, 'country', $country);
                $mapper->setLangData($lang, 'city', $city);
                $mapper->setLangData($lang, 'startDate', $startDate);
                $mapper->setLangData($lang, 'endDate', $endDate);
                foreach ($experienceName as $lang => $text) {
                    $mapper->setLangData($lang, 'experienceName', $text);
                }
                foreach ($description as $lang => $text) {
                    $mapper->setLangData($lang, 'description', $text);
                }

                $saved = $mapper->save();
                $resultOperation->setSuccessOnSingleOperation($saved);

                if ($saved) {

                    $resultOperation
                        ->setMessage($successEditMessage)
                        ->setValue('reload', false)
                        ->setValue('redirect', false)
                        ->setValue('redirect_to', self::routeName('list'));

                } else {

                    $resultOperation->setMessage($unknowErrorMessage);

                }

            } catch (SafeException $e) {

                $resultOperation->setMessage($e->getMessage());

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

        } catch (SafeException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (MissingRequiredParamaterException | InvalidParameterValueException | \Exception $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $response->withJson($resultOperation);
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

            $currentUser = getLoggedFrameworkUser();
            $adminUser = $currentUser;
            $organizationMapper = null;
            $organizationID = $adminUser->organization;
            $specialSuperiorPrivileges = self::hasSuperPrivileges($currentUser);

            //Verificar si tiene privilegios superiores
            if (!$specialSuperiorPrivileges) {

                if ($organizationID !== null && $adminUser->organizationMapper->administrator->id == $adminUser->id) {
                    $organizationID = $adminUser->organizationMapper->id;
                    $organizationMapper = $adminUser->organizationMapper;
                } else {
                    throw403($request);
                }

            } else {
                $organizationMapper = self::getOrganizationFromParams($args);
                if ($organizationMapper === null) {
                    throw403($request);
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

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

        } catch (SafeException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (MissingRequiredParamaterException | InvalidParameterValueException | \Exception $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

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
     * Devuelve la organización a partir del parámetro organizationID
     * @param array $args
     * @return OrganizationMapper|null
     */
    public static function getOrganizationFromParams(array $args)
    {
        $organizationID = array_key_exists('organizationID', $args) ? $args['organizationID'] : null;
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

                $organizationID = $currentUser->organization;
                $organizationMapper = $currentUser->organizationMapper;
                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;

                $adminProfileRoutes = [
                    'my-organization-profile',
                    'actions-save-profile',
                    'actions-save-experience',
                    'actions-delete-experience',
                    'datatables-experience',
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

                    //Privelegios regulares
                    if ($organizationID === null) {
                        return false;
                    }

                    $currentUserCanEditProfile = in_array($currentUserType, OrganizationMapper::PROFILE_EDITOR);
                    $currentUserIsOrganizationAdministrator = $organizationMapper->administrator->id == $currentUserID;

                    if ($name == 'actions-delete-experience') {

                        $id = ($getParam)('id');
                        $experienceRecord = OrganizationPreviousExperiencesMapper::getBy($id, 'id');
                        $canEditProfile = $currentUserCanEditProfile && $experienceRecord->profile == $organizationID;

                        if ($canEditProfile || in_array($currentUserType, OrganizationPreviousExperiencesMapper::CAN_DELETE_ALL)) {
                            $allow = true;
                        }

                    } else {
                        $allow = $currentUserIsOrganizationAdministrator;
                    }

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
            new Route( //Datos para datatables de experiencia
                "{$startRoute}/datatables/experience[/[{organizationID}[/]]]",
                OrganizationPreviousExperiencesController::class . ':dataTables',
                self::$baseRouteName . '-datatables-experience',
                'GET',
                true,
                null,
                $allRoles
            ),
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
            new Route( //Acción agregar experiencia
                "{$startRoute}/action/experience/save[/[{organizationID}[/]]]",
                $classname . ':addExperienceAction',
                self::$baseRouteName . '-actions-save-experience',
                'POST',
                true,
                null,
                $saveProfile
            ),
            new Route( //Acción eliminar experiencia
                "{$startRoute}/action/experience/delete/{id}[/]",
                OrganizationPreviousExperiencesController::class . ':toDelete',
                self::$baseRouteName . '-actions-delete-experience',
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
