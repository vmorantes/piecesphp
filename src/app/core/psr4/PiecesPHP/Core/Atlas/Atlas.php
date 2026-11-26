<?php

/**
 * Atlas.php
 */

namespace PiecesPHP\Core\Atlas;

use PiecesPHP\Core\Roles;

/**
 * Atlas - Lo que consultan el MCP de desarrollo, la comprobación 74 y las suites del mapa del código (ADR 0091, R1):
 * de dónde sale el permiso de una ruta, cómo lo cambia un proyecto, dónde se usa una constante y si el modelo cuadra con
 * el framework vivo. Todo se calcula en vivo; nada se escribe.
 *
 * @package     PiecesPHP\Core\Atlas
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Atlas
{
    const PRECISION = 'R1: permisos de rutas. Exacto por valor para las rutas localizadas (sus roles evaluados son los registrados); el resto se lista en «no visto». No ve lo que se concede en tiempo de ejecución (SystemApprovalsMiddleware para los restringidos, los permisos del administrador de su organización). Lo de un visitante sin sesión va en «anonymous»: Roles::hasPermissions() y la coherencia solo miran los tipos con rol.';

    /**
     * Un nombre de constante con esto no enseña su valor: podría ser una clave del proyecto.
     */
    const SECRET_NAME_PATTERN = '/(SECRET|PASSWORD|PASSWD|TOKEN|API_?KEY|PRIVATE|SALT|CREDENTIAL|_KEY$|^KEY_)/i';

    /**
     * Para las suites: olvida todo lo calculado.
     *
     * @return void
     */
    public static function reset(): void
    {
        TokenIndex::reset();
        RouteDeclarations::reset();
        PermissionModel::reset();
    }

    /**
     * route_explain: de dónde sale el permiso de una ruta para uno o todos los tipos con rol, y cómo se cambia.
     *
     * @param string $route
     * @param int|string|null $userType Número o nombre de rol; null, todos.
     * @return array<string,mixed>|null null si la ruta no existe.
     */
    public static function routeExplain(string $route, int|string|null $userType = null): ?array
    {
        $registry = get_routes();
        if (!is_array($registry) || !isset($registry[$route])) {
            return null;
        }
        $entry = $registry[$route];
        $located = RouteDeclarations::get()->located($route);
        $notSeen = [];
        if ($located === null) {
            $notSeen[] = 'la declaración: ' . (RouteDeclarations::get()->notLocated()[$route] ?? 'no se localizó');
        } elseif (!$located['verified']) {
            $notSeen[] = 'los roles de la declaración no se pudieron evaluar (' . (string) $located['roles']['reason'] . '): la unión es por nombre, sin verificar por valor';
        }
        $roles = null;
        if ($located !== null) {
            $symbol = $located['roles']['symbol'];
            $roles = [
                'text' => $located['roles']['text'],
                'kind' => $located['roles']['kind'],
                'symbol' => $symbol,
                'at' => $located['roles']['at']['file'] . ':' . $located['roles']['at']['line'],
                'value' => $located['roles']['value'],
                'usedByRoutes' => $symbol !== null ? self::routesUsing($symbol) : null,
            ];
        }
        $codes = [];
        if ($userType === null) {
            $codes = array_map(fn(array $role): int => (int) $role['code'], Roles::getRoles());
        } else {
            $role = Roles::getRole(is_string($userType) && ctype_digit($userType) ? (int) $userType : $userType);
            if (!is_array($role)) {
                return ['route' => $route, 'error' => "no hay ningún tipo de usuario con rol «{$userType}»"];
            }
            $codes = [(int) $role['code']];
        }
        $types = [];
        foreach ($codes as $code) {
            $explained = PermissionModel::explain($route, $code);
            if ($explained === null) {
                continue;
            }
            $role = Roles::getRole($code);
            $types[] = [
                'userType' => $code,
                'name' => is_array($role) ? $role['name'] : null,
                'granted' => $explained['granted'],
                'ways' => $explained['ways'],
                'changePoints' => ChangePoints::for($route, $code, $explained),
            ];
        }
        $method = $entry['method'] ?? null;
        $requireLogin = (bool) ($entry['require_login'] ?? false);
        $anonymous = $requireLogin
            ? ['enters' => false, 'detail' => 'require_login: sin sesión, el acceso la deniega antes de mirar los roles']
            : ['enters' => true, 'detail' => ($entry['roles_allowed'] ?? []) === [] ? 'ruta pública' : 'sin require_login: rolesAllowed concede a esos tipos, pero no restringe; un visitante sin sesión entra (a menos que el controlador lo frene)'];
        return [
            'route' => $route,
            'method' => is_array($method) ? implode('|', $method) : $method,
            'pattern' => $entry['route'] ?? null,
            'requireLogin' => $requireLogin,
            'anonymous' => $anonymous,
            'rolesRegistered' => RouteDeclarations::filteredRoles($entry['roles_allowed'] ?? []),
            'declaration' => $located !== null ? ['at' => $located['file'] . ':' . $located['line'], 'class' => $located['class'], 'joinedBy' => $located['via'], 'verifiedByValue' => $located['verified']] : null,
            'roles' => $roles,
            'types' => $types,
            'narrowings' => PermissionModel::narrowings($route),
            'precision' => self::PRECISION,
            'notSeen' => $notSeen,
        ];
    }

    /**
     * symbol_uses (R1: constantes de clase): dónde se declara, su valor, su comentario, sus usos y las rutas que la usan.
     *
     * @param string $symbol `Clase\Completa::NOMBRE`
     * @return array<string,mixed>|null null si no es una constante de clase conocida.
     */
    public static function symbolUses(string $symbol): ?array
    {
        $symbol = ltrim(trim($symbol), '\\');
        if (preg_match('/^([\w\\\\]+)::(\w+)$/', $symbol, $match) !== 1) {
            return null;
        }
        //Solo lo que src/app declara o ya está cargado: un nombre recibido no se autocarga, porque cargar un archivo es ejecutarlo.
        $index = TokenIndex::get();
        if (!$index->declaresClass($match[1]) && !class_exists($match[1], false) && !interface_exists($match[1], false) && !enum_exists($match[1], false)) {
            return null;
        }
        if (!class_exists($match[1]) && !interface_exists($match[1]) && !enum_exists($match[1])) {
            return null;
        }
        try {
            $reflection = new \ReflectionClassConstant($match[1], $match[2]);
        } catch (\ReflectionException) {
            return null;
        }
        $symbol = $reflection->getDeclaringClass()->getName() . '::' . $match[2];
        $declared = $index->constant($symbol);
        $value = null;
        $valueNote = null;
        if ($declared === null) {
            $valueNote = 'no se enseña: la constante no se declara en src/app';
        } elseif (preg_match(self::SECRET_NAME_PATTERN, $match[2]) === 1) {
            $valueNote = 'no se enseña: su nombre parece el de un secreto';
        } else {
            $value = $reflection->getValue();
        }
        $routes = [];
        foreach (RouteDeclarations::get()->allLocated() as $name => $entry) {
            if ($entry['roles']['symbol'] === $symbol) {
                $routes[] = ['route' => $name, 'at' => $entry['file'] . ':' . $entry['line']];
            }
        }
        //También los escritos a través de una subclase (`Hija::NOMBRE`, `self::NOMBRE` en la hija): la constante es la misma.
        $declaring = $reflection->getDeclaringClass()->getName();
        $uses = [];
        foreach ($index->constantUsesByName($match[2]) as $class => $classUses) {
            $same = $class === $declaring || ($index->declaresClass($class) && is_subclass_of($class, $declaring));
            foreach ($same ? $classUses : [] as $use) {
                $uses[] = ['at' => $use['file'] . ':' . $use['line'], 'context' => $use['context'], 'writtenAs' => $class . '::' . $match[2]];
            }
        }
        return [
            'symbol' => $symbol,
            'declaredAt' => $declared !== null ? $declared['file'] . ':' . $declared['line'] : null,
            'owner' => $declared !== null ? ChangePoints::owner($declared['file']) : null,
            'value' => $value,
            'valueNote' => $valueNote,
            'comment' => $declared['comment'] ?? '',
            'uses' => $uses,
            'routes' => $routes,
            'precision' => 'usos escritos como Clase::NOMBRE, self:: o static:: en src/app sin vendor ni local-tests; las rutas, las localizadas cuyos roles salen de esta constante (también a través de una variable). No ve constant() ni nombres construidos.',
        ];
    }

    /**
     * Cuántas rutas localizadas toman sus roles de esa constante.
     *
     * @param string $symbol
     * @return int
     */
    public static function routesUsing(string $symbol): int
    {
        return count(array_filter(RouteDeclarations::get()->allLocated(), fn(array $entry): bool => $entry['roles']['symbol'] === $symbol));
    }

    /**
     * La coherencia (comprobación 74, C1): para toda ruta registrada y todo tipo con rol, lo que concede el modelo frente a
     * Roles::hasPermissions($ruta, $rol, true).
     *
     * @return array{cells:int,mismatches:array<int,array{route:string,userType:int,model:bool,framework:bool}>}
     */
    public static function coherence(): array
    {
        $registry = get_routes();
        $cells = 0;
        $mismatches = [];
        foreach (is_array($registry) ? array_keys($registry) : [] as $route) {
            foreach (Roles::getRoles() as $role) {
                $code = (int) $role['code'];
                $model = PermissionModel::explain((string) $route, $code)['granted'] ?? false;
                $framework = Roles::hasPermissions((string) $route, $code, true);
                $cells++;
                if ($model !== $framework) {
                    $mismatches[] = ['route' => (string) $route, 'userType' => $code, 'model' => $model, 'framework' => $framework];
                }
            }
        }
        return ['cells' => $cells, 'mismatches' => $mismatches];
    }
}
