<?php

/**
 * SettingsController.php
 */

namespace PiecesPHP\Settings\Controllers;

use DataImportExportUtility\Controllers\DataTransferController;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Forms\FileUpload;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\ControllerRoutingTrait;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\Geo\GeoThumbnail;
use PiecesPHP\LangInjector;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Logs\LogsLang;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\Settings\Mail\MailTester;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Settings\SettingsRoutes;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\ServerDelegatedLinks;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\Controllers\UserTokensController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

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
    /**
     * El sha1 de las imágenes de marca que trae el framework (statics/images/). Una imagen propia en PNG se guarda en el
     * MISMO archivo, así que se distingue por el contenido y no por la ruta.
     */
    const FACTORY_BRAND_IMAGES = [
        'logo' => '9cf27f1fbb82e550d03fbaca632f9c6563eec81a',
        'partners' => 'f99eed56bd1b37230c3da8219a88aa4ca3e815c1',
        'partnersVertical' => '759ba8f2f55108ad876d8974da1ef0ae3dfbbf0f',
    ];

    /**
     * Las imágenes que el modo oscuro puede volver blancas (P277 c): imagen => su opción. Se guardan 1 o 0, nunca un
     * booleano: get_config() devuelve false también si la opción no existe, y sin opción manda brandImageInvertsOnDark().
     */
    const BRAND_IMAGES_INVERT_ON_DARK = [
        'logo' => 'logoInvertOnDark',
        'partners' => 'partnersInvertOnDark',
        'partnersVertical' => 'partnersVerticalInvertOnDark',
    ];

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
    const ROLES_SAVE_COLORS = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
    ];
    /**
     * Los colores que se guardan desde la pantalla Colores. Responde QUÉ se declara; el catálogo es ConfigOptions (ADR 0055).
     *
     * @var string[]
     */
    const COLOR_OPTIONS = [
        'main_brand_color',
        'second_brand_color',
        'third_brand_color',
        'meta_theme_color',
        'data_color_2',
        'data_color_3',
        'data_color_4',
        'data_color_5',
    ];
    /**
     * Los tres acentos: se publican siempre, y un valor inválido cae a su valor de fábrica.
     *
     * @var array<string,string> token del tema => opción
     */
    const ACCENT_TOKENS = [
        'accent' => 'main_brand_color',
        'accent-2' => 'second_brand_color',
        'accent-3' => 'third_brand_color',
    ];
    /**
     * Las series de datos: vacías no se publican y manda el tema (la serie 5 es light-dark(): fijarla rompería el oscuro).
     *
     * @var array<string,string> token del tema => opción
     */
    const DATA_TOKENS = [
        'data-2' => 'data_color_2',
        'data-3' => 'data_color_3',
        'data-4' => 'data_color_4',
        'data-5' => 'data_color_5',
    ];
    /**
     * Los valores de fábrica del tema. Una serie que llega con el suyo se guarda vacía.
     *
     * @var array<string,string>
     */
    const FACTORY_COLORS = [
        'main_brand_color' => '#356DCA',
        'second_brand_color' => '#7A5AF8',
        'third_brand_color' => '#C11574',
        'data_color_2' => '#7A5AF8',
        'data_color_3' => '#C11574',
        'data_color_4' => '#6E4A2E',
        'data_color_5' => '#1D2939',
    ];
    /**
     * El neutro de la barra del navegador en el móvil; la otra opción es el acento.
     */
    const META_THEME_DARK = '#1F2430';
    const BROWSER_BAR_ACCENT = 'accent';
    const BROWSER_BAR_DARK = 'dark';
    /**
     * Las fuentes que se pueden elegir. Una que no sea del tema lleva su hoja, relativa a la raíz pública.
     *
     * @var array<string,array{name:string,stack:string,stylesheet:string|null}>
     */
    const THEME_FONTS = [
        'inter' => [
            'name' => 'Inter',
            'stack' => '"Inter", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
            'stylesheet' => null,
        ],
        'poppins' => [
            'name' => 'Poppins',
            'stack' => "'Poppins', sans-serif",
            'stylesheet' => 'statics/fonts/poppins/poppins.css',
        ],
        'public-sans' => [
            'name' => 'Public Sans',
            'stack' => "'Public Sans', sans-serif",
            'stylesheet' => 'statics/fonts/public-sans/public-sans.css',
        ],
    ];
    const THEME_FONT_FACTORY = 'inter';
    /**
     * @var int[] En píxeles
     */
    const THEME_FONT_SIZES = [14, 15, 16];
    const THEME_FONT_SIZE_FACTORY = 14;
    /**
     * @var int[] En píxeles
     */
    const THEME_RADII = [0, 4, 6, 8, 12];
    const THEME_RADIUS_FACTORY = 8;
    /**
     * La configuración SMTP, cifrada, y las de osTicket.
     */
    const MAIL_CONFIG = 'mail';
    /**
     * El texto opcional del pie del menú, encima de la imagen del patrocinador (P258); vacío, no se pinta. Se edita en
     * Imágenes de marca.
     */
    const PARTNERS_TEXT_CONFIG = 'partnersText';
    const PARTNERS_TEXT_MAX_LENGTH = 120;

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
     * Qué valida y escribe cada pantalla de «Seguridad e IA» (el catálogo es ConfigOptions, ADR 0055): si una validara
     * todas, guardar la seguridad vaciaría las claves de IA, que no viajan en su formulario.
     */
    const SECURITY_CONFIG_KEYS = [
        'check_aud_on_auth',
        'hide_app_key_warning',
        \PiecesPHP\Core\SessionToken::INACTIVITY_CONFIG,
        \PiecesPHP\Core\SessionToken::CEILING_CONFIG,
    ];
    const AI_CONFIG_KEYS = [
        'modelOpenAI',
        'modelMistral',
        'OpenAIApiKey',
        'MistralAIApiKey',
        'translationAI',
        'translationAIEnable',
        \API\Controllers\APIController::TRANSLATION_AI_USER_TYPES_CONFIG,
    ];

    /**
     * De AI_CONFIG_KEYS, los secretos: el formulario no los pinta.
     * Responde QUÉ no se pinta; el catálogo de opciones es ConfigOptions (ADR 0055).
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

    /**
     * Responde QUÉ campo del formulario de SEO va a qué opción; el catálogo de opciones es ConfigOptions (ADR 0055).
     *
     * @var array<string,string>
     */
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
        set_title(__(self::LANG_GROUP, 'Fondos del inicio de sesión'));

        if ($requestMethod == 'GET') {

            import_cropper();

            set_custom_assets([
                SettingsRoutes::staticRoute('js/backgrounds.js'),
            ], 'js');

            $actionURL = self::routeName('appearance-backgrounds');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
            ] + $this->sectionHeader(__(self::LANG_GROUP, 'Fondos del inicio de sesión'), __(self::LANG_GROUP, 'Estas imágenes llenan el panel izquierdo del inicio de sesión; la plataforma muestra una al azar en cada visita.'));

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
            //El valor efectivo y no el de la fila: sin fila, partir de vacío borraría los fondos de fábrica.
            $currentBackgroundConfigValues = get_config('backgrounds');
            $currentBackgroundConfigValues = is_array($currentBackgroundConfigValues) ? $currentBackgroundConfigValues : (is_object($currentBackgroundConfigValues) ? (array) $currentBackgroundConfigValues : []);

            foreach ($currentBackgroundConfigValues as $v) {
                if (mb_strlen($nameImage) > 0 && is_string($v) && str_contains($v, $nameImage)) {
                    $oldImage = $v != $relativePath ? $v : '';
                    break;
                }
            }

            //El fondo de los problemas de inicio se pisa por nombre: no está en la opción.
            $newBackgroundConfigValues = str_starts_with($nameImage, 'bg') ? self::backgroundsWithImage($currentBackgroundConfigValues, $nameImage, $relativePath) : $currentBackgroundConfigValues;

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

                        //Con el mismo valor no hay nada que escribir: reemplazar un fondo pisa su archivo y deja la opción igual.
                        $updated = $newBackgroundConfigValues === $currentBackgroundConfigValues;
                        if (!$updated) {
                            $currentBackgroundConfigMapper->value = $newBackgroundConfigValues;
                            $updated = $currentBackgroundConfigMapper->id !== null ? $currentBackgroundConfigMapper->update() : SettingsModel::setConfigValue('backgrounds', $newBackgroundConfigValues);
                        }

                        if ($updated && is_string($oldImage) && mb_strlen(trim($oldImage)) > 0 && $oldImage != $relativePath) {
                            //El anterior puede no estar ya; si no se puede borrar, se registra y el guardado sigue.
                            $oldFile = basepath($oldImage);
                            if (is_file($oldFile) && (!is_writable(dirname($oldFile)) || !unlink($oldFile))) {
                                log_exception(new \RuntimeException('No se pudo borrar el archivo anterior: ' . $oldFile));
                            }
                        }

                        $result
                            ->setMessage($updated ? $createMessage : $unknowErrorMessage)
                            ->setSuccessOnSingleOperation($updated);

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
     * El nombre de una opción (o de su campo) de SEO en un idioma: el del idioma por omisión sin sufijo y los demás con
     * «_<idioma>». Lo usan el guardado, la lectura, la declaración de las opciones y la vista.
     *
     * @param string $option
     * @param string $lang
     * @return string
     */
    public static function seoOptionName(string $option, string $lang): string
    {
        return $lang === Config::get_default_lang() ? $option : "{$option}_{$lang}";
    }

    /**
     * La lista de fondos con una imagen puesta: sustituye la que lleva ese nombre (bg3 puede ser .jpg o .webp) y, si no
     * está, la añade. Un fondo vacío de la pantalla no tiene valor en la lista.
     *
     * @param array<int|string,mixed> $current
     * @param string $name El nombre del archivo sin extensión, `bg<n>`.
     * @param string $relativePath
     * @return array<int,mixed>
     */
    public static function backgroundsWithImage(array $current, string $name, string $relativePath): array
    {
        $backgrounds = array_values($current);
        foreach ($backgrounds as $index => $background) {
            if (is_string($background) && str_contains($background, $name)) {
                $backgrounds[$index] = $relativePath;
                return $backgrounds;
            }
        }
        $backgrounds[] = $relativePath;
        return $backgrounds;
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
                SettingsRoutes::staticRoute('js/logos-favicons.js'),
            ], 'js');

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
                'partnersText' => self::partnersText(),
                'invertOnDark' => array_map(fn(string $option) => self::brandImageInvertsOnDark((string) array_search($option, self::BRAND_IMAGES_INVERT_ON_DARK, true)), self::BRAND_IMAGES_INVERT_ON_DARK),
                'partnersTextMaxLength' => self::PARTNERS_TEXT_MAX_LENGTH,
            ] + $this->sectionHeader(__(self::LANG_GROUP, 'Imágenes de marca'), __(self::LANG_GROUP, 'La plataforma es marca blanca: estas imágenes identifican al cliente en toda la plataforma, desde el inicio de sesión y el menú lateral hasta los correos y el sitio público.'));

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->settingsViews->render("{$baseViewDir}/logos-favicons", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST' && is_string($req->getParsedBodyParam(self::PARTNERS_TEXT_CONFIG, null))) {

            //El texto sobre el patrocinador: texto plano, recortado; vacío lo quita del pie.
            $text = trim(strip_tags((string) $req->getParsedBodyParam(self::PARTNERS_TEXT_CONFIG, '')));
            $text = mb_substr($text, 0, self::PARTNERS_TEXT_MAX_LENGTH);
            $saved = SettingsModel::setConfigValue(self::PARTNERS_TEXT_CONFIG, $text);
            $result = new ResultOperations([], __(self::LANG_GROUP, 'Texto sobre el patrocinador'));
            $result->setSingleOperation(true);
            $result
                ->setMessage($saved ? __(self::LANG_GROUP, 'Texto guardado.') : __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.'))
                ->setSuccessOnSingleOperation($saved);
            $res = $res->withJson($result);

        } elseif ($requestMethod == 'POST' && is_string($req->getParsedBodyParam('invertOnDark', null))) {

            //La casilla «Invertir colores en modo oscuro» de una imagen: se guarda al cambiarla.
            $image = (string) $req->getParsedBodyParam('invertOnDark', '');
            $checked = $req->getParsedBodyParam('checked', null);
            $valid = array_key_exists($image, self::BRAND_IMAGES_INVERT_ON_DARK) && in_array($checked, ['0', '1'], true);
            $saved = $valid && SettingsModel::setConfigValue(self::BRAND_IMAGES_INVERT_ON_DARK[$image], (int) $checked);
            $result = new ResultOperations([], __(self::LANG_GROUP, 'Invertir colores en modo oscuro'));
            $result->setSingleOperation(true);
            $result
                ->setMessage($saved ? __(self::LANG_GROUP, 'Preferencia guardada.') : __(self::LANG_GROUP, 'Información faltante o inesperada.'))
                ->setSuccessOnSingleOperation($saved);
            $res = $res->withJson($result);

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

                        if ($updated && is_string($oldImage) && mb_strlen(trim($oldImage)) > 0 && $oldImage != $relativePath) {
                            //El anterior puede no estar ya; si no se puede borrar, se registra y el guardado sigue.
                            $oldFile = basepath($oldImage);
                            if (is_file($oldFile) && (!is_writable(dirname($oldFile)) || !unlink($oldFile))) {
                                log_exception(new \RuntimeException('No se pudo borrar el archivo anterior: ' . $oldFile));
                            }
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
                SettingsRoutes::staticRoute('js/seo.js'),
            ], 'js');

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
            ] + $this->sectionHeader(__(self::LANG_GROUP, 'Identidad y SEO'), __(self::LANG_GROUP, 'Datos del sitio para buscadores y para la vista previa cuando se comparte un enlace en redes sociales y aplicaciones de mensajería.'));

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

                    $options = [
                        self::SEO_OPTION_TITLE_APP => $titleApp,
                        self::SEO_OPTION_OWNER => $owner,
                        self::SEO_OPTION_DESCRIPTION => $description,
                        self::SEO_OPTION_KEYWORDS => $keywords,
                        self::SEO_OPTION_SHARE_TITLE => $shareTitle,
                        self::SEO_OPTION_SHARE_DESCRIPTION => $shareDescription,
                    ];

                    //Solo se escribe lo que cambia (comparado por su JSON, como se guarda), y cada opción se intenta aunque
                    //otra falle: el resultado es falso si alguna falló.
                    $success = true;
                    $sameValue = fn ($current, $new): bool => json_encode($current) === json_encode($new);

                    if (is_string($xAccount) && !$sameValue(SettingsModel::getConfigValue(self::SEO_OPTION_X_ACCOUNT), $xAccount)) {
                        $success = SettingsModel::setConfigValue(self::SEO_OPTION_X_ACCOUNT, $xAccount) && $success;
                    }

                    foreach ($options as $optionName => $optionValue) {

                        $optionName = self::seoOptionName($optionName, $lang);

                        $optionMapper = new SettingsModel($optionName);

                        if ($optionMapper->id !== null && $sameValue($optionMapper->value, $optionValue)) {
                            continue;
                        }

                        $optionMapper->value = $optionValue;

                        if ($optionMapper->id !== null) {
                            $success = $optionMapper->update() && $success;
                        } else {
                            $optionMapper->name = $optionName;
                            $success = $optionMapper->save() && $success;
                        }

                    }

                    if ($fileHandler->hasInput()) {

                        if ($fileHandler->validate()) {

                            $nameOptionOG = 'open_graph_image';

                            $folder = 'statics/images';
                            $nameImage = 'open_graph';

                            $nameImage = self::seoOptionName($nameImage, $lang);
                            $nameOptionOG = self::seoOptionName($nameOptionOG, $lang);

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
     * «Probar conexión», en Correo (solo root): conecta con el SMTP que usaría un envío, sin enviar (MailTester::probe()).
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function emailProbe(Request $req, Response $res)
    {
        return $res->withJson(MailTester::probe());
    }

    /**
     * «Enviar correo de prueba», en Correo (solo root): a la dirección que se escriba o, vacía, a la del usuario.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function emailTestSend(Request $req, Response $res)
    {
        $to = $req->getParsedBodyParam('to', '');
        $to = is_string($to) ? trim($to) : '';
        if ($to === '') {
            $user = getLoggedFrameworkUser();
            $to = $user !== null ? trim((string) $user->email) : '';
        }
        //filter_var y no Validator::isEmail(): ese consulta el DNS en cada llamada.
        if (filter_var($to, \FILTER_VALIDATE_EMAIL) === false) {
            return $res->withJson(['success' => false, 'message' => __(self::LANG_GROUP, 'La dirección no es un correo válido.')], 400);
        }
        return $res->withJson(MailTester::send($to));
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
                SettingsRoutes::staticRoute('js/email.js'),
            ], 'js');

            $actionURL = self::routeName('integrations-mail');

            $element = new MailConfig;

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'probeURL' => self::routeName('integrations-mail-probe'),
                'testSendURL' => self::routeName('integrations-mail-test-send'),
                'currentEmail' => (string) (getLoggedFrameworkUser()->email ?? ''),
                'element' => $element,
            ] + $this->sectionHeader(__(self::LANG_GROUP, 'Correo'), __(self::LANG_GROUP, 'Desde dónde y cómo sale el correo de la plataforma.'));

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
                SettingsRoutes::staticRoute('js/os-ticket.js'),
            ], 'js');

            $actionURL = self::routeName('integrations-osticket');
            $url = get_config('osTicketAPI');
            $key = get_config('osTicketAPIKey');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'url' => $url,
                'hasKey' => mb_strlen((string) $key) > 0,
            ] + $this->sectionHeader(__(self::LANG_GROUP, 'OsTicket'), __(self::LANG_GROUP, 'La conexión con el sistema de soporte osTicket.'));

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
        $title = __(self::LANG_GROUP, 'Seguridad');
        $header = $this->sectionHeader($title, __(self::LANG_GROUP, 'Las opciones de seguridad de la plataforma.'));
        return $this->securityOrAI($req, $res, 'system-security', $title, 'security', self::SECURITY_CONFIG_KEYS, $header);
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function ai(Request $req, Response $res)
    {
        $title = __(self::LANG_GROUP, 'Inteligencia artificial');
        $header = $this->sectionHeader($title, __(self::LANG_GROUP, 'Proveedor, modelo y claves que usa la plataforma para las funciones con inteligencia artificial.'));
        return $this->securityOrAI($req, $res, 'integrations-ai', $title, 'ai', self::AI_CONFIG_KEYS, $header);
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
            SettingsRoutes::staticRoute('js/site-files.js'),
        ], 'js');

        $added = fn(string $name): string => is_string($value = SettingsModel::getConfigValue($name)) ? $value : '';
        $data = $this->sectionHeader(__($langGroup, 'Archivos para buscadores'), __($langGroup, 'Lo que leen los buscadores y las inteligencias artificiales: robots.txt, humans.txt y llms.txt.')) + [
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
            SettingsRoutes::staticRoute('js/scripts.js'),
        ], 'js');

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
        ] + $this->sectionHeader(__($langGroup, 'Scripts'), __($langGroup, 'Código propio que se inserta en el sitio público, en el panel o en ambos: analítica, píxeles de anuncios, chats y similares.'));

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
            SettingsRoutes::staticRoute('js/backups.js'),
        ], 'js');
        set_custom_assets([
            SettingsRoutes::staticRoute('css/backups.css'),
        ], 'css');

        $policy = BackupPolicy::current();
        $backups = self::backupStatus();
        $plan = $backups['plan'];

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
                'total' => $backups['total'],
                'bytes' => $backups['bytes'],
                'latest' => $backups['latest'],
                'next' => $backups['next'],
                'due' => $backups['due'],
            ],
            'plan' => [
                'keep' => count($plan['keep']),
                'delete' => count($plan['delete']),
                'ignored' => count($plan['ignored']),
            ],
            'tables' => self::databaseTables(),
            'codeExcluded' => BackupPolicy::codeExcludedDataTables(),
        ] + $this->sectionHeader(
            __(self::LANG_GROUP, 'Respaldos'),
            __(self::LANG_GROUP, 'Cada cuánto se respalda la base de datos, cuántos respaldos se guardan y qué tablas salen sin sus filas.')
        );

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
     * Lo que dice la pantalla de Respaldos sobre los respaldos hechos: cuántos, cuánto ocupan, el último, el siguiente y
     * el plan de conservación. Lo usa también la Salud de la instalación.
     *
     * @return array{total:int,bytes:int,latest:?\DateTimeImmutable,next:?\DateTimeImmutable,due:bool,plan:array{keep:string[],delete:string[],ignored:string[]}}
     */
    public static function backupStatus(): array
    {
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

        return [
            'total' => count($plan['keep']) + count($plan['delete']),
            'bytes' => $bytes,
            'latest' => $latest,
            'next' => $latest !== null ? $latest->modify("+{$policy['interval_minutes']} minutes") : null,
            'due' => BackupPolicy::isDue(),
            'plan' => $plan,
        ];
    }

    /**
     * Los nombres de las tablas de la base, para validar las que salen sin filas.
     *
     * @return string[] Vacío si no hay conexión: entonces no se puede validar ninguna.
     */
    public static function databaseTables(): array
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
     * @param array<string,mixed> $header La cabecera de su grupo (sectionHeader()); vacía, la vista no la pinta.
     * @return Response
     */
    private function securityOrAI(Request $req, Response $res, string $routeSuffix, string $title, string $asset, array $keys, array $header = [])
    {
        $langGroup = self::LANG_GROUP;

        $requestMethod = mb_strtoupper($req->getMethod());

        set_title($title);

        if ($requestMethod == 'GET') {

            set_custom_assets([
                SettingsRoutes::staticRoute("js/{$asset}.js"),
            ], 'js');

            $actionURL = self::routeName($routeSuffix);

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
            ] + $header;

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
                //La sesión (P142): días en el formulario, segundos en la configuración; 0 = sin ese límite.
                \PiecesPHP\Core\SessionToken::INACTIVITY_CONFIG => new Parameter(
                    \PiecesPHP\Core\SessionToken::INACTIVITY_CONFIG,
                    null,
                    function ($value) {
                        //Se valida dos veces: lo que llega (días, en texto) y lo que devuelve parse() (segundos, entero).
                        return is_int($value) ? \PiecesPHP\Core\SessionToken::lifetimeIsValid($value) : Validator::isInteger($value) && (int) $value >= 0 && (int) $value <= 365;
                    },
                    true,
                    function ($value) {
                        return (int) $value * 86400;
                    }
                ),
                \PiecesPHP\Core\SessionToken::CEILING_CONFIG => new Parameter(
                    \PiecesPHP\Core\SessionToken::CEILING_CONFIG,
                    null,
                    function ($value) {
                        //Se valida dos veces: lo que llega (días, en texto) y lo que devuelve parse() (segundos, entero).
                        return is_int($value) ? \PiecesPHP\Core\SessionToken::lifetimeIsValid($value) : Validator::isInteger($value) && (int) $value >= 0 && (int) $value <= 365;
                    },
                    true,
                    function ($value) {
                        return (int) $value * 86400;
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
                \API\Controllers\APIController::TRANSLATION_AI_USER_TYPES_CONFIG => new Parameter(
                    \API\Controllers\APIController::TRANSLATION_AI_USER_TYPES_CONFIG,
                    null,
                    function ($value) {
                        return self::translationUserTypesIsValid($value);
                    },
                    true,
                    function ($value) {
                        return is_array($value) ? array_values(array_map('intval', array_filter($value, fn($type) => $type !== ''))) : [];
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
                        \PiecesPHP\Core\SessionToken::INACTIVITY_CONFIG => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Días sin actividad que cierran la sesión'),
                        ]),
                        \PiecesPHP\Core\SessionToken::CEILING_CONFIG => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Días desde que se abrió que la cierran (tope)'),
                        ]),
                        'modelOpenAI' => __(self::LANG_GROUP, 'Modelo de OpenAI actualizado'),
                        'modelMistral' => __(self::LANG_GROUP, 'Modelo de Mistral actualizado'),
                        'OpenAIApiKey' => __(self::LANG_GROUP, 'API Key de OpenAI actualizada'),
                        'MistralAIApiKey' => __(self::LANG_GROUP, 'API Key de Mistral actualizada'),
                        'translationAI' => __(self::LANG_GROUP, 'IA para traducciones'),
                        'translationAIEnable' => __(self::LANG_GROUP, 'Configuración de traducción con IA actualizada'),
                        \API\Controllers\APIController::TRANSLATION_AI_USER_TYPES_CONFIG => __(self::LANG_GROUP, 'Quién puede traducir con IA actualizado'),
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
            SettingsRoutes::staticRoute('css/routes.css'),
        ], 'css');
        set_custom_assets([
            SettingsRoutes::staticRoute('js/routes.js'),
        ], 'js');

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/routes', [
            'groups' => self::routesByModule(get_routes()),
        ] + $this->sectionHeader(
            __(self::LANG_GROUP, 'Rutas y permisos'),
            __(self::LANG_GROUP, 'Qué rutas existen y qué tipos de usuario pueden usar cada una.')
        ));
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * La taxonomía del panel de configuración (ADR 0028): los cinco grupos del prototipo y su orden (P255 a; diseño del
     * tema, design.md §2.2) y, en cada uno, sus pantallas. Lo nuestro que el prototipo no nombra no se quita: va al
     * final del grupo más afín (P256).
     *
     * ÚNICA FUENTE del panel de Configuración de la barra (el índice se retiró, ADR 0070).
     * `visible` es el permiso de quien mira, y un grupo se ve si se ve alguna de sus pantallas. Muchas son de otros
     * módulos y aquí solo se agrupan: el menú no obliga a la URL a espejarlo. Las de registros son pestañas del visor de
     * registros (`?tab=`), con su título, su icono y su permiso de LogTabRegistry.
     *
     * @return array<int,array{name:string,icon:string,visible:bool,items:array<int,array{name:string,icon:string,href:string,visible:bool}>}>
     */
    public static function panelGroups(): array
    {
        $langGroup = self::LANG_GROUP;
        $statusLangGroup = SystemStatusController::LANG_GROUP;
        $user = getLoggedFrameworkUser();
        $userType = $user !== null ? (int) $user->type : null;
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
        //Las dos «variables de entorno» del módulo de ayudantes (P134 a): misma ruta, ahora agrupadas aquí.
        $helpers = fn(string $suffix, string $name, string $icon): array => [
            'name' => $name,
            'icon' => $icon,
            'href' => (string) GenericContentController::routeName($suffix, [], true),
            'visible' => GenericContentController::allowedRoute($suffix),
        ];
        $permission = fn(string $routeName, string $href, string $name, string $icon): array => [
            'name' => $name,
            'icon' => $icon,
            'href' => $href,
            'visible' => $userType !== null && Roles::hasPermissions($routeName, $userType),
        ];
        //Una pestaña del visor de registros; sin título propio (`$name`), el de la pestaña.
        $recordTabs = $userType !== null && LogsController::allowedRoute('index') ? LogTabRegistry::visibleFor($userType) : [];
        $record = function (string $tab, ?string $view = null, ?string $name = null, ?string $icon = null) use ($recordTabs): array {
            $logTab = $recordTabs[$tab] ?? null;
            return [
                'name' => $name ?? ($logTab !== null ? $logTab->title() : $tab),
                'icon' => $icon ?? ($logTab !== null ? $logTab->icon() : 'clipboard list'),
                'href' => (string) LogsController::routeName('index') . '?' . http_build_query(array_filter(['tab' => $tab, 'view' => $view])),
                'visible' => $logTab !== null,
            ];
        };
        $accessTab = LoginAttemptsController::ACCESS_TAB;

        $groups = [
            __(ADMIN_MENU_LANG_GROUP, 'Gestión de usuarios') => ['users', [
                $permission('users-selection-create', (string) UsersController::routeName('selection-create'), __(AdminPanelController::ADMIN_LANG_GROUP, 'Agregar usuario'), 'user plus'),
                $permission('users-list', (string) UsersController::routeName('list'), __(AdminPanelController::ADMIN_LANG_GROUP, 'Gestionar usuarios'), 'users cog'),
                [
                    'name' => __(AdminPanelController::ADMIN_LANG_GROUP, 'Importar usuarios'),
                    'icon' => 'upload',
                    'href' => (string) DataTransferController::routeName('import-users', [], true),
                    'visible' => DataTransferController::allowedRoute('import-users'),
                ],
                $record(UserSessionsController::SESSIONS_TAB),
            ]],
            __($langGroup, 'Registros y Logs') => ['clipboard list', [
                $record(LogsController::ERRORS_TAB),
                $record($accessTab, 'attempts', __(LogsLang::LANG_GROUP, 'Intentos de ingreso'), 'sign in alternate'),
                $record($accessTab, 'logged', __(LogsLang::LANG_GROUP, 'Registro de ingresos'), 'chart bar outline'),
                $record($accessTab, 'not-logged', __(LogsLang::LANG_GROUP, 'Usuarios sin ingresos'), 'user times'),
                $record(SystemStatusController::MAIL_LOG_TAB),
                $record(UserTokensController::TOKENS_TAB),
                $record(SystemStatusController::TASK_RUNS_TAB),
                //Sin ?tab: la primera pestaña visible.
                [
                    'name' => __(LogsLang::LANG_GROUP, 'Todos los registros'),
                    'icon' => 'clipboard list',
                    'href' => (string) LogsController::routeName('index'),
                    'visible' => $recordTabs !== [],
                ],
            ]],
            __($langGroup, 'Apariencia') => ['palette', [
                $own('appearance-brand-images', __($langGroup, 'Imágenes de marca'), 'image'),
                $own('appearance-backgrounds', __($langGroup, 'Fondos del inicio de sesión'), 'images'),
                $own('appearance-colors', __($langGroup, 'Colores'), 'fill drip'),
            ]],
            __($langGroup, 'SEO y Scripts') => ['globe', [
                $own('appearance-seo', __($langGroup, 'Identidad y SEO'), 'cog'),
                $own('appearance-site-files', __($langGroup, 'Archivos para buscadores'), 'file alternate outline'),
                $own('integrations-scripts', __($langGroup, 'Scripts'), 'code'),
            ]],
            __($langGroup, 'Sistema') => ['microchip', [
                $own('integrations-ai', __($langGroup, 'Inteligencia artificial'), 'microchip'),
                $status('alerts', __($statusLangGroup, 'Avisos del sistema'), 'bell outline'),
                $status('maintenance', __($statusLangGroup, 'Estado y cachés'), 'wrench'),
                $status('health', __($statusLangGroup, 'Salud de la instalación'), 'heartbeat'),
                $status('site-maintenance', __($statusLangGroup, 'Sitio en mantenimiento'), 'power off'),
                $own('integrations-mail', __($langGroup, 'Correo'), 'envelope outline'),
                $permission('mail-catalog-list', (string) \PiecesPHP\MailCatalog\Controllers\MailCatalogController::routeName('list'), __(\PiecesPHP\MailCatalog\MailCatalogLang::LANG_GROUP, 'Correos del sitio'), 'mail bulk'),
                $helpers('forms-mapbox-keys', __($langGroup, 'Llaves de Mapas'), 'map'),
                $own('system-routes', __($langGroup, 'Rutas y permisos'), 'shield alternate'),
                $own('system-security', __($langGroup, 'Seguridad'), 'lock'),
                $own('system-backups', __($langGroup, 'Respaldos'), 'database'),
                $own('integrations-osticket', __($langGroup, 'OsTicket'), 'life ring outline'),
                $permission('admin-design-sheet', (string) AdminPanelController::routeName('design-sheet'), __(AdminPanelController::ADMIN_LANG_GROUP, 'Hoja de diseño'), 'paint brush'),
            ]],
        ];

        $result = [];
        foreach ($groups as $name => [$icon, $items]) {
            $result[] = [
                'name' => $name,
                'icon' => $icon,
                'visible' => count(array_filter($items, fn(array $item) => $item['visible'])) > 0,
                'items' => $items,
            ];
        }
        return $result;
    }

    /**
     * La cabecera de una pantalla de la configuración: título, su frase, el HelperController que pinta las piezas del
     * panel (las vistas de Settings pintan desde su carpeta) y adonde vuelve «Cancelar».
     *
     * @param string $title
     * @param string $description
     * @return array{title:string,description:string,breadcrumbs:array<string|int,string|array{url:string}>,helpController:HelperController,indexLink:string}
     */
    private function sectionHeader(string $title, string $description): array
    {
        return self::sectionHeaderData($title, $description) + [
            'helpController' => new HelperController($this->user, $this->getGlobalVariables()),
        ];
    }

    /**
     * Los datos de la cabecera de una pantalla de la configuración, sin el HelperController: la usan también las
     * pantallas de Sistema de SystemStatusController, que pintan con el suyo. Sin migas, porque son vistas principales a
     * las que se llega por el panel de Configuración, y «Cancelar» vuelve al inicio del panel (ADR 0070).
     *
     * @param string $title
     * @param string $description
     * @return array{title:string,description:string,breadcrumbs:array<string|int,string|array{url:string}>,indexLink:string}
     */
    public static function sectionHeaderData(string $title, string $description): array
    {
        return [
            'title' => $title,
            'description' => $description,
            //Vacío, page-header.php no pinta migas; las vistas lo siguen leyendo.
            'breadcrumbs' => [],
            'indexLink' => AdminPanelController::routeName(''),
        ];
    }

    /**
     * La pantalla de colores: un solo formulario (acento, series, realces, tipografía, forma y barra del navegador).
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function configurationsView(Request $req, Response $res)
    {

        set_title(__(self::LANG_GROUP, 'Colores'));

        //Las fuentes que no trae el tema, para la vista previa: solo en esta pantalla.
        set_custom_assets(array_map('base_url', array_values(array_filter(array_column(self::THEME_FONTS, 'stylesheet')))), 'css');
        set_custom_assets([
            SettingsRoutes::staticRoute('js/colors.js'),
        ], 'js');

        $tokens = self::themeTokens();
        $seriesColors = [];
        foreach (self::DATA_TOKENS as $token => $option) {
            $seriesColors[$option] = ['value' => $tokens[$token] ?? self::FACTORY_COLORS[$option], 'factory' => self::FACTORY_COLORS[$option]];
        }

        $data = [
            'langGroup' => self::LANG_GROUP,
            'actionURL' => self::routeName('appearance-colors-save'),
            'accentColor' => $tokens['accent'],
            'secondColor' => $tokens['accent-2'],
            'thirdColor' => $tokens['accent-3'],
            'seriesColors' => $seriesColors,
            'browserBar' => mb_strtoupper((string) get_config('meta_theme_color')) === self::META_THEME_DARK ? self::BROWSER_BAR_DARK : self::BROWSER_BAR_ACCENT,
            'themeFont' => self::themeFont(),
            'themeFontSize' => self::themeFontSize(),
            'themeRadius' => self::themeRadius(),
        ] + $this->sectionHeader(__(self::LANG_GROUP, 'Colores'), __(self::LANG_GROUP, 'El color del cliente se usa solo como acento (botones, selección, enlaces y foco). Los colores complementarios se usan únicamente en gráficas y datos.'));

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/inc/configuration-tabs/general', $data);
        $this->render('panel/layout/footer');

        return $res;
    }

    /**
     * Guarda la pantalla Colores de una vez. TODO se valida antes de escribir: un campo malo no cambia ninguna opción.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function saveColorsAction(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        $color = fn (string $name): Parameter => new Parameter(
            $name,
            null,
            fn ($value): bool => self::isBrandColorValue($name, $value),
            false,
            fn (string $value): string => mb_strtoupper($value)
        );
        $inList = fn (array $allowed): callable => fn ($value): bool => is_scalar($value) && in_array((string) $value, array_map('strval', $allowed), true);

        $expectedParameters = new Parameters([
            $color('main_brand_color'),
            $color('second_brand_color'),
            $color('third_brand_color'),
            ...array_map(fn (string $name): Parameter => new Parameter(
                $name,
                '',
                fn ($value): bool => self::isBrandColorValue($name, $value),
                true,
                fn (string $value): string => mb_strtoupper($value)
            ), array_values(self::DATA_TOKENS)),
            new Parameter('browser_bar', null, $inList([self::BROWSER_BAR_ACCENT, self::BROWSER_BAR_DARK])),
            new Parameter('theme_font', null, $inList(array_keys(self::THEME_FONTS))),
            new Parameter('theme_font_size', null, $inList(self::THEME_FONT_SIZES), false, fn ($value): int => (int) $value),
            new Parameter('theme_radius', null, $inList(self::THEME_RADII), false, fn ($value): int => (int) $value),
        ]);

        $inputData = $req->getParsedBody();
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        $resultOperation = new ResultOperations([], __($langGroup, 'Colores'));
        $resultOperation->setSingleOperation(true);
        $resultOperation->setSuccessOnSingleOperation(false);
        $pdo = null;

        try {

            $expectedParameters->validate();

            $accent = $expectedParameters->getValue('main_brand_color');
            $options = [
                'main_brand_color' => $accent,
                'second_brand_color' => $expectedParameters->getValue('second_brand_color'),
                'third_brand_color' => $expectedParameters->getValue('third_brand_color'),
                'meta_theme_color' => $expectedParameters->getValue('browser_bar') === self::BROWSER_BAR_DARK ? self::META_THEME_DARK : $accent,
                'theme_font' => $expectedParameters->getValue('theme_font'),
                'theme_font_size' => $expectedParameters->getValue('theme_font_size'),
                'theme_radius' => $expectedParameters->getValue('theme_radius'),
            ];
            foreach (self::DATA_TOKENS as $option) {
                $series = $expectedParameters->getValue($option);
                $options[$option] = $series === self::FACTORY_COLORS[$option] ? '' : $series;
            }

            //Las escrituras van juntas: una que falle no deja la paleta a medias.
            $pdo = SettingsModel::model()->getDatabase();
            //Null solo sin conexión, que el arranque ya exige: aquí sería un fallo de verdad, y el catch lo registra.
            if ($pdo === null) {
                throw new \Exception('Sin conexión a la base de datos al guardar los colores.');
            }
            $pdo->beginTransaction();
            $success = true;
            foreach ($options as $name => $value) {
                $success = $success && SettingsModel::setConfigValue($name, $value);
            }
            if ($success) {
                $pdo->commit();
                //La hoja de variables no depende de esto: su URL lleva la huella de su contenido. Se renueva la de los demás estáticos.
                static_files_cache_stamp(true);
                $resultOperation->setMessage(__($langGroup, 'Los colores se guardaron.'));
                $resultOperation->setSuccessOnSingleOperation(true);
            } else {
                $pdo->rollBack();
                $resultOperation->setMessage(__($langGroup, 'Ha ocurrido un error desconocido, intente más tarde.'));
            }

        } catch (MissingRequiredParameterException|InvalidParameterValueException|ParsedValueException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (\Exception $e) {

            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage(log_exception($e)));

        }

        return $res->withJson($resultOperation);
    }

    /**
     * Los tokens del contrato del tema que escribe la instalación, sin los dos guiones: nombre de la variable => valor.
     * Lo que no se aparta de lo de fábrica no se publica, y manda el tema; un valor guardado que no vale cae a fábrica,
     * nunca se publica crudo.
     *
     * @return array<string,string>
     */
    public static function themeTokens(): array
    {
        $tokens = [];
        foreach (self::ACCENT_TOKENS as $token => $option) {
            $value = get_config($option);
            $tokens[$token] = self::isBrandColorValue($option, $value) ? $value : self::FACTORY_COLORS[$option];
        }
        foreach (self::DATA_TOKENS as $token => $option) {
            $value = get_config($option);
            if ($value !== '' && self::isBrandColorValue($option, $value)) {
                $tokens[$token] = $value;
            }
        }
        $font = self::themeFont();
        if ($font !== self::THEME_FONT_FACTORY) {
            $tokens['font-body'] = self::THEME_FONTS[$font]['stack'];
            $tokens['font-heading'] = self::THEME_FONTS[$font]['stack'];
        }
        $fontSize = self::themeFontSize();
        if ($fontSize !== self::THEME_FONT_SIZE_FACTORY) {
            $tokens['font-size-base'] = "{$fontSize}px";
        }
        $radius = self::themeRadius();
        if ($radius !== self::THEME_RADIUS_FACTORY) {
            $tokens['r-xs'] = (int) round($radius / 2) . 'px';
            $tokens['r-sm'] = (int) round($radius * 3 / 4) . 'px';
            $tokens['r-md'] = "{$radius}px";
            $tokens['r-lg'] = (int) round($radius * 3 / 2) . 'px';
        }
        return $tokens;
    }

    /**
     * La fuente del panel: una clave de THEME_FONTS.
     *
     * @return string
     */
    public static function themeFont(): string
    {
        $font = get_config('theme_font');
        return is_string($font) && array_key_exists($font, self::THEME_FONTS) ? $font : self::THEME_FONT_FACTORY;
    }

    /**
     * La hoja de la fuente elegida, relativa a la raíz pública; null si es la del tema.
     *
     * @return string|null
     */
    public static function themeFontStylesheet(): ?string
    {
        return self::THEME_FONTS[self::themeFont()]['stylesheet'];
    }

    /**
     * @return int El tamaño base en píxeles, de THEME_FONT_SIZES
     */
    public static function themeFontSize(): int
    {
        return self::configFromList('theme_font_size', self::THEME_FONT_SIZES, self::THEME_FONT_SIZE_FACTORY);
    }

    /**
     * @return int El radio en píxeles, de THEME_RADII
     */
    public static function themeRadius(): int
    {
        return self::configFromList('theme_radius', self::THEME_RADII, self::THEME_RADIUS_FACTORY);
    }

    /**
     * @param string $name
     * @param int[] $allowed
     * @param int $factory
     * @return int El valor de la opción si está en la lista; si no, el de fábrica
     */
    private static function configFromList(string $name, array $allowed, int $factory): int
    {
        $value = get_config($name);
        return is_numeric($value) && in_array((int) $value, $allowed, true) ? (int) $value : $factory;
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function mapboxKey(Request $req, Response $res)
    {
        $mapboxKeys = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_MAPBOX_KEYS);
        $key = (string) ($mapboxKeys[is_local() ? 'keyLocal' : 'keyDomain'] ?? '');
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

            //Los avisos de opciones no declaradas (ADR 0055): tras limpiar, se vuelven a avisar. Sin permiso, se queda.
            $warnedOptions = basepath(\PiecesPHP\Core\ConfigHelpers\ConfigOptions::WARNED_FILE);
            if (is_file($warnedOptions) && is_writable(dirname($warnedOptions))) {
                //RETORNO-IGNORADO: si no se borra, no se vuelve a avisar; la limpieza sigue igual.
                @unlink($warnedOptions);
            }

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

            //Las miniaturas del geovisor de los puntos que ya no existen o no se ven.
            $geoThumbnails = GeoThumbnail::sweep();
            $result->setValue('geoThumbnailsSwept', count($geoThumbnails['deleted']));
            $result->setValue('geoThumbnailsSweepFailed', $geoThumbnails['failed']);

            //Caché de enlaces simbólicos: solo los enlaces y las carpetas que quedan vacías, nunca un archivo ni la raíz
            //(ServerDelegatedLinks). Lo que el proceso no puede borrar (carpetas de www-data, desde el terminal) se cuenta.
            $result->setValue('serverDelegatedSymLinksRemoved', false);
            $result->setValue('serverDelegatedSymLinksFailed', 0);
            if ($removeServerDelegatedSymLinks) {
                $removedLinks = ServerDelegatedLinks::deleteAll();
                $result->setValue('serverDelegatedSymLinksFailed', $removedLinks['failed']);
                $result->setValue('serverDelegatedSymLinksRemoved', $removedLinks['failed'] === 0);
                if ($removedLinks['failed'] > 0) {
                    log_soft_error("clean-cache: {$removedLinks['failed']} enlace(s) de statics/server-delegated sin permiso para borrarse.");
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
                    $nameLang = self::seoOptionName($nameOnConfig, $lang);
                    $nameLangOnForm = self::seoOptionName($nameOnForm, $lang);
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
     * Las rutas de la pantalla «Rutas y permisos», agrupadas por módulo.
     *
     * El módulo sale del controlador y no de $baseRouteName: el arreglo de la ruta no guarda su grupo.
     * Las rutas con un cierre como controlador se quedan fuera.
     *
     * @param array<string,array<string,mixed>> $routes Lo que devuelve get_routes()
     * @return array<string,list<array{name:string,route:string,sample:string,shortController:string,controller:string,requireLogin:bool,roles:list<string>,allRoles:bool}>>
     */
    private static function routesByModule(array $routes)
    {
        $allCodes = array_map('strval', array_keys(UsersModel::getTypesUser()));
        sort($allCodes);

        $groups = [];

        foreach ($routes as $name => $information) {

            if (!is_string($information['controller'])) {
                continue;
            }

            $name = (string) $name;
            $routeName = (string) $information['name'];
            $controller = str_replace(':', '::', $information['controller']);
            $class = ltrim(explode(':', $information['controller'])[0], '\\');
            $classParts = explode('\\', $class);

            $controllersPosition = strpos($class, '\\Controllers\\');
            $module = $controllersPosition !== false
                ? substr($class, 0, $controllersPosition)
                : implode('\\', array_slice($classParts, 0, -1));
            if (str_starts_with($module, 'PiecesPHP\\')) {
                $module = substr($module, strlen('PiecesPHP\\'));
            }
            if ($module === '') {
                $module = 'PiecesPHP';
            }

            $requireLogin = (bool) $information['require_login'];
            $roles = [];
            $allRoles = false;
            if ($requireLogin) {
                $roles = array_values(array_map('strval', get_route_roles_allowed($name, 'name')));
                $codes = array_values(array_unique(array_map('strval', get_route_roles_allowed($name, 'code'))));
                sort($codes);
                $allRoles = $codes === $allCodes;
            }

            $groups[$module][] = [
                'name' => $routeName,
                'route' => (string) $information['route'],
                //Sin $silentOnNotExists, get_route() lanza antes que devolver null.
                'sample' => str_replace(baseurl(), '', get_route_sample($routeName) ?? ''),
                'shortController' => end($classParts) . '::' . (explode(':', $information['controller'])[1] ?? ''),
                'controller' => $controller,
                'requireLogin' => $requireLogin,
                'roles' => $roles,
                'allRoles' => $allRoles,
            ];

        }

        uksort($groups, 'strnatcasecmp');

        return $groups;
    }

    /**
     * Si la imagen de esa configuración es todavía la de fábrica.
     *
     * @param string $configName Una clave de FACTORY_BRAND_IMAGES
     * @return bool
     */
    public static function isFactoryBrandImage(string $configName): bool
    {
        $value = get_config($configName);
        $file = is_string($value) && $value !== '' ? basepath($value) : '';
        return array_key_exists($configName, self::FACTORY_BRAND_IMAGES) && $file !== '' && is_file($file)
            && sha1_file($file) === self::FACTORY_BRAND_IMAGES[$configName];
    }

    /**
     * Si esa imagen se pinta en blanco en modo oscuro: su opción si existe; si no, solo la de fábrica. Así el logo de
     * fábrica la trae activa, y un logo propio nunca se invierte sin marcarla.
     *
     * @param string $configName Una clave de BRAND_IMAGES_INVERT_ON_DARK
     * @return bool
     */
    public static function brandImageInvertsOnDark(string $configName): bool
    {
        if (!array_key_exists($configName, self::BRAND_IMAGES_INVERT_ON_DARK)) {
            return false;
        }
        $value = get_config(self::BRAND_IMAGES_INVERT_ON_DARK[$configName]);
        return $value === false ? self::isFactoryBrandImage($configName) : in_array($value, [1, '1'], true);
    }

    /**
     * El texto sobre el patrocinador: lo guardado si la opción existe, también vacío (quien lo borró lo quiso quitar), y
     * si no existe, el de fábrica.
     *
     * @return string
     */
    public static function partnersText(): string
    {
        $text = get_config(self::PARTNERS_TEXT_CONFIG);
        return is_string($text) ? trim($text) : __(LANG_GROUP, 'Con el apoyo de');
    }

    /**
     * Si el valor es un color válido para esa opción: #RRGGBB, o vacío en las series de datos (manda el tema). Lo pinta
     * el CSS de cada página y la plantilla de los correos: lo que no sea una cadena con esa forma no se guarda ni se publica.
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
        if ($value === '') {
            return in_array($name, self::DATA_TOKENS, true);
        }
        return preg_match('/^#[0-9A-Fa-f]{6}\z/', $value) === 1;
    }

    /**
     * Quién puede traducir con IA, tal como llega del formulario: una lista de tipos conocidos. Sin ninguno elegido llega
     * `['']` (un '' suelto, Parameter lo toma por nulo y no se guardaría): es la lista vacía, solo root.
     *
     * @param mixed $value
     * @return bool
     */
    public static function translationUserTypesIsValid($value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        $known = array_keys(UsersModel::TYPES_USERS);
        foreach ($value as $type) {
            if ($type !== '' && (!Validator::isInteger($type) || !in_array((int) $type, $known, true))) {
                return false;
            }
        }
        return true;
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
            //Guardar los colores
            new Route(
                "{$startRoute}appearance/colors/save[/]",
                $classname . ':saveColorsAction',
                self::$baseRouteName . '-' . 'appearance-colors-save',
                'POST',
                true,
                null,
                self::ROLES_SAVE_COLORS
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
            new Route(
                "{$startRoute}/integrations/mail/probe[/]",
                $classname . ':emailProbe',
                self::$baseRouteName . '-' . 'integrations-mail-probe',
                'POST',
                true,
                null,
                self::ROLES_EMAIL
            ),
            new Route(
                "{$startRoute}/integrations/mail/test-send[/]",
                $classname . ':emailTestSend',
                self::$baseRouteName . '-' . 'integrations-mail-test-send',
                'POST',
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
            $injector = new LangInjector(dirname(__DIR__) . '/lang', Config::get_allowed_langs());
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
