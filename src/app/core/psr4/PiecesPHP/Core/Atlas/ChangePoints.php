<?php

/**
 * ChangePoints.php
 */

namespace PiecesPHP\Core\Atlas;

use PiecesPHP\UserSystem\UserTypes;

/**
 * ChangePoints - Cómo concede, retira o estrecha un proyecto el acceso de un tipo a una ruta (ADR 0091 §3), derivado por
 * reglas de lo que dice PermissionModel: en qué archivo y línea, de quién es ese archivo (su marca `@pcsphp-config`, o
 * «código del framework») y qué pasa al actualizar. Nada escrito a mano.
 *
 * @package     PiecesPHP\Core\Atlas
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ChangePoints
{
    const UPDATE_PROJECT_FILE = 'archivo del proyecto: bin/actualizar-framework (git apply --3way) solo lo toca si el framework cambia esas mismas líneas';
    const UPDATE_FRAMEWORK_FILE = 'bin/actualizar-framework funde el cambio con git apply --3way; si el framework cambió esas mismas líneas, deja un conflicto. Anótalo en DIFERENCIAS.md';

    /**
     * @param string $route
     * @param int $userType
     * @param array{granted:bool,ways:array<int,array<string,mixed>>} $explained
     * @return array{grant:array<int,array<string,mixed>>,revoke:array<int,array<string,mixed>>,narrow:array<int,array<string,mixed>>}
     */
    public static function for(string $route, int $userType, array $explained): array
    {
        $located = RouteDeclarations::get()->located($route);
        $type = PermissionModel::typeState($userType);
        $protected = $type['protected'] ?? false;
        $segment = $type['permissions'] ?? 'generals';
        $protectedKeys = TokenIndex::get()->constant(UserTypes::class . '::PROTECTED_TYPE_KEYS');
        $protectedAt = $protectedKeys !== null ? $protectedKeys['file'] . ':' . $protectedKeys['line'] : 'UserTypes::PROTECTED_TYPE_KEYS';
        $grant = [];
        $revoke = [];
        $narrow = [];

        if (!$explained['granted']) {
            if ($located !== null && $located['declaredRoles']) {
                $grant[] = self::point('añadir el tipo a los roles de la declaración', self::rolesSite($located), self::rolesNote($located));
            } elseif ($located !== null) {
                $grant[] = self::point('declarar rolesAllowed en la ruta (hoy no declara roles)', $located['file'] . ':' . $located['line'], 'con roles, la ruta pasa a exigir sesión');
            }
            $grant[] = self::point("añadir «{$route}» a la lista «{$segment}» de roles.php, en su sección «Del clon»", self::clonSectionLine($segment), 'la reciben todos los tipos que empiezan con ese segmento');
            $grant[] = self::point("añadir «{$route}» a routes del tipo {$userType} en user-types.php", PermissionModel::USER_TYPES_FILE, 'solo este tipo; si la ruta es solo de root, Avisos del sistema lo marca como crítico y verify-integrity falla');
        }

        foreach ($explained['ways'] as $way) {
            switch ($way['way']) {
                case 'declaración':
                    $revoke[] = $located !== null
                    ? self::point('quitar el tipo de los roles de la declaración', self::rolesSite($located), self::rolesNote($located))
                    : self::point('quitar el tipo de los roles de la declaración', null, 'la declaración no se localizó (ver «no visto»)', false);
                    $revoke[] = $protected
                        ? self::point("quitársela al tipo {$userType} desde user-types.php", $protectedAt, 'user-types.php solo añade rutas (routes), y root y la administración general no admiten permissions ni se retiran (PROTECTED_TYPE_KEYS)', false)
                        : self::point("quitársela al tipo {$userType} desde user-types.php", PermissionModel::USER_TYPES_FILE, 'user-types.php solo añade rutas (routes); lo único que quita es retirar el tipo entero (retire)', false);
                    break;
                case 'segmento':
                    foreach (PermissionModel::rolesFileEntries()[$route] ?? [] as $place) {
                        if (!in_array($place['list'], PermissionModel::SEGMENT_LISTS[$segment] ?? [], true)) {
                            continue;
                        }
                        $clon = $place['section'] === 'Del clon';
                        $revoke[] = self::point(
                            "quitar «{$route}» de {$place['list']} en roles.php",
                            PermissionModel::ROLES_FILE . ':' . $place['line'],
                            $clon ? 'sección «Del clon»: es del proyecto' : 'sección «Del framework: no lo edites»: desde el proyecto, cambia el segmento del tipo',
                            $clon
                        );
                    }
                    $revoke[] = $protected
                        ? self::point("cambiar el segmento del tipo {$userType} en user-types.php", $protectedAt, 'root y la administración general no admiten permissions (PROTECTED_TYPE_KEYS)', false)
                        : self::point("cambiar el segmento del tipo {$userType} en user-types.php (permissions)", PermissionModel::USER_TYPES_FILE, 'cambia todas las rutas del segmento para ese tipo');
                    break;
                case 'routes de user-types.php':
                    $revoke[] = self::point("quitar «{$route}» de routes del tipo {$userType} en user-types.php", PermissionModel::USER_TYPES_FILE, null);
                    break;
                case 'pública':
                    $revoke[] = self::point('exigir sesión o declarar roles en la ruta', $located !== null ? $located['file'] . ':' . $located['line'] : null, 'hoy la ruta es pública');
                    break;
            }
        }

        $narrowings = PermissionModel::narrowings($route);
        $allowed = $narrowings['allowedRoute'];
        $narrow[] = is_array($allowed)
            ? self::point("decidir en {$allowed['method']}", is_string($allowed['at'] ?? null) ? $allowed['at'] : null, 'hoy: ' . (string) $allowed['decides'])
            : self::point('declarar _allowedRoute() en el controlador', is_string($narrowings['controller']) ? $narrowings['controller'] : null, 'verify-integrity exige su motivo en KNOWN_ROUTE_OVERRIDES (en un clon, «route_overrides»)');
        $module = $narrowings['module'];
        if (is_array($module)) {
            $narrow[] = self::point("apagar el módulo ({$module['constant']})", is_string($module['at']) ? $module['at'] : null, 'apaga todas las rutas del módulo');
        }
        return ['grant' => $grant, 'revoke' => $revoke, 'narrow' => $narrow];
    }

    /**
     * De quién es un archivo: su marca `@pcsphp-config` o «código del framework».
     *
     * @param string $file Relativo a src/, con o sin `:línea`.
     * @return string
     */
    public static function owner(string $file): string
    {
        $path = rtrim(str_replace('\\', '/', basepath('')), '/') . '/' . preg_replace('/:\d+$/', '', $file);
        $head = @file_get_contents($path, false, null, 0, 2048);
        if (is_string($head) && preg_match('/@pcsphp-config\s+(framework|clon|ambos)\b/', $head, $match) === 1) {
            return $match[1];
        }
        return 'código del framework';
    }

    /**
     * @param string $what
     * @param string|null $where
     * @param string|null $note
     * @param bool $possible
     * @return array<string,mixed>
     */
    private static function point(string $what, ?string $where, ?string $note, bool $possible = true): array
    {
        $owner = $where !== null && preg_match('/^app\//', $where) === 1 ? self::owner($where) : null;
        return [
            'what' => $what,
            'where' => $where,
            'owner' => $owner,
            'onUpdate' => $owner === null ? null : ($owner === 'clon' ? self::UPDATE_PROJECT_FILE : self::UPDATE_FRAMEWORK_FILE),
            'possible' => $possible,
            'note' => $note,
        ];
    }

    /**
     * @param array{file:string,line:int,roles:array{symbol:?string,at:array{file:string,line:int}}} $located
     * @return string
     */
    private static function rolesSite(array $located): string
    {
        return $located['roles']['at']['file'] . ':' . $located['roles']['at']['line'];
    }

    /**
     * @param array{roles:array{symbol:?string}} $located
     * @return string|null
     */
    private static function rolesNote(array $located): ?string
    {
        $symbol = $located['roles']['symbol'];
        if ($symbol === null) {
            return null;
        }
        $count = Atlas::routesUsing($symbol);
        return "es la constante {$symbol}: el cambio vale para las {$count} ruta(s) localizadas que la usan, y para sus demás usos (symbol_uses)";
    }

    /**
     * La línea del marcador «Del clon» de la lista de ese segmento en roles.php.
     *
     * @param string $segment
     * @return string
     */
    private static function clonSectionLine(string $segment): string
    {
        $list = [
            'generals' => '$permisosGenerales',
            'administratives' => '$permisosAdministrativos',
            'superiors' => '$permisosSuperiores',
        ][$segment] ?? null;
        //La lista es la de la asignación de primer nivel (`$lista = …`): las variables de dentro de array_merge() no cuentan.
        $data = TokenIndex::get()->file(PermissionModel::ROLES_FILE);
        $current = null;
        $depth = 0;
        foreach ($data['tokens'] ?? [] as $index => $token) {
            if (in_array($token->text, ['(', '['], true)) {
                $depth++;
            } elseif (in_array($token->text, [')', ']'], true)) {
                $depth--;
            } elseif ($token->is(T_VARIABLE) && $depth === 0) {
                $next = TokenIndex::get()->next($data['tokens'], $index);
                $current = $next !== null && $data['tokens'][$next]->text === '=' ? $token->text : $current;
            } elseif ($token->is(T_COMMENT) && str_contains($token->text, 'Del clon') && $current === $list && $depth > 0) {
                return PermissionModel::ROLES_FILE . ':' . $token->line;
            }
        }
        return PermissionModel::ROLES_FILE;
    }
}
