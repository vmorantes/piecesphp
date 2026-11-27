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
     * El aviso de una `login_redirect_route` que haría un bucle, por nombre, por dirección o por la dirección que da una
     * ruta con parámetros.
     */
    const LOGIN_LOOP_MESSAGE = "\$config['login_redirect_route'] apunta al propio acceso (o a la portada sin zona pública), por nombre o por dirección, que haría un bucle: el acceso lleva al panel.";

    /**
     * El aviso de una `login_redirect_route` cuya dirección no se pudo leer para compararla con la del acceso: sin
     * comparar no se puede descartar el bucle.
     */
    const LOGIN_UNREADABLE_MESSAGE = "\$config['login_redirect_route'] lleva a una dirección que no se pudo comparar con la del acceso: el acceso lleva al panel. Revisa base_url en config.php.";

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

    /**
     * Una dirección relativa a la instalación: empieza por «/» y no por «//», solo con caracteres ASCII visibles (una letra
     * con tilde va codificada) y sin «\\». Así nunca lleva a otro dominio: se resuelve con baseurl(). Ningún segmento del
     * camino (lo anterior a «?» o «#») puede ser exactamente «.» o «..», que lo sacarían de la instalación o esquivarían el
     * bucle, y el camino no lleva «%2e», «%2f» ni «%5c» en ninguna mayúscula: «%2e» lo resuelve el navegador como «.»;
     * «%2f» y «%5c» los decodifica Slim al enrutar (RouteResolver.php:42).
     *
     * @param mixed $value
     * @return string|null Null si no vale.
     */
    public static function loginRelativeValue(mixed $value): ?string
    {
        if (!is_string($value) || !str_starts_with($value, '/') || str_starts_with($value, '//')) {
            return null;
        }
        //Sin /u: un byte de fuera de ASCII (también el UTF-8 inválido) no casa y rechaza.
        if (preg_match('/^[\x21-\x7E]+$/D', $value) !== 1 || str_contains($value, '\\')) {
            return null;
        }
        $path = (string) preg_split('/[?#]/', $value, 2)[0];
        return preg_match('/%(2e|2f|5c)/i', $path) !== 0 || array_intersect(explode('/', $path), ['.', '..']) !== [] ? null : $value;
    }

    /**
     * Una ruta con parámetros: ['route' => nombre, 'params' => [clave => valor]] con params opcional, claves y valores
     * enteros o de texto hecho solo de [A-Za-z0-9._~-] y distinto de «.» y «..», y nada más en el arreglo: la ruta los
     * sustituye sin codificar. Un booleano, un flotante (también NAN e INF) o cualquier otro texto no valen.
     *
     * @param mixed $value
     * @return array{route:string,params:array<string,int|string>}|null Null si no tiene esa forma.
     */
    public static function loginRouteWithParams(mixed $value): ?array
    {
        if (!is_array($value) || array_diff(array_keys($value), ['route', 'params']) !== []) {
            return null;
        }
        $route = self::loginRouteValue($value['route'] ?? null);
        $params = $value['params'] ?? [];
        if ($route === null || !is_array($params)) {
            return null;
        }
        $clean = [];
        foreach ($params as $key => $param) {
            if (!is_string($key) || !(is_int($param) || (is_string($param) && $param !== '.' && $param !== '..' && preg_match('/^[A-Za-z0-9._~-]+$/D', $param) === 1))) {
                return null;
            }
            $clean[$key] = $param;
        }
        return ['route' => $route, 'params' => $clean];
    }

    /**
     * Las partes de una dirección con forma de dirección completa: http o https, con host. Sin juzgar si vale (usuario y
     * contraseña, bucle): eso lo decide loginTarget().
     *
     * @param mixed $value
     * @return array{scheme:string,host:string,port?:int,user?:string,pass?:string,path?:string,query?:string,fragment?:string}|null
     */
    public static function absoluteURLParts(mixed $value): ?array
    {
        if (!is_string($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        $parts = parse_url($value);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || $parts['host'] === '' || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }
        return $parts;
    }

    /**
     * Una dirección completa que vale para `login_redirect_route` (P413 2.5, P416): http o https, con host y sin usuario
     * ni contraseña (una así parece suplantación). Puede ser de otro dominio; verify-integrity avisa si no está en
     * `allowed_hosts` (loginRedirectHostWarning()). El bucle lo mira loginTarget(), que conoce las rutas.
     *
     * @param mixed $value
     * @return string|null Null si no es una dirección completa o lleva usuario o contraseña.
     */
    public static function loginRedirectURLValue(mixed $value): ?string
    {
        $parts = self::absoluteURLParts($value);
        return $parts !== null && !isset($parts['user']) && !isset($parts['pass']) && is_string($value) ? $value : null;
    }

    /**
     * Una dirección reducida a lo que decide adónde lleva, normalizada como la resuelven el navegador y Slim:
     * «//host[:puerto]/camino», sin esquema (http y https del mismo host casan), sin el puerto por omisión, con «\\» como
     * «/», el camino decodificado y sin segmentos de punto (RFC 3986 §5.2.4), sin query, sin fragmento, sin barra final y
     * sin el segmento de idioma que sigue a la base de la instalación. No usa FILTER_VALIDATE_URL, que rechaza hosts
     * válidos para servir (con «_»): solo pide esquema http o https y host.
     *
     * @param string $url
     * @param string $basePath El camino de baseurl() (por ejemplo «/vicsen/piecesphp/src»).
     * @param string[] $langs Los idiomas permitidos.
     * @return string|null Null si no se puede leer como dirección http o https con host.
     */
    public static function comparableURL(string $url, string $basePath, array $langs): ?string
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || $parts['host'] === '' || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }
        $scheme = strtolower($parts['scheme']);
        $base = rtrim(self::removeDotSegments(rawurldecode(str_replace('\\', '/', $basePath))), '/');
        //Decodificado: Slim decodifica el camino al enrutar, así que «/users/log%69n» es el acceso.
        $path = rtrim(self::removeDotSegments(rawurldecode(str_replace('\\', '/', $parts['path'] ?? ''))), '/');
        if ($base === '' || $path === $base || str_starts_with($path, $base . '/')) {
            $rest = explode('/', ltrim(substr($path, strlen($base)), '/'));
            if ($rest !== [] && in_array(strtolower($rest[0]), array_map('strtolower', $langs), true)) {
                array_shift($rest);
            }
            $path = rtrim($base . '/' . implode('/', $rest), '/');
        }
        $defaultPort = $scheme === 'https' ? 443 : 80;
        $port = isset($parts['port']) && $parts['port'] !== $defaultPort ? ':' . $parts['port'] : '';
        return '//' . strtolower($parts['host']) . $port . $path;
    }

    /**
     * Quita los segmentos «.» y «..» de un camino, con el algoritmo de RFC 3986 §5.2.4: «/a/b/c/./../../g» da «/a/g» y
     * «mid/content=5/../6» da «mid/6». Un «..» de más no sube por encima de la raíz.
     *
     * @param string $path
     * @return string
     */
    private static function removeDotSegments(string $path): string
    {
        $output = '';
        $dropLast = function (string $output): string {
            $position = strrpos($output, '/');
            return $position === false ? '' : substr($output, 0, $position);
        };
        while ($path !== '') {
            if (str_starts_with($path, '../')) {
                $path = substr($path, 3);
            } elseif (str_starts_with($path, './')) {
                $path = substr($path, 2);
            } elseif (str_starts_with($path, '/./')) {
                $path = '/' . substr($path, 3);
            } elseif ($path === '/.') {
                $path = '/';
            } elseif (str_starts_with($path, '/../')) {
                $path = '/' . substr($path, 4);
                $output = $dropLast($output);
            } elseif ($path === '/..') {
                $path = '/';
                $output = $dropLast($output);
            } elseif ($path === '.' || $path === '..') {
                $path = '';
            } else {
                $next = strpos($path, '/', str_starts_with($path, '/') ? 1 : 0);
                $output .= $next === false ? $path : substr($path, 0, $next);
                $path = $next === false ? '' : substr($path, $next);
            }
        }
        return $output;
    }

    /**
     * Las direcciones (en forma comparableURL()) a las que llevar tras el acceso haría un bucle: la del formulario de
     * acceso y, sin páginas públicas, la portada.
     *
     * @return string[]
     */
    public static function loginLoopURLs(): array
    {
        $basePath = (string) parse_url(baseurl(), PHP_URL_PATH);
        $langs = Config::get_allowed_langs();
        $urls = [UsersController::routeName('form-login', [], true)];
        if (!self::publicPages()) {
            $urls[] = \App\Controller\PublicAreaController::routeName('index', [], true);
        }
        $comparable = [];
        foreach ($urls as $url) {
            $url = is_string($url) ? self::comparableURL($url, $basePath, $langs) : null;
            if ($url !== null) {
                $comparable[] = $url;
            }
        }
        return $comparable;
    }

    /**
     * El aviso de verify-integrity para una `login_redirect_route` que es una dirección de un dominio fuera de
     * `allowed_hosts`, o que no se pudo comprobar porque la lista está vacía. Avisa, no falla (P416).
     *
     * También cuando la dirección no se usa: lleva usuario o contraseña, o haría un bucle.
     *
     * @param mixed $loginRoute get_config('login_redirect_route').
     * @param mixed $allowedHosts get_config('allowed_hosts').
     * @param bool $inferWww get_config('allowed_hosts_infer_www').
     * @param string[] $loopURLs loginLoopURLs().
     * @param string $basePath El camino de baseurl().
     * @param string[] $langs Los idiomas permitidos.
     * @return string|null Null si no es una dirección o su dominio está en la lista.
     */
    public static function loginRedirectHostWarning(mixed $loginRoute, mixed $allowedHosts, bool $inferWww, array $loopURLs = [], string $basePath = '', array $langs = []): ?string
    {
        $parts = self::absoluteURLParts($loginRoute);
        if ($parts === null || !is_string($loginRoute)) {
            return null;
        }
        if (isset($parts['user']) || isset($parts['pass'])) {
            return "\$config['login_redirect_route'] es una dirección con usuario o contraseña, y no se usa: el acceso lleva al panel. Quítale el usuario y la contraseña, o usa el nombre de una ruta.";
        }
        if (in_array(self::comparableURL($loginRoute, $basePath, $langs), $loopURLs, true)) {
            return "\$config['login_redirect_route'] es la dirección del propio acceso (o de la portada sin zona pública), que haría un bucle, y no se usa: el acceso lleva al panel. Pon otra dirección o el nombre de una ruta.";
        }
        $url = $loginRoute;
        $host = (string) parse_url($url, PHP_URL_HOST);
        $allowed = is_array($allowedHosts) ? array_values(array_filter($allowedHosts, fn (mixed $entry): bool => is_string($entry) && trim($entry) !== '')) : [];
        if ($allowed === []) {
            return "\$config['login_redirect_route'] lleva al acceso a {$url}, y con \$config['allowed_hosts'] vacía no se pudo comprobar si {$host} es de confianza: pon tus dominios en allowed_hosts (src/app/config/config.php) o usa el nombre de una ruta.";
        }
        if (AllowedHosts::allows($host, $allowed, $inferWww)) {
            return null;
        }
        return "\$config['login_redirect_route'] lleva al acceso a {$url}, y {$host} no está en \$config['allowed_hosts']: si es tuyo, añádelo a allowed_hosts (src/app/config/config.php); si no, usa el nombre de una ruta.";
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
     * la declara, luego `admin_url` de un clon de antes (hasta la 11.0.0), y si no, el panel. `login_redirect_route`
     * admite cuatro formas: un nombre de ruta ('route'); una ruta con parámetros, ['route' => …, 'params' => […]]
     * ('route' con 'params'); una dirección relativa a la instalación, «/camino» ('url' con 'relative'); y una dirección
     * completa http o https ('url'). Con kind 'panel', value dice por qué: '' (nada declarado), 'invalid' (sin forma
     * válida), 'loop' (haría un bucle) o 'unreadable' (su dirección no se pudo leer para descartar el bucle).
     *
     * @param string|null $nextURL Ya validada (UsersController::safeNextURL()).
     * @param mixed $loginRoute get_config('login_redirect_route'): false si config.php no la declara.
     * @param mixed $adminURL get_config('admin_url').
     * @param bool $publicPages Sin páginas públicas, public-index lleva al acceso: como destino sería un bucle.
     * @param string[] $loopURLs Las direcciones que harían bucle, en forma comparableURL() (loginLoopURLs()).
     * @param string $basePath El camino de baseurl(), para comparableURL().
     * @param string[] $langs Los idiomas permitidos, para comparableURL().
     * @param string $baseURL baseurl(): con él se resuelve una dirección relativa para medir el bucle.
     * @return array{kind:'next'|'route'|'url'|'admin_url'|'panel',value:string,relative:bool,params:array<string,int|string>}
     */
    public static function loginTarget(?string $nextURL, mixed $loginRoute, mixed $adminURL, bool $publicPages = true, array $loopURLs = [], string $basePath = '', array $langs = [], string $baseURL = ''): array
    {
        if ($nextURL !== null) {
            return ['kind' => 'next', 'value' => $nextURL, 'relative' => false, 'params' => []];
        }
        if ($loginRoute !== false) {
            //Antes que el nombre de ruta: una dirección no lleva espacios y loginRouteValue() la tomaría por un nombre.
            $parts = self::absoluteURLParts($loginRoute);
            if ($parts !== null && is_string($loginRoute)) {
                if (isset($parts['user']) || isset($parts['pass'])) {
                    return ['kind' => 'panel', 'value' => 'invalid', 'relative' => false, 'params' => []];
                }
                $comparable = self::comparableURL($loginRoute, $basePath, $langs);
                //Sin forma comparable no se puede descartar el bucle: falla cerrado.
                if ($comparable === null) {
                    return ['kind' => 'panel', 'value' => 'unreadable', 'relative' => false, 'params' => []];
                }
                if (in_array($comparable, $loopURLs, true)) {
                    return ['kind' => 'panel', 'value' => 'loop', 'relative' => false, 'params' => []];
                }
                return ['kind' => 'url', 'value' => $loginRoute, 'relative' => false, 'params' => []];
            }
            //La relativa, también antes que el nombre: «/x» no lleva espacios. El bucle se mide ya resuelta.
            $relative = self::loginRelativeValue($loginRoute);
            if ($relative !== null) {
                $resolved = rtrim($baseURL, '/') . '/' . ltrim($relative, '/');
                $comparable = $baseURL !== '' ? self::comparableURL($resolved, $basePath, $langs) : '';
                //Sin forma comparable (una base que no se puede leer) no se puede descartar el bucle: falla cerrado.
                if ($comparable === null) {
                    return ['kind' => 'panel', 'value' => 'unreadable', 'relative' => false, 'params' => []];
                }
                if ($baseURL !== '' && in_array($comparable, $loopURLs, true)) {
                    return ['kind' => 'panel', 'value' => 'loop', 'relative' => false, 'params' => []];
                }
                return ['kind' => 'url', 'value' => $relative, 'relative' => true, 'params' => []];
            }
            //Una cadena con «/» inicial es siempre una dirección: si la relativa no la aceptó, no se lee como nombre de ruta.
            if (is_string($loginRoute) && str_starts_with($loginRoute, '/')) {
                return ['kind' => 'panel', 'value' => 'invalid', 'relative' => false, 'params' => []];
            }
            $withParams = self::loginRouteWithParams($loginRoute);
            $route = $withParams !== null ? $withParams['route'] : self::loginRouteValue($loginRoute);
            if ($route === 'users-form-login' || ($route === 'public-index' && !$publicPages)) {
                return ['kind' => 'panel', 'value' => 'loop', 'relative' => false, 'params' => []];
            }
            return $route !== null ? ['kind' => 'route', 'value' => $route, 'relative' => false, 'params' => $withParams['params'] ?? []] : ['kind' => 'panel', 'value' => 'invalid', 'relative' => false, 'params' => []];
        }
        $url = is_array($adminURL) ? ($adminURL['url'] ?? '') : '';
        if (is_string($url) && $url !== '') {
            $relative = $adminURL['relative'] ?? true;
            return ['kind' => 'admin_url', 'value' => $url, 'relative' => !is_bool($relative) || $relative, 'params' => []];
        }
        return ['kind' => 'panel', 'value' => '', 'relative' => false, 'params' => []];
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
        $loginRoute = get_config('login_redirect_route');
        //Las direcciones del bucle piden rutas: solo se calculan si la clave es una dirección, completa o relativa.
        $loopURLs = self::absoluteURLParts($loginRoute) !== null || self::loginRelativeValue($loginRoute) !== null ? self::loginLoopURLs() : [];
        $target = self::loginTarget($nextURL, $loginRoute, get_config('admin_url'), self::publicPages(), $loopURLs, (string) parse_url(baseurl(), PHP_URL_PATH), Config::get_allowed_langs(), baseurl());
        $url = null;
        if ($target['kind'] === 'next' || ($target['kind'] === 'url' && !$target['relative'])) {
            return $target['value'];
        } elseif ($target['kind'] === 'url') {
            return baseurl(ltrim($target['value'], '/'));
        } elseif ($target['kind'] === 'admin_url') {
            $url = $target['relative'] ? baseurl($target['value']) : $target['value'];
        } elseif ($target['kind'] === 'route' && ($target['value'] !== self::PANEL_ROUTE || $target['params'] !== [])) {
            $url = get_route($target['value'], $target['params'], true);
            if (!is_string($url) || $url === '') {
                log_soft_error("\$config['login_redirect_route'] = '{$target['value']}' no es una ruta registrada o le faltan parámetros obligatorios: el acceso lleva al panel.");
                $url = null;
            } else {
                //Por nombre no se ve: una ruta con parámetros puede dar la dirección del acceso. Sin forma comparable, falla cerrado.
                $comparable = self::comparableURL($url, (string) parse_url(baseurl(), PHP_URL_PATH), Config::get_allowed_langs());
                if ($comparable === null || in_array($comparable, self::loginLoopURLs(), true)) {
                    log_soft_error($comparable === null ? self::LOGIN_UNREADABLE_MESSAGE : self::LOGIN_LOOP_MESSAGE);
                    $url = null;
                }
            }
        } elseif ($target['kind'] === 'panel' && $target['value'] === 'invalid') {
            log_soft_error("\$config['login_redirect_route'] tiene que ser el nombre de una ruta, una ruta con parámetros, una dirección relativa («/camino») o una dirección completa (http o https, sin usuario ni contraseña): el acceso lleva al panel.");
        } elseif ($target['kind'] === 'panel' && $target['value'] === 'loop') {
            log_soft_error(self::LOGIN_LOOP_MESSAGE);
        } elseif ($target['kind'] === 'panel' && $target['value'] === 'unreadable') {
            log_soft_error(self::LOGIN_UNREADABLE_MESSAGE);
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
        //Descifrada, como la enruta Slim: «/qui%C3%A9nes» casa con la entrada «/quiénes».
        $path = self::pathWithoutBase(rawurldecode($request->getUri()->getPath()), appbase(), $langPrefix);
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
            $target = self::pathWithoutBase(rawurldecode((string) parse_url($url, \PHP_URL_PATH)), appbase(), $langPrefix);
            if (self::legacyRedirectMatch($target, $config) !== null) {
                log_soft_error("\$config['legacy_redirects']: «{$path}» lleva a la ruta «{$match['value']}», cuya dirección vuelve a casar con el banco: 404.");
                return null;
            }
        } else {
            $url = baseurl(ltrim($langPrefix . implode('/', array_map('rawurlencode', explode('/', $match['value']))), '/'));
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
     * Si una ruta registrada reclama la dirección con cualquier método; los comodines públicos no cuentan. Sin el resultado del enrutado, se supone que sí.
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
        if (!($status === RoutingResults::FOUND && $route !== null && in_array($route->getName(), self::CATCH_ALL_ROUTES, true))) {
            return true;
        }
        //El comodín GET tapa a una ruta de otro método en la misma dirección (con uno o dos segmentos): se pregunta por cada
        //método. Solo cuenta FOUND: METHOD_NOT_ALLOWED también sale del propio comodín y reclamaría toda dirección corta.
        $resolver = get_router()->getRouteResolver();
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $results = $resolver->computeRoutingResults($request->getUri()->getPath(), $method);
            if ($results->getRouteStatus() === RoutingResults::FOUND && !in_array($resolver->resolveRoute((string) $results->getRouteIdentifier())->getName(), self::CATCH_ALL_ROUTES, true)) {
                return true;
            }
        }
        return false;
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
