<?php

/**
 * GenericContentController.php
 */

namespace PiecesPHP\BuiltIn\Helpers\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\BuiltIn\Helpers\Exceptions\SafeException;
use PiecesPHP\BuiltIn\Helpers\HelpersSystemLang;
use PiecesPHP\BuiltIn\Helpers\HelpersSystemRoutes;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Forms\UploadedFileAdapter;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Exception\HttpForbiddenException;

/**
 * GenericContentController.
 *
 * @package     PiecesPHP\BuiltIn\Helpers;\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class GenericContentController extends AdminPanelController
{

    use ControllerRoutingTrait;

    /**
     * @var string
     */
    protected static $URLDirectory = 'helpers-system/generic-content';
    /**
     * @var string
     */
    protected static $baseRouteName = 'helpers-system-generic-content-admin';

    /**
     * @var string
     */
    protected $uploadDir = '';
    /**
     * @var string
     */
    protected $uploadDirURL = '';
    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js/generic';
    const BASE_CSS_DIR = 'css';
    const BASE_VIEW_DIR = 'generic';
    const UPLOAD_DIR = 'helpers-system/generic';
    const LANG_GROUP = HelpersSystemLang::LANG_GROUP;

    const TOKENS_LIMIT_PERMISSION = [
        UsersModel::TYPE_USER_ROOT,
    ];

    const MAPBOX_KEYS_PERMISSION = [
        UsersModel::TYPE_USER_ROOT,
    ];

    const HOME_IMAGE_PERMISSIONS = [
        UsersModel::TYPE_USER_ROOT,
    ];

    public function __construct()
    {
        parent::__construct();

        $baseURL = base_url();
        $pcsUploadDir = get_config('upload_dir');
        $pcsUploadDirURL = get_config('upload_dir_url');

        $this->uploadDir = append_to_path_system($pcsUploadDir, self::UPLOAD_DIR);
        $this->uploadDirURL = str_replace($baseURL, '', append_to_url($pcsUploadDirURL, self::UPLOAD_DIR));

        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());

        $this->setInstanceViewDir(__DIR__ . '/../Views/');

        add_global_asset(HelpersSystemRoutes::staticRoute('globals-vars.css'), 'css');

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function homeImage(Request $request, Response $response)
    {

        $allowedLangs = Config::get_allowed_langs();

        $element = new GenericContentPseudoMapper(GenericContentPseudoMapper::CONTENT_HOME_IMAGE);

        import_cropper();
        set_custom_assets([
            //Base
            HelpersSystemRoutes::staticRoute(self::BASE_JS_DIR . '/home-image.js'),
        ], 'js');

        $action = self::routeName('actions-edit');

        $title = __(self::LANG_GROUP, 'Imagen principal');
        $description = '';

        set_title($title . (mb_strlen($description) > 0 ? " - {$description}" : ''));

        $data = [];
        $data['action'] = $action;
        $data['element'] = $element;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        $data['breadcrumbs'] = [
            __(self::LANG_GROUP, 'Inicio') => [
                'url' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName(''),
            ],
            $title,
        ];

        $this->helpController->render('panel/layout/header');
        $this->render('forms/home-image', $data, true, false);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function tokensLimitForm(Request $request, Response $response)
    {

        $allowedLangs = Config::get_allowed_langs();

        $element = new GenericContentPseudoMapper(GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);

        set_custom_assets([
            //Base
            HelpersSystemRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
        ], 'js');

        $action = self::routeName('actions-edit');

        $title = __(self::LANG_GROUP, 'Límite de tokens de IA');
        $description = __(self::LANG_GROUP, 'Cuántos tokens se esperan como máximo en cada respuesta de las funciones de IA.');

        set_title($title);

        $data = [];
        $data['action'] = $action;
        $data['element'] = $element;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        //Viven en Configuración › Sistema (P255 a): como el resto de la configuración, sin migas y con «Cancelar» al inicio
        //(SettingsController::sectionHeaderData(), ADR 0070).
        $data = SettingsController::sectionHeaderData($title, $description) + $data;

        $this->helpController->render('panel/layout/header');
        $this->render('forms/tokens-limit', $data, true, false);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function mapboxKeysForm(Request $request, Response $response)
    {

        $allowedLangs = Config::get_allowed_langs();

        $element = new GenericContentPseudoMapper(GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS);

        set_custom_assets([
            //Base
            HelpersSystemRoutes::staticRoute(self::BASE_JS_DIR . '/forms.js'),
        ], 'js');

        $action = self::routeName('actions-edit');

        $title = __(self::LANG_GROUP, 'Llaves de Mapas');
        $description = __(self::LANG_GROUP, 'Llaves de acceso del servicio de mapas de la plataforma.');

        set_title($title);

        $data = [];
        $data['action'] = $action;
        $data['element'] = $element;
        $data['langGroup'] = self::LANG_GROUP;
        $data['title'] = $title;
        $data['description'] = $description;
        //Viven en Configuración › Sistema (P255 a): como el resto de la configuración, sin migas y con «Cancelar» al inicio
        //(SettingsController::sectionHeaderData(), ADR 0070).
        $data = SettingsController::sectionHeaderData($title, $description) + $data;

        $this->helpController->render('panel/layout/header');
        $this->render('forms/mapbox-key', $data, true, false);
        $this->helpController->render('panel/layout/footer');

        return $response;

    }

    /**
     * Creación/Edición
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function action(Request $request, Response $response)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                "configName",
                null,
                function ($value) {
                    return is_string($value) && mb_strlen(trim($value)) > 0;
                },
                true,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT,
                [],
                //Idioma → entero, siempre un arreglo: un entero suelto llegaba a array_keys() y daba 500.
                function ($value) {
                    return is_null($value) || Validator::isArray($value, function ($key, $value) {
                        return Config::is_allowed_lang($key) && Validator::isInteger($value);
                    });
                },
                true,
                function ($value) {
                    $parsed = [];
                    Validator::isArray($value, function ($key, $value) use (&$parsed) {
                        $validData = Config::is_allowed_lang($key) && Validator::isInteger($value);
                        if ($validData) {
                            $parsed[$key] = intval($value);
                        }
                    });
                    return $parsed;
                }
            ),
            new Parameter(
                GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS,
                [],
                function ($value) {
                    $valid = Validator::isArray($value, function ($key, $value) {
                        $local = $value['keyLocal'] ?? null;
                        $domain = $value['keyDomain'] ?? null;
                        return Config::is_allowed_lang($key) && Validator::isString($local) && Validator::isString($domain);
                    });
                    return $valid;
                },
                true,
                function ($value) {
                    $parsed = [];
                    Validator::isArray($value, function ($key, $value) use (&$parsed) {
                        $validData = Config::is_allowed_lang($key) && Validator::isString($value['keyLocal']) && Validator::isString($value['keyDomain']);
                        if ($validData) {
                            $parsed[$key] = $value;
                        }
                    });
                    if (empty($parsed)) {
                        return [];
                    }
                    return $parsed;
                }
            ),
            new Parameter(
                GenericContentPseudoMapper::CONTENT_MAP_DEFAULT_CENTER,
                [],
                function ($value) {
                    return is_array($value);
                },
                true,
                function ($value) {
                    //Los rangos los mira el guardado, para responder con su mensaje y no guardar nada.
                    return is_array($value) ? array_intersect_key($value, GenericContentPseudoMapper::MAP_DEFAULT_CENTER) : [];
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $request->getParsedBody();

        //Asignación de datos para procesar
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Actualizar'));
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

            //Información del formulario;
            $configName = $expectedParameters->getValue('configName');
            $tokensLimit = $expectedParameters->getValue(GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);
            $mapboxKeys = $expectedParameters->getValue(GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS);
            $mapDefaultCenter = $expectedParameters->getValue(GenericContentPseudoMapper::CONTENT_MAP_DEFAULT_CENTER);
            $homeImageByLang = [];
            $homeImageErrorMessages = [];
            foreach (UploadedFileAdapter::findAssociativePathsByName(GenericContentPseudoMapper::CONTENT_HOME_IMAGE) as $associativePath) {
                $homeImageLang = isset($associativePath[1]) && is_string($associativePath[1]) && Config::is_allowed_lang($associativePath[1]) ? $associativePath[1] : null;
                if ($homeImageLang !== null) {
                    $homeImageUploaded = new UploadedFileAdapter($associativePath, [
                        FileValidator::TYPE_ALL_IMAGES,
                    ]);
                    if ($homeImageUploaded->hasInput()) {
                        if ($homeImageUploaded->validate()) {
                            $homeImageByLang[$homeImageLang] = $homeImageUploaded;
                        } else {
                            $homeImageErrorMessages = array_merge($homeImageErrorMessages, $homeImageUploaded->getErrorMessages());
                        }
                    }
                }
            }
            if (!empty($homeImageErrorMessages)) {
                throw new SafeException(implode("\n", $homeImageErrorMessages));
            }

            /**
             * @var string $configName
             * @var array<string,int> $tokensLimit
             * @var array<string,string> $mapboxKeys
             * @var array<string,mixed> $mapDefaultCenter
             */

            $currentUser = getLoggedFrameworkUserOrFail();
            $currentUserType = $currentUser->type;
            $verifyPermission = function ($configName) use ($currentUserType, $request) {
                $hasPermission = false;
                if ($configName == GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT) {
                    $hasPermission = in_array($currentUserType, self::TOKENS_LIMIT_PERMISSION);
                }
                if ($configName == GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS || $configName == GenericContentPseudoMapper::CONTENT_MAP_DEFAULT_CENTER) {
                    $hasPermission = in_array($currentUserType, self::MAPBOX_KEYS_PERMISSION);
                }
                if ($configName == GenericContentPseudoMapper::CONTENT_HOME_IMAGE) {
                    $hasPermission = in_array($currentUserType, self::HOME_IMAGE_PERMISSIONS);
                }
                if (!$hasPermission) {
                    throw403($request);
                }
            };

            try {

                //Revisión de permisos
                $verifyPermission($configName);

                //Asignar datos según el tipo de configuración
                $updated = true;
                if ($configName == GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT) {
                    $mapper = new GenericContentPseudoMapper($configName);
                    $mapper->addDataManyLangs($configName, $tokensLimit, array_keys($tokensLimit));
                    $updated = $mapper->save();
                } else if ($configName == GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS) {
                    $mapper = new GenericContentPseudoMapper($configName);
                    $mapper->addDataManyLangs($configName, $mapboxKeys, array_keys($mapboxKeys));
                    $updated = $mapper->save();
                } else if ($configName == GenericContentPseudoMapper::CONTENT_MAP_DEFAULT_CENTER) {
                    $errors = GenericContentPseudoMapper::mapDefaultCenterErrors($mapDefaultCenter);
                    if (count($errors) > 0) {
                        throw new SafeException(implode("\n", $errors));
                    }
                    $mapper = new GenericContentPseudoMapper($configName);
                    $mapper->setLangData(Config::get_default_lang(), $configName, [
                        'longitude' => (float) $mapDefaultCenter['longitude'],
                        'latitude' => (float) $mapDefaultCenter['latitude'],
                        'zoom' => (float) $mapDefaultCenter['zoom'],
                    ]);
                    $updated = $mapper->save();
                } else if ($configName == GenericContentPseudoMapper::CONTENT_HOME_IMAGE) {
                    $homeImagePathByLang = [];
                    $currentMapper = new GenericContentPseudoMapper($configName);
                    foreach ($homeImageByLang as $homeImageLang => $homeImageUploaded) {
                        $homeImageCurrentByLang = $currentMapper->getLangData($homeImageLang, $configName, true, '');
                        $homeImageCurrentByLang = is_string($homeImageCurrentByLang) ? basepath($homeImageCurrentByLang) : null;
                        $homeImageCurrentByLang = !is_null($homeImageCurrentByLang) && file_exists($homeImageCurrentByLang) ? $homeImageCurrentByLang : null;
                        $homeImageUploadedPath = $homeImageUploaded->copyTo($this->uploadDir, uniqid("home_image_"));
                        if (is_string($homeImageUploadedPath) && mb_strlen($homeImageUploadedPath) > 1 && file_exists($homeImageUploadedPath)) {
                            chmod($homeImageUploadedPath, 0664);
                            $uploadedRelativePath = trim(str_replace(basepath(), '', $homeImageUploadedPath), \DIRECTORY_SEPARATOR);
                            $homeImagePathByLang[$homeImageLang] = $uploadedRelativePath;
                            if ($homeImageCurrentByLang !== null) {
                                @unlink($homeImageCurrentByLang);
                            }
                        }
                    }
                    $mapper = new GenericContentPseudoMapper($configName);
                    $mapper->addDataManyLangs($configName, $homeImagePathByLang, array_keys($homeImagePathByLang));
                    $updated = $mapper->save();
                }

                //Guardar datos
                $resultOperation->setSuccessOnSingleOperation($updated);

                if ($updated) {

                    $resultOperation
                        ->setMessage($successEditMessage)
                        ->setValue('reload', false);

                } else {

                    $resultOperation->setMessage($unknowErrorMessage);

                }

            } catch (SafeException $e) {

                $resultOperation->setMessage($e->getMessage());

            } catch (HttpForbiddenException $e) {
                //throw403() lanza: sin esto, el catch de \Exception se come la denegación y responde el error genérico (B16).
                throw $e;
            } catch (\Exception $e) {
                $reference = log_exception($e);

                $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));

            }

        } catch (SafeException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (MissingRequiredParameterException|InvalidParameterValueException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);
            $response = $response->withStatus(400);

        } catch (HttpForbiddenException $e) {
            //throw403() lanza: sin esto, el catch de \Exception se come la denegación y responde el error genérico (B16).
            throw $e;
        } catch (\Exception $e) {
            $reference = log_exception($e);

            $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));

        }

        return $response->withJson($resultOperation);
    }

    /**
     * @param string $currentLang
     * @param string $routeName
     * @return array
     */
    public static function allowedLangsForSelect(string $currentLang, string $routeName)
    {

        $allowedLangsForSelect = [];

        $allowedLangs = Config::get_allowed_langs();

        $allowedLangs = array_filter($allowedLangs, function ($l) use ($currentLang) {
            return $l != $currentLang;
        });

        array_unshift($allowedLangs, $currentLang);

        foreach ($allowedLangs as $i) {

            $value = self::routeName($routeName, ['lang' => $i]);

            $allowedLangsForSelect[$value] = __('lang', $i);

        }

        return $allowedLangsForSelect;

    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(self::BASE_VIEW_DIR . '/' . trim($name, '/'), $data, $mode, $format);
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
        $editionConfigurations = $allRoles;
        $editionTokensLimit = self::TOKENS_LIMIT_PERMISSION;
        $editionMapboxKeys = self::MAPBOX_KEYS_PERMISSION;
        $editionHomeImage = self::HOME_IMAGE_PERMISSIONS;

        $routes = [

            //──── GET ───────────────────────────────────────────────────────────────────────────────
            //HTML
            new Route( //Formulario de editar
                "{$startRoute}/forms/tokens-limit[/]",
                $classname . ':tokensLimitForm',
                self::$baseRouteName . '-forms-tokens-limit',
                'GET',
                true,
                null,
                $editionTokensLimit
            ),
            new Route( //Formulario de editar
                "{$startRoute}/forms/mapbox-keys[/]",
                $classname . ':mapboxKeysForm',
                self::$baseRouteName . '-forms-mapbox-keys',
                'GET',
                true,
                null,
                $editionMapboxKeys
            ),
            HOME_IMAGE_BANNER ? new Route( //Formulario de editar
                "{$startRoute}/forms/home-image[/]",
                $classname . ':homeImage',
                self::$baseRouteName . '-forms-home-image',
                'GET',
                true,
                null,
                $editionHomeImage
            ) : null,

            //──── POST ──────────────────────────────────────────────────────────────────────────────

            new Route( //Acción de editar
                "{$startRoute}/action/edit[/]",
                $classname . ':action',
                self::$baseRouteName . '-actions-edit',
                'POST',
                true,
                null,
                $editionConfigurations
            ),

        ];

        $routes = array_filter($routes, function ($route) {
            return $route !== null;
        });

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
