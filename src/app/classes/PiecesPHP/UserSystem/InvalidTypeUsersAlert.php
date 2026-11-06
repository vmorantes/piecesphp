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
 * tiene que saber cuántos hay. No cuenta al usuario de sistema (no tiene rol a propósito) ni a los ya borrados.
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class InvalidTypeUsersAlert
{
    const KEY = 'users-invalid-type';

    /**
     * @var int|null Lo contado en esta petición: el aviso pregunta varias veces por página
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
    }

    /**
     * Cuántos usuarios tienen un tipo que no es un rol.
     *
     * @param bool $fresh true vuelve a contar aunque ya se haya contado en esta petición
     * @return int
     */
    public static function count(bool $fresh = false): int
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
        $count = 0;
        foreach ((array) $model->result() as $row) {
            if (!Roles::roleExists((int) $row->type)) {
                $count += (int) $row->total;
            }
        }
        self::$memo = $count;
        return $count;
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
}
