<?php

/**
 * AppConfigController.php
 */

namespace App\Controller;

use App\Model\AppConfigModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
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
use Publications\Controllers\PublicationsCategoryController;
use Publications\Controllers\PublicationsController;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

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
    const ROLES_EMAIL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_OS_TICKET = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_SECURITY = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_VIEW_CONFIGURATIONS_VIEW = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_GENERIC_ACTION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_SITEMAP_ACTION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];
    const ROLES_ROUTES_VIEWS = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_CLEAN_CACHE_ACTION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN,
    ];

    const SEO_OPTION_TITLE_APP = 'title_app';
    const SEO_OPTION_OWNER = 'owner';
    const SEO_OPTION_DESCRIPTION = 'description';
    const SEO_OPTION_KEYWORDS = 'keywords';
    const SEO_OPTION_EXTRA_SCRIPTS = 'extra_scripts';
    const SEO_OPTION_OPEN_GRAPH_IMAGE = 'open_graph_image';

    const SEO_OPTION_TITLE_APP_ON_FORM = 'titleApp';
    const SEO_OPTION_OWNER_ON_FORM = 'owner';
    const SEO_OPTION_DESCRIPTION_ON_FORM = 'description';
    const SEO_OPTION_KEYWORDS_ON_FORM = 'keywords';
    const SEO_OPTION_EXTRA_SCRIPTS_ON_FORM = 'extraScripts';
    const SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM = 'openGraph';

    const SEO_OPTIONS_CONFIG_NAME_BY_FORM_NAME = [
        self::SEO_OPTION_TITLE_APP_ON_FORM => self::SEO_OPTION_TITLE_APP,
        self::SEO_OPTION_OWNER_ON_FORM => self::SEO_OPTION_OWNER,
        self::SEO_OPTION_DESCRIPTION_ON_FORM => self::SEO_OPTION_DESCRIPTION,
        self::SEO_OPTION_KEYWORDS_ON_FORM => self::SEO_OPTION_KEYWORDS,
        self::SEO_OPTION_EXTRA_SCRIPTS_ON_FORM => self::SEO_OPTION_EXTRA_SCRIPTS,
        self::SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM => self::SEO_OPTION_OPEN_GRAPH_IMAGE,
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
     * @return Response
     */
    public function backgrounds(Request $req, Response $res)
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

            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'));
            $result->setSingleOperation(true);

            $createMessage = __(self::LANG_GROUP, 'Imagen guardada.');
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');
            $unexpectedOrMissedParamMessage = __(self::LANG_GROUP, 'Información faltante o inesperada.');

            if ($validParamenters) {

                $fileHandler = new FileUpload($nameCurrentAllowedImage, [FileValidator::TYPE_JPG, FileValidator::TYPE_JPEG, FileValidator::TYPE_WEBP], 5);

                if ($fileHandler->validate()) {

                    $route = $fileHandler->moveTo(basepath($folder), $nameImage, $extension);

                    if ($extension == 'webp') {
                        $img = imagecreatefromwebp(basepath($relativePath));
                        if ($img !== false) {
                            imagewebp($img, basepath($relativePath), 70);
                        }
                    } elseif ($extension == 'jpg' || $extension == 'jpeg') {
                        $img = imagecreatefromjpeg(basepath($relativePath));
                        if ($img !== false) {
                            imagejpeg($img, basepath($relativePath), 70);
                        }
                    }

                    if (!empty($route)) {

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
     * @return Response
     */
    public function faviconsAndLogos(Request $req, Response $res)
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

            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'));
            $result->setSingleOperation(true);

            $createMessage = __(self::LANG_GROUP, 'Imagen guardada.');
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');
            $unexpectedOrMissedParamMessage = __(self::LANG_GROUP, 'Información faltante o inesperada.');

            if ($validParamenters) {

                $fileHandler = new FileUpload($nameCurrentAllowedImage, [FileValidator::TYPE_PNG, FileValidator::TYPE_JPG, FileValidator::TYPE_JPEG], 5);

                if ($fileHandler->validate()) {

                    $route = $fileHandler->moveTo(basepath($folder), $nameImage, $extension);

                    if ($extension == 'png') {
                        $img = imagecreatefrompng(basepath($relativePath));
                        if ($img !== false) {
                            imagealphablending($img, false);
                            imagesavealpha($img, true);
                            imagepng($img, basepath($relativePath), 9);
                        }
                    } elseif ($extension == 'jpg' || $extension == 'jpeg') {
                        $img = imagecreatefromjpeg(basepath($relativePath));
                        if ($img !== false) {
                            imagejpeg($img, basepath($relativePath), 70);
                        }
                    }

                    if (!empty($route)) {

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
     * @return Response
     */
    public function seo(Request $req, Response $res)
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

            $SEOValues = self::getSEOConfigValues();

            foreach ($SEOValues as $lang => $values) {

                foreach ($values as $name => $value) {

                    if (strpos($name, self::SEO_OPTION_KEYWORDS_ON_FORM) !== false) {

                        $keywordsToSelect = [];

                        $keywords = $value;
                        if (is_array($keywords) && !empty($keywords)) {

                            foreach ($keywords as $keyword) {
                                $keywordsToSelect[$keyword] = $keyword;
                            }

                        } else {
                            $value = [];
                            $keywordsToSelect[''] = __(self::LANG_GROUP, 'Agregue alguna palabra clave');
                        }

                        $keywordsToSelect = array_to_html_options($keywordsToSelect, $keywords, true);
                        $SEOValues[$lang][$name] = $keywordsToSelect;

                    }

                }

            }

            $extraScripts = AppConfigModel::getConfigValue('extra_scripts');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'SEOValues' => $SEOValues,
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
                    'lang',
                    null,
                    function ($value) {
                        return is_string($value) && mb_strlen(trim($value)) > 0;
                    },
                    false
                ),
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
                 * @var string $lang
                 * @var string $titleApp
                 * @var string $owner
                 * @var string $description
                 * @var string[] $keywords
                 * @var string $extraScripts
                 */
                $lang = $expectedParameters->getValue('lang');
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

                    $defaultLang = Config::get_default_lang();

                    $options = [
                        self::SEO_OPTION_TITLE_APP => $titleApp,
                        self::SEO_OPTION_OWNER => $owner,
                        self::SEO_OPTION_DESCRIPTION => $description,
                        self::SEO_OPTION_KEYWORDS => $keywords,
                        self::SEO_OPTION_EXTRA_SCRIPTS => $extraScripts,
                    ];

                    $success = true;

                    foreach ($options as $optionName => $optionValue) {

                        if ($lang !== $defaultLang) {
                            $optionName .= "_{$lang}";
                        }

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

                            $nameOptionOG = 'open_graph_image';

                            $folder = 'statics/images';
                            $nameImage = 'open_graph';

                            if ($lang !== $defaultLang) {
                                $nameImage .= "_{$lang}";
                                $nameOptionOG .= "_{$lang}";
                            }

                            $extension = 'jpg';
                            $relativePath = "{$folder}/{$nameImage}.{$extension}";

                            $openGraphMapper = new AppConfigModel($nameOptionOG);
                            $openGraphMapper->value = $relativePath;

                            $route = $fileHandler->moveTo(basepath($folder), $nameImage, $extension);
                            $img = imagecreatefromjpeg(basepath($relativePath));
                            if ($img !== false) {
                                imagejpeg($img, basepath($relativePath), 70);
                            }

                            if (!empty($route)) {

                                if ($openGraphMapper->id !== null) {
                                    $success = $success && $openGraphMapper->update();
                                } else {
                                    $openGraphMapper->name = $nameOptionOG;
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
     * @return Response
     */
    public function email(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;

        $requestMethod = mb_strtoupper($req->getMethod());

        set_title(__(self::LANG_GROUP, 'Configuración de emails'));

        if ($requestMethod == 'GET') {

            set_custom_assets([
                'statics/core/js/app_config/email.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/email.css',
            ], 'css');

            $actionURL = self::routeName('email');

            $element = new MailConfig;

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'element' => $element,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->render("{$baseViewDir}/email", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            //──── Entrada ───────────────────────────────────────────────────────────────────────────

            //Definición de validaciones y procesamiento
            $expectedParameters = new Parameters([
                new Parameter(
                    'auto_tls',
                    null,
                    function ($value) {
                        return ctype_digit($value) || is_int($value) || is_bool($value);
                    },
                    false,
                    function ($value) {
                        return $value === 1 || $value == '1' || $value === true;
                    }
                ),
                new Parameter(
                    'auth',
                    null,
                    function ($value) {
                        return ctype_digit($value) || is_int($value) || is_bool($value);
                    },
                    false,
                    function ($value) {
                        return $value === 1 || $value == '1' || $value === true;
                    }
                ),
                new Parameter(
                    'host',
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
                    'protocol',
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
                    'port',
                    null,
                    function ($value) {
                        return ctype_digit($value) || is_int($value);
                    },
                    false,
                    function ($value) {
                        return (int) $value;
                    }
                ),
                new Parameter(
                    'user',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return clean_string($value);
                    }
                ),
                new Parameter(
                    'password',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return clean_string($value);
                    }
                ),
                new Parameter(
                    'name',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return clean_string($value);
                    }
                ),
            ]);

            //Obtención de datos
            $inputData = $req->getParsedBody();

            //Asignación de datos para procesar
            $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

            //──── Estructura de respuesta ───────────────────────────────────────────────────────────

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Configuración de emails'));
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
                 * @var bool $autoTLS
                 * @var bool $auth
                 * @var string $host
                 * @var string $protocol
                 * @var int $port
                 * @var string $user
                 * @var string $password
                 * @var string $name
                 */
                $autoTLS = $expectedParameters->getValue('auto_tls');
                $auth = $expectedParameters->getValue('auth');
                $host = $expectedParameters->getValue('host');
                $protocol = $expectedParameters->getValue('protocol');
                $port = $expectedParameters->getValue('port');
                $user = $expectedParameters->getValue('user');
                $password = $expectedParameters->getValue('password');
                $name = $expectedParameters->getValue('name');

                try {

                    $mailConfig = new MailConfig;
                    $mailConfig->autoTls($autoTLS);
                    $mailConfig->auth($auth);
                    $mailConfig->host($host);
                    $mailConfig->protocol($protocol);
                    $mailConfig->port($port);
                    $mailConfig->user($user);
                    $mailConfig->password($password);
                    $mailConfig->name($name);

                    $success = true;

                    $optionName = 'mail';
                    $optionMapper = new AppConfigModel($optionName);

                    $optionMapper->value = $mailConfig->toSave();

                    if ($optionMapper->id !== null) {
                        $success = $success && $optionMapper->update();
                    } else {
                        $optionMapper->name = $optionName;
                        $success = $success && $optionMapper->save();
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
     * @return Response
     */
    public function osTicket(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;

        $requestMethod = mb_strtoupper($req->getMethod());

        set_title(__(self::LANG_GROUP, 'OsTicket'));

        if ($requestMethod == 'GET') {

            set_custom_assets([
                'statics/core/js/app_config/os-ticket.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/os-ticket.css',
            ], 'css');

            $actionURL = self::routeName('os-ticket');
            $url = get_config('osTicketAPI');
            $key = get_config('osTicketAPIKey');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'url' => $url,
                'key' => $key,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->render("{$baseViewDir}/os-ticket", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            //──── Entrada ───────────────────────────────────────────────────────────────────────────

            //Definición de validaciones y procesamiento
            $expectedParameters = new Parameters([
                new Parameter(
                    'url',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return rtrim($value, '/');
                    }
                ),
                new Parameter(
                    'key',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true
                ),
            ]);

            //Obtención de datos
            $inputData = $req->getParsedBody();

            //Asignación de datos para procesar
            $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

            //──── Estructura de respuesta ───────────────────────────────────────────────────────────

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Configuración OsTicket'));
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

                try {

                    $url = new AppConfigModel('osTicketAPI');
                    $key = new AppConfigModel('osTicketAPIKey');

                    $url->value = $expectedParameters->getValue('url');
                    $key->value = $expectedParameters->getValue('key');

                    $successUrl = $url->id !== null ? $url->update() : $url->save();
                    $successKey = $key->id !== null ? $key->update() : $key->save();
                    $success = $successUrl || $successKey;

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
     * @return Response
     */
    public function security(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;

        $requestMethod = mb_strtoupper($req->getMethod());

        set_title(__(self::LANG_GROUP, 'Seguridad'));

        if ($requestMethod == 'GET') {

            set_custom_assets([
                'statics/core/js/app_config/security.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/security.css',
            ], 'css');

            $actionURL = self::routeName('security');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->render("{$baseViewDir}/security", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            //──── Entrada ───────────────────────────────────────────────────────────────────────────

            //Definición de validaciones y procesamiento
            $expectedParameters = new Parameters([
                new Parameter(
                    'check_aud_on_auth',
                    null,
                    function ($value) {
                        return ctype_digit($value) || is_int($value) || is_bool($value) || is_null($value);
                    },
                    true,
                    function ($value) {
                        return ($value === 1 || $value == '1' || $value === true);
                    }
                ),
            ]);

            //Obtención de datos
            $inputData = $req->getParsedBody();

            //Asignación de datos para procesar
            $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

            //──── Estructura de respuesta ───────────────────────────────────────────────────────────

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Seguridad'));
            $resultOperation->setSingleOperation(true); //Se define que es de una única operación

            //Valores iniciales de la respuesta
            $resultOperation->setSuccessOnSingleOperation(false);

            //Mensajes de respuesta
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido, intente más tarde.');
            $errorSomesOptions = __(self::LANG_GROUP, 'Ha ocurrido un error al guardar algunos de los datos, es posible que algunas opciones no hayan sido actualizadas.');
            $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

            //──── Acciones ──────────────────────────────────────────────────────────────────────────
            try {

                //Intenta validar, si todo sale bien el código continúa
                $expectedParameters->validate();

                //Información del formulario
                /**
                 * @var bool $checkAudOnAuth
                 */
                $checkAudOnAuth = $expectedParameters->getValue('check_aud_on_auth');

                try {

                    $configurationsToSave = [
                        'check_aud_on_auth' => $checkAudOnAuth,
                    ];
                    $messagesOnSave = [
                        'check_aud_on_auth' => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Usar IP del usuario para encriptar el token de sesión'),
                        ]),
                    ];

                    $success = true;
                    $successMessage = __(self::LANG_GROUP, 'No ha habido ninguna modificación.');
                    $changesMessages = [];

                    foreach ($configurationsToSave as $configName => $configValue) {

                        if ($configValue !== null) {

                            $isSameValue = get_config($configName) === $configValue;

                            if (!$isSameValue) {

                                $optionMapper = new AppConfigModel($configName);
                                $optionMapper->value = $configValue;

                                if ($optionMapper->id !== null) {
                                    $configSuccess = $optionMapper->update();
                                } else {
                                    $optionMapper->name = $configName;
                                    $configSuccess = $optionMapper->save();
                                }

                                $success = $success && $configSuccess;

                                if ($configSuccess) {
                                    $changesMessages[] = $messagesOnSave[$configName];
                                } else {
                                    $unknowErrorMessage = $errorSomesOptions;
                                }

                            }

                        }

                    }

                    if (count($changesMessages) > 0) {
                        $successMessage = implode('<br>', $changesMessages);
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
     * @return Response
     */
    public function routesView(Request $req, Response $res)
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
     * @return Response
     */
    public function configurationsView(Request $req, Response $res)
    {
        import_spectrum();

        $langGroup = AppConfigController::LANG_GROUP;

        $tabsTitles = [];
        $tabsItems = [];

        $currentUser = getLoggedFrameworkUser();
        $baseViewDir = 'panel/pages/app_configurations';

        if (in_array($currentUser->type, self::ROLES_VIEW_CONFIGURATIONS_VIEW)) {

            $actionGenericURL = AppConfigController::routeName('generals-generic-action');

            $hasPermissionsGenerals = count(array_filter([
                $actionGenericURL,
            ], function ($e) {return mb_strlen(trim($e)) > 0;})) > 0;

            if ($hasPermissionsGenerals) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionGenericURL' => $actionGenericURL,
                ];

                $tabsTitles['general'] = __($langGroup, 'Generales');
                $tabsItems['general'] = $this->render("{$baseViewDir}/inc/configuration-tabs/general", $data, false, false);

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
     * @return Response
     */
    public function actionGeneric(Request $req, Response $res)
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
     * @return Response
     */
    public function createSitemap(Request $req, Response $res)
    {

        $startTime = microtime(true);

        $result = new ResultOperations([], __(self::LANG_GROUP, 'Sitemap'), '', true);

        $sitemap = new Sitemap(basepath('sitemap.xml'), false);

        $getAlternativesURLs = function ($url, callable $existOnLangVerify = null) {

            $existOnLangVerify = $existOnLangVerify !== null ? $existOnLangVerify : function ($lang) {
                return true;
            };
            $currentLang = Config::get_lang();
            $allowedLangs = Config::get_allowed_langs();
            $urls = [];

            foreach ($allowedLangs as $lang) {
                $existOnLang = ($existOnLangVerify)($lang);
                $existOnLang = is_bool($existOnLang) ? $existOnLang : false;
                if ($existOnLang && $lang != $currentLang) {
                    $url = convert_lang_url($url, $currentLang, $lang);
                    $urls[$lang] = $url;
                }
            }

            return $urls;

        };

        $somesURLs = [
            baseurl(),
            PublicationsPublicController::routeName('list'),
        ];

        $routes = array_merge(
            get_routes_by_controller(PublicationsPublicController::class),
            get_routes_by_controller(PublicAreaController::class)
        );

        foreach ($routes as $routeInfo) {
            $url = get_route_sample($routeInfo['name']);
            if (is_string($url) && mb_strpos($url, '{') === false) {
                $somesURLs[] = $url;
            }
        }

        foreach ($somesURLs as $url) {
            $sitemap->addItem(new SitemapItem($url));
            $alternativesURLs = ($getAlternativesURLs)($url);
            foreach ($alternativesURLs as $url) {
                $sitemap->addItem(new SitemapItem($url));
            }
        }

        $categories = PublicationsCategoryController::_all()->elements();
        $articles = PublicationsController::_all()->elements();

        foreach ($categories as $category) {

            $category = PublicationCategoryMapper::objectToMapper($category);
            $url = PublicationsPublicController::routeName('list-by-category', ['categorySlug' => $category->getSlug()]);

            $sitemap->addItem(new SitemapItem($url));

            $alternativesURLs = ($getAlternativesURLs)($url, function ($lang) use ($category) {
                return $category->getLangData($lang, 'name', false, null) !== null;
            });

            foreach ($alternativesURLs as $url) {
                $sitemap->addItem(new SitemapItem($url));
            }

        }

        foreach ($articles as $article) {
            $article = PublicationMapper::objectToMapper($article);
            $url = PublicationsPublicController::routeName('single', ['slug' => $article->getSlug()]);
            $date = !is_null($article->updatedAt) ? $article->updatedAt : $article->createdAt;
            $date = is_object($date) ? $date : new \DateTime($date);
            $sitemap->addItem(new SitemapItem($url, $date, SitemapItem::FREQ_WEEK));
            foreach ($article->getURLAlternatives() as $url) {
                $sitemap->addItem(new SitemapItem($url, $date, SitemapItem::FREQ_WEEK));
            }
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
     * @return Response
     */
    public function mapboxKey(Request $req, Response $res)
    {
        $keyLocal = 'pk.eyJ1Ijoic2lydmFtYiIsImEiOiJjamt1YjBzeXEwZWlvM3FxbDBuZDZmZWFtIn0.jv_5-3mX1kWLrk1ffvV2zQ';
        $keyDomain = 'pk.eyJ1Ijoic2lydmFtYiIsImEiOiJjamt1YjBzeXEwZWlvM3FxbDBuZDZmZWFtIn0.jv_5-3mX1kWLrk1ffvV2zQ';
        $key = is_local() ? $keyLocal : $keyDomain;
        $res->write($key);
        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function recreateStaticCacheStamp(Request $req, Response $res)
    {

        $startTime = microtime(true);

        $result = new ResultOperations([], __(self::LANG_GROUP, 'Limpiar caché'), '', true);

        try {

            $result->setMessage(__(self::LANG_GROUP, 'Memoria caché restaurada'));

            $endTime = microtime(true);

            //Actualizar marca de caché de estáticos js/css
            static_files_cache_stamp(true);

            $publicationsCache = new DirectoryObject(basepath('app/cache/Publications'));
            $publicationsCache->process();
            $publicationsCache->delete();

            $result->setValue('Rendimiento', [
                'Memory peak usage' => number_format(memory_get_peak_usage() / (1024 * 1024), 4) . "MB",
                'Execution time' => number_format($endTime - $startTime, 10) . "s",
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
     * @return array
     */
    public static function getSEOConfigValues()
    {

        $defaultLang = Config::get_default_lang();
        $allowedLangs = Config::get_allowed_langs();

        $values = [];

        foreach (self::SEO_OPTIONS_CONFIG_NAME_BY_FORM_NAME as $nameOnForm => $nameOnConfig) {

            $valuesToAdd = [
                $defaultLang => [],
            ];
            $valuesToAdd[$defaultLang][$nameOnForm] = AppConfigModel::getConfigValue($nameOnConfig);

            foreach ($allowedLangs as $lang) {

                if (!array_key_exists($lang, $valuesToAdd)) {
                    $valuesToAdd[$lang] = [];
                }

                if ($lang !== $defaultLang) {
                    $nameLang = "{$nameOnConfig}_{$lang}";
                    $nameLangOnForm = "{$nameOnForm}_{$lang}";
                    $valueLang = AppConfigModel::getConfigValue($nameLang);
                    $defaultValue = $valuesToAdd[$defaultLang][$nameOnForm];

                    if ($valueLang === null) {

                        $newConfig = new AppConfigModel();
                        $newConfig->name = $nameLang;

                        if ($nameOnConfig == self::SEO_OPTION_OPEN_GRAPH_IMAGE) {

                            $folder = 'statics/images';
                            $nameImage = 'open_graph';
                            $extension = 'jpg';
                            $relativePath = "{$folder}/{$nameImage}_{$lang}.{$extension}";
                            $newConfig->value = $relativePath;

                            if (file_exists(basepath($defaultValue))) {

                                if (@copy(basepath($defaultValue), basepath($relativePath))) {
                                    $newConfig->save();
                                }

                            }

                        } else {
                            $newConfig->value = $defaultValue;
                            $newConfig->save();
                        }

                        $valueLang = $newConfig->value;

                    }

                    if ($nameOnConfig == self::SEO_OPTION_OPEN_GRAPH_IMAGE) {

                        if (file_exists(basepath($defaultValue)) && !file_exists(basepath($valueLang))) {
                            @copy(basepath($defaultValue), basepath($valueLang));
                        }

                    }

                    $valuesToAdd[$lang][$nameLangOnForm] = $valueLang;

                }

            }

            foreach ($valuesToAdd as $lang => $valuesOnLang) {

                if (!array_key_exists($lang, $values)) {
                    $values[$lang] = [];
                }

                foreach ($valuesOnLang as $name => $value) {

                    if (strpos($name, self::SEO_OPTION_TITLE_APP_ON_FORM) !== false || strpos($name, self::SEO_OPTION_OWNER_ON_FORM) !== false) {
                        $value = htmlentities($value);
                    }

                    $values[$lang][$name] = $value;
                }
            }

        }

        return $values;
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

            case self::PARSE_TYPE_INT:

                if (is_scalar($value) && is_numeric($value)) {
                    return (int) $value;
                }
                return $value;

            case self::PARSE_TYPE_FLOAT:
            case self::PARSE_TYPE_DOUBLE:

                if (is_scalar($value) && is_numeric($value)) {
                    return (double) $value;
                }
                return $value;

            case self::PARSE_TYPE_JSON_ENCODE:
                $parsedValue = json_encode($value);
                if (json_last_error() == \JSON_ERROR_NONE) {
                    return $parsedValue;
                } else {
                    return $value;
                }

            case self::PARSE_TYPE_JSON_DECODE:
                $parsedValue = json_decode($value);
                if (json_last_error() == \JSON_ERROR_NONE) {
                    return $parsedValue;
                } else {
                    return $value;
                }

            case self::PARSE_TYPE_UPPERCASE:
                if (is_string($value)) {
                    return mb_strtoupper($value);
                } else {
                    return $value;
                }

            case self::PARSE_TYPE_LOWERCASE:
                if (is_string($value)) {
                    return mb_strtolower($value);
                } else {
                    return $value;
                }

            default:

                return $value;
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
        $current_user = getLoggedFrameworkUser();

        if ($current_user !== null) {
            $allowed = Roles::hasPermissions($name, $current_user->type);
        } else {
            $allowed = true;
        }

        if ($allowed) {
            $routeResult = get_route(
                $name,
                $params,
                $silentOnNotExists
            );
            return is_string($routeResult) ? $routeResult : '';
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

            //Mapbox Key
            new Route(
                "{$startRoute}/mapbox-key[/]",
                $classname . ':mapboxKey',
                self::$baseRouteName . '-' . 'mapbox-key',
                'GET'
            ),

            //Limpiar caché
            new Route(
                "{$startRoute}cache-clean[/]",
                $classname . ':recreateStaticCacheStamp',
                'configurations-generals-cache-clean',
                'POST',
                true,
                null,
                self::ROLES_CLEAN_CACHE_ACTION
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

            //Email
            new Route(
                "{$startRoute}/email[/]",
                $classname . ':email',
                self::$baseRouteName . '-' . 'email',
                'GET|POST',
                true,
                null,
                self::ROLES_EMAIL
            ),

            //OsTicket
            new Route(
                "{$startRoute}/os-ticket[/]",
                $classname . ':osTicket',
                self::$baseRouteName . '-' . 'os-ticket',
                'GET|POST',
                true,
                null,
                self::ROLES_OS_TICKET
            ),

            //Seguridad
            new Route(
                "{$startRoute}/security[/]",
                $classname . ':security',
                self::$baseRouteName . '-' . 'security',
                'GET|POST',
                true,
                null,
                self::ROLES_SECURITY
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
