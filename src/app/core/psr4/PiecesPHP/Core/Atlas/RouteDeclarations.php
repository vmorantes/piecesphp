<?php

/**
 * RouteDeclarations.php
 */

namespace PiecesPHP\Core\Atlas;

use PiecesPHP\Core\Roles;

/**
 * RouteDeclarations - Une cada ruta registrada con su `new Route(` en el código (ADR 0091, R1).
 *
 * Tres vías, en este orden: por el nombre evaluado; por el controlador evaluado; por el método del controlador y el
 * final literal del patrón. Toda unión se verifica por valor: los roles de la declaración, evaluados y filtrados con
 * Roles::roleExists() como hace register_route(), tienen que ser los registrados. La unión por nombre exacto vale sin
 * esa verificación solo si los roles no se pudieron evaluar (y lo dice); una por las otras vías, nunca.
 *
 * @package     PiecesPHP\Core\Atlas
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class RouteDeclarations
{
    /**
     * @var array<string,array{file:string,line:int,class:?string,via:string,verified:bool,roles:array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string},declaredRoles:bool}>
     */
    private array $located = [];

    /**
     * @var array<string,string> nombre => motivo
     */
    private array $notLocated = [];

    /**
     * @var array<string,array<string,mixed>>
     */
    private array $registry;

    /**
     * @var self|null
     */
    private static ?self $instance = null;

    private function __construct()
    {
        $registry = get_routes();
        $this->registry = is_array($registry) ? $registry : [];
        $index = TokenIndex::get();
        $claimed = [];
        $mismatched = [];
        foreach ($index->routes() as $declaration) {
            $result = $this->join($index, $declaration);
            if ($result === null) {
                continue;
            }
            [$name, $entry] = $result;
            if ($entry === null) {
                $claimed[$name] ??= 'la declaración de ' . $declaration['file'] . ':' . $declaration['line'] . ' no cuadra por valor con la registrada';
                $mismatched[$name] = $declaration['file'] . ':' . $declaration['line'];
                continue;
            }
            if (isset($this->located[$name])) {
                $this->notLocated[$name] = 'dos declaraciones la reclaman: ' . $this->located[$name]['file'] . ':' . $this->located[$name]['line'] . ' y ' . $entry['file'] . ':' . $entry['line'];
                continue;
            }
            $this->located[$name] = $entry;
        }
        //Una que cuadra junto a otra que la reclama sin cuadrar: no se sabe cuál registra la ruta.
        foreach ($mismatched as $name => $at) {
            if (isset($this->located[$name])) {
                $this->notLocated[$name] = 'dos declaraciones la reclaman: ' . $this->located[$name]['file'] . ':' . $this->located[$name]['line'] . ' (cuadra) y ' . $at . ' (no cuadra por valor)';
            }
        }
        foreach ($this->notLocated as $name => $reason) {
            unset($this->located[$name]);
        }
        foreach (array_keys($this->registry) as $name) {
            if (!isset($this->located[$name]) && !isset($this->notLocated[$name])) {
                $this->notLocated[$name] = $claimed[$name] ?? 'ninguna declaración new Route( la produce de forma única';
            }
        }
        ksort($this->notLocated);
    }

    /**
     * @return self
     */
    public static function get(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * La declaración unida de una ruta, o null.
     *
     * @param string $name
     * @return array{file:string,line:int,class:?string,via:string,verified:bool,roles:array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string},declaredRoles:bool}|null
     */
    public function located(string $name): ?array
    {
        return $this->located[$name] ?? null;
    }

    /**
     * @return array<string,array{file:string,line:int,class:?string,via:string,verified:bool,roles:array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string},declaredRoles:bool}>
     */
    public function allLocated(): array
    {
        return $this->located;
    }

    /**
     * @return array<string,string> nombre => por qué no se localizó
     */
    public function notLocated(): array
    {
        return $this->notLocated;
    }

    /**
     * La cobertura: N de M localizadas; K con roles resueltos; J sin roles declarados; X no localizadas.
     *
     * @return array{located:int,total:int,resolvedRoles:int,withoutRoles:int,notLocated:string[]}
     */
    public function coverage(): array
    {
        $resolved = count(array_filter($this->located, fn(array $e): bool => $e['declaredRoles'] && $e['roles']['resolved']));
        $without = count(array_filter($this->located, fn(array $e): bool => !$e['declaredRoles']));
        return ['located' => count($this->located), 'total' => count($this->registry), 'resolvedRoles' => $resolved, 'withoutRoles' => $without, 'notLocated' => array_keys($this->notLocated)];
    }

    /**
     * Los roles de una lista evaluada, filtrados como register_route() (AppHelpers.php, Roles::roleExists) y ordenados.
     *
     * @param mixed $value
     * @return int[]|null null si no es una lista de códigos.
     */
    public static function filteredRoles(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }
        $codes = [];
        foreach ($value as $role) {
            if (!is_int($role) && !(is_string($role) && ctype_digit($role))) {
                return null;
            }
            if (Roles::roleExists((int) $role)) {
                $codes[] = (int) $role;
            }
        }
        $codes = array_values(array_unique($codes));
        sort($codes);
        return $codes;
    }

    /**
     * Une una declaración: [nombre, entrada] si cuadra; [nombre, null] si se unió pero no cuadra por valor; null si no
     * se pudo unir con ninguna ruta.
     *
     * @param TokenIndex $index
     * @param array{file:string,line:int,class:?string,arguments:array<string,array{start:int,end:int}>} $declaration
     * @return array{0:string,1:array{file:string,line:int,class:?string,via:string,verified:bool,roles:array{kind:string,text:string,resolved:bool,value:mixed,symbol:?string,at:array{file:string,line:int},reason:?string},declaredRoles:bool}|null}|null
     */
    private function join(TokenIndex $index, array $declaration): ?array
    {
        $file = $declaration['file'];
        $arguments = $declaration['arguments'];
        $evaluate = fn(string $argument): ?array => isset($arguments[$argument])
            ? Expression::evaluate($index, $file, $arguments[$argument]['start'], $arguments[$argument]['end']) : null;

        $declaredRoles = isset($arguments['rolesAllowed']);
        $roles = $evaluate('rolesAllowed') ?? ['kind' => 'sin', 'text' => '', 'resolved' => true, 'value' => [], 'symbol' => null, 'at' => ['file' => $file, 'line' => $declaration['line']], 'reason' => null];

        $candidates = [];
        $via = '';
        $name = $evaluate('name');
        if ($name !== null && $name['resolved'] && is_string($name['value']) && isset($this->registry[$name['value']])) {
            $candidates = [$name['value']];
            $via = 'nombre';
        }
        $controllerClass = null;
        if ($candidates === []) {
            $controller = $evaluate('controller');
            if ($controller !== null && $controller['resolved'] && is_string($controller['value'])) {
                $candidates = array_keys(array_filter($this->registry, fn(array $route): bool => ($route['controller'] ?? null) === $controller['value']));
                $via = 'controlador';
                $controllerClass = (string) preg_replace('/:{1,2}[^:]+$/', '', $controller['value']);
            }
        }
        if ($candidates === [] || count($candidates) > 1) {
            $byPattern = $this->byPattern($index, $file, $arguments, $candidates, $controllerClass);
            if ($byPattern !== null) {
                $candidates = [$byPattern];
                $via = 'patrón y método';
            }
        }
        if (count($candidates) !== 1) {
            return null;
        }
        $routeName = $candidates[0];
        $registered = self::filteredRoles($this->registry[$routeName]['roles_allowed'] ?? []);
        $verified = false;
        if ($roles['resolved']) {
            $evaluated = self::filteredRoles($roles['value']);
            if ($evaluated === null || $evaluated !== $registered) {
                return [$routeName, null];
            }
            $verified = true;
        } elseif ($via !== 'nombre') {
            return [$routeName, null];
        }
        return [$routeName, [
            'file' => $file,
            'line' => $declaration['line'],
            'class' => $declaration['class'],
            'via' => $via,
            'verified' => $verified,
            'roles' => $roles,
            'declaredRoles' => $declaredRoles,
        ]];
    }

    /**
     * Por el método del controlador y el final literal del patrón, entre los candidatos (o entre todas si no hay).
     *
     * @param TokenIndex $index
     * @param string $file
     * @param array<string,array{start:int,end:int}> $arguments
     * @param string[] $candidates
     * @param string|null $controllerClass Si el controlador se evaluó, solo rutas de esa clase.
     * @return string|null
     */
    private function byPattern(TokenIndex $index, string $file, array $arguments, array $candidates, ?string $controllerClass): ?string
    {
        $data = $index->file($file);
        if ($data === null || !isset($arguments['route'], $arguments['controller'])) {
            return null;
        }
        $raw = function (string $argument) use ($data, $arguments): string {
            $text = '';
            for ($i = $arguments[$argument]['start']; $i < $arguments[$argument]['end']; $i++) {
                $text .= $data['tokens'][$i]->text;
            }
            return trim($text);
        };
        $pattern = $raw('route');
        //El final literal: lo que queda tras la última interpolación o variable, sin comillas.
        if (preg_match('/([^}\'"$.]*)[\'"]\s*$/', $pattern, $match) !== 1 || trim($match[1]) === '') {
            return null;
        }
        $suffix = $match[1];
        if (preg_match('/:{1,2}(\w+)[\'"]\s*$/', $raw('controller'), $method) !== 1) {
            return null;
        }
        $httpMethod = null;
        if (isset($arguments['method'])) {
            $evaluated = Expression::evaluate($index, $file, $arguments['method']['start'], $arguments['method']['end']);
            $httpMethod = $evaluated['resolved'] && is_string($evaluated['value']) ? strtoupper($evaluated['value']) : null;
        }
        $pool = $candidates !== [] ? array_intersect_key($this->registry, array_flip($candidates)) : $this->registry;
        $found = [];
        foreach ($pool as $name => $route) {
            $controller = $route['controller'] ?? null;
            $routePattern = $route['route'] ?? null;
            if (!is_string($controller) || !is_string($routePattern)) {
                continue;
            }
            if (!str_ends_with($routePattern, $suffix) || preg_match('/:{1,2}' . preg_quote($method[1], '/') . '$/', $controller) !== 1) {
                continue;
            }
            if ($controllerClass !== null && preg_replace('/:{1,2}[^:]+$/', '', $controller) !== $controllerClass) {
                continue;
            }
            $registeredMethod = $route['method'] ?? null;
            $registeredMethod = is_array($registeredMethod) ? strtoupper(implode('|', $registeredMethod)) : strtoupper((string) $registeredMethod);
            if ($httpMethod !== null && $registeredMethod !== $httpMethod) {
                continue;
            }
            $found[] = (string) $name;
        }
        return count($found) === 1 ? $found[0] : null;
    }
}
