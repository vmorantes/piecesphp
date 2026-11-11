<?php

/**
 * HomeDestinationRegistry.php
 */

namespace PiecesPHP\AdminPanel;

use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use SystemApprovals\Util\SystemApprovalManager;

/**
 * HomeDestinationRegistry - Adónde entra cada tipo de usuario al abrir el inicio del panel, en lugar de las tarjetas.
 * Cada módulo registra los suyos desde su propio Routes (los repartos de siempre: Publications y SystemApprovals); un
 * proyecto registra el suyo desde su módulo, o quita (remove()) o sustituye (replace()) uno del framework. Sin destino
 * para su tipo, el usuario ve las tarjetas (DashboardCardRegistry).
 *
 * @package     PiecesPHP\AdminPanel
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class HomeDestinationRegistry
{
    /**
     * @var array<int,array{destination:callable(Request,Response,UserDataPackage):?Response,onlyApproved:bool}>
     */
    private static $destinations = [];

    /**
     * @param int $userType
     * @param callable(Request,Response,UserDataPackage):?Response $destination La respuesta del inicio para ese usuario; null, las tarjetas
     * @param bool $onlyApproved Solo si el usuario está aprobado (SystemApprovals), como los repartos de siempre
     * @return void
     * @throws \InvalidArgumentException si ese tipo ya tiene destino: dos módulos no pueden decidir lo mismo en silencio
     */
    public static function register(int $userType, callable $destination, bool $onlyApproved = true): void
    {
        if (array_key_exists($userType, self::$destinations)) {
            throw new \InvalidArgumentException("Ya hay un destino del inicio para el tipo de usuario {$userType}: usa replace() para cambiarlo.");
        }
        self::$destinations[$userType] = ['destination' => $destination, 'onlyApproved' => $onlyApproved];
    }

    /**
     * @param int $userType
     * @param callable(Request,Response,UserDataPackage):?Response $destination
     * @param bool $onlyApproved
     * @return void
     * @throws \InvalidArgumentException si ese tipo no tiene destino que sustituir
     */
    public static function replace(int $userType, callable $destination, bool $onlyApproved = true): void
    {
        if (!array_key_exists($userType, self::$destinations)) {
            throw new \InvalidArgumentException("No hay ningún destino del inicio para el tipo de usuario {$userType} que sustituir.");
        }
        self::$destinations[$userType] = ['destination' => $destination, 'onlyApproved' => $onlyApproved];
    }

    /**
     * @param int $userType
     * @return bool false si no tenía
     */
    public static function remove(int $userType): bool
    {
        if (!array_key_exists($userType, self::$destinations)) {
            return false;
        }
        unset(self::$destinations[$userType]);
        return true;
    }

    /**
     * @param int $userType
     * @return bool
     */
    public static function has(int $userType): bool
    {
        return array_key_exists($userType, self::$destinations);
    }

    /**
     * La respuesta del inicio para este usuario, o null si le tocan las tarjetas: sin destino para su tipo, o con uno solo
     * para aprobados y él no lo está, o si el destino devuelve null.
     *
     * @param UserDataPackage $user
     * @param Request $request
     * @param Response $response
     * @return Response|null
     */
    public static function resolve(UserDataPackage $user, Request $request, Response $response): ?Response
    {
        $entry = self::$destinations[(int) $user->type] ?? null;
        if ($entry === null) {
            return null;
        }
        if ($entry['onlyApproved'] && !SystemApprovalManager::getInstance()->isApproved(UsersModel::class, $user->id)) {
            return null;
        }
        return ($entry['destination'])($request, $response, $user);
    }

    /**
     * SOLO PARA PRUEBAS: vacía el registro en memoria y devuelve lo que había, para reponerlo después.
     *
     * @internal
     * @param array<int,array{destination:callable(Request,Response,UserDataPackage):?Response,onlyApproved:bool}>|null $restore
     * @return array<int,array{destination:callable(Request,Response,UserDataPackage):?Response,onlyApproved:bool}>
     */
    public static function swapForTesting(?array $restore = null): array
    {
        $previous = self::$destinations;
        self::$destinations = $restore ?? [];
        return $previous;
    }
}
