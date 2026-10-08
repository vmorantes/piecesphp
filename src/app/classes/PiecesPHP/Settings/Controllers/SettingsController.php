<?php

/**
 * SettingsController.php
 */

namespace PiecesPHP\Settings\Controllers;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Helpers\Directories\FilesIgnore;
use PiecesPHP\Core\MaintenanceMode;
use PiecesPHP\Core\ServerStatics;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Core\Utilities\ReturnTypes\Operation;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\LangInjector;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;

/**
 * SettingsController.
 *
 * @package     PiecesPHP\Settings\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class SettingsController extends AdminPanelController
{

    use ControllerRoutingTrait;
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
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];
    const ROLES_LOGOS_FAVICONS = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];
    const ROLES_SEO = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];
    //Solo el principal: quien cambia el destino del correo con la contraseña guardada se la lleva (pendientes.md 404).
    const ROLES_EMAIL = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_OS_TICKET = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_SECURITY_AND_AI = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];
    const ROLES_VIEW_CONFIGURATIONS_VIEW = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];
    const ROLES_GENERIC_ACTION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];
    /**
     * Claves que solo el usuario principal puede escribir por la acción genérica.
     *
     * SEGUNDA CAPA: la primera es GENERIC_SAVE_ALLOWED, que ya solo deja pasar colores de marca. Esta queda por si
     * alguien amplía esa lista: ninguna de estas debe poder escribirla otro rol por aquí.
     *
     * Por constante, nunca por cadena escrita a mano: si una clave cambia de nombre, esto la sigue.
     *
     * @var string[]
     */
    const ROOT_ONLY_CONFIG_KEYS = [
        MaintenanceMode::ENABLED_CONFIG,
        MaintenanceMode::ALLOWED_ROLES_CONFIG,
        MaintenanceMode::RETRY_AFTER_CONFIG,
        //Credenciales: cambiar el destino y dejar el secreto vacío lo enviaría a otro servidor (pendientes.md 402).
        self::MAIL_CONFIG,
        MailDelivery::CONFIG_NAME,
        self::OS_TICKET_URL_CONFIG,
        self::OS_TICKET_KEY_CONFIG,
        //Las que tienen pantalla o tarea solo del principal (pendientes.md 405): un script inyectado lee la sesión de
        //cualquiera; la fecha mínima echa a todos o deshace una revocación; y respaldos y avisos.
        \PiecesPHP\Core\Utilities\Helpers\ExtraScripts::CONFIG_NAME,
        \PiecesPHP\Core\SessionToken::MINIMUM_DATE_CONFIG,
        \PiecesPHP\Core\Backups\BackupPolicy::CONFIG_NAME,
        \PiecesPHP\SystemStatus\SystemAlertRegistry::HIDDEN_CONFIG,
    ];
    /**
     * Lo ÚNICO que guarda la acción genérica: los colores de marca de la pestaña «Colores» (decisión del PO, pendientes.md
     * 176.2). Todo lo demás tiene su acción propia, con su permiso y su validación. ROOT_ONLY_CONFIG_KEYS y el patrón del
     * nombre se quedan como segunda capa.
     *
     * @var string[]
     */
    const GENERIC_SAVE_ALLOWED = [
        'main_brand_color',
        'second_brand_color',
        'font_color_one',
        'font_color_two',
        'menu_color_background',
        'menu_color_mark',
        'menu_color_font',
        'meta_theme_color',
        'bg_tools_buttons',
    ];
    /**
     * Los colores que se pintan con un alfa concatenado: solo #RRGGBB. El vacío se rechaza aunque el selector lo permita.
     *
     * @var string[]
     */
    const BRAND_COLORS_SIX_HEX = [
        'main_brand_color',
        'menu_color_background',
    ];
    /**
     * La configuración SMTP, cifrada, y las de osTicket.
     */
    const MAIL_CONFIG = 'mail';
    const OS_TICKET_URL_CONFIG = 'osTicketAPI';
    const OS_TICKET_KEY_CONFIG = 'osTicketAPIKey';
    const ROLES_ROUTES_VIEWS = [
        UsersModel::TYPE_USER_ROOT,
    ];
    /**
     * Solo el principal: un script inyectado en el panel lee la sesión de cualquiera, la del principal incluida.
     */
    const ROLES_INJECTED_SCRIPTS = self::ROLES_ROUTES_VIEWS;
    /**
     * Solo el principal: un respaldo contiene TODA la base. Constante propia y no la de
     * scripts, porque el motivo es otro y cada una puede cambiar sin la otra.
     */
    const ROLES_BACKUPS = [
        UsersModel::TYPE_USER_ROOT,
    ];
    const ROLES_CLEAN_CACHE_ACTION = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];

    /**
     * Lo que guarda cada una de las dos pantallas que eran «Seguridad e IA». Una pantalla solo valida y escribe las
     * suyas: si validara todas, guardar la seguridad vaciaría las claves de IA, que no viajan en su formulario.
     */
    const SECURITY_CONFIG_KEYS = [
        'check_aud_on_auth',
        'hide_app_key_warning',
    ];
    const AI_CONFIG_KEYS = [
        'modelOpenAI',
        'modelMistral',
        'OpenAIApiKey',
        'MistralAIApiKey',
        'translationAI',
        'translationAIEnable',
    ];

    /**
     * De AI_CONFIG_KEYS, los secretos: el formulario no los pinta.
     */
    const AI_SECRET_CONFIG_KEYS = [
        'OpenAIApiKey',
        'MistralAIApiKey',
    ];

    const SEO_OPTION_TITLE_APP = 'title_app';
    const SEO_OPTION_OWNER = 'owner';
    const SEO_OPTION_DESCRIPTION = 'description';
    const SEO_OPTION_KEYWORDS = 'keywords';
    const SEO_OPTION_OPEN_GRAPH_IMAGE = 'open_graph_image';
    const SEO_OPTION_SHARE_TITLE = 'share_title';
    const SEO_OPTION_SHARE_DESCRIPTION = 'share_description';
    /**
     * La cuenta de X del sitio: una sola, sin idioma, y por eso fuera de SEO_OPTIONS_CONFIG_NAME_BY_FORM_NAME.
     */
    const SEO_OPTION_X_ACCOUNT = 'x_account';

    const SEO_OPTION_TITLE_APP_ON_FORM = 'titleApp';
    const SEO_OPTION_OWNER_ON_FORM = 'owner';
    const SEO_OPTION_DESCRIPTION_ON_FORM = 'description';
    const SEO_OPTION_KEYWORDS_ON_FORM = 'keywords';
    const SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM = 'openGraph';
    const SEO_OPTION_SHARE_TITLE_ON_FORM = 'shareTitle';
    const SEO_OPTION_SHARE_DESCRIPTION_ON_FORM = 'shareDescription';
    const SEO_OPTION_X_ACCOUNT_ON_FORM = 'xAccount';

    const SEO_OPTIONS_CONFIG_NAME_BY_FORM_NAME = [
        self::SEO_OPTION_TITLE_APP_ON_FORM => self::SEO_OPTION_TITLE_APP,
        self::SEO_OPTION_OWNER_ON_FORM => self::SEO_OPTION_OWNER,
        self::SEO_OPTION_DESCRIPTION_ON_FORM => self::SEO_OPTION_DESCRIPTION,
        self::SEO_OPTION_KEYWORDS_ON_FORM => self::SEO_OPTION_KEYWORDS,
        self::SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM => self::SEO_OPTION_OPEN_GRAPH_IMAGE,
        self::SEO_OPTION_SHARE_TITLE_ON_FORM => self::SEO_OPTION_SHARE_TITLE,
        self::SEO_OPTION_SHARE_DESCRIPTION_ON_FORM => self::SEO_OPTION_SHARE_DESCRIPTION,
    ];

    /**
     * @var SettingsModel
     */
    protected $mapper;

    /**
     * @var string
     */
    protected static $baseRouteName = 'configurations';

    /**
     * Las nueve vistas de este módulo las pide ESTE ayudante, que lleva el directorio del módulo.
     *
     * En `$this` no puede ir: las dieciséis llamadas al armazón (`panel/layout/header` y
     * `panel/layout/footer`) son vistas GLOBALES y dejarían de encontrarse. Y tampoco se reutiliza
     * el `$moduleViews` heredado de `AdminPanelController`: apunta al módulo del panel, y
     * sobreescribirlo haría que sus tres vistas propias se buscaran aquí si alguna vez un método
     * heredado corriera sobre una instancia de esta clase.
     *
     * @var BaseController
     */
    protected $settingsViews = null;

    public function __construct()
    {
        parent::__construct();
        $this->mapper = new SettingsModel();
        $this->model = $this->mapper->getModel();
        $this->settingsViews = (new HelperController($this->user, $this->getGlobalVariables()))
            ->setInstanceViewDir(__DIR__ . '/../Views/');
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
        set_title(__(self::LANG_GROUP, 'Fondos'));

        if ($requestMethod == 'GET') {

            import_cropper();

            set_custom_assets([
                'statics/core/js/app_config/backgrounds.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/backgrounds.css',
            ], 'css');

            $actionURL = self::routeName('appearance-backgrounds');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->settingsViews->render("{$baseViewDir}/backgrounds", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            $allowedImages = [
                'background-1' => 'bg1',
                'background-2' => 'bg2',
                'background-3' => 'bg3',
                'background-4' => 'bg4',
                'background-5' => 'bg5',
                'problems-background' => 'problems-background',
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

            $currentBackgroundConfigMapper = new SettingsModel('backgrounds');
            $oldImage = '';
            $currentBackgroundConfigValues = $currentBackgroundConfigMapper->value;
            //Sin la opción guardada (o con otra forma) no hay fondos que recorrer.
            $currentBackgroundConfigValues = is_array($currentBackgroundConfigValues) ? $currentBackgroundConfigValues : (is_object($currentBackgroundConfigValues) ? (array) $currentBackgroundConfigValues : []);

            foreach ($currentBackgroundConfigValues as $i => $v) {
                if (mb_strlen($nameImage) > 0 && str_contains($v, $nameImage)) {
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

            $actionURL = self::routeName('appearance-brand-images');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'publicFavicon' => add_cache_stamp_to_url(get_config('favicon')),
                'backFavicon' => get_config('favicon-back'),
                'logo' => get_config('logo'),
                'partners' => get_config('partners'),
                'partnersVertical' => get_config('partnersVertical'),
                'mailingLogo' => get_config('mailing_logo'),
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->settingsViews->render("{$baseViewDir}/logos-favicons", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            $allowedImages = [
                'favicon' => 'favicon',
                'favicon-back' => 'favicon-back',
                'logo' => 'logo',
                'partners' => 'partners',
                'partnersVertical' => 'partners-vertical',
                'mailingLogo' => 'mailing-logo',
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

            $logosAndFaviconsMapper = new SettingsModel($nameCurrentAllowedImage);
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

                        $updated = $logosAndFaviconsMapper->id !== null ? $logosAndFaviconsMapper->update() : SettingsModel::setConfigValue($nameCurrentAllowedImage, $relativePath);

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
        //Esta vista materializa la configuración por idioma a propósito: no lo muevas a la CLI.

        $langGroup = self::LANG_GROUP;

        $requestMethod = mb_strtoupper($req->getMethod());

        set_title(__(self::LANG_GROUP, 'Identidad y SEO'));

        if ($requestMethod == 'GET') {

            import_cropper();

            set_custom_assets([
                'statics/core/js/app_config/seo.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/seo.css',
            ], 'css');

            $actionURL = self::routeName('appearance-seo');

            $SEOValues = self::getSEOConfigValues();

            foreach ($SEOValues as $lang => $values) {

                foreach ($values as $name => $value) {

                    if (str_contains($name, self::SEO_OPTION_KEYWORDS_ON_FORM)) {

                        $keywordsToSelect = [];

                        $keywords = $value;
                        if (is_array($keywords) && !empty($keywords)) {

                            //Valor y texto a la vez, escritos por quien tiene el SEO: se escapan los dos y la selección.
                            $keywords = array_map('escape_html', array_filter($keywords, 'is_scalar'));
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

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'SEOValues' => $SEOValues,
                'xAccount' => SettingsModel::getConfigValue(self::SEO_OPTION_X_ACCOUNT),
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->settingsViews->render("{$baseViewDir}/seo", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            //──── Entrada ───────────────────────────────────────────────────────────────────────────

            //Definición de validaciones y procesamiento
            $expectedParameters = new Parameters([
                new Parameter(
                    'lang',
                    null,
                    function ($value) {
                        //Solo un idioma de la instalación: con él se forma el nombre de la opción y del archivo de la imagen.
                        return is_string($value) && in_array($value, Config::get_allowed_langs(), true);
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
                    self::SEO_OPTION_SHARE_TITLE_ON_FORM,
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
                    self::SEO_OPTION_SHARE_DESCRIPTION_ON_FORM,
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return clean_string($value);
                    }
                ),
                //Solo la trae el formulario del idioma por omisión: sin ella, no se toca.
                new Parameter(
                    self::SEO_OPTION_X_ACCOUNT_ON_FORM,
                    null,
                    function ($value) {
                        return $value === null || (is_string($value) && preg_match('/^(@[A-Za-z0-9_]+)?$/', trim($value)) === 1);
                    },
                    true,
                    function ($value) {
                        return is_string($value) ? trim($value) : null;
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
            ]);

            //Obtención de datos
            $inputData = $req->getParsedBody();

            //Asignación de datos para procesar
            $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

            //──── Estructura de respuesta ───────────────────────────────────────────────────────────

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Identidad y SEO'));
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

                //Un parámetro opcional inválido cae en silencio a su valor por omisión: la cuenta se rechaza aquí, antes de escribir nada.
                $xAccountInput = is_array($inputData) ? ($inputData[self::SEO_OPTION_X_ACCOUNT_ON_FORM] ?? null) : null;
                if ($xAccountInput !== null && (!is_string($xAccountInput) || preg_match('/^(@[A-Za-z0-9_]+)?$/', trim($xAccountInput)) !== 1)) {
                    throw new InvalidParameterValueException(__(self::LANG_GROUP, 'La cuenta de X empieza por @ y solo lleva letras, números y guion bajo.'));
                }

                //Información del formulario
                /**
                 * @var string $lang
                 * @var string $titleApp
                 * @var string $owner
                 * @var string $description
                 * @var string[] $keywords
                 */
                $lang = $expectedParameters->getValue('lang');
                $titleApp = $expectedParameters->getValue('titleApp');
                $owner = $expectedParameters->getValue('owner');
                $description = $expectedParameters->getValue('description');
                $keywords = $expectedParameters->getValue('keywords');
                $shareTitle = $expectedParameters->getValue(self::SEO_OPTION_SHARE_TITLE_ON_FORM);
                $shareDescription = $expectedParameters->getValue(self::SEO_OPTION_SHARE_DESCRIPTION_ON_FORM);
                $xAccount = $expectedParameters->getValue(self::SEO_OPTION_X_ACCOUNT_ON_FORM);

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
                        self::SEO_OPTION_SHARE_TITLE => $shareTitle,
                        self::SEO_OPTION_SHARE_DESCRIPTION => $shareDescription,
                    ];

                    $success = true;

                    if (is_string($xAccount)) {
                        $success = SettingsModel::setConfigValue(self::SEO_OPTION_X_ACCOUNT, $xAccount);
                    }

                    foreach ($options as $optionName => $optionValue) {

                        if ($lang !== $defaultLang) {
                            $optionName .= "_{$lang}";
                        }

                        $optionMapper = new SettingsModel($optionName);

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

                            $openGraphMapper = new SettingsModel($nameOptionOG);
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
                    $reference = log_exception($e);
                    $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));
                }

            } catch (MissingRequiredParameterException $e) {

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

        set_title(__(self::LANG_GROUP, 'Correo'));

        if ($requestMethod == 'GET') {

            set_custom_assets([
                'statics/core/js/app_config/email.js',
            ], 'js');

            set_custom_assets([
                'statics/core/css/app_config/email.css',
            ], 'css');

            $actionURL = self::routeName('integrations-mail');

            $element = new MailConfig;

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'element' => $element,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->settingsViews->render("{$baseViewDir}/email", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            //──── Entrada ───────────────────────────────────────────────────────────────────────────

            //Definición de validaciones y procesamiento
            $expectedParameters = new Parameters([
                new Parameter(
                    'auto_tls',
                    null,
                    function ($value) {
                        return Validator::isInteger($value) || is_bool($value);
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
                        return Validator::isInteger($value) || is_bool($value);
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
                        return Validator::isInteger($value);
                    },
                    false,
                    function ($value) {
                        return (int) $value;
                    }
                ),
                new Parameter(
                    'mail_delivery',
                    MailDelivery::AUTO,
                    function ($value) {
                        return is_string($value) && in_array($value, MailDelivery::MODES, true);
                    },
                    true
                ),
                new Parameter(
                    'test_host',
                    '127.0.0.1',
                    function ($value) {
                        return is_string($value) && mb_strlen(trim($value)) > 0;
                    },
                    true,
                    function ($value) {
                        return trim((string) $value);
                    }
                ),
                new Parameter(
                    'test_port',
                    1025,
                    function ($value) {
                        return Validator::isInteger($value);
                    },
                    true,
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

            $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Correo'));
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
                /**
                 * @var string $mailDelivery
                 * @var string $testHost
                 * @var int $testPort
                 */
                $mailDelivery = $expectedParameters->getValue('mail_delivery');
                $testHost = $expectedParameters->getValue('test_host');
                $testPort = $expectedParameters->getValue('test_port');

                try {

                    $mailConfig = new MailConfig;
                    $mailConfig->autoTls($autoTLS);
                    $mailConfig->auth($auth);
                    $mailConfig->host($host);
                    $mailConfig->protocol($protocol);
                    $mailConfig->port($port);
                    $mailConfig->user($user);
                    //Vacía conserva la guardada: el formulario no la pinta. MailConfig ya la cargó descifrada.
                    if ($password !== '') {
                        $mailConfig->password($password);
                    }
                    $mailConfig->name($name);
                    $mailConfig->testHost($testHost);
                    $mailConfig->testPort($testPort);

                    $success = true;

                    //La entrega NO va en el array `mail`: es opción propia, para que la pantalla del SMTP
                    //no pueda machacarla al guardar (P52) y para que un aviso la lea sin descifrar nada.
                    $success = $success && SettingsModel::setConfigValue(MailDelivery::CONFIG_NAME, $mailDelivery);
                    set_config(MailDelivery::CONFIG_NAME, $mailDelivery);

                    $optionName = 'mail';
                    $optionMapper = new SettingsModel($optionName);

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
                    $reference = log_exception($e);
                    $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));
                }

            } catch (MissingRequiredParameterException $e) {

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

            $actionURL = self::routeName('integrations-osticket');
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
            $this->settingsViews->render("{$baseViewDir}/os-ticket", $data);
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

                    $url = new SettingsModel('osTicketAPI');
                    $key = new SettingsModel('osTicketAPIKey');

                    $url->value = $expectedParameters->getValue('url');
                    $newKey = $expectedParameters->getValue('key');

                    $successUrl = $url->id !== null ? $url->update() : $url->save();
                    //Vacía conserva la guardada: el formulario no la pinta.
                    $successKey = true;
                    if ($newKey !== '') {
                        $key->value = $newKey;
                        $successKey = $key->id !== null ? $key->update() : $key->save();
                    }
                    //Éxito solo si se guardó todo lo que había que guardar.
                    $success = $successUrl && $successKey;

                    if ($success) {
                        $resultOperation->setMessage($successMessage);
                        $resultOperation->setSuccessOnSingleOperation($success);
                    } else {
                        $resultOperation->setMessage($unknowErrorMessage);
                    }

                } catch (\Exception $e) {
                    $reference = log_exception($e);
                    $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));
                }

            } catch (MissingRequiredParameterException $e) {

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
        return $this->securityOrAI($req, $res, 'system-security', __(self::LANG_GROUP, 'Seguridad'), 'security', self::SECURITY_CONFIG_KEYS);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function ai(Request $req, Response $res)
    {
        return $this->securityOrAI($req, $res, 'integrations-ai', __(self::LANG_GROUP, 'Inteligencia artificial'), 'ai', self::AI_CONFIG_KEYS);
    }

    /**
     * «Archivos para buscadores»: lo que la instalación añade a robots.txt, humans.txt y llms.txt, con la base del
     * framework a la vista.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function siteFilesView(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        set_title(__($langGroup, 'Archivos para buscadores'));

        set_custom_assets([
            'statics/core/js/app_config/site-files.js',
        ], 'js');
        set_custom_assets([
            'statics/core/css/app_config/site-files.css',
        ], 'css');

        $added = fn(string $name): string => is_string($value = SettingsModel::getConfigValue($name)) ? $value : '';
        $data = [
            'langGroup' => $langGroup,
            'actionURL' => self::routeName('appearance-site-files-save'),
            'files' => [
                'robots' => ['file' => 'robots.txt', 'title' => __($langGroup, 'Qué pueden visitar los buscadores'), 'help' => __($langGroup, 'Por ejemplo, para que los buscadores no entren en una página: Disallow: /mi-pagina/'), 'value' => $added(SiteFilesController::CONFIG_ROBOTS), 'base' => SiteFilesController::robotsBase(), 'url' => (string) SiteFilesController::routeName('robots', [], true)],
                'humans' => ['file' => 'humans.txt', 'title' => __($langGroup, 'Quién hizo el sitio'), 'help' => __($langGroup, 'El equipo, los agradecimientos o lo que quieras contar sobre el sitio.'), 'value' => $added(SiteFilesController::CONFIG_HUMANS), 'base' => SiteFilesController::humansBase(), 'url' => (string) SiteFilesController::routeName('humans', [], true)],
                'llms' => ['file' => 'llms.txt', 'title' => __($langGroup, 'Presentación para inteligencias artificiales'), 'help' => __($langGroup, 'Texto en formato Markdown que se añade al final del resumen que ya genera el sistema.'), 'value' => $added(SiteFilesController::CONFIG_LLMS), 'base' => SiteFilesController::llmsBase(), 'url' => (string) SiteFilesController::routeName('llms', [], true)],
            ],
        ];

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/site-files', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Guarda los tres añadidos. Una línea de robots.txt que no sea vacía, comentario o `Campo: valor` con un campo de
     * robots rechaza el guardado entero, sin escribir nada. Responde 200 con `success: false`, como las demás de
     * configuración: genericFormHandler enseña un 400 como fallo de conexión y se pierde el mensaje.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function siteFilesSave(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        $resultOperation = new ResultOperations([], __($langGroup, 'Archivos para buscadores'));
        $resultOperation->setSingleOperation(true);
        $resultOperation->setSuccessOnSingleOperation(false);

        $input = $req->getParsedBody();
        //Un campo que falte no vacía lo guardado: rechaza, como uno que no sea texto.
        $text = fn(string $name): ?string => is_array($input) && is_string($input[$name] ?? null) ? $input[$name] : null;
        $values = [
            SiteFilesController::CONFIG_ROBOTS => $text('robots'),
            SiteFilesController::CONFIG_HUMANS => $text('humans'),
            SiteFilesController::CONFIG_LLMS => $text('llms'),
        ];

        if (in_array(null, $values, true) || !SiteFilesController::isValidRobotsAddition((string) $values[SiteFilesController::CONFIG_ROBOTS])) {
            $resultOperation->setMessage(__($langGroup, 'Cada línea que añadas tiene que ser un comentario (empieza por #) o una regla del tipo «Disallow: /ruta/». Se admiten User-agent, Allow, Disallow, Sitemap y Crawl-delay.'));
            return $res->withJson($resultOperation);
        }

        try {
            $success = true;
            foreach ($values as $name => $value) {
                $success = SettingsModel::setConfigValue($name, trim((string) $value)) && $success;
            }
            $success = SettingsModel::setConfigValue(SiteFilesController::CONFIG_UPDATED, date('Y/m/d')) && $success;
            $resultOperation->setMessage($success ? __($langGroup, 'Datos guardados.') : __($langGroup, 'Ha ocurrido un error desconocido, intente más tarde.'));
            $resultOperation->setSuccessOnSingleOperation($success);
        } catch (\Exception $e) {
            $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage(log_exception($e)));
        }

        return $res->withJson($resultOperation);
    }

    /**
     * La pantalla de los scripts inyectados en las páginas, por zona y punto.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function scriptsView(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        set_title(__($langGroup, 'Scripts'));

        set_custom_assets([
            'statics/core/js/app_config/scripts.js',
        ], 'js');
        set_custom_assets([
            'statics/core/css/app_config/scripts.css',
        ], 'css');

        $data = [
            'langGroup' => $langGroup,
            'actionURL' => self::routeName('integrations-scripts-save'),
            'entries' => ExtraScripts::entries(),
            'zones' => [
                ExtraScripts::ZONE_PANEL => __($langGroup, 'Panel'),
                ExtraScripts::ZONE_PUBLIC => __($langGroup, 'Sitio público'),
                ExtraScripts::ZONE_BOTH => __($langGroup, 'Los dos'),
            ],
            'positions' => [
                ExtraScripts::POSITION_HEAD => __($langGroup, 'En la cabecera'),
                ExtraScripts::POSITION_BODY_START => __($langGroup, 'Al empezar la página'),
                ExtraScripts::POSITION_BODY_END => __($langGroup, 'Al final de la página'),
            ],
        ];

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/scripts', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Guarda la lista entera de scripts inyectados. Una entrada inválida rechaza todas: 400 y nada escrito.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function scriptsSave(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        $resultOperation = new ResultOperations([], __($langGroup, 'Scripts'));
        $resultOperation->setSingleOperation(true);
        $resultOperation->setSuccessOnSingleOperation(false);

        $inputData = $req->getParsedBody();
        $raw = is_array($inputData) ? ($inputData['entries'] ?? null) : null;
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $entries = [];
        $valid = is_array($decoded) && array_is_list($decoded);
        foreach ($valid ? $decoded : [] as $entry) {
            $normalized = ExtraScripts::normalizeEntry($entry);
            if ($normalized === null) {
                $valid = false;
                break;
            }
            $entries[] = $normalized;
        }

        if (!$valid) {
            $resultOperation->setMessage(__($langGroup, 'Cada script necesita un nombre y elegir dónde y en qué parte va.'));
            return $res->withJson($resultOperation, 400);
        }

        try {
            if (SettingsModel::setConfigValue(ExtraScripts::CONFIG_NAME, $entries)) {
                $resultOperation->setMessage(__($langGroup, 'Datos guardados.'));
                $resultOperation->setSuccessOnSingleOperation(true);
            } else {
                $resultOperation->setMessage(__($langGroup, 'Ha ocurrido un error desconocido, intente más tarde.'));
            }
        } catch (\Exception $e) {
            $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage(log_exception($e)));
        }

        return $res->withJson($resultOperation);
    }

    /**
     * La pantalla de la política de respaldos (ADR 0038 §6). Solo el principal.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function backupsView(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        set_title(__($langGroup, 'Respaldos'));

        set_custom_assets([
            'statics/core/js/app_config/backups.js',
        ], 'js');
        set_custom_assets([
            'statics/core/css/app_config/backups.css',
        ], 'css');

        $policy = BackupPolicy::current();
        $directory = BackupPolicy::dumpsDirectory();
        $plan = BackupRotation::plan(BackupRotation::namesIn($directory), $policy);

        //El tamaño sale solo de los respaldos que la conservación mira: lo ajeno no es nuestro.
        $bytes = 0;
        $latest = null;
        foreach (array_merge($plan['keep'], $plan['delete']) as $name) {
            $size = @filesize("{$directory}/{$name}");
            $bytes += is_int($size) ? $size : 0;
            $date = BackupRotation::dateFromName($name);
            if ($date !== null && ($latest === null || $date > $latest)) {
                $latest = $date;
            }
        }

        $data = [
            'langGroup' => $langGroup,
            'actionURL' => self::routeName('system-backups-save'),
            'policy' => $policy,
            'limits' => [
                'interval_minutes' => [BackupPolicy::MIN_INTERVAL_MINUTES, BackupPolicy::MAX_INTERVAL_MINUTES],
                'keep_recent' => [BackupPolicy::MIN_KEEP_RECENT, BackupPolicy::MAX_KEEP_RECENT],
                'keep_daily' => [BackupPolicy::MIN_KEEP_PERIOD, BackupPolicy::MAX_KEEP_PERIOD],
                'keep_weekly' => [BackupPolicy::MIN_KEEP_PERIOD, BackupPolicy::MAX_KEEP_PERIOD],
                'keep_monthly' => [BackupPolicy::MIN_KEEP_PERIOD, BackupPolicy::MAX_KEEP_PERIOD],
            ],
            'status' => [
                'total' => count($plan['keep']) + count($plan['delete']),
                'bytes' => $bytes,
                'latest' => $latest,
                'next' => $latest !== null ? $latest->modify("+{$policy['interval_minutes']} minutes") : null,
                'due' => BackupPolicy::isDue(),
            ],
            'plan' => [
                'keep' => count($plan['keep']),
                'delete' => count($plan['delete']),
                'ignored' => count($plan['ignored']),
            ],
            'tables' => self::databaseTables(),
            'codeExcluded' => BackupPolicy::codeExcludedDataTables(),
        ];

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/backups', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Guarda la política de respaldos. Un campo inválido la rechaza entera: 400 y nada escrito.
     *
     * La lista de tablas de la base SIEMPRE se le pasa a `normalize()`, que es la condición con
     * la que su segundo argumento puede ser opcional (ADR 0038 §1).
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function backupsSave(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        $resultOperation = new ResultOperations([], __($langGroup, 'Respaldos'));
        $resultOperation->setSingleOperation(true);
        $resultOperation->setSuccessOnSingleOperation(false);

        $inputData = $req->getParsedBody();
        $raw = is_array($inputData) ? ($inputData['policy'] ?? null) : null;
        $policy = BackupPolicy::normalize(is_string($raw) ? $raw : null, self::databaseTables());

        if ($policy === null) {
            $resultOperation->setMessage(__($langGroup, 'Alguno de los valores no es válido: revise que los números estén dentro de sus límites, que se guarde al menos un respaldo reciente y que las tablas elegidas existan.'));
            return $res->withJson($resultOperation, 400);
        }

        try {
            if (SettingsModel::setConfigValue(BackupPolicy::CONFIG_NAME, $policy)) {
                //`get_config()` sirve lo cargado al arrancar: sin esto, el resto de ESTA petición vería la política vieja.
                set_config(BackupPolicy::CONFIG_NAME, json_encode($policy));
                $resultOperation->setMessage(__($langGroup, 'Datos guardados.'));
                $resultOperation->setSuccessOnSingleOperation(true);
            } else {
                $resultOperation->setMessage(__($langGroup, 'Ha ocurrido un error desconocido, intente más tarde.'));
            }
        } catch (\Exception $e) {
            $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage(log_exception($e)));
        }

        return $res->withJson($resultOperation);
    }

    /**
     * Los nombres de las tablas de la base, para validar las que salen sin filas.
     *
     * @return string[] Vacío si no hay conexión: entonces no se puede validar ninguna.
     */
    protected static function databaseTables(): array
    {
        $db = (new \PiecesPHP\Core\BaseModel())->getDatabase();
        if ($db === null) {
            return [];
        }
        $statement = $db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
        $tables = $statement->fetchAll(\PDO::FETCH_COLUMN);
        return is_array($tables) ? array_values(array_map('strval', $tables)) : [];
    }

    /**
     * Las dos pantallas que eran «Seguridad e IA»: misma validación y mismo guardado, cada una con sus claves.
     *
     * @param Request $req
     * @param Response $res
     * @param string $routeSuffix El sufijo de la ruta de la pantalla, que es también adonde envía su formulario.
     * @param string $title Ya traducido.
     * @param string $asset El nombre de su vista, su hoja y su script.
     * @param string[] $keys Las configuraciones que esta pantalla valida y escribe.
     * @return Response
     */
    private function securityOrAI(Request $req, Response $res, string $routeSuffix, string $title, string $asset, array $keys)
    {
        $langGroup = self::LANG_GROUP;

        $requestMethod = mb_strtoupper($req->getMethod());

        set_title($title);

        if ($requestMethod == 'GET') {

            set_custom_assets([
                "statics/core/js/app_config/{$asset}.js",
            ], 'js');

            set_custom_assets([
                "statics/core/css/app_config/{$asset}.css",
            ], 'css');

            $actionURL = self::routeName($routeSuffix);

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
            ];

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->settingsViews->render("{$baseViewDir}/{$asset}", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST') {

            //──── Entrada ───────────────────────────────────────────────────────────────────────────

            //Definición de validaciones y procesamiento
            $definitions = [
                'check_aud_on_auth' => new Parameter(
                    'check_aud_on_auth',
                    null,
                    function ($value) {
                        return Validator::isInteger($value) || is_bool($value) || is_null($value);
                    },
                    true,
                    function ($value) {
                        return ($value === 1 || $value == '1' || $value === true);
                    }
                ),
                'hide_app_key_warning' => new Parameter(
                    'hide_app_key_warning',
                    null,
                    function ($value) {
                        return Validator::isInteger($value) || is_bool($value) || is_null($value);
                    },
                    true,
                    function ($value) {
                        return ($value === 1 || $value == '1' || $value === true);
                    }
                ),
                'modelOpenAI' => new Parameter(
                    'modelOpenAI',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return $value;
                    }
                ),
                'modelMistral' => new Parameter(
                    'modelMistral',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return $value;
                    }
                ),
                'OpenAIApiKey' => new Parameter(
                    'OpenAIApiKey',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return $value;
                    }
                ),
                'MistralAIApiKey' => new Parameter(
                    'MistralAIApiKey',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return $value;
                    }
                ),
                'translationAI' => new Parameter(
                    'translationAI',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return $value;
                    }
                ),
                'translationAIEnable' => new Parameter(
                    'translationAIEnable',
                    null,
                    function ($value) {
                        return Validator::isInteger($value) || is_bool($value) || is_null($value);
                    },
                    true,
                    function ($value) {
                        return ($value === 1 || $value == '1' || $value === true);
                    }
                ),
            ];
            $expectedParameters = new Parameters(array_values(array_intersect_key($definitions, array_flip($keys))));

            //Obtención de datos
            $inputData = $req->getParsedBody();

            //Asignación de datos para procesar
            $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

            //──── Estructura de respuesta ───────────────────────────────────────────────────────────

            $resultOperation = new ResultOperations([], $title);
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

                try {

                    $configurationsToSave = [];
                    foreach ($keys as $key) {
                        $configurationsToSave[$key] = $expectedParameters->getValue($key);
                    }
                    $messagesOnSave = [
                        'check_aud_on_auth' => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Usar IP del usuario para encriptar el token de sesión'),
                        ]),
                        'hide_app_key_warning' => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Ocultar en el panel el aviso de app_key de relleno'),
                        ]),
                        'modelOpenAI' => __(self::LANG_GROUP, 'Modelo de OpenAI actualizado'),
                        'modelMistral' => __(self::LANG_GROUP, 'Modelo de Mistral actualizado'),
                        'OpenAIApiKey' => __(self::LANG_GROUP, 'API Key de OpenAI actualizada'),
                        'MistralAIApiKey' => __(self::LANG_GROUP, 'API Key de Mistral actualizada'),
                        'translationAI' => __(self::LANG_GROUP, 'IA para traducciones'),
                        'translationAIEnable' => __(self::LANG_GROUP, 'Configuración de traducción con IA actualizada'),
                    ];

                    $success = true;
                    $successMessage = __(self::LANG_GROUP, 'No ha habido ninguna modificación.');
                    $changesMessages = [];

                    foreach ($configurationsToSave as $configName => $configValue) {

                        //Las claves de API no se pintan en el formulario: llega vacía si no se cambia, y vacía la conserva.
                        if ($configValue === '' && in_array($configName, self::AI_SECRET_CONFIG_KEYS, true)) {
                            continue;
                        }

                        if ($configValue !== null) {

                            $isSameValue = get_config($configName) === $configValue;

                            if (!$isSameValue) {

                                $optionMapper = new SettingsModel($configName);
                                $optionMapper->value = $configValue;

                                if ($optionMapper->id !== null) {
                                    $configSuccess = $optionMapper->update();
                                } else {
                                    $optionMapper->name = $configName;
                                    $configSuccess = $optionMapper->save();
                                }

                                $success = $success && $configSuccess;

                                if ($configSuccess && array_key_exists($configName, $messagesOnSave)) {
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
                    $reference = log_exception($e);
                    $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage($reference));
                }

            } catch (MissingRequiredParameterException $e) {

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
        set_title(__(self::LANG_GROUP, 'Rutas y permisos'));

        set_custom_assets([
            'statics/core/css/app_config/routes.css',
        ], 'css');

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/routes', [
            'routes' => get_routes(),
        ]);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * La puerta de la configuración: los tres grupos y, en cada uno, las pantallas que quien mira puede abrir.
     *
     * No pinta ningún ajuste. Un grupo sin ninguna pantalla visible no se enseña.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function indexView(Request $req, Response $res)
    {
        set_title(__(self::LANG_GROUP, 'Configuración'));

        set_custom_assets([
            'statics/core/css/app_config/index.css',
        ], 'css');

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/index', [
            'langGroup' => self::LANG_GROUP,
            'groups' => array_values(array_filter(self::panelGroups(), fn(array $group) => $group['visible'])),
        ]);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * La taxonomía del panel de configuración (ADR 0028): tres grupos y, en cada uno, sus pantallas.
     *
     * ÚNICA FUENTE para el menú y para el índice: dos listas escritas por separado acaban ofreciendo cosas distintas.
     * `visible` es el permiso de quien mira, y un grupo se ve si se ve alguna de sus pantallas. Las tres de estado del
     * sistema son de otro módulo y aquí solo se agrupan: el menú no obliga a la URL a espejarlo.
     *
     * @return array<int,array{name:string,visible:bool,items:array<int,array{name:string,icon:string,href:string,visible:bool}>}>
     */
    public static function panelGroups(): array
    {
        $langGroup = self::LANG_GROUP;
        $statusLangGroup = SystemStatusController::LANG_GROUP;
        $own = fn(string $suffix, string $name, string $icon): array => [
            'name' => $name,
            'icon' => $icon,
            'href' => (string) self::routeName($suffix, [], true),
            'visible' => self::allowedRoute($suffix),
        ];
        $status = fn(string $suffix, string $name, string $icon): array => [
            'name' => $name,
            'icon' => $icon,
            'href' => (string) SystemStatusController::routeName($suffix, [], true),
            'visible' => SystemStatusController::allowedRoute($suffix),
        ];

        $groups = [
            __($langGroup, 'Apariencia') => [
                $own('appearance-brand-images', __($langGroup, 'Imágenes de marca'), 'image'),
                $own('appearance-backgrounds', __($langGroup, 'Fondos'), 'images'),
                $own('appearance-colors', __($langGroup, 'Colores'), 'fill drip'),
                $own('appearance-seo', __($langGroup, 'Identidad y SEO'), 'cog'),
                $own('appearance-site-files', __($langGroup, 'Archivos para buscadores'), 'file alternate outline'),
            ],
            __($langGroup, 'Integraciones') => [
                $own('integrations-mail', __($langGroup, 'Correo'), 'envelope outline'),
                $own('integrations-osticket', __($langGroup, 'OsTicket'), 'file alternate outline'),
                $own('integrations-ai', __($langGroup, 'Inteligencia artificial'), 'microchip'),
                $own('integrations-scripts', __($langGroup, 'Scripts'), 'code'),
            ],
            __($langGroup, 'Sistema') => [
                $status('alerts', __($statusLangGroup, 'Avisos del sistema'), 'bell outline'),
                $status('maintenance', __($statusLangGroup, 'Estado y cachés'), 'wrench'),
                $status('site-maintenance', __($statusLangGroup, 'Sitio en mantenimiento'), 'power off'),
                $status('mail-log', __($statusLangGroup, 'Registro de correos'), 'envelope open outline'),
                $own('system-routes', __($langGroup, 'Rutas y permisos'), 'shield alternate'),
                $own('system-security', __($langGroup, 'Seguridad'), 'lock'),
                $own('system-backups', __($langGroup, 'Respaldos'), 'database'),
            ],
        ];

        $result = [];
        foreach ($groups as $name => $items) {
            $result[] = [
                'name' => $name,
                'visible' => count(array_filter($items, fn(array $item) => $item['visible'])) > 0,
                'items' => $items,
            ];
        }
        return $result;
    }

    /**
     * La pantalla de colores. Conserva el contenedor de pestañas con permiso por pestaña, que hoy tiene una.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function configurationsView(Request $req, Response $res)
    {

        set_title(__(self::LANG_GROUP, 'Colores'));

        import_spectrum();

        set_custom_assets([
            'statics/core/css/app_config/configurations.css',
        ], 'css');
        set_custom_assets([
            'statics/core/js/app_config/colors.js',
        ], 'js');

        $langGroup = SettingsController::LANG_GROUP;

        $tabsTitles = [];
        $tabsItems = [];

        $currentUser = getLoggedFrameworkUserOrFail();
        $baseViewDir = 'panel/pages/app_configurations';

        if (in_array($currentUser->type, self::ROLES_VIEW_CONFIGURATIONS_VIEW)) {

            $actionGenericURL = SettingsController::routeName('generic-save');

            $hasPermissionsGenerals = count(array_filter([
                $actionGenericURL,
            ], function ($e) {return mb_strlen(trim($e)) > 0;})) > 0;

            if ($hasPermissionsGenerals) {

                $data = [
                    'langGroup' => $langGroup,
                    'actionGenericURL' => $actionGenericURL,
                ];

                $tabsTitles['general'] = __($langGroup, 'Colores');
                $tabsItems['general'] = $this->settingsViews->render("{$baseViewDir}/inc/configuration-tabs/general", $data, false, false);

            }

        }

        $data = [
            'langGroup' => $langGroup,
            'tabsTitles' => $tabsTitles,
            'tabsItems' => $tabsItems,
        ];

        $this->render('panel/layout/header');
        $this->settingsViews->render("{$baseViewDir}/configurations", $data);
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
                    //Solo nombres de clave: un espacio o una variante crearía una fila que la base iguala a otra.
                    return is_string($value) && preg_match('/^[A-Za-z0-9_.-]+\z/', $value) === 1;
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

            //ANTES DE ESCRIBIR NADA: hay claves que esta acción no puede tocar aunque su ruta te deje
            //entrar. Falla cerrado: sin usuario, tampoco.
            $currentUser = getLoggedFrameworkUser();
            $isRoot = $currentUser !== null && (int) $currentUser->type === UsersModel::TYPE_USER_ROOT;

            //Antes de leer ni escribir nada: solo los colores de marca.
            if (!in_array($name, self::GENERIC_SAVE_ALLOWED, true)) {

                $result->setMessage(__(
                    self::LANG_GROUP,
                    'Esta acción solo guarda los colores de marca: cada configuración se cambia desde su propia pantalla.'
                ));

                return $res->withJson($result, 403);

            }

            //Y el valor, un color: lo pinta el CSS de cada página y la plantilla de los correos, y un valor que no es una
            //cadena tira el sitio entero (pendientes.md 407). Lo que manda la pestaña: parse «uppercase» y sin merge.
            if ($merge || $parse !== self::PARSE_TYPE_UPPERCASE || !self::isBrandColorValue($name, $value)) {

                $result->setMessage(__(self::LANG_GROUP, 'El valor no es un color válido.'));

                return $res->withJson($result);

            }

            $option = new SettingsModel($name);
            $optionExists = !is_null($option->id);

            //También contra el nombre de la fila que se va a escribir: la base puede encontrarla con una variante del
            //texto pedido que la lista no reconoce.
            $targetName = $optionExists ? (string) $option->name : $name;
            if (self::isRootOnlyConfigName($name, $targetName) && !$isRoot) {

                $result->setMessage(__(
                    self::LANG_GROUP,
                    'Esta configuración solo la cambia el usuario principal, desde su propia pantalla.'
                ));

                return $res->withJson($result, 403);

            }

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
                    ->setMessage($message_create);
                //La operación se creó con este nombre en el constructor: si faltara, es un fallo de verdad.
                ($result->operation($operation_name) ?? throw new \LogicException('La operación del resultado no existe.'))->setSuccess(true);

            } else {

                $result
                    ->setMessage($message_unknow_error);

            }

        } catch (\PDOException $e) {
            $reference = log_exception($e);

            $result
                ->setMessage(CustomSlimErrorHandler::genericMessage($reference));

        } catch (MissingRequiredParameterException | InvalidParameterValueException | ParsedValueException $e) {

            $result->setMessage($e->getMessage());
            log_exception($e);

        } catch (\Exception $e) {
            $reference = log_exception($e);

            $result
                ->setMessage(CustomSlimErrorHandler::genericMessage($reference));

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
        $mapboxKeys = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS);
        $key = is_local() ? $mapboxKeys['keyLocal'] : $mapboxKeys['keyDomain'];
        $res = $res->write($key);
        return $res;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @param array $args
     * @return Response
     */
    public function recreateStaticCacheStamp(Request $req, Response $res, array $args = [], bool $removeServerDelegatedSymLinks = true)
    {

        $startTime = microtime(true);

        $result = new ResultOperations([], __(self::LANG_GROUP, 'Borrar todo lo temporal'), '', true);

        try {

            $result->setMessage(__(self::LANG_GROUP, 'Hecho: se borraron todas las copias temporales. Se vuelven a crear solas.'));

            $endTime = microtime(true);

            //Actualizar marca de caché de estáticos js/css
            static_files_cache_stamp(true);

            $publicationsCache = new DirectoryObject(basepath('app/cache/Publications'));
            $publicationsCache->process();
            $publicationsCache->delete();

            //La caché de conversiones a WebP de ServerStatics. Sin permiso de escritura (la creó otro usuario con 0755) no se
            //toca: delete() haría un unlink que falla con un aviso, y aquí un aviso aborta la limpieza entera.
            $webpCacheDirectory = basepath(ServerStatics::WEBP_CACHE_DIRECTORY);
            $result->setValue('webpCachePurged', false);
            if (is_dir($webpCacheDirectory)) {
                if (is_writable($webpCacheDirectory)) {
                    $webpCache = new DirectoryObject($webpCacheDirectory);
                    $webpCache->process();
                    $webpCache->delete();
                    $result->setValue('webpCachePurged', true);
                } else {
                    log_exception(new \RuntimeException("clean-cache: sin permiso para purgar {$webpCacheDirectory}; la creó otro usuario."));
                }
            }

            //Caché de enlaces simbólicos
            $result->setValue('serverDelegatedSymLinksRemoved', false);
            if ($removeServerDelegatedSymLinks) {
                try {
                    $symLinksDir = new DirectoryObject(basepath('statics/server-delegated'));
                    $symLinksDir->process(new FilesIgnore([
                        '.htaccess',
                        '.gitignore',
                    ]));
                    $symLinksDir->delete();
                    $result->setValue('serverDelegatedSymLinksRemoved', true);
                } catch (\Exception $e) {
                    log_exception($e);
                }
            }

            $result->setValue('Rendimiento', [
                'Memory peak usage' => number_format(memory_get_peak_usage() / (1024 * 1024), 4) . "MB",
                'Execution time' => number_format($endTime - $startTime, 10) . "s",
            ]);

            $result->setSuccessOnSingleOperation(true);

        } catch (\Exception $e) {
            $reference = log_exception($e);

            $result->setMessage(CustomSlimErrorHandler::genericMessage($reference));

            //Archivo, línea y código solo en local: fuera basta la referencia, que lleva al log (P56).
            $result->setValue('exception', is_local() ? [
                'message' => CustomSlimErrorHandler::genericMessage($reference),
                'reference' => $reference,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ] : [
                'message' => CustomSlimErrorHandler::genericMessage($reference),
                'reference' => $reference,
            ]);

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
            $valuesToAdd[$defaultLang][$nameOnForm] = SettingsModel::getConfigValue($nameOnConfig);

            foreach ($allowedLangs as $lang) {

                if (!array_key_exists($lang, $valuesToAdd)) {
                    $valuesToAdd[$lang] = [];
                }

                if ($lang !== $defaultLang) {
                    $nameLang = "{$nameOnConfig}_{$lang}";
                    $nameLangOnForm = "{$nameOnForm}_{$lang}";
                    $valueLang = SettingsModel::getConfigValue($nameLang);
                    $defaultValue = $valuesToAdd[$defaultLang][$nameOnForm];

                    if ($valueLang === null) {

                        $newConfig = new SettingsModel();
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
                                } else {
                                    //Un fallo mudo AQUÍ es el peor: sin fila, el siguiente render
                                    //reintenta la copia para siempre y nadie se entera nunca.
                                    log_exception(new \Exception(
                                        'Activación de idiomas: no se pudo copiar la imagen Open Graph de '
                                        . basepath($defaultValue) . ' a ' . basepath($relativePath)
                                        . '. La configuración de ese idioma queda sin materializar.'
                                    ), false);
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
                            if (!@copy(basepath($defaultValue), basepath($valueLang))) {
                                log_exception(new \Exception(
                                    'Activación de idiomas: no se pudo reponer la imagen Open Graph en '
                                    . basepath($valueLang) . '. La fila apunta a un archivo que no existe.'
                                ), false);
                            }
                        }

                    }

                    $valuesToAdd[$lang][$nameLangOnForm] = $valueLang;

                }

            }

            foreach ($valuesToAdd as $lang => $valuesOnLang) {

                if (!array_key_exists($lang, $values)) {
                    $values[$lang] = [];
                }

                //En crudo: la vista escapa al pintar (escape_html); escapar también aquí lo duplica.
                foreach ($valuesOnLang as $name => $value) {
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
     * Si la acción genérica debe reservar ese nombre al usuario principal (segunda capa: ROOT_ONLY_CONFIG_KEYS).
     *
     * Mira el nombre pedido y el de la fila que se escribiría, sin distinguir mayúsculas: en una base con colación _ci,
     * «MAIL» es la fila «mail».
     *
     * @param string $requestedName
     * @param string $targetName
     * @return bool
     */
    public static function isRootOnlyConfigName(string $requestedName, string $targetName): bool
    {
        $reservedLower = array_map('mb_strtolower', self::ROOT_ONLY_CONFIG_KEYS);
        return in_array(mb_strtolower($requestedName), $reservedLower, true) || in_array(mb_strtolower($targetName), $reservedLower, true);
    }

    /**
     * Si el valor es un color de marca válido para ese nombre: cadena; #RRGGBB, nunca vacía, para los que llevan alfa
     * concatenado, y para el resto vacía (el selector la permite), hex de 3, 4, 6 u 8 cifras o rgb()/rgba() numérico.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public static function isBrandColorValue(string $name, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        //Vacío solo donde no se concatena un alfa: en los otros dos deja botón y pie del correo en blanco sobre blanco.
        if ($value === '') {
            return !in_array($name, self::BRAND_COLORS_SIX_HEX, true);
        }
        if (in_array($name, self::BRAND_COLORS_SIX_HEX, true)) {
            return preg_match('/^#[0-9A-Fa-f]{6}\z/', $value) === 1;
        }
        return Validator::isColor($value);
    }

    /**
     * @param mixed $value
     * @param string $to
     * @return mixed
     */
    public static function parseTo($value, ?string $to = null)
    {
        $to ??= self::PARSE_TYPE_STRING;

        switch ($to) {

            case self::PARSE_TYPE_STRING :

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
                    return (float) $value;
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
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;

        /**
         * @var array<string>
         */
        $allRoles = array_keys(UsersModel::TYPES_USERS);

        $group->active(APP_CONFIGURATION_MODULE);

        //Perzonalizaciones
        $group->register([

            //──── GET ─────────────────────────────────────────────────────────────────────────
            //El índice: la puerta de la configuración. No pinta ningún ajuste.
            new Route(
                "{$startRoute}[/]",
                $classname . ':indexView',
                self::$baseRouteName . '-' . 'index',
                'GET',
                true,
                null,
                self::ROLES_VIEW_CONFIGURATIONS_VIEW
            ),
            //Colores
            new Route(
                "{$startRoute}appearance/colors[/]",
                $classname . ':configurationsView',
                self::$baseRouteName . '-' . 'appearance-colors',
                'GET',
                true,
                null,
                self::ROLES_VIEW_CONFIGURATIONS_VIEW
            ),
            //Archivos para buscadores
            new Route(
                "{$startRoute}appearance/site-files[/]",
                $classname . ':siteFilesView',
                self::$baseRouteName . '-' . 'appearance-site-files',
                'GET',
                true,
                null,
                self::ROLES_SEO
            ),
            //Scripts inyectados
            new Route(
                "{$startRoute}integrations/scripts[/]",
                $classname . ':scriptsView',
                self::$baseRouteName . '-' . 'integrations-scripts',
                'GET',
                true,
                null,
                self::ROLES_INJECTED_SCRIPTS
            ),
            //Política de respaldos
            new Route(
                "{$startRoute}system/backups[/]",
                $classname . ':backupsView',
                self::$baseRouteName . '-' . 'system-backups',
                'GET',
                true,
                null,
                self::ROLES_BACKUPS
            ),
            //Vista de configuración de rutas y permisos
            new Route(
                "{$startRoute}system/routes[/]",
                $classname . ':routesView',
                self::$baseRouteName . '-' . 'system-routes',
                'GET',
                true,
                null,
                self::ROLES_ROUTES_VIEWS
            ),

            //──── POST ────────────────────────────────────────────────────────────────────────
            //General
            new Route(
                "{$startRoute}generic-save[/]",
                $classname . ':actionGeneric',
                self::$baseRouteName . '-' . 'generic-save',
                'POST',
                true,
                null,
                self::ROLES_GENERIC_ACTION
            ),

            //Mapbox Key
            new Route(
                "{$startRoute}/integrations/mapbox-key[/]",
                $classname . ':mapboxKey',
                self::$baseRouteName . '-' . 'integrations-mapbox-key',
                'GET',
                true,
                null,
                $allRoles
            ),

            //Guardar los archivos para buscadores
            new Route(
                "{$startRoute}appearance/site-files/save[/]",
                $classname . ':siteFilesSave',
                self::$baseRouteName . '-' . 'appearance-site-files-save',
                'POST',
                true,
                null,
                self::ROLES_SEO
            ),

            //Guardar los scripts inyectados
            new Route(
                "{$startRoute}integrations/scripts/save[/]",
                $classname . ':scriptsSave',
                self::$baseRouteName . '-' . 'integrations-scripts-save',
                'POST',
                true,
                null,
                self::ROLES_INJECTED_SCRIPTS
            ),

            //Guardar la política de respaldos
            new Route(
                "{$startRoute}system/backups/save[/]",
                $classname . ':backupsSave',
                self::$baseRouteName . '-' . 'system-backups-save',
                'POST',
                true,
                null,
                self::ROLES_BACKUPS
            ),

            //Limpiar caché
            new Route(
                "{$startRoute}system/cache-clean[/]",
                $classname . ':recreateStaticCacheStamp',
                self::$baseRouteName . '-' . 'system-cache-clean',
                'POST',
                true,
                null,
                self::ROLES_CLEAN_CACHE_ACTION
            ),

            //──── Mixtas ────────────────────────────────────────────────────────────────────────────

            //Fondos
            new Route(
                "{$startRoute}/appearance/backgrounds[/]",
                $classname . ':backgrounds',
                self::$baseRouteName . '-' . 'appearance-backgrounds',
                'GET|POST',
                true,
                null,
                self::ROLES_BACKGROUND
            ),

            //Imágenes de marca: logos y favicons
            new Route(
                "{$startRoute}/appearance/brand-images[/]",
                $classname . ':faviconsAndLogos',
                self::$baseRouteName . '-' . 'appearance-brand-images',
                'GET|POST',
                true,
                null,
                self::ROLES_LOGOS_FAVICONS
            ),

            //SEO
            new Route(
                "{$startRoute}/appearance/seo[/]",
                $classname . ':seo',
                self::$baseRouteName . '-' . 'appearance-seo',
                'GET|POST',
                true,
                null,
                self::ROLES_SEO
            ),

            //Correo
            new Route(
                "{$startRoute}/integrations/mail[/]",
                $classname . ':email',
                self::$baseRouteName . '-' . 'integrations-mail',
                'GET|POST',
                true,
                null,
                self::ROLES_EMAIL
            ),

            //OsTicket
            new Route(
                "{$startRoute}/integrations/osticket[/]",
                $classname . ':osTicket',
                self::$baseRouteName . '-' . 'integrations-osticket',
                'GET|POST',
                true,
                null,
                self::ROLES_OS_TICKET
            ),

            //Seguridad
            new Route(
                "{$startRoute}/system/security[/]",
                $classname . ':security',
                self::$baseRouteName . '-' . 'system-security',
                'GET|POST',
                true,
                null,
                self::ROLES_SECURITY_AND_AI
            ),

            //Inteligencia artificial
            new Route(
                "{$startRoute}/integrations/ai[/]",
                $classname . ':ai',
                self::$baseRouteName . '-' . 'integrations-ai',
                'GET|POST',
                true,
                null,
                self::ROLES_SECURITY_AND_AI
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
