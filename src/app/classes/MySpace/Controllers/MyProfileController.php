<?php

/**
 * MyProfileController.php
 */

namespace MySpace\Controllers;

use App\Controller\AdminPanelController;
use App\Model\UsersModel;
use MySpace\Controllers\Util\ProfileTasksUtilities;
use MySpace\Exceptions\SafeException;
use MySpace\MySpaceLang;
use MySpace\MySpaceRoutes;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
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

    use ControllerRoutingTrait;

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
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/profiles-translation-config.js'),
            MySpaceRoutes::staticRoute(self::BASE_JS_DIR . '/my-profile.js'),
        ], 'js');

        $currentUser = getLoggedFrameworkUserOrFail();
        $action = self::routeName('actions-save-profile');

        $title = __(self::LANG_GROUP, 'Mi perfil');
        $description = __(self::LANG_GROUP, 'Gestionar');
        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['action'] = $action;
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
                    return Validator::isDouble($value) ? (float) $value : null;
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
                    return Validator::isDouble($value) ? (float) $value : null;
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
             * @var string[] $affiliatedInstitutions
             */
            $id = getLoggedFrameworkUserOrFail()->id;
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
            $affiliatedInstitutions = $expectedParameters->getValue('affiliatedInstitutions');

            //NOTE: Esto debería ser provisional
            //Opcional: Modifico arbitrariamente las coordenadas
            $maxOffset = 0.6000;
            $latOffset = (mt_rand(-1000, 1000) / 10000) * $maxOffset;
            $lngOffset = (mt_rand(-1000, 1000) / 10000) * $maxOffset;
            $latitude += $latOffset;
            $longitude += $lngOffset;

            try {

                //Guardar el perfil propio ES el camino legítimo de creación: si aún no
                //existe la fila, se materializa aquí y no en un constructor.
                $mapper = UserProfileMapper::createProfile($id);
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
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(trim($name, '/'), $data, $mode, $format);
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
