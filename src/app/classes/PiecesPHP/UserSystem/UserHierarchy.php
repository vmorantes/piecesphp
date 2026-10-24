<?php

/**
 * UserHierarchy.php
 */

namespace PiecesPHP\UserSystem;

use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItemGroup;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * UserHierarchy - Qué registros de otro usuario puede ver o tocar quien mira (P133).
 *
 * Root, todo (también lo de otro root). Los demás, lo propio y lo de rangos estrictamente menores; el administrador de
 * organización, además, solo de su organización. La misma regla en PHP (canSee) y en SQL (visibilityGroup): las
 * sesiones, los accesos y el registro de acciones la comparten.
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class UserHierarchy
{
    const RANKS = [
        UsersModel::TYPE_USER_ROOT => 3,
        UsersModel::TYPE_USER_ADMIN_GRAL => 2,
        UsersModel::TYPE_USER_ADMIN_ORG => 1,
        //Como un borrado: sin esto daba 0 y un administrador podría actuar sobre él.
        UsersModel::TYPE_USER_SYSTEM => self::RANK_NOBODY,
    ];

    /**
     * El rango de quien no es nadie (una acción del sistema, un usuario borrado): el de root, así que solo root la ve.
     */
    const RANK_NOBODY = 3;

    /**
     * @param int $userType
     * @return int
     */
    public static function rank(int $userType): int
    {
        return self::RANKS[$userType] ?? 0;
    }

    /**
     * Si quien actúa puede ver o tocar lo de otro usuario (sin contar lo propio).
     *
     * @param int $actorType
     * @param int|null $actorOrganization
     * @param int $targetType
     * @param int|null $targetOrganization
     * @return bool
     */
    public static function canActOn(int $actorType, ?int $actorOrganization, int $targetType, ?int $targetOrganization): bool
    {
        if ($actorType === UsersModel::TYPE_USER_ROOT) {
            return true;
        }
        if (self::rank($targetType) >= self::rank($actorType)) {
            return false;
        }
        if ($actorType === UsersModel::TYPE_USER_ADMIN_GRAL) {
            return true;
        }
        return $actorType === UsersModel::TYPE_USER_ADMIN_ORG && $actorOrganization !== null && $actorOrganization === $targetOrganization;
    }

    /**
     * Si quien mira ve un registro de ese usuario. Lo propio, siempre.
     *
     * @param object|null $actor Con id, type y organization (UserDataPackage); null no ve nada
     * @param int|null $targetID null: el registro no es de ningún usuario
     * @param int|null $targetType null si el usuario ya no existe
     * @param int|null $targetOrganization
     * @param bool $withoutUserVisible Si un registro sin usuario lo ven los administradores (un intento con un usuario
     * que no existe) o solo root (una acción del sistema)
     * @return bool
     */
    public static function canSee(?object $actor, ?int $targetID, ?int $targetType, ?int $targetOrganization, bool $withoutUserVisible = false): bool
    {
        if ($actor === null) {
            return false;
        }
        [$actorID, $actorType, $actorOrganization] = self::actorData($actor);
        if ($actorType === UsersModel::TYPE_USER_ROOT || ($targetID !== null && $targetID === $actorID)) {
            return true;
        }
        if ($targetID === null) {
            return $withoutUserVisible && self::rank($actorType) > 0;
        }
        if ($targetType === null) {
            return self::RANK_NOBODY < self::rank($actorType);
        }
        return self::canActOn($actorType, $actorOrganization, $targetType, $targetOrganization);
    }

    /**
     * El rango en SQL a partir de una expresión que da el tipo de usuario. Solo con constantes: nada de la petición
     * entra aquí.
     *
     * @param string $typeExpression Una columna o una subconsulta escrita en el código
     * @param int $rankIfNull Ver RANK_NOBODY
     * @return string
     */
    public static function rankSQL(string $typeExpression, int $rankIfNull = self::RANK_NOBODY): string
    {
        $cases = '';
        foreach (self::RANKS as $type => $rank) {
            $cases .= " WHEN ({$typeExpression}) = {$type} THEN {$rank}";
        }
        return "(CASE WHEN ({$typeExpression}) IS NULL THEN {$rankIfNull}{$cases} ELSE 0 END)";
    }

    /**
     * La visibilidad como grupo HAVING sobre tres alias de la consulta; null si quien mira es root.
     *
     * Grupo plano: AND precede a OR, así que equivale a `(rango menor [AND su organización]) OR suyo [OR sin usuario]`.
     * El rango de una fila sin usuario lo fija rankSQL(); con $withoutUserVisible, además, el administrador de
     * organización ve las filas sin usuario.
     *
     * @param object|null $actor null: un grupo que no deja pasar nada
     * @param string $userIDAlias
     * @param string $rankAlias El de rankSQL()
     * @param string $organizationAlias
     * @param bool $withoutUserVisible
     * @return HavingItemGroup|null
     */
    public static function visibilityGroup(?object $actor, string $userIDAlias, string $rankAlias, string $organizationAlias, bool $withoutUserVisible = false): ?HavingItemGroup
    {
        if ($actor === null) {
            return new HavingItemGroup([new HavingItem($rankAlias, HavingItem::LESS_THAN_OPERATOR, -1, HavingItem::AND_OPERATOR)]);
        }
        [$actorID, $actorType, $actorOrganization] = self::actorData($actor);
        if ($actorType === UsersModel::TYPE_USER_ROOT) {
            return null;
        }
        $isOrganizationAdmin = $actorType === UsersModel::TYPE_USER_ADMIN_ORG;
        $items = [new HavingItem($rankAlias, HavingItem::LESS_THAN_OPERATOR, self::rank($actorType), $isOrganizationAdmin ? HavingItem::AND_OPERATOR : HavingItem::OR_OPERATOR)];
        if ($isOrganizationAdmin) {
            $items[] = new HavingItem($organizationAlias, HavingItem::EQUAL_OPERATOR, $actorOrganization ?? -1, HavingItem::OR_OPERATOR);
        }
        $withoutUser = $withoutUserVisible && self::rank($actorType) > 0;
        $items[] = new HavingItem($userIDAlias, HavingItem::EQUAL_OPERATOR, $actorID, $withoutUser ? HavingItem::OR_OPERATOR : HavingItem::AND_OPERATOR);
        if ($withoutUser) {
            $items[] = new HavingItem($userIDAlias, HavingItem::IS_NULL_OPERATOR, '', HavingItem::AND_OPERATOR);
        }
        return new HavingItemGroup($items);
    }

    /**
     * La misma regla que visibilityGroup(), en SQL, para las consultas que solo aceptan un HAVING de texto
     * (DataTablesHelper::processFromQuery). Solo enteros de quien mira, convertidos aquí: nada de la petición.
     *
     * @param object|null $actor
     * @param string $userIDAlias
     * @param string $rankAlias
     * @param string $organizationAlias
     * @return string|null null si quien mira es root
     */
    public static function visibilitySQL(?object $actor, string $userIDAlias, string $rankAlias, string $organizationAlias): ?string
    {
        if ($actor === null) {
            return '1 = 0';
        }
        [$actorID, $actorType, $actorOrganization] = self::actorData($actor);
        if ($actorType === UsersModel::TYPE_USER_ROOT) {
            return null;
        }
        $rank = self::rank($actorType);
        $lower = "{$rankAlias} < {$rank}";
        if ($actorType === UsersModel::TYPE_USER_ADMIN_ORG) {
            $organization = $actorOrganization ?? -1;
            $lower = "({$lower} AND {$organizationAlias} = {$organization})";
        }
        return "({$lower} OR {$userIDAlias} = {$actorID})";
    }

    /**
     * @param object $actor
     * @return array{0:int,1:int,2:int|null}
     */
    private static function actorData(object $actor): array
    {
        //`??` y no isset(): en UserDataPackage son mágicas y la clase no implementa __isset().
        $id = $actor->id ?? null;
        $type = $actor->type ?? null;
        $organization = $actor->organization ?? null;
        return [
            is_numeric($id) ? (int) $id : -1,
            is_numeric($type) ? (int) $type : -1,
            is_numeric($organization) ? (int) $organization : null,
        ];
    }
}
