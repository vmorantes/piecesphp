<?php

/**
 * MyProfileController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\Controllers\Util\PreviousExperiencesController;
use MySpace\Controllers\Util\ProfileTasksUtilities;
use MySpace\Exceptions\SafeException;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
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
use PiecesPHP\UserSystem\Profile\SubMappers\PreviousExperiencesMapper;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

/**
 * MyProfileController.
 *
 * @package     MySpace\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class MyProfileController extends AdminPanelController
{

    /**
     * @var string
     */
    protected static $URLDirectory = 'my-profile';
    /**
     * @var string
     */
    protected static $baseRouteName = 'my-profile-admin';

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

        $this->setInstanceViewDir(__DIR__ . '/../Views/my-profile');

        add_global_asset(MySpaceRoutes::staticRoute('globals-vars.css'), 'css');
        add_global_asset(MySpaceRoutes::staticRoute(self::BASE_CSS_DIR . '/my-profile.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function myProfileView(Request $request, Response $response)
    {
        remove_imported_asset('locations');
        import_locations([], false, true);
        set_custom_assets([
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/experience/delete-config.js'),
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/my-profile.js'),
        ], 'js');

        $currentUser = getLoggedFrameworkUser();
        $action = self::routeName('actions-save-profile');
        $actionExperience = self::routeName('actions-save-experience');
        $dataTablesExperienceLink = self::routeName('datatables-experience');

        $title = __(self::LANG_GROUP, 'Mi perfil');
        $description = __(self::LANG_GROUP, 'Gestionar');
        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['action'] = $action;
        $data['actionExperience'] = $actionExperience;
        $data['dataTablesExperienceLink'] = $dataTablesExperienceLink;
        $data['langGroup'] = self::LANG_GROUP;
        $data['currentUser'] = $currentUser;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['breadcrumbs'] = get_breadcrumbs([
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => get_route('admin'),
            ],
            $title,
        ]);

        $this->helpController->render('panel/layout/header');
        if (in_array($currentUser->type, UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE)) {
            $this->render('my-profile', $data);
        }
        $this->helpController->render('panel/layout/footer');

    }

    /**
     * Guardar datos de perfil
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function saveProfileAction(Request $request, Response $response)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'jobPosition',
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
                'phoneCode',
                null,
                function ($value) {
                    return (is_string($value) && mb_strlen(trim($value)) > 0) || is_null($value);
                },
                true,
                function ($value) {
                    return !is_null($value) ? clean_string($value) : null;
                }
            ),
            new Parameter(
                'phoneNumber',
                null,
                function ($value) {
                    return (is_string($value) && mb_strlen(trim($value)) > 0) || is_null($value);
                },
                true,
                function ($value) {
                    return !is_null($value) ? clean_string($value) : null;
                }
            ),
            new Parameter(
                'nationality',
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
                'linkedinLink',
                null,
                function ($value) {
                    return (is_string($value) && mb_strlen(trim($value)) > 0) || is_null($value);
                },
                true,
                function ($value) {
                    return !is_null($value) ? clean_string($value) : null;
                }
            ),
            new Parameter(
                'websiteLink',
                null,
                function ($value) {
                    return (is_string($value) && mb_strlen(trim($value)) > 0) || is_null($value);
                },
                true,
                function ($value) {
                    return !is_null($value) ? clean_string($value) : null;
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
                'latitude',
                null,
                function ($value) {
                    return Validator::isDouble($value);
                },
                false,
                function ($value) {
                    return Validator::isDouble($value) ? (double) $value : null;
                }
            ),
            new Parameter(
                'longitude',
                null,
                function ($value) {
                    return Validator::isDouble($value);
                },
                false,
                function ($value) {
                    return Validator::isDouble($value) ? (double) $value : null;
                }
            ),
            new Parameter(
                'interestResearhAreas',
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
                'affiliatedInstitutions',
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
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Perfil'));
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
             * @var string $jobPosition
             * @var string|null $phoneCode
             * @var string|null $phoneNumber
             * @var string $nationality
             * @var string|null $linkedinLink
             * @var string|null $websiteLink
             * @var int $country
             * @var int $city
             * @var double $latitude
             * @var double $longitude
             * @var InterestResearchAreasMapper[] $interestResearhAreas
             * @var string[] $affiliatedInstitutions
             */
            $id = getLoggedFrameworkUser()->id;
            $jobPosition = $expectedParameters->getValue('jobPosition');
            $phoneCode = $expectedParameters->getValue('phoneCode');
            $phoneNumber = $expectedParameters->getValue('phoneNumber');
            $nationality = $expectedParameters->getValue('nationality');
            $linkedinLink = $expectedParameters->getValue('linkedinLink');
            $websiteLink = $expectedParameters->getValue('websiteLink');
            $country = $expectedParameters->getValue('country');
            $city = $expectedParameters->getValue('city');
            $latitude = $expectedParameters->getValue('latitude');
            $longitude = $expectedParameters->getValue('longitude');
            $interestResearhAreas = $expectedParameters->getValue('interestResearhAreas');
            $affiliatedInstitutions = $expectedParameters->getValue('affiliatedInstitutions');

            //NOTE: Esto debería ser provisional
            //Opcional: Modifico arbitrariamente las coordenadas
            $maxOffset = 0.6000;
            $latOffset = (mt_rand(-1000, 1000) / 10000) * $maxOffset;
            $lngOffset = (mt_rand(-1000, 1000) / 10000) * $maxOffset;
            $latitude = $latitude + $latOffset;
            $longitude = $longitude + $lngOffset;

            try {

                $mapper = UserProfileMapper::getProfile($id);
                $lang = Config::get_default_lang();

                $mapper->setLangData($lang, 'jobPosition', $jobPosition);
                $mapper->setLangData($lang, 'phoneCode', $phoneCode);
                $mapper->setLangData($lang, 'phoneNumber', $phoneNumber);
                $mapper->setLangData($lang, 'nationality', $nationality);
                $mapper->setLangData($lang, 'linkedinLink', $linkedinLink);
                $mapper->setLangData($lang, 'websiteLink', $websiteLink);
                $mapper->setLangData($lang, 'country', $country);
                $mapper->setLangData($lang, 'city', $city);
                $mapper->setLangData($lang, 'latitude', $latitude);
                $mapper->setLangData($lang, 'longitude', $longitude);
                $mapper->interestResearhAreas = $interestResearhAreas;
                $mapper->affiliatedInstitutions = $affiliatedInstitutions;

                $updated = $mapper->update();
                $resultOperation->setSuccessOnSingleOperation($updated);

                if ($updated) {

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
     * Agregar experiencia
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function addExperienceAction(Request $request, Response $response)
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

            try {

                $mapper = new PreviousExperiencesMapper();
                $lang = Config::get_default_lang();

                $mapper->profile = getLoggedFrameworkUser()->profile->id;
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

                $currentUserType = $currentUser->type;
                $currentUserID = $currentUser->id;

                if ($name == 'actions-delete-experience') {

                    $allow = false;
                    $id = ($getParam)('id');
                    $experienceRecord = PreviousExperiencesMapper::getBy($id, 'id');
                    $profileID = $currentUser->profile->id;
                    $matchProfile = $profileID == $experienceRecord->profile;

                    if ($matchProfile || in_array($currentUserType, PreviousExperiencesMapper::CAN_DELETE_ALL)) {
                        $allow = true;
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
        //Tareas globales del módulo
        ProfileTasksUtilities::generateSQL(false);
        ProfileTasksUtilities::generateMissingProfiles(true);

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
        $withProfile = UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route(
                "{$startRoute}[/]",
                $classname . ':myProfileView',
                self::$baseRouteName . '-my-profile',
                'GET',
                true,
                null,
                $withProfile
            ),
            //JSON
            new Route( //Datos para datatables de experiencia
                "{$startRoute}/datatables/experience[/]",
                PreviousExperiencesController::class . ':dataTables',
                self::$baseRouteName . '-datatables-experience',
                'GET',
                true,
                null,
                $allRoles
            ),
            //──── POST ──────────────────────────────────────────────────────────────────────────────
            new Route( //Acción guardar el perfil
                "{$startRoute}/action/profile/save[/]",
                $classname . ':saveProfileAction',
                self::$baseRouteName . '-actions-save-profile',
                'POST',
                true,
                null,
                $saveProfile
            ),
            new Route( //Acción agregar experiencia
                "{$startRoute}/action/experience/save[/]",
                $classname . ':addExperienceAction',
                self::$baseRouteName . '-actions-save-experience',
                'POST',
                true,
                null,
                $saveProfile
            ),
            new Route( //Acción eliminar experiencia
                "{$startRoute}/action/experience/delete/{id}[/]",
                PreviousExperiencesController::class . ':toDelete',
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
