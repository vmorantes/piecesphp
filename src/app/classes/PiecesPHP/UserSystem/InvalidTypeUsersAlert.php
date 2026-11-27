<?php

/**
 * InvalidTypeUsersAlert.php
 */

namespace PiecesPHP\UserSystem;

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * InvalidTypeUsersAlert - El aviso de usuarios cuyo tipo ya no es un rol (P290, fase 1): no entran ni se borran, y root
 * tiene que saber cuántos hay. No cuenta al usuario de sistema (no tiene rol a propósito) ni a los ya borrados. Con el
 * módulo Organizaciones apagado, el tipo administrador de organización no tiene rol (UserTypes::roles()): esos van en un
 * aviso aparte, que dice por qué no entran, en vez de «ya no existe».
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class InvalidTypeUsersAlert
{
    const KEY = 'users-invalid-type';
    const KEY_ADMIN_ORG_MODULE_OFF = 'users-admin-org-module-off';

    /**
     * @var array{invalid:int,adminOrgModuleOff:int}|null Lo contado en esta petición: el aviso pregunta varias veces por página
     */
    private static $memo = null;

    /**
     * @return void
     */
    public static function register(): void
    {
        SystemAlertRegistry::register(new SystemAlert(
            self::KEY,
            SystemAlert::SEVERITY_WARNING,
            fn(): string => self::message(self::count()),
            fn(): bool => self::count() > 0,
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            'users-list',
            __(UserSystemFeaturesLang::LANG_GROUP, 'Ver usuarios'),
            [],
            fn(): string => (string) self::count()
        ));
        SystemAlertRegistry::register(new SystemAlert(
            self::KEY_ADMIN_ORG_MODULE_OFF,
            SystemAlert::SEVERITY_WARNING,
            fn(): string => self::adminOrgModuleOffMessage(self::countAdminOrgModuleOff()),
            fn(): bool => self::countAdminOrgModuleOff() > 0,
            false,
            [UsersModel::TYPE_USER_ROOT],
            true,
            'users-list',
            __(UserSystemFeaturesLang::LANG_GROUP, 'Ver usuarios'),
            [],
            fn(): string => (string) self::countAdminOrgModuleOff()
        ));
    }

    /**
     * Cuántos usuarios tienen un tipo que no es un rol, sin los administradores de organización con el módulo apagado
     * (countAdminOrgModuleOff()).
     *
     * @param bool $fresh true vuelve a contar aunque ya se haya contado en esta petición
     * @return int
     */
    public static function count(bool $fresh = false): int
    {
        return self::counts($fresh)['invalid'];
    }

    /**
     * Cuántos administradores de organización no pueden entrar porque el módulo Organizaciones está apagado.
     *
     * @param bool $fresh true vuelve a contar aunque ya se haya contado en esta petición
     * @return int
     */
    public static function countAdminOrgModuleOff(bool $fresh = false): int
    {
        return self::counts($fresh)['adminOrgModuleOff'];
    }

    /**
     * @param bool $fresh
     * @return array{invalid:int,adminOrgModuleOff:int}
     */
    private static function counts(bool $fresh): array
    {
        if (self::$memo !== null && !$fresh) {
            return self::$memo;
        }
        $model = UsersModel::model();
        $model->resetAll();
        $model->select(['type', 'COUNT(id) AS total'])->where(new WhereSegment([
            UsersModel::excludeSystemUserWhereItem('id', WhereItem::AND_OPERATOR),
            new WhereItem('status', WhereItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_DELETED),
        ]))->groupBy('type')->execute();
        $totals = [];
        foreach ((array) $model->result() as $row) {
            $totals[(int) $row->type] = (int) $row->total;
        }
        $organizationsModule = defined('CRITICAL_CONSTANTS') && is_array(CRITICAL_CONSTANTS) && (bool) (CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE'] ?? false);
        self::$memo = self::split($totals, $organizationsModule, fn(int $type): bool => Roles::roleExists($type));
        return self::$memo;
    }

    /**
     * Reparte los usuarios por tipo entre los dos avisos. Con el módulo apagado, el administrador de organización va al
     * suyo aunque no tenga rol; los demás sin rol, al de siempre. El módulo llega como argumento: es una constante.
     *
     * @param array<int,int> $totals Tipo => usuarios
     * @param bool $organizationsModule CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']
     * @param callable(int):bool $roleExists
     * @return array{invalid:int,adminOrgModuleOff:int}
     */
    private static function split(array $totals, bool $organizationsModule, callable $roleExists): array
    {
        $counts = ['invalid' => 0, 'adminOrgModuleOff' => 0];
        foreach ($totals as $type => $total) {
            if (!$organizationsModule && $type === UsersModel::TYPE_USER_ADMIN_ORG) {
                $counts['adminOrgModuleOff'] += $total;
            } elseif (!$roleExists($type)) {
                $counts['invalid'] += $total;
            }
        }
        return $counts;
    }

    /**
     * @param int $count
     * @return string
     */
    public static function message(int $count): string
    {
        return $count === 1
            ? __(UserSystemFeaturesLang::LANG_GROUP, '1 usuario tiene un tipo que ya no existe')
            : sprintf(__(UserSystemFeaturesLang::LANG_GROUP, '%d usuarios tienen un tipo que ya no existe'), $count);
    }

    /**
     * @param int $count
     * @return string
     */
    public static function adminOrgModuleOffMessage(int $count): string
    {
        return $count === 1
            ? __(UserSystemFeaturesLang::LANG_GROUP, '1 administrador de organización no puede entrar: el módulo Organizaciones está apagado')
            : sprintf(__(UserSystemFeaturesLang::LANG_GROUP, '%d administradores de organización no pueden entrar: el módulo Organizaciones está apagado'), $count);
    }
}
