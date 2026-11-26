<?php

/**
 * PermissionModel.php
 */

namespace PiecesPHP\Core\Atlas;

use PiecesPHP\Core\Roles;
use PiecesPHP\UserSystem\UserTypes;
use Terminal\Tasks\VerifyIntegrityTask;

/**
 * PermissionModel - Por qué un tipo de usuario entra (o no) a una ruta, vía por vía (ADR 0091, R1).
 *
 * Las vías que conceden, y que solo añaden: (1) la declaración, el `rolesAllowed` de la ruta (register_route() →
 * Roles::addPermission()); (2) las listas de `roles.php`, «Del framework» o «Del clon»; (3) el segmento del tipo,
 * `permissions` de fábrica o de `user-types.php` (UserTypes::roles()); (4) `routes` de `user-types.php`. Una ruta sin
 * sesión ni roles es pública. Lo que estrecha después: `require_login`, `_allowedRoute()` del controlador y la constante
 * del módulo. Reconstruye lo que concede; la comprobación 74 exige que sea igual a Roles::hasPermissions().
 *
 * @package     PiecesPHP\Core\Atlas
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class PermissionModel
{
    const ROLES_FILE = 'app/config/roles.php';
    const USER_TYPES_FILE = 'app/config/user-types.php';
    const SEGMENT_LISTS = [
        'generals' => ['$permisosGenerales'],
        'administratives' => ['$permisosGenerales', '$permisosAdministrativos'],
        'superiors' => ['$permisosGenerales', '$permisosAdministrativos', '$permisosSuperiores'],
    ];

    /**
     * @var array<string,array<int,array{list:string,line:int,section:string}>>|null nombre de ruta => dónde aparece en roles.php
     */
    private static ?array $rolesFileEntries = null;

    /**
     * @var array<int,array{permissions:string,routes:string[],segmentFrom:string,protected:bool}|null>
     */
    private static array $typeStates = [];

    /**
     * Lo que concede y por qué, para una ruta y un tipo con rol.
     *
     * @param string $route
     * @param int $userType
     * @return array{granted:bool,ways:array<int,array<string,mixed>>}|null null si la ruta o el rol no existen.
     */
    public static function explain(string $route, int $userType): ?array
    {
        $registry = get_routes();
        $role = Roles::getRole($userType);
        if (!is_array($registry) || !isset($registry[$route]) || !is_array($role)) {
            return null;
        }
        $entry = $registry[$route];
        $declared = RouteDeclarations::filteredRoles($entry['roles_allowed'] ?? []) ?? [];
        $requireLogin = (bool) ($entry['require_login'] ?? false);
        $ways = [];

        if (!$requireLogin && $declared === []) {
            $ways[] = ['way' => 'pública', 'detail' => 'sin require_login ni roles declarados: entra cualquier tipo con rol'];
        }
        if (in_array($userType, $declared, true)) {
            $located = RouteDeclarations::get()->located($route);
            $ways[] = ['way' => 'declaración', 'detail' => 'su rolesAllowed lo incluye', 'at' => $located !== null ? $located['file'] . ':' . $located['line'] : null,
                'roles' => $located['roles'] ?? null];
        }
        $type = self::typeState($userType);
        $segment = $type['permissions'] ?? null;
        //Las vías (2) y (3) son una: la lista de roles.php en la que está la ruta, y el segmento del tipo que la incluye.
        if (is_string($segment) && self::inSegment($route, $segment)) {
            $places = array_values(array_filter(self::rolesFileEntries()[$route] ?? [], fn(array $p): bool => in_array($p['list'], self::SEGMENT_LISTS[$segment] ?? [], true)));
            $ways[] = [
                'way' => 'segmento',
                'detail' => "el tipo empieza con «{$segment}» (" . ($type['segmentFrom'] ?? 'fábrica') . ') y la ruta está en esa lista de roles.php',
                'segment' => $segment,
                'segmentFrom' => $type['segmentFrom'] ?? null,
                'inRolesFile' => array_map(fn(array $p): string => self::ROLES_FILE . ':' . $p['line'] . ' (' . $p['list'] . ', «' . $p['section'] . '»)', $places),
            ];
        }
        if (in_array($route, $type['routes'] ?? [], true)) {
            $ways[] = ['way' => 'routes de user-types.php', 'detail' => 'el tipo la trae en routes', 'at' => self::USER_TYPES_FILE];
        }
        if (($role['all'] ?? false) === true) {
            $ways[] = ['way' => 'all', 'detail' => 'el rol tiene todas las rutas'];
        }
        return ['granted' => $ways !== [], 'ways' => $ways];
    }

    /**
     * Lo que estrecha una ruta tras concederla: la sesión, el `_allowedRoute()` del controlador y la constante del módulo.
     *
     * @param string $route
     * @return array<string,mixed>
     */
    public static function narrowings(string $route): array
    {
        $registry = get_routes();
        $entry = is_array($registry) ? ($registry[$route] ?? []) : [];
        $controller = $entry['controller'] ?? null;
        $class = is_string($controller) ? preg_replace('/:{1,2}[^:]+$/', '', $controller) : null;
        $allowedRoute = null;
        if (is_string($class) && class_exists($class) && method_exists($class, '_allowedRoute')) {
            $method = new \ReflectionMethod($class, '_allowedRoute');
            $owner = $method->getDeclaringClass()->getName();
            $file = TokenIndex::get()->relative((string) $method->getFileName());
            $key = $owner . '::_allowedRoute';
            $known = self::routeOverrides()[$key] ?? null;
            $allowedRoute = [
                'method' => $key,
                'at' => $file !== null ? $file . ':' . $method->getStartLine() : null,
                'decides' => $known ?? 'la plantilla del trait: no decide nada propio',
            ];
        }
        $located = RouteDeclarations::get()->located($route);
        return [
            'requireLogin' => (bool) ($entry['require_login'] ?? false),
            'controller' => is_string($controller) ? $controller : (is_object($controller) ? 'una función anónima' : null),
            'allowedRoute' => $allowedRoute,
            'module' => $located !== null ? self::moduleOf($located['file']) : null,
        ];
    }

    /**
     * Lo que decide cada sobreescritura conocida: las del framework y, en un clon, las de su línea base («route_overrides»).
     *
     * @return array<string,string>
     */
    private static function routeOverrides(): array
    {
        $clone = (new \ReflectionMethod(VerifyIntegrityTask::class, 'cloneBaseline'))->invoke(null);
        $cloneOverrides = is_array($clone) && is_array($clone['overrides'] ?? null) ? $clone['overrides'] : [];
        return VerifyIntegrityTask::KNOWN_ROUTE_OVERRIDES + array_map('strval', $cloneOverrides);
    }

    /**
     * Si la ruta está en la lista de rutas del segmento (`baseInitialSegmentedPermissions` de roles.php).
     *
     * @param string $route
     * @param string $segment
     * @return bool
     */
    public static function inSegment(string $route, string $segment): bool
    {
        $roles = get_config('roles');
        $segments = is_array($roles) ? ($roles['baseInitialSegmentedPermissions'] ?? []) : [];
        return is_array($segments) && in_array($route, (array) ($segments[$segment] ?? []), true);
    }

    /**
     * El segmento, sus rutas propias y de dónde sale el segmento de un tipo, leídos de UserTypes.
     *
     * @param int $userType
     * @return array{permissions:string,routes:string[],segmentFrom:string,protected:bool}|null
     */
    public static function typeState(int $userType): ?array
    {
        if (array_key_exists($userType, self::$typeStates)) {
            return self::$typeStates[$userType];
        }
        return self::$typeStates[$userType] = self::readTypeState($userType);
    }

    /**
     * @param int $userType
     * @return array{permissions:string,routes:string[],segmentFrom:string,protected:bool}|null
     */
    private static function readTypeState(int $userType): ?array
    {
        UserTypes::codes();
        $state = (new \ReflectionProperty(UserTypes::class, 'state'))->getValue();
        $type = is_array($state) ? ($state['types'][$userType] ?? null) : null;
        if (!is_array($type)) {
            return null;
        }
        $factory = (new \ReflectionMethod(UserTypes::class, 'factory'))->invoke(null);
        $fromFactory = is_array($factory) && isset($factory[$userType]) && ($factory[$userType]['permissions'] ?? null) === $type['permissions'];
        return [
            'permissions' => (string) $type['permissions'],
            'routes' => array_values(array_map('strval', (array) $type['routes'])),
            'segmentFrom' => $fromFactory ? 'fábrica: ' . self::factorySegmentLine() : self::USER_TYPES_FILE,
            'protected' => UserTypes::isProtected($userType),
        ];
    }

    /**
     * Dónde aparece cada nombre de ruta en las listas de roles.php y en qué sección.
     *
     * @return array<string,array<int,array{list:string,line:int,section:string}>>
     */
    public static function rolesFileEntries(): array
    {
        if (self::$rolesFileEntries !== null) {
            return self::$rolesFileEntries;
        }
        $entries = [];
        $data = TokenIndex::get()->file(self::ROLES_FILE);
        if ($data !== null) {
            $section = 'Del framework';
            $list = null;
            $depth = 0;
            foreach ($data['tokens'] as $index => $token) {
                if ($token->is(T_COMMENT)) {
                    if (str_contains($token->text, 'Del clon')) {
                        $section = 'Del clon';
                    } elseif (str_contains($token->text, 'Del framework')) {
                        $section = 'Del framework';
                    }
                    continue;
                }
                if ($token->is(T_VARIABLE) && $depth === 0) {
                    $next = TokenIndex::get()->next($data['tokens'], $index);
                    $list = $next !== null && $data['tokens'][$next]->text === '=' ? $token->text : $list;
                    continue;
                }
                if (in_array($token->text, ['(', '['], true)) {
                    $depth++;
                } elseif (in_array($token->text, [')', ']'], true)) {
                    $depth--;
                } elseif ($token->is(T_CONSTANT_ENCAPSED_STRING) && in_array($list, self::SEGMENT_LISTS['superiors'], true) && $depth > 0) {
                    $name = mb_substr($token->text, 1, -1);
                    $entries[$name][] = ['list' => $list, 'line' => $token->line, 'section' => $section];
                }
            }
        }
        return self::$rolesFileEntries = $entries;
    }

    /**
     * Para las suites: olvida lo leído de roles.php.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$rolesFileEntries = null;
        self::$typeStates = [];
    }

    /**
     * La línea de UserTypes donde se fija el segmento de fábrica de cada tipo.
     *
     * @return string
     */
    private static function factorySegmentLine(): string
    {
        $file = TokenIndex::get()->relative((string) (new \ReflectionClass(UserTypes::class))->getFileName());
        $data = $file !== null ? TokenIndex::get()->file($file) : null;
        foreach ($data['tokens'] ?? [] as $index => $token) {
            if ($token->is(T_CONSTANT_ENCAPSED_STRING) && $token->text === "'permissions'") {
                $arrow = TokenIndex::get()->next($data['tokens'], $index);
                $match = $arrow !== null ? TokenIndex::get()->next($data['tokens'], $arrow) : null;
                if ($match !== null && $data['tokens'][$match]->is(T_MATCH)) {
                    return $file . ':' . $token->line;
                }
            }
        }
        return (string) $file;
    }

    /**
     * La clase *Routes con ENABLE más cercana al archivo de la declaración, subiendo carpetas dentro de app/classes.
     *
     * @param string $file
     * @return array{class:string,constant:string,value:mixed,at:?string}|null
     */
    private static function moduleOf(string $file): ?array
    {
        $directory = dirname($file);
        $index = TokenIndex::get();
        while (str_starts_with($directory, 'app/classes/') && $directory !== 'app/classes') {
            $candidates = glob(rtrim(str_replace('\\', '/', basepath('')), '/') . '/' . $directory . '/*Routes.php') ?: [];
            foreach ($candidates as $candidate) {
                $relative = $index->relative($candidate);
                $data = $relative !== null ? $index->file($relative) : null;
                $class = $data['classes'][0]['name'] ?? null;
                if ($class !== null && class_exists($class) && defined($class . '::ENABLE')) {
                    $declared = $index->constant($class . '::ENABLE');
                    return ['class' => $class, 'constant' => $class . '::ENABLE', 'value' => constant($class . '::ENABLE'),
                        'at' => $declared !== null ? $declared['file'] . ':' . $declared['line'] : null];
                }
            }
            $directory = dirname($directory);
        }
        return null;
    }
}
