<?php

/**
 * ProjectStart.php
 */

namespace PiecesPHP\Core;

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\UserSystem\Controllers\UsersController;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

/**
 * ProjectStart - El arranque del proyecto: `public_area`, `admin_path`, `login_redirect_route` y el banco de
 * redirecciones `legacy_redirects` de config.php (P399).
 *
 * De fábrica, todo como antes: un config.php sin estas claves recibe las de DEFAULTS en bootstrap.php, y un valor que
 * no es del tipo esperado se anota y se sustituye por el de fábrica.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ProjectStart
{

    /**
     * Lo que vale cada clave cuando config.php no la trae (un clon de antes). `login_redirect_route` no va: sin ella
     * manda `admin_url`, si el clon lo puso.
     */
    const DEFAULTS = [
        'public_area' => true,
        'admin_path' => 'admin',
    ];

    const MODE_FULL = 'full';
    const MODE_HEADLESS = 'headless';
    const MODE_CLOSED = 'closed';

    /**
     * El nombre de la ruta del panel.
     */
    const PANEL_ROUTE = 'admin';

    /**
     * Los comodines de la zona pública: casan con casi cualquier dirección y responden 404 si no hay vista; para el banco
     * de redirecciones no reclaman la dirección.
     */
    const CATCH_ALL_ROUTES = ['public-generic', 'public-generic-2'];

    /**
     * La configuración leída de config.php y roles.php, con las claves del arranque que falten.
     *
     * @param array<string,mixed> $config
     * @return array<string,mixed>
     */
    public static function withDefaults(array $config): array
    {
        foreach (self::DEFAULTS as $name => $value) {
            if (!isset($config[$name])) {
                $config[$name] = $value;
            }
        }
        return $config;
    }

    //──── Validación ────────────────────────────────────────────────────────────────────────

    /**
     * El modo de la zona pública: true es 'full', 'headless' es 'headless' y false es 'closed'.
     *
     * @param mixed $value
     * @return 'full'|'headless'|'closed'|null Null si no es ninguno de los tres.
     */
    public static function publicAreaValue(mixed $value): ?string
    {
        return match (true) {
            $value === true => self::MODE_FULL,
            $value === 'headless' => self::MODE_HEADLESS,
            $value === false => self::MODE_CLOSED,
            default => null,
        };
    }

    /**
     * Un segmento de minúsculas, dígitos y guiones, sin barras. '' (el panel en la raíz) no vale: no está soportado.
     *
     * @param mixed $value
     * @return string|null Null si no vale.
     */
    public static function adminPathValue(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[a-z0-9-]+$/D', $value) === 1 ? $value : null;
    }

    /**
     * Un nombre de ruta: una cadena no vacía y sin espacios. Si existe se comprueba al usarlo.
     *
     * @param mixed $value
     * @return string|null Null si no vale.
     */
    public static function loginRouteValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' && preg_match('/\s/uD', $value) !== 1 ? $value : null;
    }

    //──── Lectura ───────────────────────────────────────────────────────────────────────────

    /**
     * El modo de la zona pública. Un valor que no es true, 'headless' ni false: en local lanza; fuera, lo anota y usa true.
     *
     * @return 'full'|'headless'|'closed'
     * @throws \RuntimeException
     */
    public static function publicAreaMode(): string
    {
        $raw = get_config('public_area');
        $value = self::publicAreaValue($raw);
        if ($value === null) {
            $message = "\$config['public_area'] tiene que ser true, 'headless' o false.";
            if (is_local()) {
                throw new \RuntimeException($message);
            }
            log_soft_error($message . ' Mientras tanto, se usa true.', ['valor' => is_scalar($raw) ? (string) $raw : get_debug_type($raw)]);
            return self::MODE_FULL;
        }
        return $value;
    }

    /**
     * Si se registran las páginas públicas HTML (las del framework, el sitio por marcas y las de los módulos): solo con true.
     *
     * @return bool
     */
    public static function publicPages(): bool
    {
        return self::publicAreaMode() === self::MODE_FULL;
    }

    /**
     * Si se registran las rutas públicas de datos de los módulos (JSON, formularios de máquina): con true y con 'headless'.
     *
     * @return bool
     */
    public static function publicData(): bool
    {
        return self::publicAreaMode() !== self::MODE_CLOSED;
    }

    /**
     * El segmento del panel. '' (la raíz) aún no está soportado: sus rutas chocan con las públicas y tumbarían todas las
     * peticiones. En local lanza; fuera, lo anota y usa el de fábrica.
     *
     * @return string
     * @throws \RuntimeException
     */
    public static function adminPath(): string
    {
        $raw = get_config('admin_path');
        if ($raw === '') {
            $message = "El panel en la raíz (admin_path vacío) aún no está soportado: usa 'admin' u otro nombre en \$config['admin_path'].";
            if (is_local()) {
                throw new \RuntimeException($message);
            }
            log_soft_error($message . " Mientras tanto, el panel sigue en /admin.");
            return self::DEFAULTS['admin_path'];
        }
        $value = self::adminPathValue($raw);
        if ($value === null) {
            log_soft_error("\$config['admin_path'] tiene que ser un segmento de minúsculas, dígitos y guiones, sin barras: se usa 'admin'.", ['valor' => is_scalar($raw) ? (string) $raw : get_debug_type($raw)]);
            return self::DEFAULTS['admin_path'];
        }
        return $value;
    }

    /**
     * El prefijo de los grupos del panel.
     *
     * @param string $langPrefix
     * @param string $adminPath
     * @return string
     */
    public static function adminPrefix(string $langPrefix, string $adminPath): string
    {
        return $langPrefix . '/' . $adminPath;
    }

    //──── Adónde lleva el acceso ────────────────────────────────────────────────────────────

    /**
     * Adónde va quien ya tiene sesión y pide el formulario de acceso: `next`, luego `login_redirect_route` si config.php
     * la declara, luego `admin_url` de un clon de antes (hasta la 11.0.0), y si no, el panel.
     *
     * @param string|null $nextURL Ya validada (UsersController::safeNextURL()).
     * @param mixed $loginRoute get_config('login_redirect_route'): false si config.php no la declara.
     * @param mixed $adminURL get_config('admin_url').
     * @param bool $publicPages Sin páginas públicas, public-index lleva al acceso: como destino sería un bucle.
     * @return array{kind:'next'|'route'|'admin_url'|'panel',value:string,relative:bool}
     */
    public static function loginTarget(?string $nextURL, mixed $loginRoute, mixed $adminURL, bool $publicPages = true): array
    {
        if ($nextURL !== null) {
            return ['kind' => 'next', 'value' => $nextURL, 'relative' => false];
        }
        if ($loginRoute !== false) {
            $route = self::loginRouteValue($loginRoute);
            if ($route === 'users-form-login' || ($route === 'public-index' && !$publicPages)) {
                return ['kind' => 'panel', 'value' => 'loop', 'relative' => false];
            }
            return $route !== null ? ['kind' => 'route', 'value' => $route, 'relative' => false] : ['kind' => 'panel', 'value' => 'invalid', 'relative' => false];
        }
        $url = is_array($adminURL) ? ($adminURL['url'] ?? '') : '';
        if (is_string($url) && $url !== '') {
            $relative = $adminURL['relative'] ?? true;
            return ['kind' => 'admin_url', 'value' => $url, 'relative' => !is_bool($relative) || $relative];
        }
        return ['kind' => 'panel', 'value' => '', 'relative' => false];
    }

    /**
     * La URL de loginTarget() con la configuración cargada. `next` se valida aquí también (safeNextURL() es idempotente).
     *
     * @param string|null $nextURL
     * @return string
     */
    public static function afterLoginURL(?string $nextURL): string
    {
        $nextURL = UsersController::safeNextURL($nextURL);
        $target = self::loginTarget($nextURL, get_config('login_redirect_route'), get_config('admin_url'), self::publicPages());
        $url = null;
        if ($target['kind'] === 'next') {
            return $target['value'];
        } elseif ($target['kind'] === 'admin_url') {
            $url = $target['relative'] ? baseurl($target['value']) : $target['value'];
        } elseif ($target['kind'] === 'route' && $target['value'] !== self::PANEL_ROUTE) {
            $url = get_route($target['value'], [], true);
            if (!is_string($url) || $url === '') {
                log_soft_error("\$config['login_redirect_route'] = '{$target['value']}' no es una ruta registrada sin parámetros obligatorios: el acceso lleva al panel.");
                $url = null;
            }
        } elseif ($target['kind'] === 'panel' && $target['value'] === 'invalid') {
            log_soft_error("\$config['login_redirect_route'] tiene que ser el nombre de una ruta: el acceso lleva al panel.");
        } elseif ($target['kind'] === 'panel' && $target['value'] === 'loop') {
            log_soft_error("\$config['login_redirect_route'] apunta al propio acceso (o a la portada sin zona pública), que haría un bucle: el acceso lleva al panel.");
        }
        $url ??= AdminPanelController::routeName('');
        return convert_lang_url($url, get_config('default_lang'), get_config('app_lang'));
    }

    //──── El banco de redirecciones (en desuso desde que nace: se retira en la 12.0.0) ──────

    /**
     * Qué dice el banco de una dirección (sin la base de la instalación ni el idioma): la dirección exacta da un nombre
     * de ruta; si no, el prefijo viejo más largo que case da la dirección nueva.
     *
     * @param string $path
     * @param mixed $config get_config('legacy_redirects').
     * @return array{kind:'route'|'path',value:string}|null
     */
    public static function legacyRedirectMatch(string $path, mixed $config): ?array
    {
        $rules = self::legacyRedirectRules($config);
        $path = self::normalizePath($path);
        if (isset($rules['paths'][$path])) {
            return ['kind' => 'route', 'value' => $rules['paths'][$path]];
        }
        foreach ($rules['prefixes'] as $old => $new) {
            if (self::underPrefix($path, $old)) {
                return ['kind' => 'path', 'value' => self::normalizePath($new . substr($path, strlen($old)))];
            }
        }
        return null;
    }

    /**
     * Las reglas del banco, normalizadas. Un prefijo cuyo destino cae bajo algún prefijo viejo (también el suyo) haría
     * un bucle o una cadena sin fin: se descarta y va a `rejected`.
     *
     * @param mixed $config
     * @return array{paths:array<string,string>,prefixes:array<string,string>,rejected:string[]}
     */
    public static function legacyRedirectRules(mixed $config): array
    {
        $rules = ['paths' => [], 'prefixes' => [], 'rejected' => []];
        if (!self::legacyRedirectsEnabled($config) || !is_array($config)) {
            return $rules;
        }
        foreach (is_array($config['paths'] ?? null) ? $config['paths'] : [] as $old => $routeName) {
            if (is_string($old) && is_string($routeName) && $routeName !== '') {
                $rules['paths'][self::normalizePath($old)] = $routeName;
            }
        }
        $prefixes = [];
        foreach (is_array($config['prefixes'] ?? null) ? $config['prefixes'] : [] as $old => $new) {
            if (is_string($old) && is_string($new) && self::normalizePath($old) !== '/') {
                $prefixes[self::normalizePath($old)] = self::normalizePath($new);
            }
        }
        foreach ($prefixes as $old => $new) {
            foreach (array_keys($prefixes) as $anyOld) {
                if (self::underPrefix($new, $anyOld)) {
                    $rules['rejected'][] = "«{$old}» => «{$new}» (el destino vuelve a caer en «{$anyOld}»)";
                    continue 2;
                }
            }
            $rules['prefixes'][$old] = $new;
        }
        uksort($rules['prefixes'], fn(string $a, string $b): int => strlen($b) <=> strlen($a));
        return $rules;
    }

    /**
     * @param string $path Normalizada.
     * @param string $prefix Normalizado.
     * @return bool
     */
    private static function underPrefix(string $path, string $prefix): bool
    {
        return $path === $prefix || str_starts_with($path, $prefix . '/');
    }

    /**
     * @param mixed $config get_config('legacy_redirects'): false en un config.php sin la clave.
     * @return bool
     */
    public static function legacyRedirectsEnabled(mixed $config): bool
    {
        return is_array($config) && ($config['enabled'] ?? false) === true;
    }

    /**
     * El 301 del banco, o null si está apagado, si la petición no es GET/HEAD o si una ruta reclama la dirección.
     *
     * @param RequestRoute $request La del 404.
     * @return ResponseRoute|null
     */
    public static function legacyRedirectResponse(RequestRoute $request): ?ResponseRoute
    {
        $config = get_config('legacy_redirects');
        if (!self::legacyRedirectsEnabled($config) || TerminalData::getInstance()->isTerminal()) {
            return null;
        }
        return self::legacyRedirectFor($request, $config);
    }

    /**
     * legacyRedirectResponse() con la configuración dada, también desde la terminal.
     *
     * @param RequestRoute $request
     * @param mixed $config
     * @return ResponseRoute|null
     */
    public static function legacyRedirectFor(RequestRoute $request, mixed $config): ?ResponseRoute
    {
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true) || self::routeClaims($request)) {
            return null;
        }
        $langPrefix = get_config('prefix_lang');
        $langPrefix = is_string($langPrefix) ? $langPrefix : '';
        $path = self::pathWithoutBase($request->getUri()->getPath(), appbase(), $langPrefix);
        foreach (self::legacyRedirectRules($config)['rejected'] as $rejected) {
            log_soft_error("\$config['legacy_redirects']: se descarta el prefijo {$rejected}: haría un bucle.");
        }
        $match = self::legacyRedirectMatch($path, $config);
        if ($match === null) {
            return null;
        }
        if ($match['kind'] === 'route') {
            $url = get_route($match['value'], [], true);
            if (!is_string($url) || $url === '') {
                log_soft_error("\$config['legacy_redirects']: «{$path}» lleva a la ruta «{$match['value']}», que no existe o pide parámetros: 404.");
                return null;
            }
            //La dirección de la ruta no puede volver a casar con el banco: sería otra vuelta.
            $target = self::pathWithoutBase((string) parse_url($url, \PHP_URL_PATH), appbase(), $langPrefix);
            if (self::legacyRedirectMatch($target, $config) !== null) {
                log_soft_error("\$config['legacy_redirects']: «{$path}» lleva a la ruta «{$match['value']}», cuya dirección vuelve a casar con el banco: 404.");
                return null;
            }
        } else {
            $url = baseurl(ltrim($langPrefix . $match['value'], '/'));
        }
        $query = $request->getUri()->getQuery();
        $url .= $query !== '' ? (str_contains($url, '?') ? '&' : '?') . $query : '';
        //Se retira en la 12.0.0: el aviso pide cambiar el enlace de origen.
        GenericHandler::logSoft(new \ErrorException(self::legacyRedirectNotice($path, $match), 0, \E_USER_DEPRECATED, __FILE__, __LINE__), 'E_USER_DEPRECATED');
        return (new ResponseRoute())->withRedirect($url, 301);
    }

    /**
     * El aviso de desuso: la dirección vieja y el destino (nombre de ruta o dirección), nunca la consulta (ADR 0060 §6).
     *
     * @param string $path
     * @param array{kind:'route'|'path',value:string} $match
     * @return string
     */
    public static function legacyRedirectNotice(string $path, array $match): string
    {
        $destination = $match['kind'] === 'route' ? "la ruta «{$match['value']}»" : "«{$match['value']}»";
        return "\$config['legacy_redirects'] está en desuso y se retira en la 12.0.0: «{$path}» redirige a {$destination}; cambia el enlace de origen.";
    }

    /**
     * Si una ruta registrada reclama la dirección de la petición: casa (aunque sea con otro método), y no es un comodín.
     * Sin el resultado del enrutado, se supone que sí: el banco no actúa a ciegas.
     *
     * @param RequestRoute $request
     * @return bool
     */
    public static function routeClaims(RequestRoute $request): bool
    {
        try {
            $context = RouteContext::fromRequest($request);
        } catch (\Throwable) {
            return true;
        }
        $status = $context->getRoutingResults()->getRouteStatus();
        if ($status === RoutingResults::NOT_FOUND) {
            return false;
        }
        $route = $context->getRoute();
        return !($status === RoutingResults::FOUND && $route !== null && in_array($route->getName(), self::CATCH_ALL_ROUTES, true));
    }

    /**
     * La dirección de la petición sin la base de la instalación ni el prefijo de idioma.
     *
     * @param string $uriPath
     * @param string $appBase
     * @param string $langPrefix
     * @return string
     */
    public static function pathWithoutBase(string $uriPath, string $appBase, string $langPrefix): string
    {
        $path = self::normalizePath($uriPath);
        $base = self::normalizePath($appBase);
        if ($base !== '/' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = self::normalizePath(substr($path, strlen($base)));
        }
        $lang = $langPrefix !== '' ? self::normalizePath($langPrefix) : '';
        if ($lang !== '' && $lang !== '/' && ($path === $lang || str_starts_with($path, $lang . '/'))) {
            $path = self::normalizePath(substr($path, strlen($lang)));
        }
        return $path;
    }

    /**
     * Con barra inicial, sin barra final ni barras repetidas: «admin//x/» → «/admin/x».
     *
     * @param string $path
     * @return string
     */
    private static function normalizePath(string $path): string
    {
        return '/' . trim((string) preg_replace('#/+#D', '/', $path), '/');
    }
}
