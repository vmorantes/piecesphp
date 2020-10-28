<?php

/**
 * AppConfigController.php
 */

namespace App\Controller;

use App\Model\AppConfigModel;
use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryContentMapper;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
use PiecesPHP\BuiltIn\Article\Mappers\ArticleViewMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Sitemap\Sitemap;
use PiecesPHP\Core\Sitemap\SitemapItem;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\LangInjector;
use PiecesPHP\LetsEncryptHandler;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * AppConfigController.
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class AppConfigController extends AdminPanelController
{
    const PARSE_TYPE_STRING = 'string';
    const PARSE_TYPE_BOOL = 'bool';
    const PARSE_TYPE_INT = 'int';
    const PARSE_TYPE_FLOAT = 'float';
    const PARSE_TYPE_DOUBLE = 'double';
    const PARSE_TYPE_JSON_ENCODE = 'json_encode';
    const PARSE_TYPE_JSON_DECODE = 'json_decode';
    const PARSE_TYPE_UPPERCASE = 'uppercase';
    const PARSE_TYPE_LOWERCASE = 'lowercase';

    const LANG_GROUP = 'appConfig';
    const LANG_GROUP_FORMS = 'configurationsAdminZone';
    const LANG_GROUP_FORMS_2 = 'customizationAdminZone';

    const ROLES_BACKGROUND = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_LOGOS_FAVICONS = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_SEO = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_VIEW_CUTOMIZATION_VIEW = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_VIEW_CONFIGURATIONS_VIEW = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_ROUTES_VIEWS = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_GENERIC_ACTION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_IMAGES_ACTION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_OS_TICKET_ACTION = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_SITEMAP_ACTION = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_SSL_ACTION = [
        UsersModel::TYPE_USER_ROOT,
    ];

    /**
     * @var AppConfigModel
     */
    protected $mapper;

    /**
     * @var string
     */
    protected static $baseRouteName = 'configurations';

    public function __construct()
    {
        parent::__construct();
        $this->mapper = new AppConfigModel();
        $this->model = $this->mapper->getModel();
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function backgrounds(Request $req, Response $res, array $args)
    {
        $langGroup = self::LANG_GROUP;
        $requestMethod = mb_strtoupper($req->getMethod());
        set_title(__(self::LANG_GROUP, 'Personalización de fondos'));

        if ($requestMethod == 'GET') {

            import_cropper();

            set_custom_assets([
                'statics/core/js/app_config/backgrounds.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/backgrounds.css',
            ], 'css');

            $actionURL = self::routeName('backgrounds');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->render("{$baseViewDir}/backgrounds", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            $allowedImages = [
                'background-1' => 'bg1',
                'background-2' => 'bg2',
                'background-3' => 'bg3',
                'background-4' => 'bg4',
                'background-5' => 'bg5',
            ];

            $nameCurrentAllowedImage = '';
            $nameImage = '';
            $extension = '';
            $folder = 'statics/login-and-recovery/images/login';
            $relativePath = "{$folder}/";
            $validParamenters = false;

            foreach ($allowedImages as $imageName => $name) {
                $validParamenters = isset($_FILES[$imageName]) && $_FILES[$imageName]['error'] == \UPLOAD_ERR_OK;
                if ($validParamenters) {
                    $nameCurrentAllowedImage = $imageName;
                    $nameImage = $name;
                    $extension = mb_strtolower(pathinfo($_FILES[$imageName]['name'], \PATHINFO_EXTENSION));
                    $extension = $extension == 'jpeg' ? 'jpg' : $extension;
                    $relativePath = "{$folder}/{$name}.{$extension}";
                    break;
                }
            }

            $currentBackgroundConfigMapper = new AppConfigModel('backgrounds');
            $oldImage = '';
            $currentBackgroundConfigValues = $currentBackgroundConfigMapper->value;

            foreach ($currentBackgroundConfigValues as $i => $v) {
                if (mb_strlen($nameImage) > 0 && strpos($v, $nameImage) !== false) {
                    $currentBackgroundConfigValues[$i] = $relativePath;
                    $oldImage = $v != $relativePath ? $v : '';
                    break;
                }
            }

            $currentBackgroundConfigMapper->value = $currentBackgroundConfigValues;

            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'), true);

            $createMessage = __(self::LANG_GROUP, 'Imagen guardada.');
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');
            $unexpectedOrMissedParamMessage = __(self::LANG_GROUP, 'Información faltante o inesperada.');

            if ($validParamenters) {

                $fileHandler = new FileUpload($nameCurrentAllowedImage, [FileValidator::TYPE_JPG, FileValidator::TYPE_JPEG, FileValidator::TYPE_WEBP], 5);

                if ($fileHandler->validate()) {

                    $route = $fileHandler->moveTo(basepath($folder), $nameImage, $extension);

                    if ($extension == 'webp') {
                        $img = imagecreatefromwebp(basepath($relativePath));
                        imagewebp($img, basepath($relativePath), 70);
                    } elseif ($extension == 'jpg' || $extension == 'jpeg') {
                        $img = imagecreatefromjpeg(basepath($relativePath));
                        imagejpeg($img, basepath($relativePath), 70);
                    }

                    if (count($route) > 0) {

                        $updated = $currentBackgroundConfigMapper->update();

                        if ($updated && mb_strlen(trim($oldImage)) > 0 && $oldImage != $relativePath) {
                            unlink(basepath($oldImage));
                        }

                        $result
                            ->setMessage($createMessage)
                            ->setSuccessOnSingleOperation(true);

                    } else {
                        $result->setMessage($unknowErrorMessage);
                    }

                } else {
                    $result->setMessage(implode('<br>', $fileHandler->getErrorMessages()));
                }

            } else {
                $result->setMessage($unexpectedOrMissedParamMessage);
            }

            $res = $res->withJson($result);

        }

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function faviconsAndLogos(Request $req, Response $res, array $args)
    {
        $langGroup = self::LANG_GROUP;
        $requestMethod = mb_strtoupper($req->getMethod());
        set_title(__(self::LANG_GROUP, 'Imágenes de marca'));

        if ($requestMethod == 'GET') {

            import_cropper();

            set_custom_assets([
                'statics/core/js/app_config/logos-favicons.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/logos-favicons.css',
            ], 'css');

            $actionURL = self::routeName('logos-favicons');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'publicFavicon' => get_config('favicon'),
                'backFavicon' => get_config('favicon-back'),
                'logo' => get_config('logo'),
                'whiteLogo' => get_config('white-logo'),
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->render("{$baseViewDir}/logos-favicons", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            $allowedImages = [
                'favicon' => 'favicon',
                'favicon-back' => 'favicon-back',
                'logo' => 'logo',
                'white-logo' => 'white-logo',
            ];

            $nameCurrentAllowedImage = '';
            $nameImage = '';
            $extension = '';
            $folder = 'statics/images';
            $relativePath = "{$folder}/";
            $validParamenters = false;

            foreach ($allowedImages as $imageName => $name) {
                $validParamenters = isset($_FILES[$imageName]) && $_FILES[$imageName]['error'] == \UPLOAD_ERR_OK;
                if ($validParamenters) {
                    $nameCurrentAllowedImage = $imageName;
                    $nameImage = $name;
                    $extension = mb_strtolower(pathinfo($_FILES[$imageName]['name'], \PATHINFO_EXTENSION));
                    $extension = $extension == 'jpeg' ? 'jpg' : $extension;
                    $relativePath = "{$folder}/{$name}.{$extension}";
                    break;
                }
            }

            $logosAndFaviconsMapper = new AppConfigModel($nameCurrentAllowedImage);
            $oldImage = $logosAndFaviconsMapper->value;
            $logosAndFaviconsMapper->value = $relativePath;

            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'), true);

            $createMessage = __(self::LANG_GROUP, 'Imagen guardada.');
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');
            $unexpectedOrMissedParamMessage = __(self::LANG_GROUP, 'Información faltante o inesperada.');

            if ($validParamenters) {

                $fileHandler = new FileUpload($nameCurrentAllowedImage, [FileValidator::TYPE_PNG, FileValidator::TYPE_JPG, FileValidator::TYPE_JPEG], 5);

                if ($fileHandler->validate()) {

                    $route = $fileHandler->moveTo(basepath($folder), $nameImage, $extension);

                    if ($extension == 'png') {
                        $img = imagecreatefrompng(basepath($relativePath));
                        imagealphablending($img, false);
                        imagesavealpha($img, true);
                        imagepng($img, basepath($relativePath), 9);
                    } elseif ($extension == 'jpg' || $extension == 'jpeg') {
                        $img = imagecreatefromjpeg(basepath($relativePath));
                        imagejpeg($img, basepath($relativePath), 70);
                    }

                    if (count($route) > 0) {

                        $updated = $logosAndFaviconsMapper->update();

                        if ($updated && mb_strlen(trim($oldImage)) > 0 && $oldImage != $relativePath) {
                            unlink(basepath($oldImage));
                        }

                        $result
                            ->setMessage($createMessage)
                            ->setSuccessOnSingleOperation(true);

                    } else {
                        $result->setMessage($unknowErrorMessage);
                    }

                } else {
                    $result->setMessage(implode('<br>', $fileHandler->getErrorMessages()));
                }

            } else {
                $result->setMessage($unexpectedOrMissedParamMessage);
            }

            $res = $res->withJson($result);

        }

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function seo(Request $req, Response $res, array $args)
    {
        $langGroup = self::LANG_GROUP;

        $requestMethod = mb_strtoupper($req->getMethod());

        set_title(__(self::LANG_GROUP, 'Ajustes SEO'));

        if ($requestMethod == 'GET') {

            import_cropper();

            set_custom_assets([
                'statics/core/js/app_config/seo.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/seo.css',
            ], 'css');

            $actionURL = self::routeName('seo');

            $titleApp = htmlentities(AppConfigModel::getConfigValue('title_app'));
            $owner = htmlentities(AppConfigModel::getConfigValue('owner'));
            $description = AppConfigModel::getConfigValue('description');
            $extraScripts = AppConfigModel::getConfigValue('extra_scripts');

            $keywordsSelect = [];
            $keywords = AppConfigModel::getConfigValue('keywords');

            if (is_array($keywords) && count($keywords) > 0) {

                foreach ($keywords as $keyword) {
                    $keywordsSelect[$keyword] = $keyword;
                }

            } else {
                $keywords = [];
                $keywordsSelect[''] = __(self::LANG_GROUP, 'Agregue alguna palabra clave');
            }

            $keywordsSelect = array_to_html_options($keywordsSelect, $keywords, true);

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'openGraph' => get_config('open_graph_image'),
                'titleApp' => $titleApp,
                'owner' => $owner,
                'description' => $description,
                'keywords' => $keywordsSelect,
                'extraScripts' => $extraScripts,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->render("{$baseViewDir}/seo", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            //──── Entrada ───────────────────────────────────────────────────────────────────────────

            //Definición de validaciones y procesamiento
            $expectedParameters = new Parameters([
                new Parameter(
                    'titleApp',
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
                    'owner',
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
                    'description',
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
                    'keywords',
                    [],
                    function ($value) {
                        return is_array($value);
                    },
                    true,
                    function ($value) {
                        return array_filter(array_map(function ($e) {
                            return clean_string($e);
                        }, $value), function ($e) {
                            return is_string($e);
                        });
                    }
                ),
                new Parameter(
                    'extraScripts',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return trim($value);
                    }
                ),
            ]);

            //Obtención de datos
            $inputData = $req->getParsedBody();

            //Asignación de datos para procesar
            $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

            //──── Estructura de respuesta ───────────────────────────────────────────────────────────

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Ajustes SEO'));
            $resultOperation->setSingleOperation(true); //Se define que es de una única operación

            //Valores iniciales de la respuesta
            $resultOperation->setSuccessOnSingleOperation(false);

            //Mensajes de respuesta
            $successMessage = __(self::LANG_GROUP, 'Datos guardados.');
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido, intente más tarde.');
            $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

            //──── Acciones ──────────────────────────────────────────────────────────────────────────
            try {

                //Intenta validar, si todo sale bien el código continúa
                $expectedParameters->validate();

                //Información del formulario
                /**
                 * @var string $titleApp
                 * @var string $owner
                 * @var string $description
                 * @var string[] $keywords
                 * @var string $extraScripts
                 */;
                $titleApp = $expectedParameters->getValue('titleApp');
                $owner = $expectedParameters->getValue('owner');
                $description = $expectedParameters->getValue('description');
                $keywords = $expectedParameters->getValue('keywords');
                $extraScripts = $expectedParameters->getValue('extraScripts');

                $nameImageUploaded = 'open-graph';
                $fileHandler = new FileUpload($nameImageUploaded, [
                    FileValidator::TYPE_JPG, FileValidator::TYPE_JPEG,
                ], 5);

                try {

                    $options = [
                        'title_app' => $titleApp,
                        'owner' => $owner,
                        'description' => $description,
                        'keywords' => $keywords,
                        'extra_scripts' => $extraScripts,
                    ];

                    $success = true;

                    foreach ($options as $optionName => $optionValue) {

                        $optionMapper = new AppConfigModel($optionName);

                        $optionMapper->value = $optionValue;

                        if ($optionMapper->id !== null) {
                            $success = $success && $optionMapper->update();
                        } else {
                            $optionMapper->name = $optionName;
                            $success = $success && $optionMapper->save();
                        }

                    }

                    if ($fileHandler->hasInput()) {

                        if ($fileHandler->validate()) {

                            $folder = 'statics/images';
                            $nameImage = 'open_graph';
                            $extension = 'jpg';
                            $relativePath = "{$folder}/{$nameImage}.{$extension}";

                            $openGraphMapper = new AppConfigModel('open_graph_image');
                            $openGraphMapper->value = $relativePath;

                            $route = $fileHandler->moveTo(basepath($folder), $nameImage, $extension);
                            $img = imagecreatefromjpeg(basepath($relativePath));
                            imagejpeg($img, basepath($relativePath), 70);

                            if (count($route) > 0) {

                                if ($openGraphMapper->id !== null) {
                                    $success = $success && $openGraphMapper->update();
                                } else {
                                    $openGraphMapper->name = 'open_graph_image';
                                    $success = $success && $openGraphMapper->save();
                                }

                            }

                        } else {
                            $unknowErrorMessage = implode('<br>', $fileHandler->getErrorMessages());
                        }

                    }

                    if ($success) {
                        $resultOperation->setMessage($successMessage);
                        $resultOperation->setSuccessOnSingleOperation($success);
                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } catch (\Exception $e) {
                    $resultOperation->setMessage($e->getMessage());
                    log_exception($e);
                }

            } catch (MissingRequiredParamaterException $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            } catch (ParsedValueException $e) {

                $resultOperation->setMessage($unknowErrorWithValuesMessage);
                log_exception($e);

            } catch (InvalidParameterValueException $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

            return $res->withJson($resultOperation);

        }

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function routesView(Request $req, Response $res, array $args)
    {
        $this->render('panel/layout/header');
        $this->render('panel/pages/app_configurations/routes', [
            'routes' => get_routes(),
        ]);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function configurationsView(Request $req, Response $res, array $args)
    {
        import_spectrum();

        $langGroup = AppConfigController::LANG_GROUP_FORMS;

        $tabsTitles = [];
        $tabsItems = [];

        $currentUser = get_config('current_user');
        $baseViewDir = 'panel/pages/app_configurations';

        if (in_array($currentUser->type, self::ROLES_VIEW_CONFIGURATIONS_VIEW)) {

            $actionGenericURL = AppConfigController::routeName('generals-generic-action');
            $actionSitemapURL = AppConfigController::routeName('generals-sitemap-create');
            $actionOsTicketURL = AppConfigController::routeName('generals-osticket-action');
            $actionSSLURL = AppConfigController::routeName('ssl');

            $hasPermissionsGenerals = count(array_filter([
                $actionGenericURL,
                $actionSitemapURL,
            ], function ($e) {return mb_strlen(trim($e)) > 0;})) > 0;
            $hasPermissionsMail = mb_strlen(trim($actionGenericURL)) > 0;
            $hasPermissionsOsTicket = mb_strlen(trim($actionOsTicketURL)) > 0;
            $hasPermissionsSSL = mb_strlen(trim($actionSSLURL)) > 0;

            if ($hasPermissionsGenerals) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionGenericURL' => $actionGenericURL,
                    'actionSitemapURL' => $actionSitemapURL,
                ];

                $tabsTitles['general'] = __($langGroup, 'Generales');
                $tabsItems['general'] = $this->render("{$baseViewDir}/inc/configuration-tabs/general", $data, false, false);

            }

            if ($hasPermissionsMail) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionGenericURL' => $actionGenericURL,
                ];

                $tabsTitles['mail'] = __($langGroup, 'Email');
                $tabsItems['mail'] = $this->render("{$baseViewDir}/inc/configuration-tabs/email", $data, false, false);

            }

            if ($hasPermissionsOsTicket) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionOsTicketURL' => $actionOsTicketURL,
                ];

                $tabsTitles['os-ticket'] = __($langGroup, 'OsTicket');
                $tabsItems['os-ticket'] = $this->render("{$baseViewDir}/inc/configuration-tabs/os-ticket", $data, false, false);

            }

            if ($hasPermissionsSSL) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionSSLURL' => $actionSSLURL,
                ];

                $tabsTitles['ssl'] = __($langGroup, 'SSL');
                $tabsItems['ssl'] = $this->render("{$baseViewDir}/inc/configuration-tabs/ssl", $data, false, false);

            }

        }

        $data = [
            'langGroup' => $langGroup,
            'tabsTitles' => $tabsTitles,
            'tabsItems' => $tabsItems,
        ];

        $this->render('panel/layout/header');
        $this->render("{$baseViewDir}/configurations", $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return void
     */
    public function customizationView(Request $req, Response $res, array $args)
    {
        $langGroup = AppConfigController::LANG_GROUP_FORMS_2;

        $tabsTitles = [];
        $tabsItems = [];

        $currentUser = get_config('current_user');
        $baseViewDir = 'panel/pages/app_configurations';

        if (in_array($currentUser->type, self::ROLES_VIEW_CONFIGURATIONS_VIEW)) {

            $actionCustomImagesURL = AppConfigController::routeName('customization-images-action');

            $hasPermissionsImages = mb_strlen(trim($actionCustomImagesURL)) > 0;
            $hasPermissionsBGImages = mb_strlen(trim($actionCustomImagesURL)) > 0;

            if ($hasPermissionsImages) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionCustomImagesURL' => $actionCustomImagesURL,
                ];

                $tabsTitles['images'] = __($langGroup, 'Imágenes');
                $tabsItems['images'] = $this->render("{$baseViewDir}/inc/customization-tabs/images", $data, false, false);

            }

            if ($hasPermissionsBGImages) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionCustomImagesURL' => $actionCustomImagesURL,
                ];

                $tabsTitles['bg'] = __($langGroup, 'Fondos del login');
                $tabsItems['bg'] = $this->render("{$baseViewDir}/inc/customization-tabs/background", $data, false, false);

            }

        }

        $data = [
            'langGroup' => $langGroup,
            'tabsTitles' => $tabsTitles,
            'tabsItems' => $tabsItems,
        ];

        $this->render('panel/layout/header');
        $this->render("{$baseViewDir}/customization", $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function actionGeneric(Request $req, Response $res, array $args)
    {
        $operation_name = __(self::LANG_GROUP, 'Configuración');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = __(self::LANG_GROUP, 'Guardado.');
        $message_unknow_error = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');

        $parametersExcepted = new Parameters([
            new Parameter(
                'name',
                null,
                function ($value) {
                    return is_string($value);
                }
            ),
            new Parameter(
                'value',
                '',
                function ($value) {
                    return is_string($value) || is_array($value) || is_object($value);
                },
                true
            ),
            new Parameter(
                'parse',
                null,
                function ($value) {

                    if (is_array($value)) {

                        $valid = true;

                        array_map(function ($v) use (&$valid) {
                            if (!is_string($v)) {
                                $valid = false;
                            }
                        }, $value);

                        return $valid;

                    } else {
                        return is_string($value);
                    }

                },
                true
            ),
            new Parameter(
                'merge',
                false,
                function ($value) {
                    return is_string($value) || is_bool($value);
                },
                true,
                function ($value) {
                    return self::parseTo($value, self::PARSE_TYPE_BOOL) === true;
                }
            ),
        ]);

        $parametersExcepted->setInputValues($req->getParsedBody());

        try {

            $parametersExcepted->validate();

            $name = $parametersExcepted->getValue('name');
            $value = $parametersExcepted->getValue('value');
            $parse = $parametersExcepted->getValue('parse');
            $merge = $parametersExcepted->getValue('merge');

            $option = new AppConfigModel($name);
            $optionExists = !is_null($option->id);

            if ($optionExists && $merge) {

                $oldValue = $option->value;
                if (
                    (is_array($oldValue) || $oldValue instanceof \stdClass)
                    &&
                    (is_array($value) || $value instanceof \stdClass)
                ) {
                    $value = self::recursiveMergeArray($oldValue, $value);
                }

            }

            $value = self::processGenericInputValues($value, $parse);

            $option->value = $value;

            if ($optionExists) {

                $success = $option->update();

            } else {

                $option->name = $name;
                $success = $option->save();

            }

            if ($success) {

                $result
                    ->setMessage($message_create)
                    ->operation($operation_name)
                    ->setSuccess(true);

            } else {

                $result
                    ->setMessage($message_unknow_error)
                    ->operation($operation_name);

            }

        } catch (\PDOException $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        } catch (\Exception $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        }

        return $res->withJson($result);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function actionOsTicket(Request $req, Response $res, array $args)
    {
        $operation_name = __(self::LANG_GROUP, 'Configuración OsTicket');

        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = __(self::LANG_GROUP, 'Guardado.');
        $message_unknow_error = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');

        $parametersExcepted = new Parameters([
            new Parameter(
                'url',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return rtrim($value, '/');
                }
            ),
            new Parameter(
                'key',
                null,
                function ($value) {
                    return is_string($value);
                }
            ),
        ]);

        $parametersExcepted->setInputValues($req->getParsedBody());

        try {

            $parametersExcepted->validate();

            $url = new AppConfigModel('osTicketAPI');
            $key = new AppConfigModel('osTicketAPIKey');

            $url->value = $parametersExcepted->getValue('url');
            $key->value = $parametersExcepted->getValue('key');

            $successUrl = $url->update();
            $successKey = $key->update();
            $success = $successUrl || $successKey;

            if ($success) {

                $result
                    ->setMessage($message_create)
                    ->operation($operation_name)
                    ->setSuccess(true);

            } else {

                $result
                    ->setMessage($message_unknow_error)
                    ->operation($operation_name);

            }

        } catch (\PDOException $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        } catch (\Exception $e) {

            $result
                ->setMessage($e->getMessage())
                ->operation($operation_name);
            log_exception($e);

        }

        return $res->withJson($result);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function actionImages(Request $req, Response $res, array $args)
    {

        $allowedImages = [
            'favicon' => [
                'name' => 'favicon',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
                'dafault' => 'statics/images/favicon.png',
            ],
            'favicon-back' => [
                'name' => 'favicon-back',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
                'dafault' => 'statics/images/favicon-back.png',
            ],
            'logo' => [
                'name' => 'logo',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
                'dafault' => 'statics/images/logo.png',
            ],
            'white-logo' => [
                'name' => 'white-logo',
                'extension' => 'png',
                'folder' => basepath('statics/images'),
                'dafault' => 'statics/images/white-logo.png',
            ],
            'background-1' => [
                'name' => 'bg1',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
                'dafault' => 'statics/login-and-recovery/images/login/bg1.jpg',
            ],
            'background-2' => [
                'name' => 'bg2',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
                'dafault' => 'statics/login-and-recovery/images/login/bg2.jpg',
            ],
            'background-3' => [
                'name' => 'bg3',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
                'dafault' => 'statics/login-and-recovery/images/login/bg3.jpg',
            ],
            'background-4' => [
                'name' => 'bg4',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
                'dafault' => 'statics/login-and-recovery/images/login/bg4.jpg',
            ],
            'background-5' => [
                'name' => 'bg5',
                'extension' => 'jpg',
                'folder' => basepath('statics/login-and-recovery/images/login'),
                'dafault' => 'statics/login-and-recovery/images/login/bg5.jpg',
            ],
            'open_graph_image' => [
                'name' => 'open_graph',
                'extension' => 'jpg',
                'folder' => basepath('statics/images'),
                'dafault' => 'statics/login-and-recovery/images/login/bg6.jpg',
            ],
        ];

        $nameCurrentAllowedImage = '';
        $nameImage = '';
        $extension = '';
        $folder = '';
        $validParamenters = false;

        foreach ($allowedImages as $imageName => $config) {

            $validParamenters = isset($_FILES[$imageName]) && $_FILES[$imageName]['error'] == \UPLOAD_ERR_OK;

            if ($validParamenters) {
                $nameCurrentAllowedImage = $imageName;
                $nameImage = $config['name'];
                $extension = $config['extension'];
                $folder = $config['folder'];
                break;
            }

        }

        $operation_name = __(self::LANG_GROUP, 'Guardar imagen');
        $result = new ResultOperations([
            new Operation($operation_name),
        ], $operation_name);

        $message_create = __(self::LANG_GROUP, 'Imagen guardada.');
        $message_unknow_error = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');
        $message_unexpected_or_missing_params = __(self::LANG_GROUP, 'Información faltante o inesperada.');

        if ($validParamenters) {

            $fileHandler = new FileUpload($nameCurrentAllowedImage, [
                $extension == 'png' ? FileValidator::TYPE_PNG : FileValidator::TYPE_JPG,
            ], 5);

            if ($fileHandler->validate()) {

                $route = $fileHandler->moveTo($folder, $nameImage, $extension);

                if (count($route) > 0) {

                    $configElement = new AppConfigModel($nameCurrentAllowedImage);

                    if ($configElement->id === null) {
                        $configElement->name = $nameCurrentAllowedImage;
                        $configElement->value = $allowedImages[$nameCurrentAllowedImage]['dafault'];
                        $configElement->save();
                    }

                    $result->setValue('reload', true);
                    $result
                        ->setMessage($message_create)
                        ->operation($operation_name)
                        ->setSuccess(true);
                } else {
                    $result
                        ->setMessage($message_unknow_error)
                        ->operation($operation_name);
                }

            } else {

                $result
                    ->setMessage(implode('<br>', $fileHandler->getErrorMessages()))
                    ->operation($operation_name);

            }

        } else {
            $result
                ->setMessage($message_unexpected_or_missing_params)
                ->operation($operation_name);
        }

        return $res->withJson($result);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function createSitemap(Request $req, Response $res, array $args)
    {

        $startTime = microtime(true);

        $result = new ResultOperations([], __(self::LANG_GROUP, 'Sitemap'), '', true);

        $sitemap = new Sitemap(basepath('sitemap.xml'), true);

        $sitemap->addItem(new SitemapItem(baseurl()));
        $sitemap->addItem(new SitemapItem(ArticleControllerPublic::routeName('list')));

        $routes = array_merge(
            get_routes_by_controller(ArticleControllerPublic::class),
            get_routes_by_controller(PublicAreaController::class)
        );

        foreach ($routes as $routeInfo) {

            $url = get_route_sample($routeInfo['name']);

            if (mb_strpos($url, '{') === false) {

                $sitemap->addItem(new SitemapItem($url));

            }

        }

        $categories = CategoryContentMapper::all();
        $articles = ArticleViewMapper::all();

        foreach ($categories as $categorie) {

            $url = ArticleControllerPublic::routeName('list-by-category', ['category' => $categorie->friendly_url]);

            $sitemap->addItem(new SitemapItem($url));

        }

        foreach ($articles as $article) {
            $url = ArticleControllerPublic::routeName('single', ['friendly_name' => $article->friendly_url]);
            $date = !is_null($article->updated) ? $article->updated : $article->created;
            $date = new \DateTime($date);
            $sitemap->addItem(new SitemapItem($url, $date, SitemapItem::FREQ_WEEK));
        }

        try {

            $sitemap->save();

            $result->setMessage(__(self::LANG_GROUP, 'Sitemap creado'));

            $endTime = microtime(true);

            $result->setValue('Rendimiento', [
                'Memory peak usage' => number_format(memory_get_peak_usage() / (1024 * 1024), 4) . "MB",
                'Execution time' => number_format($endTime - $startTime, 10) . "s",
                'urls' => $sitemap->getLocations(),
            ]);

            $result->setSuccessOnSingleOperation(true);

        } catch (\Exception $e) {

            $result->setMessage($e->getMessage());

            $result->setValue('exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ]);
            log_exception($e);

        }

        return $res->withJson($result);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function sslGenerator(Request $req, Response $res, array $args)
    {
        $sslMessages = 'config-ssl';

        $result = new ResultOperations([], 'SSL', '', true);

        $sslCreatedMessage = __($sslMessages, 'SSL creado.');
        $tryLaterMessage = __($sslMessages, 'El SSL no pudo ser creado, intente más tarde.');
        $unknowErrorMessage = __($sslMessages, 'Ha ocurrido un error inesperado.');

        $parametersExcepted = new Parameters([
            new Parameter(
                'domain',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'folder',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
            new Parameter(
                'email',
                null,
                function ($value) {
                    return is_string($value);
                },
                false,
                function ($value) {
                    return clean_string($value);
                }
            ),
        ]);

        $parametersExcepted->setInputValues($req->getParsedBody());

        try {

            $parametersExcepted->validate();

            ini_set('max_execution_time', 120);

            $domain = $parametersExcepted->getValue('domain');
            $folder = $parametersExcepted->getValue('folder');
            $email = $parametersExcepted->getValue('email');

            $client = new LetsEncryptHandler($domain, $email, $folder);
            $client->testMode(false);
            $client->init();
            $client->order();
            $success = $client->certify();

            $result->setSuccessOnSingleOperation($success);

            if ($success) {

                $result->setMessage($sslCreatedMessage);

            } else {

                $result->setMessage($tryLaterMessage);

            }

        } catch (\Exception $e) {

            $result
                ->setMessage($unknowErrorMessage)
                ->setValue('errorMessage', $e->getMessage());
            log_exception($e);

        }

        return $res->withJson($result);
    }

    /**
     * @param mixed $input
     * @param string|array $parse
     * @return mixed
     */
    private static function processGenericInputValues($input, $parse = null)
    {

        if (is_array($input) || $input instanceof \stdClass) {

            $parseIsArray = is_array($parse);
            $inputIsArray = is_array($input);

            foreach ($input as $index => $value) {

                if ($parseIsArray) {

                    $parseType = null;

                    if (array_key_exists($index, $parse)) {
                        $parseType = $parse[$index];
                    }

                    if (!is_array($value)) {

                        if (is_string($parseType)) {

                            if ($inputIsArray) {
                                $input[$index] = self::parseTo($value, $parseType);
                            } else {
                                $input->$index = self::parseTo($value, $parseType);
                            }

                        }

                    } else {

                        $input[$index] = self::processGenericInputValues($value, $parseType);

                    }

                }

            }

        } elseif (is_scalar($input)) {

            $parseType = is_string($parse) ? $parse : '';
            $input = self::parseTo($input, $parseType);

        }

        return $input;

    }

    /**
     * @param mixed $value
     * @param string $to
     * @return mixed
     */
    public static function parseTo($value, string $to = null)
    {
        $to = is_null($to) ? self::PARSE_TYPE_STRING : $to;

        switch ($to) {

            case self::PARSE_TYPE_STRING:

                if (is_scalar($value)) {
                    return (string) $value;
                }
                return $value;

                break;

            case self::PARSE_TYPE_BOOL:

                if (is_scalar($value)) {
                    $toFalse = [0, '0', 'off', 'no', 'false', null, 'null'];
                    $toTrue = [1, '1', 'on', 'yes', 'si', 'sí', 'true'];
                    if (is_string($value)) {
                        $value = mb_strtolower($value);
                    }
                    foreach ($toTrue as $i) {
                        if ($i === $value) {
                            return true;
                        }
                    }
                    foreach ($toFalse as $i) {
                        if ($i === $value) {
                            return false;
                        }
                    }
                }
                return $value;

                break;

            case self::PARSE_TYPE_INT:

                if (is_scalar($value) && is_numeric($value)) {
                    return (int) $value;
                }
                return $value;

                break;

            case self::PARSE_TYPE_FLOAT:
            case self::PARSE_TYPE_DOUBLE:

                if (is_scalar($value) && is_numeric($value)) {
                    return (double) $value;
                }
                return $value;

                break;

            case self::PARSE_TYPE_JSON_ENCODE:
                $parsedValue = json_encode($value);
                if (json_last_error() == \JSON_ERROR_NONE) {
                    return $parsedValue;
                } else {
                    return $value;
                }
                break;

            case self::PARSE_TYPE_JSON_DECODE:
                $parsedValue = json_decode($value);
                if (json_last_error() == \JSON_ERROR_NONE) {
                    return $parsedValue;
                } else {
                    return $value;
                }
                break;

            case self::PARSE_TYPE_UPPERCASE:
                if (is_string($value)) {
                    return mb_strtoupper($value);
                } else {
                    return $value;
                }
                break;

            case self::PARSE_TYPE_LOWERCASE:
                if (is_string($value)) {
                    return mb_strtolower($value);
                } else {
                    return $value;
                }
                break;

            default:

                return $value;

                break;
        }
    }

    /**
     * @param array|\stdClass $one
     * @param array|\stdClass $two
     * @return array
     */
    public static function recursiveMergeArray($one, $two)
    {
        if (!is_array($one) && !($one instanceof \stdClass)) {
            throw new \TypeError('$one debe ser un array o una instancia de \stdClass');
        }
        if (!is_array($two) && !($two instanceof \stdClass)) {
            throw new \TypeError('$two debe ser un array o una instancia de \stdClass');
        }

        $oneIsArray = is_array($one);
        $twoIsArray = is_array($two);

        $oneKeys = $oneIsArray ? array_keys($one) : array_keys(get_object_vars($one));
        $twoKeys = $twoIsArray ? array_keys($two) : array_keys(get_object_vars($two));

        $keys = array_unique(array_merge($oneKeys, $twoKeys));

        foreach ($keys as $key) {

            $oneHasKey = $oneIsArray ? array_key_exists($key, $one) : isset($two->$one);
            $twoHasKey = $twoIsArray ? array_key_exists($key, $two) : isset($two->$key);

            if ($twoHasKey) {

                $twoValue = $twoIsArray ? $two[$key] : $two->$key;

                if ($oneHasKey) {

                    $oneValue = $one[$key];

                    if (is_scalar($oneValue)) {

                        if ($oneIsArray) {
                            $one[$key] = $twoValue;
                        } else {
                            $one->$key = $twoValue;
                        }

                    } else {

                        if ($oneIsArray) {
                            $one[$key] = self::recursiveMergeArray($oneValue, $twoValue);
                        } else {
                            $one->$key = self::recursiveMergeArray($oneValue, $twoValue);
                        }

                    }

                } else {

                    if ($oneIsArray) {

                        $one[$key] = $twoValue;

                    } else {

                        $one->$key = $twoValue;

                    }

                }
            }

        }

        return $one;

    }

    /**
     * @param string $name
     * @param array $params
     * @return bool
     */
    public static function allowedRoute(string $name, array $params = [])
    {

        $route = self::routeName($name, $params, true);
        $allow = strlen($route) > 0;

        if ($allow) {

            if ($name == 'SAMPLE') { //do something
            }

        }

        return $allow;
    }

    /**
     * @param string $name
     * @param array $params
     * @param bool $silentOnNotExists
     * @return string
     */
    public static function routeName(string $name = null, array $params = [], bool $silentOnNotExists = false)
    {
        if (!is_null($name)) {
            $name = trim($name);
            $name = strlen($name) > 0 ? "-{$name}" : '';
        }

        $name = !is_null($name) ? self::$baseRouteName . $name : self::$baseRouteName;

        $allowed = false;
        $current_user = get_config('current_user');

        if ($current_user != false) {
            $allowed = Roles::hasPermissions($name, (int) $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            return get_route(
                $name,
                $params,
                $silentOnNotExists
            );
        } else {
            return '';
        }
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;

        $group->active(APP_CONFIGURATION_MODULE);

        //Perzonalizaciones
        $group->register([

            //──── GET ─────────────────────────────────────────────────────────────────────────
            //Vista de configuración general
            new Route(
                "{$startRoute}[/]",
                $classname . ':configurationsView',
                'configurations-generals',
                'GET',
                true,
                null,
                self::ROLES_VIEW_CONFIGURATIONS_VIEW
            ),
            //Vista de personalización de imágenes y textos
            new Route(
                "{$startRoute}images[/]",
                $classname . ':customizationView',
                'configurations-customization',
                'GET',
                true,
                null,
                self::ROLES_VIEW_CUTOMIZATION_VIEW
            ),
            //Vista de configuración de rutas y permisos
            new Route(
                "{$startRoute}routes[/]",
                $classname . ':routesView',
                'configurations-routes',
                'GET',
                true,
                null,
                self::ROLES_ROUTES_VIEWS
            ),

            //──── POST ────────────────────────────────────────────────────────────────────────
            //Manejadores de imágenes
            new Route(
                "{$startRoute}images/add/images[/]",
                $classname . ':actionImages',
                'configurations-customization-images-action',
                'POST',
                true,
                null,
                self::ROLES_IMAGES_ACTION
            ),
            //OsTicket
            new Route(
                "{$startRoute}images/config/osticket[/]",
                $classname . ':actionOsTicket',
                'configurations-generals-osticket-action',
                'POST',
                true,
                null,
                self::ROLES_OS_TICKET_ACTION
            ),
            //General
            new Route(
                "{$startRoute}images/config/generic[/]",
                $classname . ':actionGeneric',
                'configurations-generals-generic-action',
                'POST',
                true,
                null,
                self::ROLES_GENERIC_ACTION
            ),

            //Generar sitemap
            new Route(
                "{$startRoute}sitemap/create[/]",
                $classname . ':createSitemap',
                'configurations-generals-sitemap-create',
                'POST',
                true,
                null,
                self::ROLES_SITEMAP_ACTION
            ),

            //Generar ssl
            new Route(
                "{$startRoute}ssl[/]",
                $classname . ':sslGenerator',
                'configurations-ssl',
                'POST',
                true,
                null,
                self::ROLES_SSL_ACTION
            ),

            //──── Mixtas ────────────────────────────────────────────────────────────────────────────

            //Fondos
            new Route(
                "{$startRoute}/backgrounds[/]",
                $classname . ':backgrounds',
                self::$baseRouteName . '-' . 'backgrounds',
                'GET|POST',
                true,
                null,
                self::ROLES_BACKGROUND
            ),

            //Logos y favicons
            new Route(
                "{$startRoute}/logos-favicons[/]",
                $classname . ':faviconsAndLogos',
                self::$baseRouteName . '-' . 'logos-favicons',
                'GET|POST',
                true,
                null,
                self::ROLES_LOGOS_FAVICONS
            ),

            //SEO
            new Route(
                "{$startRoute}/seo[/]",
                $classname . ':seo',
                self::$baseRouteName . '-' . 'seo',
                'GET|POST',
                true,
                null,
                self::ROLES_SEO
            ),

        ]);

        //──── POST ─────────────────────────────────────────────────────────────────────────

        //Inject lang
        if (APP_CONFIGURATION_MODULE) {
            $injector = new LangInjector(basepath('app/lang/app_config'), Config::get_allowed_langs());
            $injector->injectGroup(self::LANG_GROUP);
        }

        return $group;
    }
}
