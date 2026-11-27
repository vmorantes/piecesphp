<?php

/**
 * LegacyClassAliases.php
 *
 * @deprecated 10.69.0 sale en 11.0.0 · muere: decisiones-del-po 2026-11-25 «los nombres viejos de clases con alias que se cargan solo cuando se piden, hasta la 11.0.0»
 */

namespace PiecesPHP\Core;

/**
 * LegacyClassAliases - Los nombres de clase de la 7.1 que cambiaron de sitio cargan su clase de hoy.
 *
 * El alias se crea solo cuando alguien pide el nombre viejo: un clon que aún escribe `App\Model\UsersModel` sigue
 * funcionando, y quien no lo pide no carga nada. Se registra el último en bootstrap.php, tras los autoloads del proyecto:
 * si un clon conserva su propia clase con un nombre viejo, gana la suya.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class LegacyClassAliases
{

    /**
     * Nombre viejo (v7.1.0) → clase de hoy que hace lo mismo. Las clases de la 7.1 sin sustituta no están: un alias a
     * otra cosa engañaría. Tampoco los nombres viejos de ServerStatics y ProtectFileMiddleware: el PO los quitó sin
     * compatibilidad (P146).
     *
     * @var array<string,class-string>
     */
    public const MAP = [
        'App\\Controller\\AdminPanelController' => \PiecesPHP\AdminPanel\Controllers\AdminPanelController::class,
        'App\\Controller\\AppConfigController' => \PiecesPHP\Settings\Controllers\SettingsController::class,
        'App\\Controller\\AvatarController' => \PiecesPHP\UserSystem\Controllers\AvatarController::class,
        'App\\Controller\\GenericTokenController' => \PiecesPHP\Tokens\Controllers\GenericTokenController::class,
        'App\\Controller\\LoginAttemptsController' => \PiecesPHP\UserSystem\Controllers\LoginAttemptsController::class,
        'App\\Controller\\RecoveryPasswordController' => \PiecesPHP\UserSystem\Controllers\RecoveryPasswordController::class,
        'App\\Controller\\TimerController' => \PiecesPHP\UserSystem\Controllers\TimerController::class,
        'App\\Controller\\TokenController' => \PiecesPHP\Tokens\Controllers\TokenController::class,
        'App\\Controller\\UserProblemsController' => \PiecesPHP\UserSystem\Controllers\UserProblemsController::class,
        'App\\Controller\\UsersController' => \PiecesPHP\UserSystem\Controllers\UsersController::class,
        'App\\Locations\\Controllers\\City' => \PiecesPHP\App\Locations\Controllers\City::class,
        'App\\Locations\\Controllers\\Country' => \PiecesPHP\App\Locations\Controllers\Country::class,
        'App\\Locations\\Controllers\\HelperController' => \PiecesPHP\App\Locations\Controllers\HelperController::class,
        'App\\Locations\\Controllers\\Locations' => \PiecesPHP\App\Locations\Controllers\Locations::class,
        'App\\Locations\\Controllers\\Point' => \PiecesPHP\App\Locations\Controllers\Point::class,
        'App\\Locations\\Controllers\\Region' => \PiecesPHP\App\Locations\Controllers\Region::class,
        'App\\Locations\\Controllers\\State' => \PiecesPHP\App\Locations\Controllers\State::class,
        'App\\Locations\\LocationsLang' => \PiecesPHP\App\Locations\LocationsLang::class,
        'App\\Locations\\Mappers\\CityMapper' => \PiecesPHP\App\Locations\Mappers\CityMapper::class,
        'App\\Locations\\Mappers\\CountryMapper' => \PiecesPHP\App\Locations\Mappers\CountryMapper::class,
        'App\\Locations\\Mappers\\PointMapper' => \PiecesPHP\App\Locations\Mappers\PointMapper::class,
        'App\\Locations\\Mappers\\StateMapper' => \PiecesPHP\App\Locations\Mappers\StateMapper::class,
        'App\\Model\\AppConfigModel' => \PiecesPHP\Settings\ORM\SettingsModel::class,
        'App\\Model\\AvatarModel' => \PiecesPHP\UserSystem\ORM\AvatarModel::class,
        'App\\Model\\LoginAttemptsModel' => \PiecesPHP\UserSystem\ORM\LoginAttemptsModel::class,
        'App\\Model\\RecoveryPasswordModel' => \PiecesPHP\UserSystem\ORM\RecoveryPasswordModel::class,
        'App\\Model\\TicketsLogModel' => \PiecesPHP\UserSystem\ORM\TicketsLogModel::class,
        'App\\Model\\TimeOnPlatformModel' => \PiecesPHP\UserSystem\ORM\TimeOnPlatformModel::class,
        'App\\Model\\TokenModel' => \PiecesPHP\Tokens\ORM\TokenModel::class,
        'App\\Model\\UserProblemsModel' => \PiecesPHP\UserSystem\ORM\UserProblemsModel::class,
        'App\\Model\\UsersModel' => \PiecesPHP\UserSystem\ORM\UsersModel::class,
        'Components\\ComponentProviderLang' => \PiecesPHP\Components\ComponentProviderLang::class,
        'Components\\ComponentProviderRoutes' => \PiecesPHP\Components\ComponentProviderRoutes::class,
        'Components\\Controllers\\ComponentProvider' => \PiecesPHP\Components\Controllers\ComponentProvider::class,
        'Components\\Controllers\\HelperController' => \PiecesPHP\Components\Controllers\HelperController::class,
        'FileManager\\Controllers\\FileManagerController' => \PiecesPHP\FileManager\Controllers\FileManagerController::class,
        'FileManager\\Controllers\\HelperController' => \PiecesPHP\FileManager\Controllers\HelperController::class,
        'FileManager\\FileManagerLang' => \PiecesPHP\FileManager\FileManagerLang::class,
        'FileManager\\FileManagerRoutes' => \PiecesPHP\FileManager\FileManagerRoutes::class,
        'GoogleReCaptchaV3\\Controllers\\GoogleReCaptchaV3Controller' => \PiecesPHP\GoogleReCaptchaV3\Controllers\GoogleReCaptchaV3Controller::class,
        'GoogleReCaptchaV3\\GoogleReCaptchaV3Lang' => \PiecesPHP\GoogleReCaptchaV3\GoogleReCaptchaV3Lang::class,
        'GoogleReCaptchaV3\\GoogleReCaptchaV3Routes' => \PiecesPHP\GoogleReCaptchaV3\GoogleReCaptchaV3Routes::class,
        'MySpace\\Controllers\\AllProfilesController' => \Directory\Controllers\AllProfilesController::class,
        'MySpace\\Controllers\\MyOrganizationProfileController' => \Organizations\Controllers\MyOrganizationProfileController::class,
        'MySpace\\Controllers\\OrganizationProfileController' => \Organizations\Controllers\OrganizationProfileController::class,
        'MySpace\\Controllers\\ProfileController' => \Directory\Controllers\ProfileController::class,
        'PiecesPHP\\Core\\Validation\\Parameters\\Exceptions\\MissingRequiredParamaterException' => \PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParameterException::class,
        'PiecesPHP\\Core\\Validation\\Parameters\\Exceptions\\ParamaterNotExistsException' => \PiecesPHP\Core\Validation\Parameters\Exceptions\ParameterNotExistsException::class,
    ];

    private static bool $registered = false;

    /**
     * Añade el cargador de los nombres viejos. Una segunda llamada no añade otro.
     *
     * @return void
     */
    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;
        spl_autoload_register([self::class, 'load']);
    }

    /**
     * El cargador: si el nombre pedido es del mapa, crea el alias a su clase de hoy; si no, no hace nada.
     *
     * @param string $class
     * @return void
     */
    public static function load(string $class): void
    {
        $target = self::MAP[ltrim($class, '\\')] ?? null;
        if ($target !== null && class_exists($target)) {
            class_alias($target, ltrim($class, '\\'));
        }
    }
}
