<?php

/**
 * SettingsRoutes.php
 */

namespace PiecesPHP\Settings;

use API\Controllers\APIController;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\ConfigHelpers\MailjetConfig;
use PiecesPHP\Core\Crypto\EncryptedData;
use PiecesPHP\Core\Crypto\EncryptedDataRegistry;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ServerStatics;
use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Core\VisitorIP;
use PiecesPHP\MailCatalog\Definitions\MailSettingsTestMail;
use PiecesPHP\MailCatalog\MailCatalog;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\Controllers\SiteFilesController;

/**
 * SettingsRoutes.
 *
 * Las rutas de la configuración y sus estáticos (Statics/: las hojas y el JavaScript de sus pantallas), como las de los
 * demás módulos (MySpaceRoutes).
 *
 * @package     PiecesPHP\Settings
 */
class SettingsRoutes
{

    const ENABLE = true;

    /**
     * @param RouteGroup $group El de la configuración (/admin/configurations).
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        //Fuera del if: las filas existen y se leen aunque la pantalla esté apagada.
        self::declareConfigOptions();
        if (self::ENABLE) {
            //El correo de prueba de Configuración › Correo, en el catálogo de correos.
            MailCatalog::register(new MailSettingsTestMail());
            $group = SettingsController::routes($group);
            self::staticResolver($group);
        }
        return $group;
    }

    /**
     * Las opciones que guardan las pantallas de la configuración, y las del correo (ADR 0055).
     *
     * @return void
     */
    public static function declareConfigOptions(): void
    {
        $owner = SettingsController::class;
        $declare = fn (array $names, string $optionOwner, ?string $shape) => array_map(fn (string $name) => ConfigOptions::declare($name, $optionOwner, $shape), $names);

        //Fondos e imágenes de marca (la de los problemas de usuario la siembra el arranque).
        $declare(['backgrounds'], $owner, ConfigOptions::SHAPE_JSON);
        $declare(['backgoundProblems', 'favicon', 'favicon-back', 'logo', 'partners', 'partnersVertical', 'mailingLogo'], $owner, ConfigOptions::SHAPE_TEXT);
        //El texto sobre el patrocinador, uno por idioma (SettingsController::partnersTextOptionName()).
        $declare(array_map(fn (string $lang) => SettingsController::partnersTextOptionName($lang), Config::get_allowed_langs()), $owner, ConfigOptions::SHAPE_TEXT);
        $declare(array_values(SettingsController::BRAND_IMAGES_INVERT_ON_DARK), $owner, ConfigOptions::SHAPE_BOOL);

        //SEO: el nombre por idioma lo da SettingsController::seoOptionName() (sin sufijo el del idioma por omisión).
        $seoText = [SettingsController::SEO_OPTION_TITLE_APP, SettingsController::SEO_OPTION_OWNER, SettingsController::SEO_OPTION_DESCRIPTION, SettingsController::SEO_OPTION_SHARE_TITLE, SettingsController::SEO_OPTION_SHARE_DESCRIPTION, SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE];
        foreach (Config::get_allowed_langs() as $lang) {
            $declare(array_map(fn (string $name) => SettingsController::seoOptionName($name, $lang), $seoText), $owner, ConfigOptions::SHAPE_TEXT);
            $declare([SettingsController::seoOptionName(SettingsController::SEO_OPTION_KEYWORDS, $lang)], $owner, ConfigOptions::SHAPE_JSON);
        }
        $declare([SettingsController::SEO_OPTION_X_ACCOUNT], $owner, ConfigOptions::SHAPE_TEXT);

        //Correo.
        $declare([MailConfig::CONFIG_NAME], MailConfig::class, ConfigOptions::SHAPE_SEALED);
        $declare([MailjetConfig::CONFIG_NAME], MailjetConfig::class, ConfigOptions::SHAPE_SEALED);
        $declare([MailDelivery::CONFIG_NAME], MailDelivery::class, ConfigOptions::SHAPE_TEXT);

        //osTicket, archivos del sitio, scripts inyectados y copias.
        $declare([SettingsController::OS_TICKET_URL_CONFIG], $owner, ConfigOptions::SHAPE_TEXT);
        $declare([SettingsController::OS_TICKET_KEY_CONFIG], $owner, ConfigOptions::SHAPE_SECRET);
        $declare([SiteFilesController::CONFIG_ROBOTS, SiteFilesController::CONFIG_HUMANS, SiteFilesController::CONFIG_LLMS, SiteFilesController::CONFIG_UPDATED], SiteFilesController::class, ConfigOptions::SHAPE_TEXT);
        $declare([ExtraScripts::CONFIG_NAME], ExtraScripts::class, ConfigOptions::SHAPE_JSON);
        $declare([BackupPolicy::CONFIG_NAME], BackupPolicy::class, ConfigOptions::SHAPE_JSON);

        //Seguridad.
        $declare(['check_aud_on_auth', 'hide_app_key_warning'], $owner, ConfigOptions::SHAPE_BOOL);
        $declare([SessionToken::INACTIVITY_CONFIG, SessionToken::CEILING_CONFIG], SessionToken::class, ConfigOptions::SHAPE_INTEGER);
        $declare([VisitorIP::CONFIG_NAME], VisitorIP::class, ConfigOptions::SHAPE_JSON);

        //Inteligencia artificial.
        $declare(['modelOpenAI', 'modelMistral', 'modelGroq', 'translationAI'], $owner, ConfigOptions::SHAPE_TEXT);
        //Las claves de API, selladas en la base y en claro en get_config() (P310 a).
        $declare(SettingsController::AI_SECRET_CONFIG_KEYS, $owner, ConfigOptions::SHAPE_SECRET);
        $declare(['translationAIEnable'], $owner, ConfigOptions::SHAPE_BOOL);
        $declare([APIController::TRANSLATION_AI_USER_TYPES_CONFIG], APIController::class, ConfigOptions::SHAPE_JSON);
        $declare([AIGateway::CONFIG_NAME], AIGateway::class, ConfigOptions::SHAPE_JSON);
        //La caché de los modelos que dio cada proveedor («Actualizar modelos», ADR 0077).
        $declare([AIGateway::MODELS_CONFIG_NAME], AIGateway::class, ConfigOptions::SHAPE_JSON);
        //Las claves de los registros de proveedor (ADR 0077): un mapa sellado que solo abre su dueño, y su sitio en el registro
        //de lo cifrado para que la rotación de la clave de la aplicación lo vuelva a sellar.
        $declare([AIProviderRegistry::KEYS_CONFIG_NAME], AIProviderRegistry::class, ConfigOptions::SHAPE_SEALED);
        if (!array_key_exists(AIProviderRegistry::SEAL_PURPOSE, EncryptedDataRegistry::all())) {
            EncryptedDataRegistry::register(new EncryptedData(AIProviderRegistry::SEAL_PURPOSE, AIProviderRegistry::SEAL_PURPOSE, EncryptedData::KIND_OPTION, AIProviderRegistry::KEYS_CONFIG_NAME));
        }

        //Colores, tipografía y forma: la pantalla Colores.
        $declare(SettingsController::COLOR_OPTIONS, $owner, ConfigOptions::SHAPE_COLOR);
        $declare(['theme_font'], $owner, ConfigOptions::SHAPE_TEXT);
        $declare(['theme_font_size', 'theme_radius'], $owner, ConfigOptions::SHAPE_INTEGER);

        //Accesibilidad: lo que ve quien no ha elegido lo suyo.
        $declare([SettingsController::ACCESSIBILITY_TEXT_SIZE_CONFIG, SettingsController::ACCESSIBILITY_THEME_MODE_CONFIG, SettingsController::ACCESSIBILITY_CONTRAST_CONFIG, SettingsController::ACCESSIBILITY_MOTION_CONFIG, SettingsController::ACCESSIBILITY_FOCUS_CONFIG, SettingsController::ACCESSIBILITY_TEXT_SPACING_CONFIG, SettingsController::ACCESSIBILITY_TRANSPARENCY_CONFIG], $owner, ConfigOptions::SHAPE_TEXT);
    }

    /**
     * @param string $segment
     * @return string
     */
    public static function staticRoute(string $segment = '')
    {
        return get_router()->getContainer()->get('staticRouteModulesResolver')(self::class, $segment, __DIR__ . '/Statics', self::ENABLE);
    }

    /**
     * @param RouteGroup $group
     * @return void
     */
    protected static function staticResolver(RouteGroup $group)
    {

        $lastIsBar = last_char($group->getGroupSegment()) == '/';
        $startRoute = $lastIsBar ? '' : '/';

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        $callableHandler = function (Request $request, Response $response, array $args) {
            $server = new ServerStatics();
            return $server->serve($request, $response, $args, __DIR__ . '/Statics');
        };

        $group->register([
            new Route("{$startRoute}statics/[{params:.*}]", $callableHandler, SettingsRoutes::class),
        ]);

    }

}
