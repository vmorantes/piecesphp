<?php

/**
 * SettingsController.php
 */

namespace PiecesPHP\Settings\Controllers;

use DataImportExportUtility\Controllers\DataTransferController;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AIUsageReport;
use PiecesPHP\AI\Providers\AIProviderCatalog;
use PiecesPHP\AI\Providers\AIProviderRecord;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
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
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\Core\VisitorIP;
use PiecesPHP\Geo\GeoRoutes;
use PiecesPHP\Geo\GeoThumbnail;
use PiecesPHP\LangInjector;
use PiecesPHP\Logs\Controllers\LogsController;
use PiecesPHP\Logs\LogsLang;
use PiecesPHP\Logs\LogTabRegistry;
use PiecesPHP\Mcp\Assistant;
use PiecesPHP\Settings\Mail\MailTester;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Settings\SettingsRoutes;
use PiecesPHP\Settings\Uploads\GifStructureValidator;
use PiecesPHP\Settings\Uploads\ImageMetadataStripper;
use PiecesPHP\Settings\Uploads\SvgWhitelistValidator;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\ServerDelegatedLinks;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSessionsController;
use PiecesPHP\UserSystem\Controllers\UserTokensController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserTypes;
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
     * Los sha1 de fábrica de cada imagen de marca (statics/images/), el de hoy y los anteriores. Por contenido y no por
     * ruta: una instalación anterior a H230 guardó su imagen propia en el MISMO archivo de fábrica.
     */
    const FACTORY_BRAND_IMAGES = [
        'logo' => ['267a9e14146deb1b23e52417da4512737bbceaa2', '98b74efa0dca1da2cbe5a671dd3d940d733319ee', '9cf27f1fbb82e550d03fbaca632f9c6563eec81a'],
        'partners' => ['4035cfe5e931fd5ed7ccf5370a382dcef01e49dd', 'f99eed56bd1b37230c3da8219a88aa4ca3e815c1'],
        'partnersVertical' => ['4371bf0fda8f73c17624ec88e99b486b6ad64181', '759ba8f2f55108ad876d8974da1ef0ae3dfbbf0f'],
        //El SVG y el GIF de fábrica de la animación, el de hoy y los anteriores.
        'loaderAnimation' => ['24e8de007b908f0fe65dd22d561537a3dfaa1a37', '05423f01ecaf50b4a6ebfbdf1e26b439b9c3215a', '8cb66d7d24cd62352b3c021137e3d00db0fbe5ef', '325da5872e027530321fe7bfbfb60ee61183792d'],
        'loaderLogo' => ['267a9e14146deb1b23e52417da4512737bbceaa2'],
        //Las pantallas de carga de fábrica (P383): SVG animados, con el centro limpio.
        'loaderScreen' => ['a5cd5f810389a8cb739718978b24fb55161c3191'],
        'loaderScreenMobile' => ['9d759ff771ab335dcf2db8049d54387a3c4f1569'],
    ];

    /**
     * La imagen de fábrica de las opciones que pueden no existir en la base: la siembra del arranque solo corre con la
     * base vacía, y una instalación anterior no las tiene. Ver brandImagePath().
     */
    const FACTORY_BRAND_IMAGE_DEFAULTS = [
        'loaderAnimation' => 'statics/images/loader-animation.svg',
        'loaderLogo' => 'statics/images/loader-logo.png',
        'loaderScreen' => 'statics/images/loader-screen.svg',
        'loaderScreenMobile' => 'statics/images/loader-screen-mobile.svg',
    ];

    /**
     * Imagen => la que tiene que ser de fábrica para que esa tome la suya de FACTORY_BRAND_IMAGE_DEFAULTS. Ver brandImagePath().
     */
    const FACTORY_DEFAULT_ONLY_WITH_FACTORY = [
        'loaderScreenMobile' => 'loaderScreen',
    ];

    /**
     * Guardada vacía = apagada, lo que dejaba el «Eliminar» de antes; lo limpia la actualización del 2026-11-19.
     * @deprecated 11.0.0 Lo sustituye LOADER_LAYERS_ENABLED.
     */
    const BRAND_IMAGES_EMPTY_IS_OFF = self::EMPTY_IS_OFF_UNTIL_MIGRATED;

    /** La lista de dentro, sin el aviso de desuso: brandImagePath() la sigue leyendo hasta la 11.0.0. */
    private const EMPTY_IS_OFF_UNTIL_MIGRATED = ['loaderAnimation', 'loaderLogo', 'loaderScreen', 'loaderScreenMobile'];

    /**
     * Capa => su opción ('1'/'0'; sin opción, encendida); apagar no borra ni descarga la imagen. Sin mensaje ni porcentaje.
     */
    const LOADER_LAYERS_ENABLED = [
        'animation' => 'loaderAnimationEnabled',
        'logo' => 'loaderLogoEnabled',
        'screen' => 'loaderScreenEnabled',
    ];

    /**
     * La animación de carga subida (H345): GIF o SVG, tal cual, hasta este tamaño.
     */
    const LOADER_ANIMATION_MAX_BYTES = 2 * 1024 * 1024;

    /**
     * El lado máximo, en píxeles, de un GIF de la animación de carga: un GIF pequeño puede declarar un lienzo enorme, y
     * el navegador reserva su memoria al pintarlo.
     */
    const LOADER_ANIMATION_MAX_SIDE = 4096;

    /**
     * La pantalla de carga subida (H417), tal cual: se pinta mientras carga la página y tiene que llegar antes que ella.
     */
    const LOADER_SCREEN_MAX_BYTES = 3 * 1024 * 1024;

    /**
     * Las imágenes que se suben tal cual, sin recortador: opción => [nombre del archivo, si es una pantalla de carga].
     */
    const BRAND_IMAGES_DIRECT_UPLOAD = [
        'loaderAnimation' => ['loader-animation', false],
        'loaderAnimationDark' => ['loader-animation-dark', false],
        'loaderScreen' => ['loader-screen', true],
        'loaderScreenDark' => ['loader-screen-dark', true],
        'loaderScreenMobile' => ['loader-screen-mobile', true],
        'loaderScreenMobileDark' => ['loader-screen-mobile-dark', true],
    ];

    /**
     * El logo del desarrollador que trae el framework (config.php, `developer_logo`).
     */
    const FACTORY_DEVELOPER_LOGO = 'statics/images/developer-logo.png';

    /**
     * Los sha1 de fábrica del logo del desarrollador, el de hoy y el anterior (67 × 32). Por contenido y no por ruta: un
     * clon pone su logo en el MISMO archivo de fábrica (isFactoryDeveloperLogo()).
     */
    const FACTORY_DEVELOPER_LOGO_SHA1 = ['5e3fe1eb6be62e5c35ede914bcfaa6d4d7da86de', 'b029c66bf76ea8af5ad8f41a1ae3025ba5c46124'];

    /**
     * Su versión para el modo oscuro: con el logo de fábrica y sin `developer_logo_invert_on_dark`, se pinta esta en vez de
     * invertir la clara (developerLogoTag()).
     */
    const FACTORY_DEVELOPER_LOGO_DARK = 'statics/images/developer-logo-dark.png';

    /**
     * Las imágenes que el modo oscuro puede volver blancas (P277 c): imagen => su opción. Se guardan 1 o 0, nunca un
     * booleano: get_config() devuelve false también si la opción no existe, y sin opción manda brandImageInvertsOnDark().
     */
    const BRAND_IMAGES_INVERT_ON_DARK = [
        'logo' => 'logoInvertOnDark',
        'partners' => 'partnersInvertOnDark',
        'partnersVertical' => 'partnersVerticalInvertOnDark',
    ];

    /**
     * Imagen => [opción de su modo oscuro, opción de su imagen oscura]; el modo, uno de BRAND_IMAGE_DARK_MODES.
     * Solo se escriben estas: BRAND_IMAGES_INVERT_ON_DARK se lee para el modo no guardado (brandImageDarkMode()).
     */
    const BRAND_IMAGES_DARK_MODE = [
        'logo' => ['mode' => 'logoDarkMode', 'image' => 'logoDark'],
        'partners' => ['mode' => 'partnersDarkMode', 'image' => 'partnersDark'],
        'partnersVertical' => ['mode' => 'partnersVerticalDarkMode', 'image' => 'partnersVerticalDark'],
        'loaderAnimation' => ['mode' => 'loaderAnimationDarkMode', 'image' => 'loaderAnimationDark'],
        'loaderLogo' => ['mode' => 'loaderLogoDarkMode', 'image' => 'loaderLogoDark'],
        'loaderScreen' => ['mode' => 'loaderScreenDarkMode', 'image' => 'loaderScreenDark'],
        'loaderScreenMobile' => ['mode' => 'loaderScreenMobileDarkMode', 'image' => 'loaderScreenMobileDark'],
    ];

    const BRAND_IMAGE_DARK_SAME = 'same';
    const BRAND_IMAGE_DARK_INVERT = 'invert';
    const BRAND_IMAGE_DARK_IMAGE = 'image';
    const BRAND_IMAGE_DARK_MODES = [self::BRAND_IMAGE_DARK_SAME, self::BRAND_IMAGE_DARK_INVERT, self::BRAND_IMAGE_DARK_IMAGE];

    /**
     * La imagen oscura que trae el framework: con la clara de fábrica y sin modo guardado, el modo es `image` con esta.
     */
    const FACTORY_BRAND_IMAGES_DARK = [
        'logo' => 'statics/images/white-logo.png',
        'partners' => 'statics/images/partners-dark.png',
        'partnersVertical' => 'statics/images/partners-vertical-dark.png',
        'loaderAnimation' => 'statics/images/loader-animation-dark.svg',
        'loaderLogo' => 'statics/images/white-logo.png',
        'loaderScreen' => 'statics/images/loader-screen-dark.svg',
        'loaderScreenMobile' => 'statics/images/loader-screen-mobile-dark.svg',
    ];

    /**
     * Las imágenes cuya oscura de fábrica se puede sustituir por la clara invertida: sin ella en disco (white-logo.png,
     * partners-dark.png…), la de fábrica vuelve a invertirse, como antes de que trajera su oscura.
     */
    const FACTORY_BRAND_IMAGES_DARK_IS_INVERTED = ['logo', 'partners', 'partnersVertical'];

    /**
     * Lo que ya midió isFactoryBrandImage() en esta petición, por clave y ruta: hasta dos sha1_file() por imagen y
     * llamada, y una página pinta el logo varias veces. Lo vacía clearBrandImageCache() al guardar una imagen.
     *
     * @var array<string,bool>
     */
    private static array $factoryBrandImageCache = [];

    /**
     * Las hojas de la página para las muestras (themePreviewDocument()), leídas una vez por petición.
     *
     * @var string|null
     */
    private static ?string $themePreviewHead = null;

    const LANG_GROUP = 'appConfig';

    /**
     * Los valores que entiende `SMTPSecure` de PHPMailer: 'tls' es STARTTLS, 'ssl' es SMTPS y '' es sin cifrado
     * explícito (con Auto TLS, PHPMailer sube a STARTTLS si el servidor lo ofrece). Otro valor lo ignora en silencio.
     *
     * @var string[]
     */
    const MAIL_PROTOCOLS = ['tls', 'ssl', ''];

    /**
     * Los nombres habituales de cada protocolo, sin mayúsculas ni espacios a los lados: lo que normalizeMailProtocol()
     * acepta al guardar, al pintar la vista y al enviar.
     *
     * @var array<string,string>
     */
    const MAIL_PROTOCOL_ALIASES = [
        'tls' => 'tls',
        'starttls' => 'tls',
        'ssl' => 'ssl',
        'ssl/tls' => 'ssl',
        'smtps' => 'ssl',
        '' => '',
        'ninguno' => '',
        'none' => '',
    ];

    /**
     * Los fondos del inicio de sesión: de uno a cinco, cada uno con su nombre de archivo `bg<n>` en la carpeta.
     */
    const LOGIN_BACKGROUNDS_MAX = 5;
    const LOGIN_BACKGROUNDS_FOLDER = 'statics/login-and-recovery/images/login';

    /**
     * Toda imagen subida desde Configuración, por tipo (login-backgrounds, brand, open-graph). Las carpetas de fábrica
     * están versionadas: la aplicación no las escribe ni borra; una opción vieja que apunte allí se lee tal cual.
     */
    const SETTINGS_UPLOADS_FOLDER = 'statics/uploads/settings';

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
        'main_brand_color_dark',
        'second_brand_color',
        'third_brand_color',
        'meta_theme_color',
        'data_color_2',
        'data_color_3',
        'data_color_4',
        'data_color_5',
    ];
    /**
     * Los tres acentos. Un valor inválido cae a su valor de fábrica; el primero se publica siempre y los otros dos solo si
     * se apartan de fábrica (themeTokens()).
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
        'main_brand_color' => '#00008B',
        'main_brand_color_dark' => '',
        'second_brand_color' => '#0B7A77',
        'third_brand_color' => '#E8730C',
        'data_color_2' => '#0B7A77',
        'data_color_3' => '#E8730C',
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
     * Las fuentes que se pueden elegir: todas las trae el tema, con su clave de data-font y su stack copiado del tema.
     *
     * @var array<string,array{name:string,stack:string}>
     */
    const THEME_FONTS = [
        'atkinson-hyperlegible-next' => [
            'name' => 'Atkinson Hyperlegible Next',
            'stack' => '"Atkinson Hyperlegible Next", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        ],
        'red-hat-text' => [
            'name' => 'Red Hat Text',
            'stack' => '"Red Hat Text", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        ],
        'ibm-plex-sans' => [
            'name' => 'IBM Plex Sans',
            'stack' => '"IBM Plex Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        ],
        'onest' => [
            'name' => 'Onest',
            'stack' => '"Onest", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
        ],
    ];
    const THEME_FONT_FACTORY = 'atkinson-hyperlegible-next';
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
     * Lo que ve quien no ha elegido lo suyo en el menú de su nombre (Apariencia › Accesibilidad): un nivel de
     * AdminPanelController::TEXT_SIZES y un modo de AdminPanelController::THEME_MODES.
     */
    const ACCESSIBILITY_TEXT_SIZE_CONFIG = 'accessibility_text_size';
    const ACCESSIBILITY_THEME_MODE_CONFIG = 'accessibility_theme_mode';
    /**
     * Contraste, movimiento y foco del sitio (H252, fase 2), de AdminPanelController::CONTRASTS, MOTIONS y FOCUSES.
     */
    const ACCESSIBILITY_CONTRAST_CONFIG = 'accessibility_contrast';
    const ACCESSIBILITY_MOTION_CONFIG = 'accessibility_motion';
    const ACCESSIBILITY_FOCUS_CONFIG = 'accessibility_focus';
    /**
     * Espaciado del texto y transparencias del sitio (tema 0.16.0), de AdminPanelController::TEXT_SPACINGS y TRANSPARENCIES.
     */
    const ACCESSIBILITY_TEXT_SPACING_CONFIG = 'accessibility_text_spacing';
    const ACCESSIBILITY_TRANSPARENCY_CONFIG = 'accessibility_transparency';
    /**
     * La fábrica de «Restablecer» en Estilo del panel y de los siete lectores accessibility*().
     *
     * @var array<string,string> opción => valor
     */
    const ACCESSIBILITY_FACTORY = [
        self::ACCESSIBILITY_TEXT_SIZE_CONFIG => AdminPanelController::TEXT_SIZE_NORMAL,
        self::ACCESSIBILITY_THEME_MODE_CONFIG => AdminPanelController::THEME_MODE_AUTO,
        self::ACCESSIBILITY_CONTRAST_CONFIG => 'auto',
        self::ACCESSIBILITY_MOTION_CONFIG => 'auto',
        self::ACCESSIBILITY_FOCUS_CONFIG => 'normal',
        self::ACCESSIBILITY_TEXT_SPACING_CONFIG => 'normal',
        self::ACCESSIBILITY_TRANSPARENCY_CONFIG => 'auto',
    ];
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
     * Quién sube una animación de carga SVG: un SVG validado sigue siendo un documento que el navegador analiza, y la lista blanca no acota el coste de pintarlo.
     */
    const ROLES_SVG_BRAND_IMAGES = [UsersModel::TYPE_USER_ROOT];
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
        \PiecesPHP\Core\SessionToken::APP_ACCESS_LIFETIME_CONFIG,
        \PiecesPHP\Core\SessionToken::APP_INACTIVITY_CONFIG,
        \PiecesPHP\Core\SessionToken::APP_CEILING_CONFIG,
    ];
    /**
     * Solo el centro de mando (ADR 0074 y 0077): las opciones de antes (claves, modelos y translationAI) ya no se escriben
     * desde aquí; se leen hasta la 11.0.0 como los registros convertidos (AIProviderRegistry).
     */
    const AI_CONFIG_KEYS = [
        AIGateway::CONFIG_NAME,
    ];

    /**
     * Las claves de API de antes: selladas en la base (SettingsRoutes las declara SHAPE_SECRET). Ya no las escribe
     * Configuración › IA.
     */
    const AI_SECRET_CONFIG_KEYS = [
        'OpenAIApiKey',
        'MistralAIApiKey',
        'GroqAPIKey',
    ];

    /**
     * Cuántos modelos recuerda cada registro de proveedor para sugerirlos (los últimos que eligió una función).
     */
    const AI_PROVIDER_MODELS_REMEMBERED = 10;

    /**
     * El precio más alto que admite un modelo en Configuración › IA: USD por millón de tokens (ADR 0077 §8).
     */
    const MAX_AI_PRICE = 10000;

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
            $backgrounds = get_config('backgrounds');

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'deleteURL' => self::routeName('appearance-backgrounds-delete'),
                'loginCards' => self::loginBackgroundCards(is_array($backgrounds) ? $backgrounds : (is_object($backgrounds) ? (array) $backgrounds : [])),
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
            $validParamenters = false;

            foreach ($allowedImages as $imageName => $name) {
                $validParamenters = isset($_FILES[$imageName]) && $_FILES[$imageName]['error'] == \UPLOAD_ERR_OK;
                if ($validParamenters) {
                    $nameCurrentAllowedImage = $imageName;
                    $nameImage = $name;
                    break;
                }
            }

            $isLoginBackground = str_starts_with($nameImage, 'bg');
            $currentBackgroundConfigMapper = new SettingsModel('backgrounds');
            //El valor efectivo y no el de la fila: sin fila, partir de vacío borraría los fondos de fábrica.
            $currentBackgroundConfigValues = get_config('backgrounds');
            $currentBackgroundConfigValues = is_array($currentBackgroundConfigValues) ? $currentBackgroundConfigValues : (is_object($currentBackgroundConfigValues) ? (array) $currentBackgroundConfigValues : []);

            //Cuántos quedarían, antes de guardar nada: la ruta nueva aún no existe y basta un valor que no esté en la lista.
            $countAfterUpload = $isLoginBackground ? count(self::backgroundsWithImage($currentBackgroundConfigValues, $nameImage, "\0")) : 0;

            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'));
            $result->setSingleOperation(true);

            $createMessage = __(self::LANG_GROUP, 'Imagen guardada.');
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');
            $unexpectedOrMissedParamMessage = __(self::LANG_GROUP, 'Información faltante o inesperada.');

            if ($validParamenters && $countAfterUpload > self::LOGIN_BACKGROUNDS_MAX) {

                $result->setMessage(strReplaceTemplate(__(self::LANG_GROUP, 'Ya hay ${max} fondos: cambie o elimine uno.'), ['${max}' => self::LOGIN_BACKGROUNDS_MAX]));

            } elseif ($validParamenters) {

                $fileHandler = new FileUpload($nameCurrentAllowedImage, [FileValidator::TYPE_JPG, FileValidator::TYPE_JPEG, FileValidator::TYPE_WEBP], 5);

                if ($fileHandler->validate()) {

                    $relativePath = self::storeSettingsUpload($fileHandler, $nameCurrentAllowedImage, 'login-backgrounds', $nameImage, $storeError);

                    if ($relativePath !== null) {

                        $oldImage = '';
                        if ($isLoginBackground) {
                            foreach ($currentBackgroundConfigValues as $v) {
                                if (is_string($v) && str_contains($v, $nameImage)) {
                                    $oldImage = $v;
                                    break;
                                }
                            }
                            $newBackgroundConfigValues = self::backgroundsWithImage($currentBackgroundConfigValues, $nameImage, $relativePath);
                            //La misma imagen otra vez da la misma ruta: no hay nada que escribir.
                            $updated = $newBackgroundConfigValues === $currentBackgroundConfigValues;
                            if (!$updated) {
                                $currentBackgroundConfigMapper->value = $newBackgroundConfigValues;
                                $updated = $currentBackgroundConfigMapper->id !== null ? $currentBackgroundConfigMapper->update() : SettingsModel::setConfigValue('backgrounds', $newBackgroundConfigValues);
                            }
                        } else {
                            $oldImage = (string) get_config('backgoundProblems');
                            $updated = $oldImage === $relativePath || SettingsModel::setConfigValue('backgoundProblems', $relativePath);
                        }

                        //Solo se borra una subida anterior: un fondo de fábrica no está en la carpeta de subidas.
                        if ($updated && $oldImage !== '' && $oldImage !== $relativePath) {
                            self::deleteSettingsImage($oldImage);
                        }

                        $result
                            ->setMessage($updated ? $createMessage : $unknowErrorMessage)
                            ->setSuccessOnSingleOperation($updated);

                    } else {
                        $result->setMessage($storeError ?? $unknowErrorMessage);
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
     * El protocolo del correo con el valor que entiende PHPMailer ('tls', 'ssl' o ''), o null si no es ninguno de sus
     * nombres (MAIL_PROTOCOL_ALIASES). Lo usan el guardado, la vista de Correo y el Mailer.
     *
     * @param mixed $value Lo que devuelva MailConfig::protocol() o llegue del formulario; si no es texto, null.
     * @return string|null
     */
    public static function normalizeMailProtocol(mixed $value): ?string
    {
        return is_string($value) ? (self::MAIL_PROTOCOL_ALIASES[mb_strtolower(trim($value))] ?? null) : null;
    }

    /**
     * Los protocolos de la lista de Correo, en su orden y con su texto: la única fuente de la vista.
     *
     * @return array<string,string>
     */
    public static function mailProtocolOptions(): array
    {
        return [
            'tls' => __(self::LANG_GROUP, 'STARTTLS'),
            'ssl' => __(self::LANG_GROUP, 'SSL/TLS'),
            '' => __(self::LANG_GROUP, 'Ninguno'),
        ];
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
     * Las cinco tarjetas de Fondos del inicio de sesión: las imágenes primero, en el orden de la lista, y después las
     * vacías, cada una con el primer nombre `bg<n>` libre. Cada tarjeta envía su archivo con el campo de su nombre, así
     * que cambiar la imagen de una pisa la suya y subir en una vacía no pisa ninguna de la lista.
     *
     * @param array<int|string,mixed> $current La opción `backgrounds`.
     * @return array<int,array{field:string,name:string,image:string}>
     */
    public static function loginBackgroundCards(array $current): array
    {
        $names = [];
        for ($number = 1; $number <= self::LOGIN_BACKGROUNDS_MAX; $number++) {
            $names[] = "bg{$number}";
        }
        $nameOf = function (string $background) use ($names): ?string {
            $file = pathinfo($background, \PATHINFO_FILENAME);
            return in_array($file, $names, true) ? $file : null;
        };
        $images = array_slice(array_values(array_filter($current, fn($background) => is_string($background) && $background !== '')), 0, self::LOGIN_BACKGROUNDS_MAX);
        $used = array_values(array_filter(array_map($nameOf, $images)));
        $free = array_values(array_diff($names, $used));
        $cards = [];
        foreach ($images as $image) {
            //Una ruta que no es `bg<n>` (puesta a mano en la opción) se cambia con un nombre libre.
            $name = $nameOf($image) ?? (string) array_shift($free);
            $cards[] = ['field' => 'background-' . substr($name, 2), 'name' => $name, 'image' => $image];
        }
        foreach ($free as $name) {
            if (count($cards) >= self::LOGIN_BACKGROUNDS_MAX) {
                break;
            }
            $cards[] = ['field' => 'background-' . substr($name, 2), 'name' => $name, 'image' => ''];
        }
        return $cards;
    }

    /**
     * La lista de fondos sin uno: siempre una lista compacta de cadenas, para que en JSON sea un arreglo y no un objeto
     * (login.js usa `.length`). Null si no está o si quitarlo la dejaría vacía.
     *
     * @param array<int|string,mixed> $current
     * @param string $background El valor exacto de la lista.
     * @return array<int,string>|null
     */
    public static function backgroundsWithout(array $current, string $background): ?array
    {
        $backgrounds = array_values(array_filter($current, fn($value) => is_string($value) && $value !== ''));
        $index = array_search($background, $backgrounds, true);
        if ($background === '' || $index === false) {
            return null;
        }
        array_splice($backgrounds, (int) $index, 1);
        return count($backgrounds) > 0 ? $backgrounds : null;
    }

    /**
     * Guarda una imagen subida (ya validada) en SETTINGS_UPLOADS_FOLDER/<tipo>/, recomprimida y con el nombre de
     * settleSettingsImage(). La extensión sale del contenido, no del nombre que mandó el cliente.
     *
     * @param FileUpload $fileHandler
     * @param string $field El campo de $_FILES
     * @param string $type login-backgrounds, brand u open-graph
     * @param string $name El de la opción: dos opciones con la misma imagen no comparten archivo, y borrar una no rompe la otra
     * @param string|null $error Con null devuelto, el motivo ya traducido si lo hay; si no, null
     * @return string|null La ruta relativa, o null si no se guardó
     */
    public static function storeSettingsUpload(FileUpload $fileHandler, string $field, string $type, string $name, ?string &$error = null): ?string
    {
        $error = null;
        $temporary = is_string($_FILES[$field]['tmp_name'] ?? null) ? $_FILES[$field]['tmp_name'] : '';
        $clientName = is_string($_FILES[$field]['name'] ?? null) ? $_FILES[$field]['name'] : '';
        $extension = ProtectedUploads::safeExtension($temporary, pathinfo($clientName, \PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $folder = basepath(self::SETTINGS_UPLOADS_FOLDER . "/{$type}");
        if (!is_dir($folder) && !self::makeGroupWritableFolder($folder)) {
            return null;
        }
        $moved = $fileHandler->moveTo($folder, "{$name}-upload-" . bin2hex(random_bytes(6)), $extension);
        $file = is_string($moved[0] ?? null) ? $moved[0] : '';
        if ($file === '' || !is_file($file)) {
            return null;
        }
        $img = false;
        //Con @: un JPG de 12 bits o un WebP que GD no entiende avisa, y aquí abortaría; el false se trata abajo.
        if ($extension === 'webp') {
            $img = @imagecreatefromwebp($file);
            if ($img !== false) {
                imagewebp($img, $file, 70);
            }
        } elseif ($extension === 'jpg') {
            $img = @imagecreatefromjpeg($file);
            if ($img !== false) {
                imagejpeg($img, $file, 70);
            }
        } elseif ($extension === 'png') {
            $img = @imagecreatefrompng($file);
            if ($img !== false) {
                imagealphablending($img, false);
                imagesavealpha($img, true);
                imagepng($img, $file, 9);
            }
        }
        //Lo que GD no decodifica se guardaba con sus bytes originales, y con ellos su EXIF: se limpia o se rechaza.
        if ($img === false) {
            $stripped = ImageMetadataStripper::strip((string) file_get_contents($file), $extension);
            if ($stripped === null || file_put_contents($file, $stripped, \LOCK_EX) !== strlen($stripped)) {
                //RETORNO-IGNORADO: el archivo no se queda; si ya no existe, no hay nada que borrar.
                @unlink($file);
                $error = $stripped === null ? __(self::LANG_GROUP, 'La imagen está incompleta o mal formada.') : null;
                return null;
            }
        }
        return self::settleSettingsImage($file, $type, $name);
    }

    /**
     * Crea la carpeta, y las que falten hasta ella, con escritura de grupo: la crea el servidor web y la tiene que poder
     * escribir también la terminal. El umask de Apache quita la del grupo, así que se pone con chmod.
     *
     * @param string $folder Ruta absoluta
     * @return bool Si la carpeta existe al terminar
     */
    public static function makeGroupWritableFolder(string $folder): bool
    {
        $missing = [];
        for ($current = $folder; !is_dir($current) && $current !== dirname($current); $current = dirname($current)) {
            $missing[] = $current;
        }
        if (!is_dir($folder) && !mkdir($folder, 02775, true) && !is_dir($folder)) {
            return false;
        }
        foreach ($missing as $created) {
            //RETORNO-IGNORADO: sin el chmod la carpeta sigue sirviendo al servidor web; solo la terminal se queda fuera.
            chmod($created, 02775);
        }
        return true;
    }

    /**
     * Copia un archivo existente a SETTINGS_UPLOADS_FOLDER/<tipo>/ con el nombre de settleSettingsImage().
     *
     * @param string $source Ruta absoluta
     * @param string $type
     * @param string $name
     * @return string|null La ruta relativa, o null si no se copió
     */
    public static function copyIntoSettingsUploads(string $source, string $type, string $name): ?string
    {
        $folder = basepath(self::SETTINGS_UPLOADS_FOLDER . "/{$type}");
        if (!is_file($source) || (!is_dir($folder) && !self::makeGroupWritableFolder($folder))) {
            return null;
        }
        $temporary = "{$folder}/{$name}-upload-" . bin2hex(random_bytes(6)) . '.' . mb_strtolower(pathinfo($source, \PATHINFO_EXTENSION));
        return copy($source, $temporary) ? self::settleSettingsImage($temporary, $type, $name) : null;
    }

    /**
     * LA regla del nombre de toda imagen de Configuración: <nombre>-<huella>.<ext>, con la huella del contenido final.
     * Una imagen nueva es una URL nueva, y la caché del navegador no depende de la marca de tiempo. El archivo ya está
     * en la carpeta de su tipo; si ya hay uno con la misma huella es el mismo contenido, y se queda ese.
     *
     * @param string $file Ruta absoluta, dentro de SETTINGS_UPLOADS_FOLDER/<tipo>/
     * @param string $type
     * @param string $name
     * @return string|null La ruta relativa, o null si no se pudo
     */
    public static function settleSettingsImage(string $file, string $type, string $name): ?string
    {
        $hash = is_file($file) ? sha1_file($file) : false;
        if ($hash === false) {
            return null;
        }
        $relativePath = self::SETTINGS_UPLOADS_FOLDER . "/{$type}/{$name}-" . substr($hash, 0, 10) . '.' . mb_strtolower(pathinfo($file, \PATHINFO_EXTENSION));
        $final = basepath($relativePath);
        if ($final === $file) {
            return $relativePath;
        }
        if (is_file($final)) {
            //RETORNO-IGNORADO: es el temporal «-upload-», que nada lee; el guardado vale igual.
            unlink($file);
            return $relativePath;
        }
        if (!rename($file, $final)) {
            //RETORNO-IGNORADO: es el temporal «-upload-», que nada lee; el fallo ya lo dice el null.
            unlink($file);
            return null;
        }
        return $relativePath;
    }

    /**
     * Si una ruta relativa cae DENTRO de SETTINGS_UPLOADS_FOLDER (por realpath de su carpeta: un «..» no la saca). El
     * archivo puede no existir todavía.
     *
     * @param string $relativePath
     * @return bool
     */
    public static function isSettingsUploadPath(string $relativePath): bool
    {
        $root = realpath(basepath(self::SETTINGS_UPLOADS_FOLDER));
        $folder = $relativePath !== '' ? realpath(dirname(basepath($relativePath))) : false;
        $basename = basename($relativePath);
        return $root !== false && $folder !== false && $basename !== '' && $basename !== '..'
            && ($folder === $root || str_starts_with($folder, $root . \DIRECTORY_SEPARATOR));
    }

    /**
     * Borra una imagen de Configuración solo si está dentro de SETTINGS_UPLOADS_FOLDER: un archivo de fábrica (versionado)
     * u otro cualquiera no se toca nunca. Lo que no se pueda borrar se registra; el guardado sigue.
     *
     * @param string $relativePath
     * @return bool Si borró
     */
    public static function deleteSettingsImage(string $relativePath): bool
    {
        if (!self::isSettingsUploadPath($relativePath) || !is_file(basepath($relativePath))) {
            return false;
        }
        $deleted = unlink(basepath($relativePath));
        if (!$deleted) {
            log_exception(new \RuntimeException('No se pudo borrar la imagen anterior: ' . $relativePath));
        }
        return $deleted;
    }

    /**
     * Quita un fondo del inicio de sesión de la opción `backgrounds` y borra su archivo solo si es una subida
     * (deleteSettingsImage()): un fondo de fábrica, versionado, sale de la lista y se queda en disco. Siempre queda al
     * menos uno.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function backgroundsDelete(Request $req, Response $res)
    {
        $background = $req->getParsedBodyParam('background', null);
        $background = is_string($background) ? $background : '';

        $current = get_config('backgrounds');
        $current = is_array($current) ? $current : (is_object($current) ? (array) $current : []);
        $isListed = in_array($background, $current, true) && $background !== '';
        $remaining = self::backgroundsWithout($current, $background);

        $result = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar fondo'));
        $result->setSingleOperation(true);
        $result->setSuccessOnSingleOperation(false);

        if (!$isListed) {
            $result->setMessage(__(self::LANG_GROUP, 'Ese fondo no está entre los fondos del inicio de sesión.'));
        } elseif ($remaining === null) {
            $result->setMessage(__(self::LANG_GROUP, 'Debe quedar al menos un fondo.'));
        } else {
            $mapper = new SettingsModel('backgrounds');
            $saved = false;
            try {
                if ($mapper->id !== null) {
                    $mapper->value = $remaining;
                    $saved = $mapper->update();
                } else {
                    $saved = SettingsModel::setConfigValue('backgrounds', $remaining);
                }
            } catch (\Exception $e) {
                $reference = log_exception($e);
                $result->setMessage(CustomSlimErrorHandler::genericMessage($reference));
                return $res->withJson($result);
            }
            if ($saved) {
                set_config('backgrounds', $remaining);
                self::deleteSettingsImage($background);
            }
            $result
                ->setMessage($saved ? __(self::LANG_GROUP, 'Fondo eliminado.') : __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.'))
                ->setSuccessOnSingleOperation($saved);
        }

        return $res->withJson($result);
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

        if ($requestMethod == 'GET' && $req->getQueryParam('preview', null) === 'yes') {

            //Solo la vista previa (H416), sin el armazón: logos-favicons.js la pide al guardar una imagen. Misma ruta y permiso.
            $res = $res->write((string) $this->settingsViews->render('panel/pages/app_configurations/inc/brand-previews', [
                'langGroup' => $langGroup,
            ], false));

        } elseif ($requestMethod == 'GET' && $req->getQueryParam('loader', null) === 'yes') {

            //Los data-loader-* guardados, para el área de pruebas de la pestaña «Cargador» (H417): el <html> de la página es de cuando se abrió.
            $res = $res->withJson(['attributes' => self::loaderAttributeValues(), 'screenAvailable' => self::loaderScreenAvailable()]);

        } elseif ($requestMethod == 'GET') {

            import_cropper();

            set_custom_assets([
                SettingsRoutes::staticRoute('js/theme-preview.js'),
                SettingsRoutes::staticRoute('js/logos-favicons.js'),
            ], 'js');
            set_custom_assets([
                SettingsRoutes::staticRoute('css/theme-preview.css'),
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
                'loaderAnimation' => self::brandImagePath('loaderAnimation'),
                'loaderAnimationSize' => self::imageFileSize(self::brandImagePath('loaderAnimation')),
                'loaderLogo' => self::brandImagePath('loaderLogo'),
                'loaderScreen' => self::brandImagePath('loaderScreen'),
                'loaderScreenSize' => self::imageFileSize(self::brandImagePath('loaderScreen')),
                'loaderScreenMobile' => self::brandImagePath('loaderScreenMobile'),
                'loaderScreenMobileSize' => self::imageFileSize(self::brandImagePath('loaderScreenMobile')),
                'loaderScreenAvailable' => self::loaderScreenAvailable(),
                'loaderLayers' => array_combine(array_keys(self::LOADER_LAYERS_ENABLED), array_map(fn (string $layer): bool => self::loaderLayerEnabled($layer), array_keys(self::LOADER_LAYERS_ENABLED))),
                'canUploadSvg' => self::canUploadSvgBrandImages(),
                'mailingLogo' => mailing_logo(),
                'partnersTexts' => array_combine(Config::get_allowed_langs(), array_map(fn (string $lang): string => self::storedPartnersText($lang), Config::get_allowed_langs())),
                'darkModes' => array_combine(array_keys(self::BRAND_IMAGES_DARK_MODE), array_map(fn (string $image): array => [
                    'mode' => self::brandImageDarkMode($image),
                    'saved' => get_config(self::BRAND_IMAGES_DARK_MODE[$image]['mode']) !== false,
                    'onReplace' => self::brandImageDarkModeWithoutSaved($image, false),
                    'image' => self::brandImageDarkImage($image),
                    'factoryDark' => self::brandImageDarkImage($image) !== '' && trim((string) get_config(self::BRAND_IMAGES_DARK_MODE[$image]['image'])) === '',
                    'view' => self::brandImageDarkView($image),
                    'fallbackMode' => self::brandImageDarkFallbackMode($image),
                    'field' => self::BRAND_IMAGES_DARK_MODE[$image]['image'],
                ], array_keys(self::BRAND_IMAGES_DARK_MODE))),
                'partnersTextMaxLength' => self::PARTNERS_TEXT_MAX_LENGTH,
            ] + $this->sectionHeader(__(self::LANG_GROUP, 'Imágenes de marca'), __(self::LANG_GROUP, 'La plataforma es marca blanca: estas imágenes identifican al cliente en toda la plataforma, desde el inicio de sesión y el menú lateral hasta los correos y el sitio público.'));

            $baseViewDir = 'panel/pages/app_configurations';
            $this->render('panel/layout/header');
            $this->settingsViews->render("{$baseViewDir}/logos-favicons", $data);
            $this->render('panel/layout/footer');

        } elseif ($requestMethod == 'POST' && (is_string($req->getParsedBodyParam(self::PARTNERS_TEXT_CONFIG, null)) || is_array($req->getParsedBodyParam(self::PARTNERS_TEXT_CONFIG, null)))) {

            //El texto sobre el patrocinador, uno por idioma: texto plano, recortado. Vacío en el idioma por omisión lo quita
            //del pie; vacío en otro borra el suyo y se usa el del idioma por omisión. Un texto suelto es el del idioma por omisión.
            $posted = $req->getParsedBodyParam(self::PARTNERS_TEXT_CONFIG, null);
            $texts = is_array($posted) ? $posted : [Config::get_default_lang() => $posted];
            $saved = true;
            foreach (Config::get_allowed_langs() as $lang) {
                if (!is_string($texts[$lang] ?? null)) {
                    continue;
                }
                $text = mb_substr(trim(strip_tags($texts[$lang])), 0, self::PARTNERS_TEXT_MAX_LENGTH);
                $optionName = self::partnersTextOptionName($lang);
                if ($lang !== Config::get_default_lang() && $text === '') {
                    //RETORNO-IGNORADO: si no existía, no hay nada que borrar; partnersText() cae al del idioma por omisión igual.
                    SettingsModel::deleteConfigValue($optionName);
                    continue;
                }
                //El de fábrica, en cualquier idioma, no se fija: guardar la pantalla sin cambiarlo lo dejaría en ese idioma.
                if ($lang === Config::get_default_lang() && self::isFactoryPartnersText($text)) {
                    //RETORNO-IGNORADO: si no existía, no hay nada que borrar; partnersText() da el de fábrica traducido igual.
                    SettingsModel::deleteConfigValue($optionName);
                    continue;
                }
                $saved = SettingsModel::setConfigValue($optionName, $text) && $saved;
            }
            $result = new ResultOperations([], __(self::LANG_GROUP, 'Texto sobre el patrocinador'));
            $result->setSingleOperation(true);
            $result
                ->setMessage($saved ? __(self::LANG_GROUP, 'Texto guardado.') : __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.'))
                ->setSuccessOnSingleOperation($saved);
            $res = $res->withJson($result);

        } elseif ($requestMethod == 'POST' && (is_string($req->getParsedBodyParam('darkMode', null)) || is_string($req->getParsedBodyParam('invertOnDark', null)))) {

            //El selector «En modo oscuro» de una imagen: se guarda al cambiarlo. El `invertOnDark` de antes de H340 (con
            //`checked` a 1 o 0) se sigue aceptando, y también escribe el modo: la opción vieja ya no se escribe.
            $legacy = !is_string($req->getParsedBodyParam('darkMode', null));
            $image = (string) $req->getParsedBodyParam($legacy ? 'invertOnDark' : 'darkMode', '');
            $checked = $req->getParsedBodyParam('checked', null);
            $mode = $legacy ? ($checked === '1' ? self::BRAND_IMAGE_DARK_INVERT : ($checked === '0' ? self::BRAND_IMAGE_DARK_SAME : null)) : $req->getParsedBodyParam('mode', null);
            $valid = array_key_exists($image, self::BRAND_IMAGES_DARK_MODE) && in_array($mode, self::BRAND_IMAGE_DARK_MODES, true);
            $saved = $valid && SettingsModel::setConfigValue(self::BRAND_IMAGES_DARK_MODE[$image]['mode'], $mode);
            self::clearBrandImageCache();
            $result = new ResultOperations([], __(self::LANG_GROUP, 'En modo oscuro'));
            $result->setSingleOperation(true);
            $result
                ->setMessage($saved ? __(self::LANG_GROUP, 'Preferencia guardada.') : __(self::LANG_GROUP, 'Información faltante o inesperada.'))
                ->setSuccessOnSingleOperation($saved);
            $res = $res->withJson($result);

        } elseif ($requestMethod == 'POST' && is_string($req->getParsedBodyParam('loaderLayer', null))) {

            //Un interruptor de «Qué enseña el cargador»: se guarda al cambiarlo, como «En modo oscuro». Solo las capas de la lista y '1' o '0'.
            $layer = (string) $req->getParsedBodyParam('loaderLayer', '');
            $enabled = $req->getParsedBodyParam('enabled', null);
            $valid = array_key_exists($layer, self::LOADER_LAYERS_ENABLED) && ($enabled === '1' || $enabled === '0');
            $saved = $valid && SettingsModel::setConfigValue(self::LOADER_LAYERS_ENABLED[$layer], $enabled);
            $result = new ResultOperations([], __(self::LANG_GROUP, 'Qué enseña el cargador'));
            $result->setSingleOperation(true);
            $result
                ->setMessage($saved ? __(self::LANG_GROUP, 'Preferencia guardada.') : __(self::LANG_GROUP, 'Información faltante o inesperada.'))
                ->setSuccessOnSingleOperation($saved);
            $res = $res->withJson($result);

        } elseif ($requestMethod == 'POST' && $req->getParsedBodyParam('deleteBrandImage', null) !== null) {

            //@deprecated 11.0.0: el «Eliminar» de las imágenes de marca ya no existe (PO: en un proyecto vivo volverían las de
            //PiecesPHP). Se responde 400 con el motivo, sin tocar nada, para quien aún lo mande; se retira en la 11.0.0.
            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'));
            $result->setSingleOperation(true);
            $result
                ->setMessage(__(self::LANG_GROUP, 'Las imágenes de marca ya no se eliminan: cámbiala por otra o apágala con su interruptor en «Qué enseña el cargador».'))
                ->setSuccessOnSingleOperation(false);
            $res = $res->withStatus(400)->withJson($result);

        } elseif ($requestMethod == 'POST' && array_intersect(array_keys(self::BRAND_IMAGES_DIRECT_UPLOAD), array_keys($_FILES)) !== []) {

            //La animación de carga (H345) y las pantallas de carga (H417), con sus oscuras: tal cual, sin recortador; el SVG, por lista blanca y solo del principal.
            $optionName = (string) array_values(array_intersect(array_keys(self::BRAND_IMAGES_DIRECT_UPLOAD), array_keys($_FILES)))[0];
            [$fileName, $isScreen] = self::BRAND_IMAGES_DIRECT_UPLOAD[$optionName];
            $stored = self::storeDirectBrandUpload($optionName, $fileName, $isScreen);
            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'));
            $result->setSingleOperation(true);
            $saved = false;
            if ($stored['path'] !== null) {
                $oldImage = get_config($optionName);
                $saved = SettingsModel::setConfigValue($optionName, $stored['path']);
                self::clearBrandImageCache();
                $saved = $saved && self::saveDarkModeImageFor($optionName);
                //Solo se borra una subida anterior: la de fábrica no está en la carpeta de subidas.
                if ($saved && is_string($oldImage) && $oldImage !== '' && $oldImage !== $stored['path']) {
                    //RETORNO-IGNORADO: la nueva ya está guardada; una anterior que no se borra solo ocupa sitio, y deleteSettingsImage() lo registra.
                    self::deleteSettingsImage($oldImage);
                }
            }
            $result
                ->setMessage($saved ? __(self::LANG_GROUP, 'Imagen guardada.') : ($stored['error'] !== '' ? $stored['error'] : __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.')))
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
                self::BRAND_IMAGES_DARK_MODE['logo']['image'] => 'logo-dark',
                self::BRAND_IMAGES_DARK_MODE['partners']['image'] => 'partners-dark',
                self::BRAND_IMAGES_DARK_MODE['partnersVertical']['image'] => 'partners-vertical-dark',
                'loaderLogo' => 'loader-logo',
                self::BRAND_IMAGES_DARK_MODE['loaderLogo']['image'] => 'loader-logo-dark',
            ];

            $nameCurrentAllowedImage = '';
            $nameImage = '';
            $validParamenters = false;

            foreach ($allowedImages as $imageName => $name) {
                $validParamenters = isset($_FILES[$imageName]) && $_FILES[$imageName]['error'] == \UPLOAD_ERR_OK;
                if ($validParamenters) {
                    $nameCurrentAllowedImage = $imageName;
                    $nameImage = $name;
                    break;
                }
            }

            $logosAndFaviconsMapper = new SettingsModel($nameCurrentAllowedImage);
            $oldImage = $logosAndFaviconsMapper->value;

            $result = new ResultOperations([], __(self::LANG_GROUP, 'Guardar imagen'));
            $result->setSingleOperation(true);

            $createMessage = __(self::LANG_GROUP, 'Imagen guardada.');
            $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error inesperado.');
            $unexpectedOrMissedParamMessage = __(self::LANG_GROUP, 'Información faltante o inesperada.');

            if ($validParamenters) {

                $fileHandler = new FileUpload($nameCurrentAllowedImage, [FileValidator::TYPE_PNG, FileValidator::TYPE_JPG, FileValidator::TYPE_JPEG], 5);

                if ($fileHandler->validate()) {

                    $relativePath = self::storeSettingsUpload($fileHandler, $nameCurrentAllowedImage, 'brand', $nameImage, $storeError);

                    if ($relativePath !== null) {

                        $logosAndFaviconsMapper->value = $relativePath;
                        $updated = $logosAndFaviconsMapper->id !== null ? $logosAndFaviconsMapper->update() : SettingsModel::setConfigValue($nameCurrentAllowedImage, $relativePath);
                        self::clearBrandImageCache();
                        //Subir una oscura es elegir «Otra imagen» (H340): el modo se guarda en la misma petición.
                        $updated = $updated && self::saveDarkModeImageFor($nameCurrentAllowedImage);

                        //Solo se borra una subida anterior: la imagen de fábrica no está en la carpeta de subidas.
                        if ($updated && is_string($oldImage) && $oldImage !== '' && $oldImage !== $relativePath) {
                            self::deleteSettingsImage($oldImage);
                        }

                        if ($updated) {
                            $result
                                ->setMessage($createMessage)
                                ->setSuccessOnSingleOperation(true);
                        } else {
                            $result->setMessage($unknowErrorMessage);
                        }

                    } else {
                        $result->setMessage($storeError ?? $unknowErrorMessage);
                    }

                } else {
                    $result->setMessage(implode('<br>', $fileHandler->getErrorMessages()));
                }

            } else {
                //Un archivo que pasa del tope del servidor llega sin archivo: se dice el tope, no «información faltante».
                //Y si pasó de post_max_size no llega ni el nombre del campo: lo dice CONTENT_LENGTH.
                $sizeError = self::postSizeErrorMessage();
                foreach (array_keys($allowedImages) as $imageName) {
                    $sizeError ??= self::uploadSizeErrorMessage($_FILES[$imageName]['error'] ?? null);
                }
                $result->setMessage($sizeError ?? $unexpectedOrMissedParamMessage);
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

            //Lo que la portada emite y no se edita aquí, por idioma: la canónica (también og:url) con el mismo cálculo que
            //MetaTags, y og:locale de og_locales (config.php), como MetaTags::ogLocale().
            $home = \App\Controller\PublicAreaController::routeName('index', [], true);
            $ogLocales = get_config('og_locales');
            $shareFacts = [];
            foreach (array_keys($SEOValues) as $lang) {
                $shareFacts[$lang] = [
                    'url' => \PiecesPHP\Core\Utilities\Helpers\MetaTags::canonicalFor($home, (string) $lang),
                    'locale' => is_array($ogLocales) && is_string($ogLocales[$lang] ?? null) ? $ogLocales[$lang] : (string) $lang,
                ];
            }

            $data = [
                'langGroup' => $langGroup,
                'actionURL' => $actionURL,
                'SEOValues' => $SEOValues,
                'shareFacts' => $shareFacts,
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

                    if (is_string($xAccount) && !$sameValue(SettingsModel::readFromDatabase(self::SEO_OPTION_X_ACCOUNT), $xAccount)) {
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

                            $nameOptionOG = self::seoOptionName('open_graph_image', $lang);
                            $openGraphMapper = new SettingsModel($nameOptionOG);
                            $oldImage = is_string($openGraphMapper->value) ? $openGraphMapper->value : '';

                            $relativePath = self::storeSettingsUpload($fileHandler, $nameImageUploaded, 'open-graph', self::seoOptionName('open_graph', $lang), $storeError);

                            if ($relativePath !== null) {

                                $openGraphMapper->value = $relativePath;
                                if ($openGraphMapper->id !== null) {
                                    $saved = $openGraphMapper->update();
                                } else {
                                    $openGraphMapper->name = $nameOptionOG;
                                    $saved = $openGraphMapper->save();
                                }
                                $success = $success && $saved;

                                //Solo se borra una subida anterior: la imagen de fábrica no está en la carpeta de subidas.
                                if ($saved && $oldImage !== '' && $oldImage !== $relativePath) {
                                    self::deleteSettingsImage($oldImage);
                                }

                            } else {
                                $success = false;
                                $unknowErrorMessage = $storeError ?? $unknowErrorMessage;
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
                'mailProtocols' => self::mailProtocolOptions(),
                'mailProtocolSaved' => is_string($element->protocol()) ? $element->protocol() : '',
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
                //«Ninguno» viaja vacío, y Parameter toma '' por ausente: por eso es opcional con '' por omisión, y la
                //presencia y la lista (MAIL_PROTOCOLS) se miran abajo, con un mensaje propio.
                new Parameter(
                    'protocol',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return trim((string) $value);
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
                //Sin clean_string(): una contraseña se guarda tal cual se escribe, con sus espacios y tabuladores.
                new Parameter(
                    'password',
                    '',
                    function ($value) {
                        return is_string($value);
                    },
                    true,
                    function ($value) {
                        return $value;
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
                //«Quitar la guardada» (P317): marcada, la contraseña se guarda vacía.
                new Parameter(
                    'clear_password',
                    false,
                    function ($value) {
                        return Validator::isInteger($value) || is_bool($value);
                    },
                    true,
                    function ($value) {
                        return $value === 1 || $value == '1' || $value === true;
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

                /** @var string $protocolSent */
                $protocolSent = $expectedParameters->getValue('protocol');
                $protocol = self::normalizeMailProtocol($protocolSent);
                //Un rechazo de quien rellena el formulario, no un fallo: se contesta sin pasar por el registro de errores.
                if (!is_array($inputData) || !array_key_exists('protocol', $inputData) || $protocol === null || !in_array($protocol, self::MAIL_PROTOCOLS, true)) {
                    $resultOperation->setMessage(__(self::LANG_GROUP, 'El protocolo debe ser STARTTLS, SSL/TLS o Ninguno.'));
                    return $res->withJson($resultOperation);
                }

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
                    $passwordToSave = self::secretToSave($password, $expectedParameters->getValue('clear_password') === true);
                    if ($passwordToSave !== null) {
                        $mailConfig->password($passwordToSave);
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
                        $key->value = ConfigOptions::valueToStore(self::OS_TICKET_KEY_CONFIG, $newKey);
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
        //Los proxies de confianza, solo root (H28): se valida y guarda solo para root; otro que los envíe, 403.
        $isRoot = (int) (getLoggedFrameworkUser()->type ?? -1) === UsersModel::TYPE_USER_ROOT;
        $body = $req->getParsedBody();
        if (!$isRoot && mb_strtoupper($req->getMethod()) === 'POST' && is_array($body) && array_key_exists(VisitorIP::CONFIG_NAME, $body)) {
            return throw403($req);
        }
        $keys = $isRoot ? array_merge(self::SECURITY_CONFIG_KEYS, [VisitorIP::CONFIG_NAME]) : self::SECURITY_CONFIG_KEYS;
        return $this->securityOrAI($req, $res, 'system-security', $title, 'security', $keys, $header, ['canEditProxies' => $isRoot]);
    }

    /**
     * La lista de proxies de confianza que llega del área de texto (H28): una IP o un CIDR por línea, sin duplicados.
     *
     * @param mixed $input
     * @return string[]
     * @throws InvalidParameterValueException si una línea no vale o abarca todas las direcciones (/0), con su motivo
     */
    public static function trustedProxiesFromInput($input): array
    {
        //Parameters valida dos veces: el texto que llega y la lista que devuelve el parseo.
        $lines = is_string($input) ? (preg_split('/\R/', $input) ?: []) : (is_array($input) ? $input : null);
        if ($lines === null) {
            throw new InvalidParameterValueException(__(self::LANG_GROUP, 'Proxies de confianza: la lista enviada no es válida.'));
        }
        $list = [];
        foreach ($lines as $line) {
            if (!is_string($line)) {
                throw new InvalidParameterValueException(__(self::LANG_GROUP, 'Proxies de confianza: la lista enviada no es válida.'));
            }
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $error = VisitorIP::entryError($line);
            if ($error === VisitorIP::ERROR_ALL) {
                throw new InvalidParameterValueException(strReplaceTemplate(__(self::LANG_GROUP, 'Proxies de confianza: «%entry%» abarca todas las direcciones; así cualquiera podría inventar su IP.'), ['%entry%' => escape_html($line)]));
            }
            if ($error !== null) {
                throw new InvalidParameterValueException(strReplaceTemplate(__(self::LANG_GROUP, 'Proxies de confianza: «%entry%» no es una IP ni un rango CIDR válido.'), ['%entry%' => escape_html($line)]));
            }
            $list[] = $line;
        }
        return array_values(array_unique($list));
    }

    /**
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function ai(Request $req, Response $res)
    {
        $title = __(self::LANG_GROUP, 'Inteligencia artificial');
        $header = $this->sectionHeader($title, __(self::LANG_GROUP, 'El centro de mando de la inteligencia artificial: encenderla, sus proveedores y claves, y quién usa cada función y cuánto puede gastar.'));
        //El «Registro» (ADR 0077 §8): sus dos filtros llegan por GET; lo que no está en su lista cae en el valor por omisión.
        $group = $req->getQueryParam('group', AIUsageReport::GROUP_CAPABILITY);
        $group = is_string($group) && in_array($group, AIUsageReport::GROUPS, true) ? $group : AIUsageReport::GROUP_CAPABILITY;
        $since = $req->getQueryParam('since', (string) AIUsageReport::SINCE_ALL);
        $since = is_string($since) && in_array((int) $since, AIUsageReport::SINCE_MONTHS, true) && preg_match('/^\d+$/', $since) === 1 ? (int) $since : AIUsageReport::SINCE_ALL;
        $extra = [];
        if ($req->getMethod() === 'GET') {
            try {
                $report = AIUsageReport::report($group, $since);
            } catch (\Throwable $e) {
                log_exception($e);
                $report = null;
            }
            $extra = ['usageReport' => $report, 'usageReportGroup' => $group, 'usageReportSince' => $since];
        }
        return $this->securityOrAI($req, $res, 'integrations-ai', $title, 'ai', self::AI_CONFIG_KEYS, $header, $extra);
    }

    /**
     * La dirección del «Registro» de Configuración › IA con sus dos filtros, en su pestaña (#registro).
     *
     * @param string $group Uno de AIUsageReport::GROUPS
     * @param int $since Uno de AIUsageReport::SINCE_MONTHS
     * @return string
     */
    public static function aiUsageReportURL(string $group, int $since): string
    {
        return self::routeName('integrations-ai') . '?' . http_build_query(['group' => $group, 'since' => $since]) . '#registro';
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
                ExtraScripts::ZONE_PUBLIC => __($langGroup, 'Sitio público'),
                ExtraScripts::ZONE_PANEL => __($langGroup, 'Panel'),
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
            'codeTransformed' => BackupPolicy::codeColumnTransforms(),
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
     * Qué se guarda de un secreto que el formulario no pinta (la contraseña del correo, una clave de IA): con «Quitar la
     * guardada» marcada, vacío (P317); si llega vacío, null (se conserva lo guardado); si no, lo que llegó.
     *
     * @param string $sent
     * @param bool $clear
     * @return string|null
     */
    public static function secretToSave(string $sent, bool $clear): ?string
    {
        if ($clear) {
            return '';
        }
        return $sent === '' ? null : $sent;
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
     * @param array<string,mixed> $extra Más datos para la vista (el «Registro» de la IA)
     * @return Response
     */
    private function securityOrAI(Request $req, Response $res, string $routeSuffix, string $title, string $asset, array $keys, array $header = [], array $extra = [])
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
            ] + $header + $extra;

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
                //Las sesiones de app (ADR 0092 §3): la vida del acceso en minutos en el formulario; las otras dos, en días.
                \PiecesPHP\Core\SessionToken::APP_ACCESS_LIFETIME_CONFIG => new Parameter(
                    \PiecesPHP\Core\SessionToken::APP_ACCESS_LIFETIME_CONFIG,
                    null,
                    function ($value) {
                        //Se valida dos veces: lo que llega (minutos, en texto) y lo que devuelve parse() (segundos, entero).
                        return is_int($value) ? \PiecesPHP\Core\SessionToken::appAccessLifetimeIsValid($value) : Validator::isInteger($value) && (int) $value >= 5 && (int) $value <= 1440;
                    },
                    true,
                    function ($value) {
                        return (int) $value * 60;
                    }
                ),
                \PiecesPHP\Core\SessionToken::APP_INACTIVITY_CONFIG => new Parameter(
                    \PiecesPHP\Core\SessionToken::APP_INACTIVITY_CONFIG,
                    null,
                    function ($value) {
                        return is_int($value) ? \PiecesPHP\Core\SessionToken::lifetimeIsValid($value) : Validator::isInteger($value) && (int) $value >= 0 && (int) $value <= 365;
                    },
                    true,
                    function ($value) {
                        return (int) $value * 86400;
                    }
                ),
                \PiecesPHP\Core\SessionToken::APP_CEILING_CONFIG => new Parameter(
                    \PiecesPHP\Core\SessionToken::APP_CEILING_CONFIG,
                    null,
                    function ($value) {
                        return is_int($value) ? \PiecesPHP\Core\SessionToken::lifetimeIsValid($value) : Validator::isInteger($value) && (int) $value >= 0 && (int) $value <= 365;
                    },
                    true,
                    function ($value) {
                        return (int) $value * 86400;
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
                //Una entrada por línea; lo que no vale lanza con su motivo y no se guarda nada.
                VisitorIP::CONFIG_NAME => new Parameter(
                    VisitorIP::CONFIG_NAME,
                    null,
                    function ($value) {
                        self::trustedProxiesFromInput($value);
                        return true;
                    },
                    true,
                    function ($value) {
                        return self::trustedProxiesFromInput($value);
                    }
                ),
                //El centro de mando: el JSON entero sale de los campos; lo que no vale lanza con su motivo (nada a medias).
                AIGateway::CONFIG_NAME => new Parameter(
                    AIGateway::CONFIG_NAME,
                    null,
                    function ($value) {
                        self::aiCenterFromInput($value, get_config(AIGateway::CONFIG_NAME));
                        return true;
                    },
                    true,
                    function ($value) {
                        return self::aiCenterFromInput($value, get_config(AIGateway::CONFIG_NAME));
                    }
                ),
            ];
            $expectedParameters = new Parameters(array_values(array_intersect_key($definitions, array_flip($keys))));

            //Obtención de datos (los registros de proveedor nuevos reciben aquí su id, del servidor)
            $inputData = self::aiAssignProviderIds($req->getParsedBody());

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
                        //Parameters toma '' por «no llega» (null, no se guarda): la lista enviada vacía se vacía; sin la clave, no se toca.
                        if ($key === VisitorIP::CONFIG_NAME && is_array($inputData) && is_string($inputData[$key] ?? null) && trim($inputData[$key]) === '') {
                            $configurationsToSave[$key] = [];
                        }
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
                        \PiecesPHP\Core\SessionToken::APP_ACCESS_LIFETIME_CONFIG => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Minutos que vale el acceso de una app'),
                        ]),
                        \PiecesPHP\Core\SessionToken::APP_INACTIVITY_CONFIG => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Días sin actividad que cierran la sesión de una app'),
                        ]),
                        \PiecesPHP\Core\SessionToken::APP_CEILING_CONFIG => strReplaceTemplate(__(self::LANG_GROUP, '"%s" fue guardado'), [
                            '%s' => __(self::LANG_GROUP, 'Tope en días de la sesión de una app'),
                        ]),
                        'translationAIEnable' => __(self::LANG_GROUP, 'Configuración de traducción con IA actualizada'),
                        \API\Controllers\APIController::TRANSLATION_AI_USER_TYPES_CONFIG => __(self::LANG_GROUP, 'Quién puede traducir con IA actualizado'),
                        AIGateway::CONFIG_NAME => __(self::LANG_GROUP, 'Las funciones de inteligencia artificial, actualizadas'),
                        VisitorIP::CONFIG_NAME => __(self::LANG_GROUP, 'La lista de proxies de confianza fue guardada'),
                    ];

                    $success = true;
                    $successMessage = __(self::LANG_GROUP, 'No ha habido ninguna modificación.');
                    $changesMessages = [];

                    //Las claves de los convertidos en memoria y las entradas de antes se toman ANTES de guardar ai_center (con la
                    //marca, los convertidos ya no existen).
                    $convertedKeys = array_filter(array_map(fn(array $converted): string => $converted['key'], AIProviderRegistry::convertedPending()), fn(string $key): bool => $key !== '');
                    $previousEntries = self::aiProviderEntriesBeforeSave();

                    foreach ($configurationsToSave as $configName => $configValue) {

                        if ($configValue !== null) {

                            $isSameValue = get_config($configName) === $configValue;

                            if (!$isSameValue) {

                                $optionMapper = new SettingsModel($configName);
                                //Las claves de API, selladas (ConfigOptions::SHAPE_SECRET).
                                $optionMapper->value = ConfigOptions::valueToStore($configName, $configValue);

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

                    //Las claves de los registros de proveedor, selladas en su opción (nunca en ai_center).
                    if ($success && in_array(AIGateway::CONFIG_NAME, $keys, true)) {
                        $savedCenter = $expectedParameters->getValue(AIGateway::CONFIG_NAME);
                        $savedKeys = AIProviderRegistry::keys();
                        $providerKeys = self::aiProviderKeysFromInput($inputData, is_array($savedCenter) && is_array($savedCenter['providers'] ?? null) ? $savedCenter['providers'] : [], $savedKeys + $convertedKeys, $previousEntries);
                        if ($providerKeys !== $savedKeys) {
                            $keysOption = new SettingsModel(AIProviderRegistry::KEYS_CONFIG_NAME);
                            $keysOption->value = AIProviderRegistry::sealKeys($providerKeys);
                            if ($keysOption->id !== null) {
                                $keysSaved = $keysOption->update();
                            } else {
                                $keysOption->name = AIProviderRegistry::KEYS_CONFIG_NAME;
                                $keysSaved = $keysOption->save();
                            }
                            $success = $success && $keysSaved;
                            if ($keysSaved) {
                                set_config(AIProviderRegistry::KEYS_CONFIG_NAME, $keysOption->value);
                                $changesMessages[] = __(self::LANG_GROUP, 'Las claves de los proveedores, actualizadas');
                            } else {
                                $unknowErrorMessage = $errorSomesOptions;
                            }
                        }
                        //La caché de modelos, sin los registros que ya no están (solo si el formulario mandó la lista: sin ella, un
                        //convertido aún en memoria parecería quitado).
                        $providersSent = is_array($inputData) && in_array($inputData[AIGateway::CONFIG_NAME]['providers_sent'] ?? null, [true, 1, '1', 'on'], true);
                        $savedProviders = is_array($savedCenter) && is_array($savedCenter['providers'] ?? null) ? $savedCenter['providers'] : [];
                        $converted = is_array($savedCenter) && ($savedCenter[AIProviderRegistry::CONVERTED_FLAG] ?? null) === true;
                        //Los que siguen y no cambiaron de destino ni de nombre en el archivo; sin la marca, también los convertidos en memoria.
                        $remaining = $converted ? [] : array_keys(array_diff_key($previousEntries, self::aiProviderEntries()));
                        foreach ($savedProviders as $entry) {
                            if (!is_array($entry) || !is_string($entry['id'] ?? null)) {
                                continue;
                            }
                            $before = $previousEntries[$entry['id']] ?? null;
                            if ($before === null || (!self::aiProviderDestinationChanged($before, $entry) && ($before['keyFile'] ?? null) === ($entry['keyFile'] ?? null))) {
                                $remaining[] = $entry['id'];
                            }
                        }
                        if ($providersSent && !AIGateway::pruneModelsCache($remaining)) {
                            $success = false;
                            $unknowErrorMessage = $errorSomesOptions;
                        }
                        //Quitar un convertido, o «Quitar la guardada» en él, vacía también su opción de antes, que siguen leyendo
                        //los caminos en desuso. Solo con la conversión escrita, y nunca la que fija el archivo.
                        if ($providersSent && $converted) {
                            $savedById = array_column(array_filter($savedProviders, 'is_array'), null, 'id');
                            $rows = is_array($inputData) && is_array($inputData[AIGateway::CONFIG_NAME]['providers'] ?? null) ? $inputData[AIGateway::CONFIG_NAME]['providers'] : [];
                            $cleared = [];
                            foreach ($rows as $row) {
                                if (is_array($row) && is_string($row['id'] ?? null) && in_array($row['clear_key'] ?? null, [true, 1, '1', 'on'], true)) {
                                    $cleared[trim($row['id'])] = true;
                                }
                            }
                            foreach (AIProviderRegistry::CONVERTED as $convertedId => $definition) {
                                $removed = isset($previousEntries[$convertedId]) && !isset($savedById[$convertedId]);
                                if (!$removed && !isset($cleared[$convertedId])) {
                                    continue;
                                }
                                $option = $definition['keyOption'];
                                $current = get_config($option);
                                if (ConfigOptions::isFixedByFile($option) || !is_string($current) || $current === '') {
                                    continue;
                                }
                                $oldOption = new SettingsModel($option);
                                $oldOption->value = ConfigOptions::valueToStore($option, '');
                                $oldSaved = $oldOption->id !== null ? $oldOption->update() : true;
                                if ($oldSaved) {
                                    set_config($option, '');
                                } else {
                                    $success = false;
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
     * `keywords`, opcional, son palabras con las que también se encuentra la pantalla en el buscador del asistente
     * (PanelSearchSource::find()), como «logo» para Imágenes de marca.
     *
     * @return array<int,array{name:string,icon:string,visible:bool,items:array<int,array{name:string,icon:string,href:string,visible:bool,keywords?:string}>}>
     */
    public static function panelGroups(): array
    {
        $langGroup = self::LANG_GROUP;
        $statusLangGroup = SystemStatusController::LANG_GROUP;
        $user = getLoggedFrameworkUser();
        $userType = $user !== null ? (int) $user->type : null;
        $own = fn(string $suffix, string $name, string $icon, ?string $keywords = null): array => [
            'name' => $name,
            'icon' => $icon,
            'href' => (string) self::routeName($suffix, [], true),
            'visible' => self::allowedRoute($suffix),
        ] + ($keywords !== null ? ['keywords' => $keywords] : []);
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
                $own('appearance-brand-images', __($langGroup, 'Imágenes de marca'), 'image', __($langGroup, 'logo, logos, favicon, icono, marca')),
                $own('appearance-backgrounds', __($langGroup, 'Fondos del inicio de sesión'), 'images'),
                //Colores y Accesibilidad son una pantalla; appearance-accessibility sigue como ruta (es un permiso) y redirige.
                $own('appearance-colors', __($langGroup, 'Estilo del panel'), 'fill drip', __($langGroup, 'colores, accesibilidad, fuente, tipografía, contraste')),
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
     * Estilo del panel: Colores, Tipografía y forma, y Accesibilidad en pestañas, con la muestra en claro y en oscuro. Los
     * dos formularios guardan por sus rutas de siempre; Accesibilidad solo sale con el permiso de appearance-accessibility.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function configurationsView(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        $accessibilityVisible = self::allowedRoute('appearance-accessibility');

        set_title(__($langGroup, 'Estilo del panel'));

        set_custom_assets(array_merge([
            //statisticsChartThemeColors(): colors.js mide con él el acento de oscuro que calcula el tema.
            'statics/core/js/statistics-chart-legend.js',
            SettingsRoutes::staticRoute('js/theme-preview.js'),
            SettingsRoutes::staticRoute('js/style-panel.js'),
            SettingsRoutes::staticRoute('js/colors.js'),
        ], $accessibilityVisible ? [SettingsRoutes::staticRoute('js/accessibility.js')] : []), 'js');
        set_custom_assets([
            SettingsRoutes::staticRoute('css/theme-preview.css'),
        ], 'css');

        $tokens = self::themeTokens();
        $seriesColors = [];
        foreach (self::DATA_TOKENS as $token => $option) {
            $seriesColors[$option] = ['value' => $tokens[$token] ?? self::FACTORY_COLORS[$option], 'factory' => self::FACTORY_COLORS[$option]];
        }
        $browserBar = mb_strtoupper((string) get_config('meta_theme_color')) === self::META_THEME_DARK ? self::BROWSER_BAR_DARK : self::BROWSER_BAR_ACCENT;

        //Lo guardado del formulario de colores, por campo: «Restablecer» de una pestaña lo reenvía con lo de fábrica de ESA pestaña encima.
        $colorsSaved = [
            'main_brand_color' => $tokens['accent'],
            'main_brand_color_dark' => self::accentDarkColor(),
            'second_brand_color' => $tokens['accent-2'] ?? self::FACTORY_COLORS['second_brand_color'],
            'third_brand_color' => $tokens['accent-3'] ?? self::FACTORY_COLORS['third_brand_color'],
            'browser_bar' => $browserBar,
            'theme_font' => self::themeFont(),
            'theme_font_size' => self::themeFontSize(),
            'theme_radius' => self::themeRadius(),
        ];
        foreach ($seriesColors as $option => $series) {
            $colorsSaved[$option] = $series['value'];
        }
        //Las series de fábrica se guardan vacías (manda el tema), igual que el oscuro automático; la barra de fábrica es el acento.
        $colorsFactory = [
            'main_brand_color' => self::FACTORY_COLORS['main_brand_color'],
            'main_brand_color_dark' => self::FACTORY_COLORS['main_brand_color_dark'],
            'second_brand_color' => self::FACTORY_COLORS['second_brand_color'],
            'third_brand_color' => self::FACTORY_COLORS['third_brand_color'],
            'browser_bar' => self::BROWSER_BAR_ACCENT,
        ] + array_fill_keys(array_values(self::DATA_TOKENS), '');
        $typographyFactory = [
            'theme_font' => self::THEME_FONT_FACTORY,
            'theme_font_size' => self::THEME_FONT_SIZE_FACTORY,
            'theme_radius' => self::THEME_RADIUS_FACTORY,
        ];

        $data = [
            'langGroup' => $langGroup,
            'actionURL' => self::routeName('appearance-colors-save'),
            'accentColor' => $tokens['accent'],
            'accentDarkColor' => $colorsSaved['main_brand_color_dark'],
            'secondColor' => $colorsSaved['second_brand_color'],
            'thirdColor' => $colorsSaved['third_brand_color'],
            'seriesColors' => $seriesColors,
            'browserBar' => $browserBar,
            'themeFont' => $colorsSaved['theme_font'],
            'themeFontSize' => $colorsSaved['theme_font_size'],
            'themeRadius' => $colorsSaved['theme_radius'],
            'resetColors' => $colorsFactory + $colorsSaved,
            'resetTypography' => $typographyFactory + $colorsSaved,
            'accessibilityVisible' => $accessibilityVisible,
            'accessibilityActionURL' => self::routeName('appearance-accessibility-save'),
            'textSize' => self::accessibilityTextSize(),
            'themeMode' => self::accessibilityThemeMode(),
            'contrast' => self::accessibilityContrast(),
            'motion' => self::accessibilityMotion(),
            'focus' => self::accessibilityFocus(),
            'textSpacing' => self::accessibilityTextSpacing(),
            'transparency' => self::accessibilityTransparency(),
            'resetAccessibility' => self::ACCESSIBILITY_FACTORY,
        ] + $this->sectionHeader(__($langGroup, 'Estilo del panel'), __($langGroup, 'Cómo se ve el panel: el acento y la paleta de las gráficas, la letra y la forma, y la accesibilidad de quien no ha elegido la suya.'));

        $this->render('panel/layout/header');
        $this->settingsViews->render('panel/pages/app_configurations/style-panel', $data);
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
            new Parameter(
                'main_brand_color_dark',
                '',
                fn ($value): bool => self::isBrandColorValue('main_brand_color_dark', $value),
                true,
                fn (string $value): string => mb_strtoupper($value)
            ),
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
                'main_brand_color_dark' => $expectedParameters->getValue('main_brand_color_dark'),
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
                SettingsModel::forgetCache();
                $resultOperation->setMessage(__($langGroup, 'Ha ocurrido un error desconocido, intente más tarde.'));
            }

        } catch (MissingRequiredParameterException|InvalidParameterValueException|ParsedValueException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (\Exception $e) {

            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
                SettingsModel::forgetCache();
            }
            $resultOperation->setMessage(CustomSlimErrorHandler::genericMessage(log_exception($e)));

        }

        return $res->withJson($resultOperation);
    }

    /**
     * La ruta de la antigua pantalla Accesibilidad: es un permiso y no se borra. Lleva a Estilo del panel con la pestaña
     * Accesibilidad abierta (el fragmento lo lee style-panel.js; el servidor no lo ve).
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function accessibilityView(Request $req, Response $res)
    {
        return self::redirectToPanelStyle($req, $res, '#accessibility');
    }

    /**
     * La dirección vieja de Estilo del panel (appearance/colors): en desuso, se retira en la 11.0.0 (H422). El fragmento
     * que traiga lo conserva el navegador en el 302.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function panelStyleLegacyView(Request $req, Response $res)
    {
        return self::redirectToPanelStyle($req, $res);
    }

    /**
     * Lleva a Estilo del panel; sin permiso de appearance-colors responde 403 en vez de redirigir a sí misma.
     *
     * @param Request $req
     * @param Response $res
     * @param string $fragment
     * @return Response
     */
    private static function redirectToPanelStyle(Request $req, Response $res, string $fragment = ''): Response
    {
        $url = self::routeName('appearance-colors');
        if ($url === '') {
            return throw403($req);
        }
        return $res->withRedirect($url . $fragment);
    }

    /**
     * Guarda la pantalla Accesibilidad de una vez: un valor fuera de su lista no cambia ninguna de las opciones.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function saveAccessibilityAction(Request $req, Response $res)
    {
        $langGroup = self::LANG_GROUP;
        $inList = fn (array $allowed): callable => fn ($value): bool => is_string($value) && in_array($value, $allowed, true);

        $expectedParameters = new Parameters([
            new Parameter(self::ACCESSIBILITY_TEXT_SIZE_CONFIG, null, $inList(AdminPanelController::TEXT_SIZES)),
            new Parameter(self::ACCESSIBILITY_THEME_MODE_CONFIG, null, $inList(AdminPanelController::THEME_MODES)),
            new Parameter(self::ACCESSIBILITY_CONTRAST_CONFIG, null, $inList(AdminPanelController::CONTRASTS)),
            new Parameter(self::ACCESSIBILITY_MOTION_CONFIG, null, $inList(AdminPanelController::MOTIONS)),
            new Parameter(self::ACCESSIBILITY_FOCUS_CONFIG, null, $inList(AdminPanelController::FOCUSES)),
            new Parameter(self::ACCESSIBILITY_TEXT_SPACING_CONFIG, null, $inList(AdminPanelController::TEXT_SPACINGS)),
            new Parameter(self::ACCESSIBILITY_TRANSPARENCY_CONFIG, null, $inList(AdminPanelController::TRANSPARENCIES)),
        ]);

        $inputData = $req->getParsedBody();
        $expectedParameters->setInputValues(is_array($inputData) ? $inputData : []);

        $resultOperation = new ResultOperations([], __($langGroup, 'Accesibilidad'));
        $resultOperation->setSingleOperation(true);
        $resultOperation->setSuccessOnSingleOperation(false);
        $pdo = null;

        try {

            $expectedParameters->validate();

            $options = [
                self::ACCESSIBILITY_TEXT_SIZE_CONFIG => $expectedParameters->getValue(self::ACCESSIBILITY_TEXT_SIZE_CONFIG),
                self::ACCESSIBILITY_THEME_MODE_CONFIG => $expectedParameters->getValue(self::ACCESSIBILITY_THEME_MODE_CONFIG),
                self::ACCESSIBILITY_CONTRAST_CONFIG => $expectedParameters->getValue(self::ACCESSIBILITY_CONTRAST_CONFIG),
                self::ACCESSIBILITY_MOTION_CONFIG => $expectedParameters->getValue(self::ACCESSIBILITY_MOTION_CONFIG),
                self::ACCESSIBILITY_FOCUS_CONFIG => $expectedParameters->getValue(self::ACCESSIBILITY_FOCUS_CONFIG),
                self::ACCESSIBILITY_TEXT_SPACING_CONFIG => $expectedParameters->getValue(self::ACCESSIBILITY_TEXT_SPACING_CONFIG),
                self::ACCESSIBILITY_TRANSPARENCY_CONFIG => $expectedParameters->getValue(self::ACCESSIBILITY_TRANSPARENCY_CONFIG),
            ];

            //Todas juntas: una que falle no deja las otras cambiadas.
            $pdo = SettingsModel::model()->getDatabase();
            //Null solo sin conexión, que el arranque ya exige: aquí sería un fallo de verdad, y el catch lo registra.
            if ($pdo === null) {
                throw new \Exception('Sin conexión a la base de datos al guardar la accesibilidad.');
            }
            $pdo->beginTransaction();
            $success = true;
            foreach ($options as $name => $value) {
                $success = $success && SettingsModel::setConfigValue($name, $value);
            }
            if ($success) {
                $pdo->commit();
                $resultOperation->setMessage(__($langGroup, 'Datos guardados.'));
                $resultOperation->setSuccessOnSingleOperation(true);
            } else {
                $pdo->rollBack();
                SettingsModel::forgetCache();
                $resultOperation->setMessage(__($langGroup, 'Ha ocurrido un error desconocido, intente más tarde.'));
            }

        } catch (MissingRequiredParameterException|InvalidParameterValueException|ParsedValueException $e) {

            $resultOperation->setMessage($e->getMessage());

        } catch (\Exception $e) {

            if ($pdo !== null && $pdo->inTransaction()) {
                $pdo->rollBack();
                SettingsModel::forgetCache();
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
            $value = self::isBrandColorValue($option, $value) ? $value : self::FACTORY_COLORS[$option];
            //--accent siempre: hojas y JS lo leen sin reserva. Los otros dos, solo si cambian.
            if ($token === 'accent' || mb_strtoupper($value) !== self::FACTORY_COLORS[$option]) {
                $tokens[$token] = $value;
            }
        }
        $accentDark = self::accentDarkColor();
        //Vacío no se publica nunca: el oscuro lo calcula el tema del acento, también el de fábrica.
        if ($accentDark !== '') {
            $tokens['accent-dark'] = $accentDark;
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
            $tokens['font-size-site'] = "{$fontSize}px";
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
     * El acento de modo oscuro guardado, o '' si es automático o no vale.
     *
     * @return string
     */
    public static function accentDarkColor(): string
    {
        $value = get_config('main_brand_color_dark');
        return is_string($value) && $value !== '' && self::isBrandColorValue('main_brand_color_dark', $value) ? $value : '';
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
     * Ya no hay fuentes con hoja propia: todas las trae el tema.
     *
     * @deprecated 11.0.0 Siempre null; se conserva para el código de los clones que la llame.
     * @return string|null
     */
    public static function themeFontStylesheet(): ?string
    {
        return null;
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
     * @return string El tamaño de letra del sitio, de AdminPanelController::TEXT_SIZES; si lo guardado no vale, normal
     */
    public static function accessibilityTextSize(): string
    {
        $size = get_config(self::ACCESSIBILITY_TEXT_SIZE_CONFIG);
        return is_string($size) && in_array($size, AdminPanelController::TEXT_SIZES, true) ? $size : self::ACCESSIBILITY_FACTORY[self::ACCESSIBILITY_TEXT_SIZE_CONFIG];
    }

    /**
     * @return string El modo de color del sitio, de AdminPanelController::THEME_MODES; si lo guardado no vale, auto
     */
    public static function accessibilityThemeMode(): string
    {
        $mode = get_config(self::ACCESSIBILITY_THEME_MODE_CONFIG);
        return is_string($mode) && in_array($mode, AdminPanelController::THEME_MODES, true) ? $mode : self::ACCESSIBILITY_FACTORY[self::ACCESSIBILITY_THEME_MODE_CONFIG];
    }

    /**
     * @return string El contraste del sitio, de AdminPanelController::CONTRASTS; si lo guardado no vale, auto
     */
    public static function accessibilityContrast(): string
    {
        $value = get_config(self::ACCESSIBILITY_CONTRAST_CONFIG);
        return is_string($value) && in_array($value, AdminPanelController::CONTRASTS, true) ? $value : self::ACCESSIBILITY_FACTORY[self::ACCESSIBILITY_CONTRAST_CONFIG];
    }

    /**
     * @return string El movimiento del sitio, de AdminPanelController::MOTIONS; si lo guardado no vale, auto
     */
    public static function accessibilityMotion(): string
    {
        $value = get_config(self::ACCESSIBILITY_MOTION_CONFIG);
        return is_string($value) && in_array($value, AdminPanelController::MOTIONS, true) ? $value : self::ACCESSIBILITY_FACTORY[self::ACCESSIBILITY_MOTION_CONFIG];
    }

    /**
     * @return string El foco del sitio, de AdminPanelController::FOCUSES; si lo guardado no vale, normal
     */
    public static function accessibilityFocus(): string
    {
        $value = get_config(self::ACCESSIBILITY_FOCUS_CONFIG);
        return is_string($value) && in_array($value, AdminPanelController::FOCUSES, true) ? $value : self::ACCESSIBILITY_FACTORY[self::ACCESSIBILITY_FOCUS_CONFIG];
    }

    /**
     * @return string El espaciado del texto del sitio, de AdminPanelController::TEXT_SPACINGS; si lo guardado no vale, normal
     */
    public static function accessibilityTextSpacing(): string
    {
        $value = get_config(self::ACCESSIBILITY_TEXT_SPACING_CONFIG);
        return is_string($value) && in_array($value, AdminPanelController::TEXT_SPACINGS, true) ? $value : self::ACCESSIBILITY_FACTORY[self::ACCESSIBILITY_TEXT_SPACING_CONFIG];
    }

    /**
     * @return string Las transparencias del sitio, de AdminPanelController::TRANSPARENCIES; si lo guardado no vale, auto
     */
    public static function accessibilityTransparency(): string
    {
        $value = get_config(self::ACCESSIBILITY_TRANSPARENCY_CONFIG);
        return is_string($value) && in_array($value, AdminPanelController::TRANSPARENCIES, true) ? $value : self::ACCESSIBILITY_FACTORY[self::ACCESSIBILITY_TRANSPARENCY_CONFIG];
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

            //Las miniaturas del geovisor de los puntos que ya no existen o no se ven; con Geo apagado, nada (H381).
            $geoThumbnails = GeoRoutes::ENABLE ? GeoThumbnail::sweep() : ['deleted' => [], 'failed' => 0];
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

                            //La copia del idioma va a las subidas: en statics/images pisaría una versionada (open_graph_en.jpg).
                            $newConfig->value = $defaultValue;
                            if (is_string($defaultValue) && file_exists(basepath($defaultValue))) {

                                $relativePath = self::copyIntoSettingsUploads(basepath($defaultValue), 'open-graph', self::seoOptionName('open_graph', $lang));

                                if ($relativePath !== null) {
                                    $newConfig->value = $relativePath;
                                    $newConfig->save();
                                } else {
                                    //Un fallo mudo AQUÍ es el peor: sin fila, el siguiente render
                                    //reintenta la copia para siempre y nadie se entera nunca.
                                    log_exception(new \Exception(
                                        'Activación de idiomas: no se pudo copiar la imagen Open Graph de '
                                            . basepath($defaultValue) . ' a ' . self::SETTINGS_UPLOADS_FOLDER
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

                        if (is_string($defaultValue) && is_string($valueLang) && file_exists(basepath($defaultValue)) && !file_exists(basepath($valueLang))) {
                            //Solo se repone dentro de las subidas; fuera (una ruta vieja o de fábrica) no se escribe.
                            if (!self::isSettingsUploadPath($valueLang) || !@copy(basepath($defaultValue), basepath($valueLang))) {
                                log_exception(new \Exception(
                                    'Activación de idiomas: no se pudo reponer la imagen Open Graph en '
                                        . $valueLang . '. La fila apunta a un archivo que no existe.'
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
        $value = self::brandImagePath($configName);
        $key = "{$configName}\0{$value}";
        if (!array_key_exists($key, self::$factoryBrandImageCache)) {
            $file = $value !== '' ? basepath($value) : '';
            self::$factoryBrandImageCache[$key] = array_key_exists($configName, self::FACTORY_BRAND_IMAGES) && $file !== '' && is_file($file)
                && in_array(sha1_file($file), (array) self::FACTORY_BRAND_IMAGES[$configName], true);
        }
        return self::$factoryBrandImageCache[$key];
    }

    /**
     * Si la opción recién guardada es la imagen oscura de una imagen de marca, guarda su modo `image`: subir una oscura
     * es elegir «Otra imagen». Cualquier otra opción no toca nada.
     *
     * @param string $optionName
     * @return bool Si quedó guardado (true también si no había nada que guardar)
     */
    public static function saveDarkModeImageFor(string $optionName): bool
    {
        foreach (self::BRAND_IMAGES_DARK_MODE as $options) {
            if ($options['image'] === $optionName) {
                return get_config($options['mode']) === self::BRAND_IMAGE_DARK_IMAGE || SettingsModel::setConfigValue($options['mode'], self::BRAND_IMAGE_DARK_IMAGE);
            }
        }
        return true;
    }

    /**
     * Vacía la caché de isFactoryBrandImage(): la llama quien guarda una imagen de marca en la misma petición.
     *
     * @return void
     */
    public static function clearBrandImageCache(): void
    {
        self::$factoryBrandImageCache = [];
    }

    /**
     * Si esa imagen se pinta en blanco en modo oscuro: true solo con el modo EFECTIVO `invert`
     * (brandImageEffectiveDarkMode()), es decir, con el modo `invert` y, además, con el logo de fábrica en modo `image`
     * cuando su oscura de fábrica falta en disco. Con el modo `image` y una oscura que existe es false aunque la opción
     * vieja `*InvertOnDark` diga 1. Para pintarla, brandImageTag().
     *
     * @param string $configName Una clave de BRAND_IMAGES_DARK_MODE
     * @return bool
     */
    public static function brandImageInvertsOnDark(string $configName): bool
    {
        return self::brandImageEffectiveDarkMode($configName) === self::BRAND_IMAGE_DARK_INVERT;
    }

    /**
     * El modo que se PINTA: el elegido (brandImageDarkMode()) salvo con `image` sin oscura que exista, que se pinta como
     * `same`; o como `invert` si la clara es la de fábrica y está en FACTORY_BRAND_IMAGES_DARK_IS_INVERTED: un clon que
     * borró white-logo.png sigue viendo el logo invertido.
     *
     * @param string $configName Una clave de BRAND_IMAGES_DARK_MODE
     * @return string
     */
    public static function brandImageEffectiveDarkMode(string $configName): string
    {
        return self::brandImageDarkView($configName)['mode'];
    }

    /**
     * Lo que enseña esa imagen en modo oscuro, resuelto en UN solo sitio: lo pintan brandImageTag(), el cargador
     * (loaderHtmlAttributes()) y la vista previa oscura de Imágenes de marca, con su «Descargar». `mode`, el que se pinta
     * (brandImageEffectiveDarkMode()); `image`, lo que se ve: la oscura (la guardada; si no, la de fábrica) con `image`,
     * y la clara con `same` o `invert` (esta, con el filtro). Vale para cualquier clave de BRAND_IMAGES_DARK_MODE.
     *
     * @param string $configName
     * @return array{mode:string,image:string}
     */
    public static function brandImageDarkView(string $configName): array
    {
        $mode = self::brandImageDarkMode($configName);
        $dark = $mode === self::BRAND_IMAGE_DARK_IMAGE ? self::brandImageDarkImage($configName) : '';
        if ($mode === self::BRAND_IMAGE_DARK_IMAGE && $dark === '') {
            $mode = self::brandImageDarkFallbackMode($configName);
        }
        return ['mode' => $mode, 'image' => $dark !== '' ? $dark : self::brandImagePath($configName)];
    }

    /**
     * Cómo se pinta `image` sin oscura que exista: `invert` si la clara es la de fábrica y su oscura de fábrica es la clara
     * invertida (FACTORY_BRAND_IMAGES_DARK_IS_INVERTED: un clon que borró white-logo.png); si no, `same`.
     *
     * @param string $configName
     * @return string
     */
    public static function brandImageDarkFallbackMode(string $configName): string
    {
        return in_array($configName, self::FACTORY_BRAND_IMAGES_DARK_IS_INVERTED, true) && self::isFactoryBrandImage($configName) ? self::BRAND_IMAGE_DARK_INVERT : self::BRAND_IMAGE_DARK_SAME;
    }

    /**
     * Para el marcador viejo de una sola imagen (`${logoClass}` de una plantilla de «problemas de acceso» de un clon):
     * si en modo oscuro esa imagen se pinta con el filtro de inversión. Con el modo efectivo `invert`, y también con `image`
     * cuando la oscura es la de fábrica: el marcador no admite dos imágenes y la inversión es lo más parecido a white-logo.png.
     *
     * @param string $configName Una clave de BRAND_IMAGES_DARK_MODE
     * @return bool
     */
    public static function brandImageLooksInvertedOnDark(string $configName): bool
    {
        $mode = self::brandImageEffectiveDarkMode($configName);
        if ($mode === self::BRAND_IMAGE_DARK_INVERT) {
            return true;
        }
        return $mode === self::BRAND_IMAGE_DARK_IMAGE && in_array($configName, self::FACTORY_BRAND_IMAGES_DARK_IS_INVERTED, true)
            && self::brandImageDarkImage($configName) === (self::FACTORY_BRAND_IMAGES_DARK[$configName] ?? null);
    }

    /**
     * El modo oscuro de esa imagen, uno de BRAND_IMAGE_DARK_MODES. Por orden: el guardado; la clara de fábrica con
     * oscura de fábrica (FACTORY_BRAND_IMAGES_DARK), `image`, aunque haya una `*InvertOnDark` guardada; la opción vieja
     * `*InvertOnDark` (1, `invert`; 0, `same`); sin ella, `invert` solo la de fábrica. Es el ELEGIDO; el que se pinta
     * lo da brandImageEffectiveDarkMode().
     *
     * @param string $configName Una clave de BRAND_IMAGES_DARK_MODE; otra da `same`
     * @return string
     */
    public static function brandImageDarkMode(string $configName): string
    {
        if (!array_key_exists($configName, self::BRAND_IMAGES_DARK_MODE)) {
            return self::BRAND_IMAGE_DARK_SAME;
        }
        $stored = get_config(self::BRAND_IMAGES_DARK_MODE[$configName]['mode']);
        if (is_string($stored) && in_array($stored, self::BRAND_IMAGE_DARK_MODES, true)) {
            return $stored;
        }
        if (array_key_exists($configName, self::FACTORY_BRAND_IMAGES_DARK) && self::isFactoryBrandImage($configName)) {
            return self::BRAND_IMAGE_DARK_IMAGE;
        }
        return self::brandImageDarkModeWithoutSaved($configName);
    }

    /**
     * El modo sin modo guardado y sin oscura de fábrica: el de la opción vieja `*InvertOnDark` si existe (1 o 0); sin
     * ella, `invert` solo la de fábrica. Con `$factory` false, el que toma una imagen propia recién subida.
     *
     * @param string $configName Una clave de BRAND_IMAGES_DARK_MODE
     * @param bool|null $factory Si la clara es la de fábrica; null, la de hoy (isFactoryBrandImage())
     * @return string
     */
    public static function brandImageDarkModeWithoutSaved(string $configName, ?bool $factory = null): string
    {
        $option = self::BRAND_IMAGES_INVERT_ON_DARK[$configName] ?? null;
        if ($option === null) {
            return self::BRAND_IMAGE_DARK_SAME;
        }
        $value = get_config($option);
        $inverts = $value === false ? ($factory ?? self::isFactoryBrandImage($configName)) : in_array($value, [1, '1'], true);
        return $inverts ? self::BRAND_IMAGE_DARK_INVERT : self::BRAND_IMAGE_DARK_SAME;
    }

    /**
     * La imagen oscura de esa imagen de marca, guardada o, con la clara de fábrica, la de fábrica; vacía si no hay o
     * si no existe en disco: nunca una imagen rota. No mira el modo.
     *
     * @param string $configName Una clave de BRAND_IMAGES_DARK_MODE
     * @return string La ruta relativa, o vacía
     */
    public static function brandImageDarkImage(string $configName): string
    {
        if (!array_key_exists($configName, self::BRAND_IMAGES_DARK_MODE)) {
            return '';
        }
        $stored = get_config(self::BRAND_IMAGES_DARK_MODE[$configName]['image']);
        $path = is_string($stored) ? trim($stored) : '';
        if ($path === '' && array_key_exists($configName, self::FACTORY_BRAND_IMAGES_DARK) && self::isFactoryBrandImage($configName)) {
            $path = self::FACTORY_BRAND_IMAGES_DARK[$configName];
        }
        return $path !== '' && is_file(basepath($path)) ? $path : '';
    }

    /**
     * La ruta de una imagen de marca: su opción si es un texto no vacío; si no, la de fábrica de
     * FACTORY_BRAND_IMAGE_DEFAULTS (la animación de carga de una instalación anterior), o vacía. Una de
     * BRAND_IMAGES_EMPTY_IS_OFF guardada vacía da vacía: está apagada. La de fábrica de FACTORY_DEFAULT_ONLY_WITH_FACTORY
     * solo con la otra también de fábrica: la pantalla de móvil de fábrica no acompaña a una de escritorio propia.
     *
     * @param string $configName
     * @return string
     */
    public static function brandImagePath(string $configName): string
    {
        $value = get_config($configName);
        $path = is_string($value) ? trim($value) : '';
        if ($path === '' && is_string($value) && in_array($configName, self::EMPTY_IS_OFF_UNTIL_MIGRATED, true)) {
            return '';
        }
        if ($path !== '') {
            return $path;
        }
        //Sin esto, una pantalla de escritorio propia sale en vertical con la de móvil de fábrica, con la marca de PiecesPHP.
        $companion = self::FACTORY_DEFAULT_ONLY_WITH_FACTORY[$configName] ?? null;
        if ($companion !== null && !self::isFactoryBrandImage($companion)) {
            return '';
        }
        return self::FACTORY_BRAND_IMAGE_DEFAULTS[$configName] ?? '';
    }

    /**
     * La medida real de una imagen de las subidas o de fábrica, para pintar su vista previa con su proporción: la de un
     * SVG sale de su `width` y `height` o, sin ellos, de su `viewBox`; la de un GIF, PNG, JPG o WEBP, de getimagesize().
     *
     * @param string $relativePath
     * @return array{width:int,height:int}|null null si no se puede leer, o si la medida no es finita, no es positiva o pasa de LOADER_ANIMATION_MAX_SIDE
     */
    public static function imageFileSize(string $relativePath): ?array
    {
        $file = $relativePath !== '' ? basepath($relativePath) : '';
        $bytes = $file !== '' && is_file($file) ? filesize($file) : false;
        if ($bytes === false || $bytes === 0) {
            return null;
        }
        //Una medida que no es finita, no es positiva o pasa del tope no se pinta: la vista usa la de reserva.
        $size = function (float $width, float $height): ?array {
            $valid = fn (float $side): bool => is_finite($side) && $side > 0 && $side <= self::LOADER_ANIMATION_MAX_SIDE;
            return $valid($width) && $valid($height) ? ['width' => max(1, (int) round($width)), 'height' => max(1, (int) round($height))] : null;
        };
        if (mb_strtolower(pathinfo($file, \PATHINFO_EXTENSION)) === 'svg') {
            $content = (string) file_get_contents($file);
            if (trim($content) === '') {
                return null;
            }
            $previous = libxml_use_internal_errors(true);
            $document = new \DOMDocument();
            $loaded = $document->loadXML($content, \LIBXML_NONET);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $root = $loaded ? $document->documentElement : null;
            if (!($root instanceof \DOMElement)) {
                return null;
            }
            //Solo longitudes absolutas (un número, o en px): `100%` o `10em` no dicen la medida y se cae al viewBox.
            $length = fn (string $value): float => preg_match('/^\s*([0-9]*\.?[0-9]+(?:e[+-]?[0-9]+)?)\s*(?:px)?\s*$/i', $value, $match) === 1 ? (float) $match[1] : 0.0;
            $fromAttributes = $size($length($root->getAttribute('width')), $length($root->getAttribute('height')));
            if ($fromAttributes !== null) {
                return $fromAttributes;
            }
            $viewBox = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox')));
            return is_array($viewBox) && count($viewBox) === 4 ? $size($length($viewBox[2]), $length($viewBox[3])) : null;
        }
        $information = finfo_open(\FILEINFO_MIME_TYPE);
        $mime = $information !== false ? finfo_file($information, $file) : false;
        if (!is_string($mime) || !str_starts_with($mime, 'image/')) {
            return null;
        }
        $dimensions = getimagesize($file);
        return is_array($dimensions) ? $size((float) $dimensions[0], (float) $dimensions[1]) : null;
    }

    /**
     * Las imágenes del cargador (GenericLoaderElement.js) como atributos del `<html>`, ya escapados y con un espacio
     * delante: `data-loader-animation` y `data-loader-logo` (la clara; el logo es el «Logo del cargador», `loaderLogo`:
     * de fábrica, loader-logo.png; vacío solo si el «Eliminar» de antes lo guardó vacío, y entonces el cargador no pinta logo), con su `-dark` (la oscura, vacía si el modo no es
     * `image` o no hay una que exista) y su `-invert` (`1` con el modo `invert`, `0` si no), de brandImageDarkView(). Los
     * ponen AdminPanelController::htmlPreferenceAttributes() y las dos cabeceras públicas; sin ellos, el cargador cae a
     * sus rutas fijas. Si algo falla (la página de error puede pintarse con la base caída), no pone ninguno.
     *
     * @return string
     */
    public static function loaderHtmlAttributes(): string
    {
        $attributes = '';
        foreach (self::loaderAttributeValues() as $name => $value) {
            $attributes .= ' ' . $name . '="' . escape_html($value) . '"';
        }
        return $attributes;
    }

    /**
     * Los valores de loaderHtmlAttributes(), sin escapar: también los de las pantallas de carga (H417),
     * `data-loader-screen` y `data-loader-screen-mobile` con su `-dark` y su `-invert` (de fábrica, las SVG animadas de
     * statics/images/; vacías si se guardaron vacías, y la de móvil también sin guardar con una de escritorio propia: vacía, el cargador usa la de escritorio). Una animación guardada vacía da `data-loader-animation=""`:
     * apagada. Después, `data-loader-<capa>-enabled` (`1` o `0`) de cada interruptor de LOADER_LAYERS_ENABLED: con `0`, el
     * cargador no pinta ni descarga esa capa, aunque su imagen siga guardada. Vacío si algo falla.
     *
     * @return array<string,string>
     */
    public static function loaderAttributeValues(): array
    {
        try {
            $values = [];
            foreach (['animation' => 'loaderAnimation', 'logo' => 'loaderLogo', 'screen' => 'loaderScreen', 'screen-mobile' => 'loaderScreenMobile'] as $suffix => $image) {
                $view = self::brandImageDarkView($image);
                $light = self::brandImagePath($image);
                $values["data-loader-{$suffix}"] = $light;
                //Sin clara no hay oscura: una oscura huérfana no enciende la capa.
                $values["data-loader-{$suffix}-dark"] = $light !== '' && $view['mode'] === self::BRAND_IMAGE_DARK_IMAGE ? $view['image'] : '';
                $values["data-loader-{$suffix}-invert"] = $light !== '' && $view['mode'] === self::BRAND_IMAGE_DARK_INVERT ? '1' : '0';
            }
            foreach (array_keys(self::LOADER_LAYERS_ENABLED) as $layer) {
                $values["data-loader-{$layer}-enabled"] = self::loaderLayerEnabled($layer) ? '1' : '0';
            }
        } catch (\Throwable $e) {
            return [];
        }
        return $values;
    }

    /**
     * Si una capa del cargador está encendida: sin opción, sí (encendidas de fábrica); solo un '0' guardado la apaga. La
     * opción se escribe como el texto 0 y la base la devuelve como el entero 0: valen los dos.
     *
     * @param string $layer Una clave de LOADER_LAYERS_ENABLED
     * @return bool
     */
    public static function loaderLayerEnabled(string $layer): bool
    {
        if (!array_key_exists($layer, self::LOADER_LAYERS_ENABLED)) {
            return true;
        }
        $value = get_config(self::LOADER_LAYERS_ENABLED[$layer]);
        return !($value === 0 || $value === '0');
    }

    /**
     * Si el cargador tiene una pantalla de carga que enseñar: su interruptor encendido y la de escritorio o la de móvil
     * (subida o de fábrica) en disco. El área de pruebas solo ofrece «Con la pantalla de carga» con esto.
     *
     * @return bool
     */
    public static function loaderScreenAvailable(): bool
    {
        if (!self::loaderLayerEnabled('screen')) {
            return false;
        }
        foreach (['loaderScreen', 'loaderScreenMobile'] as $screen) {
            $path = self::brandImagePath($screen);
            if ($path !== '' && is_file(basepath($path))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Los textos de «Eliminar» de cada imagen de BRAND_IMAGES_EMPTY_IS_OFF, ya traducidos: el título de la respuesta, la
     * confirmación y el aviso de hecho.
     *
     * @return array<string,array{title:string,confirm:string,deleted:string}>
     * @deprecated 11.0.0 Nadie la llama: las imágenes de marca ya no se eliminan (los interruptores de LOADER_LAYERS_ENABLED).
     */
    public static function brandImageDeleteTexts(): array
    {
        return [
            'loaderAnimation' => [
                'title' => __(self::LANG_GROUP, 'Animación de carga'),
                'confirm' => __(self::LANG_GROUP, '¿Quitar la animación de carga? El cargador no enseñará animación; sin logo ni pantalla de carga, el aro.'),
                'deleted' => __(self::LANG_GROUP, 'Animación de carga quitada.'),
            ],
            'loaderLogo' => [
                'title' => __(self::LANG_GROUP, 'Logo del cargador'),
                'confirm' => __(self::LANG_GROUP, '¿Quitar el logo del cargador? El cargador enseñará solo la animación.'),
                'deleted' => __(self::LANG_GROUP, 'Logo del cargador quitado.'),
            ],
            'loaderScreen' => [
                'title' => __(self::LANG_GROUP, 'Pantalla de carga'),
                'confirm' => __(self::LANG_GROUP, '¿Quitar la pantalla de carga? El cargador volverá a su fondo liso.'),
                'deleted' => __(self::LANG_GROUP, 'Pantalla de carga quitada.'),
            ],
            'loaderScreenMobile' => [
                'title' => __(self::LANG_GROUP, 'Pantalla de carga para móvil'),
                'confirm' => __(self::LANG_GROUP, '¿Quitar la pantalla de carga para móvil? En vertical se usará la de escritorio, recortada.'),
                'deleted' => __(self::LANG_GROUP, 'Pantalla de carga para móvil quitada.'),
            ],
        ];
    }

    /**
     * Los atributos del `<html>` de una muestra, ya escapados y con un espacio delante: los de Accesibilidad del SITIO (no
     * las preferencias de quien edita) y las imágenes del cargador, sin `data-theme`: cada muestra fija el suyo.
     *
     * @return string
     */
    public static function themePreviewAttributes(): string
    {
        $attributes = '';
        foreach (['data-text-size' => self::accessibilityTextSize(), 'data-contrast' => self::accessibilityContrast(), 'data-motion' => self::accessibilityMotion(), 'data-focus' => self::accessibilityFocus(), 'data-text-spacing' => self::accessibilityTextSpacing(), 'data-transparency' => self::accessibilityTransparency()] as $name => $value) {
            $attributes .= " {$name}=\"" . escape_html($value) . '"';
        }
        return $attributes . self::loaderHtmlAttributes();
    }

    /**
     * El documento de una muestra en un `<iframe srcdoc>` («Estilo del panel» y la vista previa de Imágenes de marca): el
     * tema no tiene modo por contenedor, así que cada modo es un documento entero, con `data-theme` fijo y las hojas de la
     * página que lo pinta (load_font() y load_css(), leídas una vez) o las de `$head`.
     *
     * @param string $theme `light` o `dark`; otro valor, `light`
     * @param string $body El cuerpo, ya escapado
     * @param string|null $head Las hojas; null, las de la página
     * @param string|null $htmlAttributes Los del `<html>`, ya escapados y con un espacio delante; null, themePreviewAttributes()
     * @return string
     */
    public static function themePreviewDocument(string $theme, string $body, ?string $head = null, ?string $htmlAttributes = null): string
    {
        $theme = $theme === 'dark' ? 'dark' : 'light';
        if ($head === null) {
            if (self::$themePreviewHead === null) {
                ob_start();
                load_font();
                load_css([
                    'base_url' => '',
                    'custom_url' => '',
                ]);
                self::$themePreviewHead = (string) ob_get_clean();
            }
            $head = self::$themePreviewHead;
        }
        return '<!DOCTYPE html><html lang="' . escape_html((string) get_config('app_lang')) . '" data-theme="' . $theme . '"' . ($htmlAttributes ?? self::themePreviewAttributes()) . '>'
            . '<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><base href="' . escape_html(baseurl()) . '">' . $head . '</head>'
            . '<body>' . $body . '</body></html>';
    }

    /**
     * Las hojas de la página del acceso (UsersController::loginForm()), para su muestra en Imágenes de marca: se piden con
     * los recursos de la página vacíos y se reponen después, como MailCatalog::preview(), porque la página que la pinta
     * sigue cargando los suyos.
     *
     * @return string
     */
    public static function accessPreviewHead(): string
    {
        $saved = [];
        foreach (\PiecesPHP\MailCatalog\MailCatalog::PAGE_STATE_CONFIG as $name) {
            $saved[$name] = get_config($name);
        }
        try {
            clear_global_assets();
            clear_assets_imports();
            set_custom_assets([], 'js');
            set_custom_assets([], 'font');
            import_fomantic();
            import_app_libraries(['baseStyle']);
            set_custom_assets([
                \PiecesPHP\UserSystem\UserSystemFeaturesRoutes::staticRoute('css/login.css'),
            ], 'css');
            ob_start();
            load_css([
                'base_url' => '',
                'custom_url' => '',
            ]);
            return (string) ob_get_clean();
        } finally {
            foreach ($saved as $name => $value) {
                set_config($name, $value);
            }
        }
    }

    /**
     * Si el usuario actual puede subir una animación de carga SVG (ROLES_SVG_BRAND_IMAGES); sin usuario, no.
     *
     * @return bool
     */
    public static function canUploadSvgBrandImages(): bool
    {
        $user = getLoggedFrameworkUser();
        return $user !== null && in_array((int) $user->type, self::ROLES_SVG_BRAND_IMAGES, true);
    }

    /**
     * El motivo de una subida que pasó de un tope, con el tope en MB: PHP la entrega sin archivo, y «Información faltante
     * o inesperada» no ayuda a nadie. UPLOAD_ERR_INI_SIZE dice upload_max_filesize; UPLOAD_ERR_FORM_SIZE, el MAX_FILE_SIZE
     * que mandó el formulario (si no lo mandó, sin cifra).
     *
     * @param mixed $error El `error` de la entrada de $_FILES
     * @return string|null El mensaje, ya traducido; null si el error es otro o el servidor no tiene tope
     */
    public static function uploadSizeErrorMessage(mixed $error): ?string
    {
        if ($error === \UPLOAD_ERR_FORM_SIZE) {
            $formLimit = $_POST['MAX_FILE_SIZE'] ?? null;
            //PHP compara MAX_FILE_SIZE en bytes; un valor que no sea un entero positivo no da cifra.
            if (is_string($formLimit) && ctype_digit($formLimit) && (int) $formLimit > 0) {
                return strReplaceTemplate(__(self::LANG_GROUP, 'El archivo pesa más de lo que admite el formulario (${size} MB).'), ['${size}' => self::megabytesText((int) $formLimit)]);
            }
            return __(self::LANG_GROUP, 'El archivo pesa más de lo que admite el formulario.');
        }
        if ($error !== \UPLOAD_ERR_INI_SIZE) {
            return null;
        }
        $bytes = FileValidator::iniSizeToBytes((string) ini_get('upload_max_filesize'));
        if ($bytes === null) {
            return null;
        }
        return strReplaceTemplate(__(self::LANG_GROUP, 'El archivo pesa más de lo que admite el servidor (${size} MB).'), ['${size}' => self::megabytesText($bytes)]);
    }

    /**
     * El motivo de una petición que pasó de post_max_size: PHP deja $_FILES y $_POST vacíos sin ningún error que leer, y
     * solo CONTENT_LENGTH lo delata.
     *
     * @return string|null El mensaje con post_max_size en MB, ya traducido; null si la petición no pasó del tope
     */
    public static function postSizeErrorMessage(): ?string
    {
        $contentLength = $_SERVER['CONTENT_LENGTH'] ?? null;
        $limit = FileValidator::iniSizeToBytes((string) ini_get('post_max_size'));
        if ($_FILES !== [] || $_POST !== [] || !is_numeric($contentLength) || $limit === null || $limit <= 0 || (int) $contentLength <= $limit) {
            return null;
        }
        return strReplaceTemplate(__(self::LANG_GROUP, 'Lo enviado pesa más de lo que admite el servidor (${size} MB).'), ['${size}' => self::megabytesText($limit)]);
    }

    /**
     * Bytes en MB para un texto: sin decimales si son enteros; si no, uno, con el separador decimal del idioma (la
     * traducción de «,», como LoginAttemptsController::formatDecimal(): «0,5» en español, «0.5» en inglés).
     *
     * @param int $bytes
     * @return string
     */
    public static function megabytesText(int $bytes): string
    {
        $text = rtrim(rtrim(number_format($bytes / (1024 * 1024), 1, '.', ''), '0'), '.');
        return str_replace('.', __(self::LANG_GROUP, ','), $text);
    }

    /**
     * Guarda la animación de carga subida (o su oscura): tal cual, sin recortar ni recomprimir, en
     * SETTINGS_UPLOADS_FOLDER/brand/. El tipo sale del CONTENIDO, nunca del nombre ni del tipo que mande el cliente: un
     * GIF por su firma y su MIME, o un SVG que pase SvgWhitelistValidator entero y solo de ROLES_SVG_BRAND_IMAGES. El nombre lo pone el servidor.
     *
     * @param string $field El campo de $_FILES
     * @param string $name El de la opción en el archivo (loader-animation, loader-animation-dark)
     * @return array{path:?string,error:string} La ruta relativa, o null con el motivo ya traducido
     * @deprecated Nadie la llama: la ruta usa storeDirectBrandUpload() con BRAND_IMAGES_DIRECT_UPLOAD. Se retira en la 11.0.0.
     */
    public static function storeLoaderAnimationUpload(string $field, string $name): array
    {
        return self::storeDirectBrandUpload($field, $name, false);
    }

    /**
     * Guarda una imagen de marca que se sube tal cual: la animación de carga, GIF o SVG,
     * hasta LOADER_ANIMATION_MAX_BYTES; o, con `$screen`, una pantalla de carga (H417), que además admite PNG, JPG y WebP,
     * hasta LOADER_SCREEN_MAX_BYTES. Las fijas no se recomprimen: GD descomprimiría el lienzo entero, y su lado se mide
     * en la cabecera (getimagesizefromstring()), sin decodificar.
     *
     * @param string $field El campo de $_FILES
     * @param string $name El de la opción en el archivo (BRAND_IMAGES_DIRECT_UPLOAD)
     * @param bool $screen Si es una pantalla de carga
     * @return array{path:?string,error:string} La ruta relativa, o null con el motivo ya traducido
     */
    public static function storeDirectBrandUpload(string $field, string $name, bool $screen): array
    {
        $upload = $_FILES[$field] ?? null;
        $temporary = is_array($upload) && is_string($upload['tmp_name'] ?? null) ? $upload['tmp_name'] : '';
        $fail = fn (string $message): array => ['path' => null, 'error' => $message];
        if (!is_array($upload) || ($upload['error'] ?? null) !== \UPLOAD_ERR_OK || $temporary === '' || !is_uploaded_file($temporary)) {
            return $fail(self::uploadSizeErrorMessage(is_array($upload) ? ($upload['error'] ?? null) : null) ?? __(self::LANG_GROUP, 'Información faltante o inesperada.'));
        }
        $size = filesize($temporary);
        if ($size === false || $size > ($screen ? self::LOADER_SCREEN_MAX_BYTES : self::LOADER_ANIMATION_MAX_BYTES)) {
            return $fail($screen ? __(self::LANG_GROUP, 'La pantalla de carga pesa más de 3 MB.') : __(self::LANG_GROUP, 'La animación pesa más de 2 MB.'));
        }
        $tooLarge = $screen ? __(self::LANG_GROUP, 'La pantalla de carga no puede pasar de 4096 × 4096 píxeles.') : __(self::LANG_GROUP, 'La animación no puede pasar de 4096 × 4096 píxeles.');
        $content = (string) file_get_contents($temporary);
        $information = finfo_open(\FILEINFO_MIME_TYPE);
        $mime = $information !== false ? finfo_buffer($information, $content) : false;
        //Las fijas, por su firma Y su MIME: el nombre y el tipo que manda el cliente no cuentan.
        $rasterExtension = match (true) {
            str_starts_with($content, "\x89PNG\r\n\x1A\n") && $mime === 'image/png' => 'png',
            str_starts_with($content, "\xFF\xD8\xFF") && $mime === 'image/jpeg' => 'jpg',
            str_starts_with($content, 'RIFF') && substr($content, 8, 4) === 'WEBP' && $mime === 'image/webp' => 'webp',
            default => null,
        };
        if ((str_starts_with($content, 'GIF87a') || str_starts_with($content, 'GIF89a')) && $mime === 'image/gif') {
            //El lienzo y CADA fotograma, recorriendo sus bloques: un Image Descriptor enorme en un lienzo pequeño también.
            $gifReason = GifStructureValidator::rejectionReason($content, self::LOADER_ANIMATION_MAX_SIDE);
            if ($gifReason === GifStructureValidator::REASON_TOO_LARGE || $gifReason === GifStructureValidator::REASON_FRAME_OUTSIDE) {
                return $fail($tooLarge);
            }
            if ($gifReason !== null) {
                return $fail(__(self::LANG_GROUP, 'El GIF está incompleto o mal formado.'));
            }
            $extension = 'gif';
        } elseif ($screen && $rasterExtension !== null) {
            $dimensions = getimagesizefromstring($content);
            if (!is_array($dimensions) || $dimensions[0] < 1 || $dimensions[1] < 1) {
                return $fail(__(self::LANG_GROUP, 'La imagen está incompleta o mal formada.'));
            }
            if ($dimensions[0] > self::LOADER_ANIMATION_MAX_SIDE || $dimensions[1] > self::LOADER_ANIMATION_MAX_SIDE) {
                return $fail($tooLarge);
            }
            $extension = $rasterExtension;
        } elseif (SvgWhitelistValidator::isAllowed($content)) {
            if (!self::canUploadSvgBrandImages()) {
                return $fail($screen ? __(self::LANG_GROUP, 'Solo el usuario principal puede subir una pantalla de carga SVG; sube un GIF, PNG, JPG o WebP.') : __(self::LANG_GROUP, 'Solo el usuario principal puede subir una animación SVG; sube un GIF.'));
            }
            $extension = 'svg';
        } elseif (preg_match('/<svg[\s>]/i', $content) === 1) {
            return $fail(__(self::LANG_GROUP, 'El SVG trae algo que no está permitido (código, enlaces o recursos externos): se rechaza entero.'));
        } else {
            return $fail($screen ? __(self::LANG_GROUP, 'La pantalla de carga tiene que ser un GIF, PNG, JPG, WebP o SVG.') : __(self::LANG_GROUP, 'La animación tiene que ser un GIF o un SVG.'));
        }
        $folder = basepath(self::SETTINGS_UPLOADS_FOLDER . '/brand');
        if (!is_dir($folder) && !self::makeGroupWritableFolder($folder)) {
            return $fail(__(self::LANG_GROUP, 'Ha ocurrido un error inesperado.'));
        }
        //Se guarda sin metadatos: su URL va en las cabeceras públicas, y un EXIF con GPS lo publicaría.
        $stripped = ImageMetadataStripper::strip($content, $extension);
        if ($stripped === null) {
            return $fail(__(self::LANG_GROUP, 'La imagen está incompleta o mal formada.'));
        }
        $file = "{$folder}/{$name}-upload-" . bin2hex(random_bytes(6)) . ".{$extension}";
        if (file_put_contents($file, $stripped, \LOCK_EX) !== strlen($stripped)) {
            //RETORNO-IGNORADO: el archivo a medias no se queda; si no existe, no hay nada que borrar.
            @unlink($file);
            return $fail(__(self::LANG_GROUP, 'Ha ocurrido un error inesperado.'));
        }
        $relativePath = self::settleSettingsImage($file, 'brand', $name);
        return $relativePath !== null ? ['path' => $relativePath, 'error' => ''] : $fail(__(self::LANG_GROUP, 'Ha ocurrido un error inesperado.'));
    }

    /**
     * El único punto que pinta una imagen de marca (H340), con lo que enseña en modo oscuro: `same`, un `<img>`;
     * `invert`, el mismo con `invert-on-dark`; `image`, dos, la clara con `brand-image-light` y la oscura con
     * `brand-image-dark`, y ui-pcs.scss enseña una según `data-theme`. Con `image` sin oscura, lo que diga
     * brandImageEffectiveDarkMode() (`same`, o `invert` con el logo de fábrica sin su oscura de fábrica).
     *
     * @param string $configName Una clave de BRAND_IMAGES_DARK_MODE
     * @param string $classes Las clases del `<img>`, sin las del modo oscuro
     * @param string $alt El mismo en las dos: solo una se ve
     * @return string El HTML, escapado
     */
    public static function brandImageTag(string $configName, string $classes = '', string $alt = ''): string
    {
        $tag = fn (string $src, string $extra): string => '<img class="' . escape_html(trim("{$classes} {$extra}")) . '" src="' . escape_html($src) . '" alt="' . escape_html($alt) . '">';
        $light = self::brandImagePath($configName);
        $view = self::brandImageDarkView($configName);
        if ($view['mode'] === self::BRAND_IMAGE_DARK_IMAGE) {
            return $tag($light, 'brand-image-light') . $tag($view['image'], 'brand-image-dark');
        }
        return $tag($light, $view['mode'] === self::BRAND_IMAGE_DARK_INVERT ? 'invert-on-dark' : '');
    }

    /**
     * Si el logo del desarrollador (`developer_logo`, de config.php) se pinta en blanco en modo oscuro:
     * `developer_logo_invert_on_dark` a 1 o 0 manda; sin ponerla, o a false (get_config() no distingue false de
     * ausente), solo el logo de fábrica. Es la regla de UNA imagen: con su oscura (developerLogoDarkImage()),
     * developerLogoTag() pinta las dos y no invierte.
     *
     * @return bool
     */
    public static function developerLogoInvertsOnDark(): bool
    {
        $value = get_config('developer_logo_invert_on_dark');
        if (in_array($value, [1, '1'], true)) {
            return true;
        }
        if (in_array($value, [0, '0'], true)) {
            return false;
        }
        return self::isFactoryDeveloperLogo();
    }

    /**
     * Si el logo del desarrollador (`developer_logo`) es todavía uno de fábrica, por su sha1 (FACTORY_DEVELOPER_LOGO_SHA1),
     * con la caché de isFactoryBrandImage().
     *
     * @return bool
     */
    public static function isFactoryDeveloperLogo(): bool
    {
        $value = trim((string) get_config('developer_logo'));
        $key = "developer_logo\0{$value}";
        if (!array_key_exists($key, self::$factoryBrandImageCache)) {
            $file = $value !== '' ? basepath($value) : '';
            self::$factoryBrandImageCache[$key] = $file !== '' && is_file($file) && in_array(sha1_file($file), self::FACTORY_DEVELOPER_LOGO_SHA1, true);
        }
        return self::$factoryBrandImageCache[$key];
    }

    /**
     * La imagen oscura del logo del desarrollador: FACTORY_DEVELOPER_LOGO_DARK con el logo de fábrica, sin
     * `developer_logo_invert_on_dark` a 1 o 0 y con ella en disco; si no, vacía.
     *
     * @return string
     */
    public static function developerLogoDarkImage(): string
    {
        if (in_array(get_config('developer_logo_invert_on_dark'), [1, '1', 0, '0'], true) || !self::isFactoryDeveloperLogo()) {
            return '';
        }
        return is_file(basepath(self::FACTORY_DEVELOPER_LOGO_DARK)) ? self::FACTORY_DEVELOPER_LOGO_DARK : '';
    }

    /**
     * El único punto que pinta el logo del desarrollador, como brandImageTag(): con su oscura (developerLogoDarkImage()),
     * dos `<img>`, `brand-image-light` y `brand-image-dark`; si no, uno, con `invert-on-dark` si developerLogoInvertsOnDark().
     *
     * @param string $classes Las clases del `<img>`, sin las del modo oscuro
     * @param string $title Su `title`; vacío, sin él
     * @return string El HTML, escapado; vacío sin `developer_logo`
     */
    public static function developerLogoTag(string $classes = '', string $title = ''): string
    {
        $logo = trim((string) get_config('developer_logo'));
        if ($logo === '') {
            return '';
        }
        $titleAttribute = $title !== '' ? ' title="' . escape_html($title) . '"' : '';
        $tag = fn (string $src, string $extra): string => '<img class="' . escape_html(trim("{$classes} {$extra}")) . '" src="' . escape_html($src) . '" alt=""' . $titleAttribute . '>';
        $dark = self::developerLogoDarkImage();
        if ($dark !== '') {
            return $tag($logo, 'brand-image-light') . $tag($dark, 'brand-image-dark');
        }
        return $tag($logo, self::developerLogoInvertsOnDark() ? 'invert-on-dark' : '');
    }

    /**
     * El texto sobre el patrocinador en un idioma: el suyo si existe; si no, el del idioma por omisión, guardado también
     * vacío (quien lo borró lo quiso quitar); y si tampoco existe, el de fábrica, traducido al idioma actual. El del idioma
     * por omisión que es el de fábrica en cualquier idioma (isFactoryPartnersText()) sale traducido al actual: nadie lo escribió.
     *
     * @param string|null $lang Por omisión, el de la petición
     * @return string
     */
    public static function partnersText(?string $lang = null): string
    {
        $lang ??= Config::get_lang();
        if ($lang !== Config::get_default_lang()) {
            $own = get_config(self::partnersTextOptionName($lang));
            if (is_string($own) && trim($own) !== '') {
                return trim($own);
            }
        }
        $text = get_config(self::PARTNERS_TEXT_CONFIG);
        if (!is_string($text)) {
            return __(LANG_GROUP, 'Con el apoyo de');
        }
        return self::isFactoryPartnersText($text) ? __(LANG_GROUP, 'Con el apoyo de') : trim($text);
    }

    /**
     * Si un texto sobre el patrocinador es el de fábrica en alguno de los idiomas del sitio (tras recortarlo).
     *
     * @param string $text
     * @return bool
     */
    private static function isFactoryPartnersText(string $text): bool
    {
        $text = trim($text);
        if ($text === 'Con el apoyo de') {
            return true;
        }
        foreach (Config::get_allowed_langs() as $lang) {
            if ($text === trim(lang(LANG_GROUP, 'Con el apoyo de', $lang))) {
                return true;
            }
        }
        return false;
    }

    /**
     * El nombre de la opción del texto sobre el patrocinador en un idioma, como seoOptionName(): sin sufijo el del idioma
     * por omisión.
     *
     * @param string $lang
     * @return string
     */
    public static function partnersTextOptionName(string $lang): string
    {
        return self::seoOptionName(self::PARTNERS_TEXT_CONFIG, $lang);
    }

    /**
     * Lo que muestra el campo de un idioma: en el idioma por omisión, el texto de hoy (también el de fábrica); en los
     * demás, solo el suyo, y vacío si no tiene (entonces se usa el del idioma por omisión).
     *
     * @param string $lang
     * @return string
     */
    private static function storedPartnersText(string $lang): string
    {
        if ($lang === Config::get_default_lang()) {
            return self::partnersText($lang);
        }
        $own = get_config(self::partnersTextOptionName($lang));
        return is_string($own) ? trim($own) : '';
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
            return $name === 'main_brand_color_dark' || in_array($name, self::DATA_TOKENS, true);
        }
        return preg_match('/^#[0-9A-Fa-f]{6}\z/', $value) === 1;
    }

    /**
     * El JSON de ai_center a partir de los campos del formulario (o de un JSON ya armado: es idempotente). Parte de lo
     * guardado: conserva el periodAnchor de cada capacidad y las entradas de las que ya no están registradas. Un toggle que
     * no llega es false.
     *
     * @param mixed $input ['enabled' => …, 'capabilities' => ['<key>' => ['enabled', 'userTypes', 'quota', 'period', 'periodLength']]]
     * @param mixed $saved Lo guardado (get_config)
     * @return array<string,mixed>
     * @throws InvalidParameterValueException si algún campo no vale, con el motivo traducido
     */
    public static function aiCenterFromInput($input, $saved): array
    {
        $invalid = function (string $capability, string $field) {
            throw new InvalidParameterValueException(strReplaceTemplate(__(self::LANG_GROUP, 'Inteligencia artificial: el campo «%field%» de «%capability%» no es válido.'), [
                '%field%' => $field,
                '%capability%' => $capability,
            ]));
        };
        if (!is_array($input)) {
            throw new InvalidParameterValueException(__(self::LANG_GROUP, 'Inteligencia artificial: la configuración enviada no es válida.'));
        }
        $truthy = fn ($value): bool => $value === true || $value === 1 || $value === '1' || $value === 'on';

        $center = AIGateway::normalizeCenter($saved);
        $center = is_array($center) ? $center : [];
        $center['enabled'] = $truthy($input['enabled'] ?? false);
        $capabilities = is_array($center['capabilities'] ?? null) ? $center['capabilities'] : [];
        $sent = is_array($input['capabilities'] ?? null) ? $input['capabilities'] : [];
        $known = UserTypes::codes();

        foreach (AICapabilityRegistry::all() as $key => $capability) {
            $fields = is_array($sent[$key] ?? null) ? $sent[$key] : [];
            $name = $capability->name();
            $entry = is_array($capabilities[$key] ?? null) ? $capabilities[$key] : [];

            $entry['enabled'] = $truthy($fields['enabled'] ?? false);

            $types = $fields['userTypes'] ?? [];
            if (!is_array($types)) {
                $invalid($name, __(self::LANG_GROUP, 'Quién la usa'));
            }
            $userTypes = [];
            foreach ($types as $type) {
                if ($type === '' || $type === null) {
                    continue;
                }
                if (!Validator::isInteger($type) || !in_array((int) $type, $known, true)) {
                    $invalid($name, __(self::LANG_GROUP, 'Quién la usa'));
                }
                $userTypes[] = (int) $type;
            }
            $entry['userTypes'] = array_values(array_unique($userTypes));

            $quota = $fields['quota'] ?? 0;
            if (!(is_int($quota) || (is_string($quota) && preg_match('/^\d+$/', trim($quota)) === 1)) || (int) $quota < 0) {
                $invalid($name, __(self::LANG_GROUP, 'Cuota (tokens)'));
            }
            $entry['quota'] = (int) $quota;

            $period = $fields['period'] ?? AICapability::PERIOD_ACCUMULATED;
            if (!is_string($period) || !in_array($period, AICapability::PERIODS, true)) {
                $invalid($name, __(self::LANG_GROUP, 'Periodo'));
            }
            $entry['period'] = $period;

            $length = $fields['periodLength'] ?? 1;
            $length = $length === '' ? 1 : $length;
            if (!(is_int($length) || (is_string($length) && preg_match('/^\d+$/', trim($length)) === 1)) || (int) $length < 1) {
                $invalid($name, 'N');
            }
            $entry['periodLength'] = (int) $length;

            //La posición de la ventana del asistente (ADR 0075): solo la suya, y solo si el formulario la manda.
            if ($key === Assistant::CAPABILITY && array_key_exists('position', $fields)) {
                if (!is_string($fields['position']) || !in_array($fields['position'], Assistant::POSITIONS, true)) {
                    $invalid($name, __(self::LANG_GROUP, 'Posición de la ventana'));
                }
                $entry['position'] = $fields['position'];
            }
            //Los minutos sin uso tras los que el navegador olvida la conversación (H329): vacío, el de omisión.
            if ($key === Assistant::CAPABILITY && array_key_exists('forgetAfterMinutes', $fields)) {
                $forget = $fields['forgetAfterMinutes'];
                $forget = is_string($forget) ? trim($forget) : $forget;
                if ($forget === '' || $forget === null) {
                    $forget = Assistant::FORGET_AFTER_MINUTES_DEFAULT;
                }
                if (!(is_int($forget) || (is_string($forget) && preg_match('/^\d+$/', $forget) === 1)) || (int) $forget < Assistant::FORGET_AFTER_MINUTES_MIN || (int) $forget > Assistant::FORGET_AFTER_MINUTES_MAX) {
                    $invalid($name, __(self::LANG_GROUP, 'Olvidar la conversación tras (minutos)'));
                }
                $entry['forgetAfterMinutes'] = (int) $forget;
            }

            //El proveedor y el modelo de la función (ADR 0077); un id heredado (EN DESUSO) se guarda como su convertido.
            if (array_key_exists('provider', $fields)) {
                $provider = is_string($fields['provider']) ? AIProviderRegistry::resolveId(trim($fields['provider'])) : null;
                $model = is_string($fields['model'] ?? null) ? trim($fields['model']) : '';
                if ($provider === null) {
                    $invalid($name, __(self::LANG_GROUP, 'Proveedor'));
                }
                if ($provider === '') {
                    unset($entry['provider'], $entry['model']);
                } else {
                    $entry['provider'] = $provider;
                    //'__other__' es la opción «Otro…» del desplegable: llegar así es que no se escribió el modelo.
                    if ($model === '' || $model === '__other__' || mb_strlen($model) > AIGateway::MODEL_ID_MAX_LENGTH) {
                        $invalid($name, __(self::LANG_GROUP, 'Modelo'));
                    }
                    $entry['model'] = $model;
                }
            }

            $capabilities[$key] = $entry;
        }

        $center['capabilities'] = $capabilities;

        //Los registros de proveedor: solo si el formulario los mandó (providers_sent); si no, se conservan los guardados. La
        //marca de la conversión solo la pone el formulario de esta versión (converted_sent): uno de antes guarda sin convertir.
        $pending = AIProviderRegistry::isConverted() ? [] : AIProviderRegistry::convertedPending();
        if ($truthy($input['providers_sent'] ?? false)) {
            $center['providers'] = self::aiProvidersFromInput(is_array($input['providers'] ?? null) ? $input['providers'] : [], is_array($center['providers'] ?? null) ? $center['providers'] : []);
            if ($truthy($input['converted_sent'] ?? false)) {
                $center[AIProviderRegistry::CONVERTED_FLAG] = true;
                $pending = [];
            }
        }
        $providers = is_array($center['providers'] ?? null) ? $center['providers'] : [];
        $byId = [];
        foreach ($providers as $entry) {
            $byId[(string) $entry['id']] = $entry;
        }

        //Cada función con proveedor: que exista (guardado o, sin guardar la conversión, convertido), que sepa lo que pide y,
        //si es guardado, su modelo entra en las sugerencias del registro. Y no se quita un registro que una función usa.
        foreach ($capabilities as $key => $entry) {
            $chosen = is_array($entry) && is_string($entry['provider'] ?? null) ? $entry['provider'] : null;
            if ($chosen === null) {
                continue;
            }
            $capability = AICapabilityRegistry::get((string) $key);
            $name = $capability !== null ? $capability->name() : (string) $key;
            $chosen = AIProviderRegistry::resolveId($chosen);
            if (!isset($byId[$chosen]) && !isset($pending[$chosen])) {
                throw new InvalidParameterValueException(strReplaceTemplate(__(self::LANG_GROUP, '«%capability%» lo usa: elige otro proveedor para «%capability%» antes de quitarlo.'), ['%capability%' => $name]));
            }
            $record = isset($byId[$chosen]) ? new AIProviderRecord($chosen, (string) $byId[$chosen]['type'], (string) $byId[$chosen]['alias'], '', $byId[$chosen]['baseUrl'] ?? null, $byId[$chosen]['keyHeader'] ?? null) : $pending[$chosen]['record'];
            $fits = $capability !== null && $capability->providerType() === AICapability::PROVIDER_SPEECH
                ? $record->supports(AIProviderCatalog::CAPABILITY_SPEECH)
                : $record->supports(AIProviderCatalog::CAPABILITY_CHAT) && !($capability !== null && $capability->usesTools() && !$record->supports(AIProviderCatalog::CAPABILITY_TOOLS));
            if (!$fits) {
                $invalid($name, __(self::LANG_GROUP, 'Proveedor'));
            }
            if (isset($byId[$chosen]) && is_string($entry['model'] ?? null)) {
                $models = is_array($byId[$chosen]['models'] ?? null) ? $byId[$chosen]['models'] : [];
                if (!in_array($entry['model'], $models, true)) {
                    $models[] = $entry['model'];
                    $byId[$chosen]['models'] = array_slice($models, -self::AI_PROVIDER_MODELS_REMEMBERED);
                }
            }
        }
        if ($byId !== []) {
            $center['providers'] = array_values($byId);
        }
        return $center;
    }

    /**
     * Los registros de proveedor tal como se guardan en ai_center.providers (SIN clave), a partir de las filas del
     * formulario. Cada fila trae su id (uno guardado o, sin la conversión escrita, uno convertido) o el _newId que puso el
     * servidor (aiAssignProviderIds()): el cliente nunca elige uno. Lo que no vale lanza con el campo y el registro.
     *
     * @param array<mixed> $rows
     * @param array<mixed> $saved Los guardados
     * @return list<array<string,mixed>>
     * @throws InvalidParameterValueException
     */
    public static function aiProvidersFromInput(array $rows, array $saved): array
    {
        $savedById = [];
        foreach ($saved as $entry) {
            if (is_array($entry) && is_string($entry['id'] ?? null)) {
                $savedById[$entry['id']] = $entry;
            }
        }
        $converted = AIProviderRegistry::isConverted() ? [] : array_map(fn(array $pending): array => $pending['entry'], AIProviderRegistry::convertedPending());
        $aliases = [];
        $providers = [];
        $position = 0;
        foreach ($rows as $row) {
            $position++;
            $row = is_array($row) ? $row : [];
            $alias = is_string($row['alias'] ?? null) ? trim($row['alias']) : '';
            $label = $alias !== '' ? $alias : strReplaceTemplate(__(self::LANG_GROUP, 'nuevo %n%'), ['%n%' => (string) $position]);
            $invalid = function (string $field) use ($label) {
                throw new InvalidParameterValueException(strReplaceTemplate(__(self::LANG_GROUP, 'Inteligencia artificial: el campo «%field%» del proveedor «%provider%» no es válido.'), ['%field%' => $field, '%provider%' => $label]));
            };
            $sentId = is_string($row['id'] ?? null) ? trim($row['id']) : '';
            if ($sentId !== '' && !isset($savedById[$sentId]) && !isset($converted[$sentId])) {
                $invalid('id');
            }
            $id = $sentId !== '' ? $sentId : (is_string($row['_newId'] ?? null) ? $row['_newId'] : '');
            if ($id === '' || isset($providers[$id])) {
                $invalid('id');
            }
            $type = is_string($row['type'] ?? null) ? $row['type'] : '';
            if (AIProviderCatalog::get($type) === null) {
                $invalid(__(self::LANG_GROUP, 'Tipo'));
            }
            if ($alias === '' || in_array(mb_strtolower($alias), $aliases, true)) {
                $invalid(__(self::LANG_GROUP, 'Sobrenombre'));
            }
            $isCustom = $type === AIProviderCatalog::TYPE_CUSTOM;
            $baseUrl = $isCustom && is_string($row['baseUrl'] ?? null) ? trim($row['baseUrl']) : null;
            $keyHeader = $isCustom && is_string($row['keyHeader'] ?? null) && trim($row['keyHeader']) !== '' ? trim($row['keyHeader']) : null;
            if ($isCustom && ($baseUrl === null || !AIProviderCatalog::isValidCustomBaseUrl($baseUrl))) {
                $invalid(__(self::LANG_GROUP, 'Dirección base'));
            }
            if ($keyHeader !== null && preg_match(AIProviderRecord::KEY_HEADER_PATTERN, $keyHeader) !== 1) {
                $invalid(__(self::LANG_GROUP, 'Cabecera de la clave'));
            }
            $quota = $row['quota'] ?? 0;
            $quota = $quota === '' ? 0 : $quota;
            if (!(is_int($quota) || (is_string($quota) && preg_match('/^\d+$/', trim($quota)) === 1))) {
                $invalid(__(self::LANG_GROUP, 'Cuota (tokens)'));
            }
            $period = $row['period'] ?? AICapability::PERIOD_ACCUMULATED;
            if (!is_string($period) || !in_array($period, AICapability::PERIODS, true)) {
                $invalid(__(self::LANG_GROUP, 'Periodo'));
            }
            $length = $row['periodLength'] ?? 1;
            $length = $length === '' ? 1 : $length;
            if (!(is_int($length) || (is_string($length) && preg_match('/^\d+$/', trim($length)) === 1)) || (int) $length < 1) {
                $invalid('N');
            }
            $keySource = is_string($row['keySource'] ?? null) && $row['keySource'] !== '' ? $row['keySource'] : AIProviderRecord::KEY_SOURCE_STORED;
            if (!in_array($keySource, AIProviderRecord::KEY_SOURCES, true)) {
                $invalid(__(self::LANG_GROUP, 'Origen de la clave'));
            }
            $keyFile = null;
            if ($keySource === AIProviderRecord::KEY_SOURCE_FILE) {
                $keyFile = is_string($row['keyFile'] ?? null) ? trim($row['keyFile']) : '';
                if (!AIProviderRecord::isValidKeyFile($keyFile, $type)) {
                    $invalid(__(self::LANG_GROUP, 'Nombre en el archivo'));
                }
            }
            $previous = $savedById[$id] ?? $converted[$id] ?? [];
            $entry = [
                'id' => $id,
                'type' => $type,
                'alias' => $alias,
                'baseUrl' => $baseUrl,
                'keyHeader' => $keyHeader,
                'models' => is_array($previous['models'] ?? null) ? array_values(array_filter($previous['models'], 'is_string')) : [],
                'quota' => (int) $quota,
                'period' => $period,
                'periodLength' => (int) $length,
                'keySource' => $keySource,
                'keyFile' => $keyFile,
            ];
            if (is_string($previous['periodAnchor'] ?? null)) {
                $entry['periodAnchor'] = $previous['periodAnchor'];
            }
            //Los precios, solo en un registro GUARDADO (ADR 0077 §8): si la fila los manda, los suyos; si no, los que tenía.
            $prices = isset($savedById[$id]) && is_array($previous['prices'] ?? null) ? $previous['prices'] : [];
            if (isset($savedById[$id]) && ($row['prices_sent'] ?? null) === '1') {
                $prices = self::aiPricesFromInput($row['prices'] ?? [], fn() => $invalid(__(self::LANG_GROUP, 'Precios')));
            }
            if ($prices !== []) {
                $entry['prices'] = $prices;
            }
            try {
                new AIProviderRecord($id, $type, $alias, '', $baseUrl, $keyHeader, $entry['models'], null, $keySource, $keyFile);
            } catch (\InvalidArgumentException $e) {
                $invalid(__(self::LANG_GROUP, 'Tipo'));
            }
            $providers[$id] = $entry;
            $aliases[] = mb_strtolower($alias);
        }
        return array_values($providers);
    }

    /**
     * Los precios de un registro (ADR 0077 §8): por modelo, USD por millón de tokens de entrada y de salida. Una fila sin
     * modelo ni precios, o con el modelo y los dos precios vacíos, no cuenta; un precio vacío vale 0. Cada precio, ≥ 0, con
     * hasta 4 decimales y como mucho MAX_AI_PRICE; el modelo, como el de una función, y sin repetirse.
     *
     * @param mixed $rows
     * @param callable():never $invalid
     * @return array<string,array{input:float,output:float}>
     */
    public static function aiPricesFromInput($rows, callable $invalid): array
    {
        $prices = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $row = is_array($row) ? $row : [];
            $text = fn($value): ?string => is_string($value) ? trim($value) : (is_int($value) || is_float($value) ? (string) $value : null);
            $model = $text($row['model'] ?? '');
            $input = $text($row['input'] ?? '');
            $output = $text($row['output'] ?? '');
            if ($model === null || $input === null || $output === null) {
                $invalid();
            }
            if ($input === '' && $output === '') {
                continue;
            }
            if ($model === '' || $model === '__other__' || mb_strlen($model) > AIGateway::MODEL_ID_MAX_LENGTH || isset($prices[$model])) {
                $invalid();
            }
            $amounts = [];
            foreach (['input' => $input, 'output' => $output] as $field => $amount) {
                if ($amount === '') {
                    $amounts[$field] = 0.0;
                    continue;
                }
                if (preg_match('/^\d{1,5}(?:\.\d{1,4})?$/', $amount) !== 1 || (float) $amount > self::MAX_AI_PRICE) {
                    $invalid();
                }
                $amounts[$field] = (float) $amount;
            }
            $prices[$model] = ['input' => $amounts['input'], 'output' => $amounts['output']];
        }
        return $prices;
    }

    /**
     * Los registros de proveedor guardados, tal cual (sin clave), por id: lo que pinta Configuración › IA en cada fila.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function aiProviderEntries(): array
    {
        $center = AIGateway::normalizeCenter(get_config(AIGateway::CONFIG_NAME));
        $entries = [];
        foreach (is_array($center) && is_array($center['providers'] ?? null) ? $center['providers'] : [] as $entry) {
            if (is_array($entry) && is_string($entry['id'] ?? null)) {
                $entries[$entry['id']] = $entry;
            }
        }
        return $entries;
    }

    /**
     * Pone un _newId (AIProviderRegistry::newId()) en cada fila de proveedor sin id, y quita el que mande el cliente: así
     * validar y procesar ven el mismo id, y lo elige el servidor.
     *
     * @param mixed $input El cuerpo de la petición
     * @return mixed
     */
    public static function aiAssignProviderIds($input)
    {
        if (!is_array($input) || !is_array($input[AIGateway::CONFIG_NAME]['providers'] ?? null)) {
            return $input;
        }
        foreach ($input[AIGateway::CONFIG_NAME]['providers'] as $index => $row) {
            if (!is_array($row)) {
                continue;
            }
            unset($row['_newId']);
            if (!is_string($row['id'] ?? null) || trim($row['id']) === '') {
                $row['_newId'] = AIProviderRegistry::newId();
            }
            $input[AIGateway::CONFIG_NAME]['providers'][$index] = $row;
        }
        return $input;
    }

    /**
     * Las claves de los registros tras el guardado: la escrita, o la guardada si llega vacía; «Quitar la guardada» la
     * borra; la de un registro que ya no está, o cuya clave sale del archivo, fuera. Y la guardada de un registro que cambia
     * de destino (aiProviderDestinationChanged()) también sale, salvo que llegue una nueva.
     *
     * @param mixed $input El cuerpo de la petición, ya con los _newId
     * @param array<mixed> $providers Los registros guardados en ai_center
     * @param array<string,string> $savedKeys
     * @param array<string,array<string,mixed>> $previous Las entradas de antes de este guardado, por id (guardadas o convertidas)
     * @return array<string,string>
     */
    public static function aiProviderKeysFromInput($input, array $providers, array $savedKeys, array $previous = []): array
    {
        $rows = is_array($input) && is_array($input[AIGateway::CONFIG_NAME]['providers'] ?? null) ? $input[AIGateway::CONFIG_NAME]['providers'] : [];
        $sent = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = is_string($row['id'] ?? null) && trim($row['id']) !== '' ? trim($row['id']) : (is_string($row['_newId'] ?? null) ? $row['_newId'] : '');
            $sent[$id] = $row;
        }
        $keys = [];
        foreach ($providers as $entry) {
            if (!is_array($entry) || !is_string($entry['id'] ?? null)) {
                continue;
            }
            $id = $entry['id'];
            if (($entry['keySource'] ?? AIProviderRecord::KEY_SOURCE_STORED) === AIProviderRecord::KEY_SOURCE_FILE) {
                continue;
            }
            $row = $sent[$id] ?? [];
            $written = is_string($row['key'] ?? null) ? trim($row['key']) : '';
            $clear = in_array($row['clear_key'] ?? null, [true, 1, '1', 'on'], true);
            //Otro tipo, dirección o cabecera: la clave escrita no viaja a otro destino sin que alguien la escriba de nuevo.
            $moved = isset($previous[$id]) && self::aiProviderDestinationChanged($previous[$id], $entry);
            if ($written !== '') {
                $keys[$id] = $written;
            } elseif (!$clear && !$moved && isset($savedKeys[$id])) {
                $keys[$id] = $savedKeys[$id];
            }
        }
        return $keys;
    }

    /**
     * Si entre dos entradas de un registro cambia adónde va su clave: el tipo, la dirección base o la cabecera.
     *
     * @param array<string,mixed> $before
     * @param array<string,mixed> $after
     * @return bool
     */
    public static function aiProviderDestinationChanged(array $before, array $after): bool
    {
        foreach (['type', 'baseUrl', 'keyHeader'] as $field) {
            $old = is_string($before[$field] ?? null) && $before[$field] !== '' ? $before[$field] : null;
            $new = is_string($after[$field] ?? null) && $after[$field] !== '' ? $after[$field] : null;
            if ($field === 'baseUrl') {
                $old = $old !== null ? rtrim($old, '/') : null;
                $new = $new !== null ? rtrim($new, '/') : null;
            }
            if ($old !== $new) {
                return true;
            }
        }
        return false;
    }

    /**
     * Las entradas que hay ANTES de un guardado de Configuración › IA, por id: las guardadas y, sin la conversión escrita,
     * las de los convertidos en memoria.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function aiProviderEntriesBeforeSave(): array
    {
        return self::aiProviderEntries() + array_map(fn(array $converted): array => $converted['entry'], AIProviderRegistry::isConverted() ? [] : AIProviderRegistry::convertedPending());
    }

    /**
     * «Reiniciar el contador» de una capacidad de IA: su periodo empieza ahora (periodAnchor). No borra ninguna fila del
     * consumo: lo gastado desde ahora va a una fila nueva.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function aiUsageReset(Request $req, Response $res)
    {
        $body = $req->getParsedBody();
        $capability = is_array($body) && is_string($body['capability'] ?? null) ? $body['capability'] : '';
        if ($capability === '' || AICapabilityRegistry::get($capability) === null) {
            return $res->withJson([
                'success' => false,
                'name' => __(self::LANG_GROUP, 'Reiniciar el contador'),
                'message' => __(self::LANG_GROUP, 'Esa función de inteligencia artificial no existe.'),
            ], 400);
        }

        $center = AIGateway::normalizeCenter(get_config(AIGateway::CONFIG_NAME));
        $center = is_array($center) ? $center : [];
        $capabilities = is_array($center['capabilities'] ?? null) ? $center['capabilities'] : [];
        $entry = is_array($capabilities[$capability] ?? null) ? $capabilities[$capability] : [];
        $entry['periodAnchor'] = date(AIGateway::ANCHOR_FORMAT);
        $capabilities[$capability] = $entry;
        $center['capabilities'] = $capabilities;

        $option = new SettingsModel(AIGateway::CONFIG_NAME);
        $option->value = ConfigOptions::valueToStore(AIGateway::CONFIG_NAME, $center);
        if ($option->id !== null) {
            $saved = $option->update();
        } else {
            $option->name = AIGateway::CONFIG_NAME;
            $saved = $option->save();
        }
        if ($saved) {
            set_config(AIGateway::CONFIG_NAME, $center);
        }

        $periodStart = AIGateway::periodStart($capability);
        return $res->withJson([
            'success' => (bool) $saved,
            'name' => __(self::LANG_GROUP, 'Reiniciar el contador'),
            'message' => $saved ? __(self::LANG_GROUP, 'Contador reiniciado.') : __(self::LANG_GROUP, 'Ha ocurrido un error desconocido, intente más tarde.'),
            //El dato, no el texto: la pantalla lo pone como aiPeriodStartText() (d-m-Y H:i).
            'periodStart' => $periodStart,
            'usage' => AIGateway::usage($capability),
        ]);
    }

    /**
     * «Probar» un proveedor desde su fila de Configuración › IA (ADR 0077 §5): uno guardado (o convertido) por su id, con
     * su origen de la clave (la del archivo, por SU nombre) o la escrita si llega; o uno sin guardar por sus campos y con
     * la clave escrita, nunca con la del archivo. Y un modelo. Una llamada mínima por
     * AIGateway::testProvider(): no guarda nada ni anota consumo. Responde 200 con {ok, message}.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function aiProviderTest(Request $req, Response $res)
    {
        $body = $req->getParsedBody();
        $body = is_array($body) ? $body : [];
        $text = fn(string $name): string => is_string($body[$name] ?? null) ? trim($body[$name]) : '';
        $answer = fn(bool $ok, string $message): Response => $res->withJson(['ok' => $ok, 'name' => __(self::LANG_GROUP, 'Probar'), 'message' => $message]);

        $model = $text('model');
        if ($model === '') {
            return $answer(false, __(self::LANG_GROUP, 'Escribe el modelo con que probarlo.'));
        }
        if (mb_strlen($model) > AIGateway::MODEL_ID_MAX_LENGTH) {
            return $answer(false, __(self::LANG_GROUP, 'El modelo no vale: como mucho 128 caracteres.'));
        }
        $id = $text('id');
        $key = $text('key');
        $saved = null;
        if ($id !== '') {
            //Un guardado o, sin la conversión escrita, un convertido (AIProviderRegistry::all()).
            $saved = AIProviderRegistry::get($id);
            if ($saved === null) {
                return $answer(false, __(self::LANG_GROUP, 'Ese proveedor no está guardado.'));
            }
            $type = $saved->type();
            $baseUrl = $type === AIProviderCatalog::TYPE_CUSTOM ? $saved->baseUrl() : null;
            $keyHeader = $type === AIProviderCatalog::TYPE_CUSTOM ? ($saved->keyHeader() ?? null) : null;
            $key = $key !== '' ? $key : $saved->key();
        } else {
            $type = $text('type');
            $baseUrl = $type === AIProviderCatalog::TYPE_CUSTOM ? $text('baseUrl') : null;
            $keyHeader = $type === AIProviderCatalog::TYPE_CUSTOM && $text('keyHeader') !== '' ? $text('keyHeader') : null;
        }
        if (AIProviderCatalog::get($type) === null) {
            return $answer(false, __(self::LANG_GROUP, 'Elige el tipo del proveedor.'));
        }
        if ($type === AIProviderCatalog::TYPE_CUSTOM && ($baseUrl === null || !AIProviderCatalog::isValidCustomBaseUrl($baseUrl))) {
            return $answer(false, __(self::LANG_GROUP, 'La dirección base no vale: https://, o http://localhost o http://127.0.0.1.'));
        }
        //La clave del archivo, solo la de un registro guardado y con SU origen y SU nombre: los de la petición no cuentan, o
        //un «Personalizado» sin guardar mandaría a su dirección una llave del servidor.
        $keySource = AIProviderRecord::KEY_SOURCE_STORED;
        $keyFile = null;
        if ($saved !== null) {
            $keySource = $saved->keySource();
            $keyFile = $saved->keyFile();
            if ($keySource === AIProviderRecord::KEY_SOURCE_FILE) {
                $key = $saved->key();
                if ($key === '') {
                    return $answer(false, __(self::LANG_GROUP, 'No está en el archivo de claves.'));
                }
            }
        } elseif ($text('keySource') === AIProviderRecord::KEY_SOURCE_FILE) {
            return $answer(false, __(self::LANG_GROUP, 'Guarda el proveedor para probarlo con la clave del archivo.'));
        } elseif ($text('keySource') !== '' && $text('keySource') !== AIProviderRecord::KEY_SOURCE_STORED) {
            return $answer(false, __(self::LANG_GROUP, 'Elige de dónde sale la clave.'));
        }
        if ($key === '') {
            return $answer(false, __(self::LANG_GROUP, 'Escribe la clave para probarlo.'));
        }
        try {
            $record = new AIProviderRecord($id !== '' ? $id : 'prueba', $type, $text('alias') !== '' ? $text('alias') : 'prueba', $key, $baseUrl, $keyHeader, [], null, $keySource, $keyFile);
        } catch (\InvalidArgumentException $e) {
            return $answer(false, __(self::LANG_GROUP, 'Los datos del proveedor no valen: revisa la cabecera de la clave.'));
        }
        $result = AIGateway::testProvider($record, $model);
        return $answer($result['ok'], $result['message']);
    }

    /**
     * «Actualizar modelos» de un registro guardado (o, sin la conversión escrita, convertido) por su id: pide la lista al
     * proveedor (AIGateway::refreshProviderModels()) y la guarda en la caché. Responde 200 con {ok, name, message, models,
     * fetchedAt}.
     *
     * @param Request $req
     * @param Response $res
     * @return Response
     */
    public function aiProviderModels(Request $req, Response $res)
    {
        $body = $req->getParsedBody();
        $id = is_array($body) && is_string($body['id'] ?? null) ? trim($body['id']) : '';
        $answer = fn(array $result): Response => $res->withJson(['ok' => $result['ok'], 'name' => __(self::LANG_GROUP, 'Actualizar modelos'), 'message' => $result['message'], 'models' => $result['models'], 'fetchedAt' => $result['fetchedAt']]);
        $record = $id !== '' ? AIProviderRegistry::get($id) : null;
        if ($record === null) {
            return $answer(['ok' => false, 'message' => __(self::LANG_GROUP, 'Guarda el proveedor para actualizar sus modelos.'), 'models' => [], 'fetchedAt' => null]);
        }
        return $answer(AIGateway::refreshProviderModels($record));
    }

    /**
     * Las posiciones de la ventana del asistente, para el desplegable de Configuración › IA: valor (Assistant::POSITIONS)
     * y nombre traducido.
     *
     * @return array<string,string>
     */
    public static function aiAssistantPositionOptions(): array
    {
        return [
            'right' => __(self::LANG_GROUP, 'Derecha'),
            'left' => __(self::LANG_GROUP, 'Izquierda'),
            'floating right' => __(self::LANG_GROUP, 'Flotante abajo a la derecha'),
            'floating left' => __(self::LANG_GROUP, 'Flotante abajo a la izquierda'),
        ];
    }

    /**
     * El inicio de un periodo, para leer: la fecha y la hora, sin segundos.
     *
     * @param string $periodStart Y-m-d H:i:s
     * @return string
     */
    public static function aiPeriodStartText(string $periodStart): string
    {
        $date = \DateTimeImmutable::createFromFormat('!' . AIGateway::ANCHOR_FORMAT, $periodStart);
        return $date === false ? $periodStart : $date->format('d-m-Y H:i');
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
        $known = UserTypes::codes();
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
         * @var int[]
         */
        $allRoles = UserTypes::codes();

        $group->active(APP_CONFIGURATION_MODULE);

        //Perzonalizaciones
        $group->register([

            //──── GET ─────────────────────────────────────────────────────────────────────────
            //Estilo del panel
            new Route(
                "{$startRoute}appearance/panel-style[/]",
                $classname . ':configurationsView',
                self::$baseRouteName . '-' . 'appearance-colors',
                'GET',
                true,
                null,
                self::ROLES_VIEW_CONFIGURATIONS_VIEW
            ),
            //En desuso: la dirección vieja de Estilo del panel; se retira en la 11.0.0 (H422).
            new Route(
                "{$startRoute}appearance/colors[/]",
                $classname . ':panelStyleLegacyView',
                self::$baseRouteName . '-' . 'appearance-colors-legacy',
                'GET',
                true,
                null,
                self::ROLES_VIEW_CONFIGURATIONS_VIEW
            ),
            //Accesibilidad
            new Route(
                "{$startRoute}appearance/accessibility[/]",
                $classname . ':accessibilityView',
                self::$baseRouteName . '-' . 'appearance-accessibility',
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
            //Guardar la accesibilidad
            new Route(
                "{$startRoute}appearance/accessibility/save[/]",
                $classname . ':saveAccessibilityAction',
                self::$baseRouteName . '-' . 'appearance-accessibility-save',
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
            new Route(
                "{$startRoute}/appearance/backgrounds/delete[/]",
                $classname . ':backgroundsDelete',
                self::$baseRouteName . '-' . 'appearance-backgrounds-delete',
                'POST',
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
            new Route(
                "{$startRoute}/integrations/ai/usage-reset[/]",
                $classname . ':aiUsageReset',
                self::$baseRouteName . '-' . 'integrations-ai-usage-reset',
                'POST',
                true,
                null,
                self::ROLES_SECURITY_AND_AI
            ),
            new Route(
                "{$startRoute}/integrations/ai/provider-test[/]",
                $classname . ':aiProviderTest',
                self::$baseRouteName . '-' . 'integrations-ai-provider-test',
                'POST',
                true,
                null,
                self::ROLES_SECURITY_AND_AI
            ),
            new Route(
                "{$startRoute}/integrations/ai/provider-models[/]",
                $classname . ':aiProviderModels',
                self::$baseRouteName . '-' . 'integrations-ai-provider-models',
                'POST',
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
