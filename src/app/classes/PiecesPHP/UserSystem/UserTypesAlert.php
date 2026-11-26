<?php

/**
 * UserTypesAlert.php
 */

namespace PiecesPHP\UserSystem;

use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * UserTypesAlert - Los avisos de `src/app/config/user-types.php` para root (ADR 0078 y 0090): el archivo tiene errores y
 * rigen los tipos de fábrica, retira un tipo que un módulo encendido sigue asignando, da a un tipo una ruta solo de
 * root, o tiene algo que conviene revisar (UserTypes::warnings()).
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class UserTypesAlert
{
    const KEY_ERRORS = 'user-types-file-errors';
    const KEY_MODULES = 'user-types-module-conflicts';
    const KEY_ROOT_ROUTES = 'user-types-root-only-routes';
    const KEY_WARNINGS = 'user-types-warnings';

    /**
     * @return void
     */
    public static function register(): void
    {
        SystemAlertRegistry::register(new SystemAlert(
            self::KEY_ERRORS,
            SystemAlert::SEVERITY_DANGER,
            fn(): string => sprintf(__(UserSystemFeaturesLang::LANG_GROUP, 'src/app/config/user-types.php tiene errores y rigen los tipos de usuario de fábrica: %s'), implode('; ', UserTypes::errors())),
            fn(): bool => UserTypes::errors() !== [],
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            null,
            null,
            [],
            fn(): string => md5(implode('|', UserTypes::errors()))
        ));
        SystemAlertRegistry::register(new SystemAlert(
            self::KEY_MODULES,
            SystemAlert::SEVERITY_WARNING,
            fn(): string => sprintf(__(UserSystemFeaturesLang::LANG_GROUP, 'Un tipo de usuario retirado sigue en uso por un módulo encendido: %s'), implode('; ', UserTypes::moduleConflicts())),
            fn(): bool => UserTypes::moduleConflicts() !== [],
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            null,
            null,
            [],
            fn(): string => md5(implode('|', UserTypes::moduleConflicts()))
        ));
        SystemAlertRegistry::register(new SystemAlert(
            self::KEY_ROOT_ROUTES,
            SystemAlert::SEVERITY_DANGER,
            fn(): string => sprintf(__(UserSystemFeaturesLang::LANG_GROUP, 'src/app/config/user-types.php da a un tipo de usuario una ruta reservada a root: %s'), implode('; ', UserTypes::rootOnlyRoutes())),
            fn(): bool => UserTypes::rootOnlyRoutes() !== [],
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            null,
            null,
            [],
            fn(): string => md5(implode('|', UserTypes::rootOnlyRoutes()))
        ));
        SystemAlertRegistry::register(new SystemAlert(
            self::KEY_WARNINGS,
            SystemAlert::SEVERITY_WARNING,
            fn(): string => sprintf(__(UserSystemFeaturesLang::LANG_GROUP, 'Revise los tipos de usuario de src/app/config/user-types.php: %s'), implode('; ', UserTypes::warnings())),
            fn(): bool => UserTypes::warnings() !== [],
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            null,
            null,
            [],
            fn(): string => md5(implode('|', UserTypes::warnings()))
        ));
    }
}
