<?php

//P399: el arranque del proyecto en config.php (public_area, admin_path, login_redirect_route) y el banco de redirecciones.
//En proceso, con set_config y restaurando; las direcciones servidas con la configuración cambiada se prueban por HTTP aparte.
//@areas nucleo
//@requires-module Newsletter\NewsletterRoutes
//@requires-module PiecesPHP\BuiltIn\Banner\BuiltInBannerRoutes

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\ProjectStart;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Settings\Controllers\SiteFilesController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Slim\Routing\RouteContext;
use Slim\Routing\RoutingResults;

CliActions::make('unit-tests:core/project-start', function ($args) {

    echoTerminal("\e[33m[TEST:project-start] El arranque del proyecto y el banco de redirecciones\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $keys = ['public_area', 'admin_path', 'login_redirect_route', 'admin_url', 'legacy_redirects'];
    $previous = [];
    foreach ($keys as $key) {
        $previous[$key] = get_config($key);
    }
    $terminalData = $_SERVER['PCSPHP_TERMINAL_DATA'] ?? null;

    try {
        //──── a. De fábrica ──────────────────────────────────────────────────────────────────
        echoTerminal('[a] De fábrica, todo como antes');
        //En un clon (ADR 0085 §5), cambiar estos valores de fábrica es una decisión del proyecto, no un fallo.
        $deFabrica = $previous['public_area'] === true && $previous['admin_path'] === 'admin' && $previous['login_redirect_route'] === 'admin';
        if (\Terminal\Distribution::isDistribution() && !$deFabrica) {
            echoTerminal("   \e[33m" . \Terminal\Distribution::NOT_APPLICABLE . " a1 config.php trae public_area, admin_path y login_redirect_route de fábrica: este proyecto los cambió\e[39m");
        } else {
            $check($deFabrica, 'a1 config.php trae public_area = true, admin_path = admin y login_redirect_route = admin', var_export([$previous['public_area'], $previous['admin_path'], $previous['login_redirect_route']], true));
        }
        //En un clon (ADR 0085 §5), el banco encendido es una decisión del proyecto, no un fallo de fábrica.
        if (\Terminal\Distribution::isDistribution() && ProjectStart::legacyRedirectsEnabled($previous['legacy_redirects'])) {
            echoTerminal("   \e[33m" . \Terminal\Distribution::NOT_APPLICABLE . " a2 el banco de redirecciones viene apagado: este proyecto lo encendió\e[39m");
        } else {
            $check(ProjectStart::legacyRedirectsEnabled($previous['legacy_redirects']) === false, 'a2 el banco de redirecciones viene apagado');
        }
        $legacy = ['/admin/', '/*/admin/', '/users/', '/*/users/', '/terminal/', '/core/', '/components-provider/', '/organizations/', '/locations/', '/tokens/'];
        $check(SiteFilesController::robotsDisallow() === $legacy, 'a3 robots.txt cierra lo mismo que antes, en el mismo orden', implode(' ', SiteFilesController::robotsDisallow()));
        $check(ProjectStart::withDefaults([]) === ['public_area' => true, 'admin_path' => 'admin'], 'a4 un config.php de antes, sin las claves, recibe las de fábrica');
        $check(ProjectStart::withDefaults(['public_area' => false, 'admin_path' => 'gestion']) === ['public_area' => false, 'admin_path' => 'gestion'], 'a5 las que trae config.php se respetan, también false');
        $check(ProjectStart::withDefaults(['public_area' => null])['public_area'] === true, 'a6 null cuenta como sin poner');
        echoTerminal(' ');

        //──── b. Validación ──────────────────────────────────────────────────────────────────
        echoTerminal('[b] Los tipos de cada clave');
        $check(ProjectStart::publicAreaValue(true) === 'full' && ProjectStart::publicAreaValue('headless') === 'headless' && ProjectStart::publicAreaValue(false) === 'closed', "b1 public_area: true, 'headless' y false valen (full, headless, closed)");
        foreach ([0, 'no', null, 'Headless', 'headless ', 'true', 1] as $bad) {
            $check(ProjectStart::publicAreaValue($bad) === null, 'b2 public_area ' . json_encode($bad) . ' no vale');
        }
        foreach (['admin', 'gestion', 'panel-2'] as $good) {
            $check(ProjectStart::adminPathValue($good) === $good, "b3 admin_path «{$good}» vale");
        }
        foreach (['', "admin\n", '/admin', 'admin/', 'Admin', 'admin/x', ' admin', 'pan el', null, 1] as $bad) {
            $check(ProjectStart::adminPathValue($bad) === null, 'b4 admin_path ' . json_encode($bad) . ' no vale');
        }
        $check(ProjectStart::loginRouteValue('users-list') === 'users-list' && ProjectStart::loginRouteValue('Mod\\Rutas-x') === 'Mod\\Rutas-x', 'b5 login_redirect_route: un nombre de ruta vale');
        $check(ProjectStart::loginRouteValue('') === null && ProjectStart::loginRouteValue('a b') === null && ProjectStart::loginRouteValue(['admin']) === null, 'b6 login_redirect_route: vacío, con espacios o no cadena, no');
        echoTerminal(' ');

        //──── c. El prefijo del panel ────────────────────────────────────────────────────────
        echoTerminal('[c] El prefijo del panel');
        $check(ProjectStart::adminPrefix('', 'admin') === '/admin', 'c1 admin, sin idioma: /admin');
        $check(ProjectStart::adminPrefix('/en', 'gestion') === '/en/gestion', 'c2 gestion, en inglés: /en/gestion');
        set_config('public_area', true);
        set_config('admin_path', 'gestion');
        $check(ProjectStart::adminPath() === 'gestion', 'c4 admin_path = gestion se lee');
        set_config('admin_path', '/gestion/');
        $check(ProjectStart::adminPath() === 'admin', 'c5 un admin_path que no vale se anota y se usa admin');
        set_config('admin_path', 'gestion');
        $check(SiteFilesController::robotsDisallow()[0] === '/gestion/' && !in_array('/admin/', SiteFilesController::robotsDisallow(), true), 'c6 robots.txt cierra el panel donde diga admin_path', implode(' ', SiteFilesController::robotsDisallow()));
        echoTerminal(' ');

        //──── d. admin_path = '' (el panel en la raíz) se rechaza ────────────────────────────
        echoTerminal("[d] admin_path = '' aún no está soportado");
        $expected = "El panel en la raíz (admin_path vacío) aún no está soportado: usa 'admin' u otro nombre en \$config['admin_path'].";
        set_config('admin_path', '');
        foreach ([true, false] as $index => $publicArea) {
            set_config('public_area', $publicArea);
            $message = '';
            try {
                ProjectStart::adminPath();
            } catch (\RuntimeException $e) {
                $message = $e->getMessage();
            }
            $check($message === $expected, 'd' . ($index + 1) . ' en local, error con el texto acordado, con public_area = ' . var_export($publicArea, true), $message);
        }
        $logFile = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/' . GenericHandler::FILE_ENTRIES;
        $softEntry = function () use ($logFile): ?array {
            $found = null;
            foreach (explode("\n", (string) @file_get_contents($logFile)) as $line) {
                $entry = json_decode($line, true);
                if (is_array($entry) && str_contains((string) ($entry['message'] ?? ''), 'admin_path vacío') && is_string($entry['softKey'] ?? null)) {
                    $found = $entry;
                }
            }
            return $found;
        };
        $_SERVER['PCSPHP_TERMINAL_DATA'] = array_merge(is_array($terminalData) ? $terminalData : [], ['isTerminal' => true, 'local' => false]);
        $outside = [];
        $counts = [];
        try {
            foreach ([0, 1] as $round) {
                $outside[] = ProjectStart::adminPath();
                $entry = $softEntry();
                $counts[] = $entry !== null ? (GenericHandler::softCounter(dirname($logFile), (string) $entry['softKey'])['count'] ?? 0) : -1;
            }
        } catch (\Throwable $e) {
            $outside[] = get_class($e);
        } finally {
            $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalData;
        }
        $check($outside === ['admin', 'admin'], 'd3 fuera de local no tumba nada: el panel sigue en admin', var_export($outside, true));
        $check(count($counts) === 2 && $counts[0] >= 1 && $counts[1] === $counts[0] + 1, 'd4 y cada vez queda el aviso en el registro de errores (un suave que suma su cuenta)', json_encode($counts) ?: '');
        echoTerminal(' ');

        //──── e. Los tres modos: robots, llms y sitemap ──────────────────────────────────────
        echoTerminal("[e] public_area: true, 'headless' y false");
        set_config('admin_path', 'admin');
        foreach ([true => 'full', 'headless' => 'headless', 0 => 'closed'] as $raw => $mode) {
            set_config('public_area', $raw === 0 ? false : ($raw === 1 ? true : $raw));
            $check(ProjectStart::publicAreaMode() === $mode && ProjectStart::publicPages() === ($mode === 'full') && ProjectStart::publicData() === ($mode !== 'closed'), "e1 {$mode}: modo, páginas y datos");
        }
        foreach (['headless', false] as $raw) {
            set_config('public_area', $raw);
            $label = json_encode($raw);
            $check(SiteFilesController::robotsBase() === "User-agent: *\nDisallow: /", "e2 {$label}: robots.txt no invita a indexar", SiteFilesController::robotsBase());
            $check(SiteFilesController::llmsBase() === '', "e3 {$label}: llms.txt sin contenido", SiteFilesController::llmsBase());
            $req = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/sitemap.xml'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            $xml = (string) (new SiteFilesController())->sitemap($req, new \PiecesPHP\Core\Routing\ResponseRoute())->getBody();
            $check(str_contains($xml, '<urlset') && !str_contains($xml, '<url>'), "e4 {$label}: el sitemap sale vacío", mb_substr($xml, 0, 200));
        }
        set_config('public_area', true);
        $check(str_contains(SiteFilesController::robotsBase(), 'Sitemap: ') && str_starts_with(SiteFilesController::llmsBase(), '# '), 'e5 CANARIO: con true, robots.txt da el sitemap y llms.txt su contenido');
        $_SERVER['PCSPHP_TERMINAL_DATA'] = array_merge(is_array($terminalData) ? $terminalData : [], ['isTerminal' => true, 'local' => true]);
        set_config('public_area', 'sí');
        $message = '';
        try {
            ProjectStart::publicAreaMode();
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
        }
        $check($message === "\$config['public_area'] tiene que ser true, 'headless' o false.", 'e6 otro valor, en local: error claro', $message);
        $_SERVER['PCSPHP_TERMINAL_DATA'] = array_merge(is_array($terminalData) ? $terminalData : [], ['isTerminal' => true, 'local' => false]);
        $outside = null;
        try {
            $outside = ProjectStart::publicAreaMode();
        } catch (\Throwable $e) {
            $outside = get_class($e);
        } finally {
            $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalData;
        }
        $check($outside === 'full', 'e7 otro valor, fuera de local: true, con aviso, sin tumbar nada', var_export($outside, true));
        set_config('public_area', true);
        echoTerminal(' ');

        //──── i. Qué rutas públicas registra cada modo ───────────────────────────────────────
        echoTerminal('[i] Las rutas públicas de cada modo, con grupos que solo apuntan (no tocan el enrutador)');
        $capture = function (string $segment): \PiecesPHP\Core\RouteGroup {
            return new class($segment) extends \PiecesPHP\Core\RouteGroup {
                /** @var array<string,string> */
                public array $captured = [];
                public function register($routes)
                {
                    foreach ($routes as $route) {
                        if ($route instanceof \PiecesPHP\Core\Route) {
                            $controller = $route->controller();
                            $name = $route->name();
                            $this->captured[is_string($name) ? $name : ''] = is_string($controller) ? $controller : 'Closure';
                        }
                    }
                    return $this;
                }
            };
        };
        $pages = ['public-contact', 'public-generic', 'public-generic-2', 'publications-list', 'publications-list-by-category', 'publications-single'];
        $data = ['contact-forms-general', 'built-in-banner-ajax-all', 'newsletter-admin-add', 'components-provider-provide', 'publications-ajax-all'];
        $always = ['public-index', 'public-unsubscribe'];
        $panelCounts = [];
        foreach (['full' => true, 'headless' => 'headless', 'closed' => false] as $mode => $raw) {
            set_config('public_area', $raw);
            $public = $capture('');
            $admin = $capture('/admin');
            $components = $capture('/components-provider');
            \App\Controller\PublicAreaController::routes($public);
            \PiecesPHP\BuiltIn\Banner\BuiltInBannerRoutes::routes($admin, $public);
            \Newsletter\NewsletterRoutes::routes($admin, $public);
            \PiecesPHP\Components\ComponentProviderRoutes::routes($components, $components);
            \Publications\Controllers\PublicationsPublicController::routes($public, ProjectStart::publicPages(), ProjectStart::publicData());
            $got = array_keys($public->captured + array_filter($components->captured, fn(string $c): bool => $c !== 'Closure'));
            $expected = array_merge($always, $mode === 'full' ? $pages : [], $mode !== 'closed' ? $data : []);
            sort($got);
            sort($expected);
            $check($got === $expected, "i1 {$mode}: quedan exactamente las rutas públicas de su modo", 'sobran ' . implode(', ', array_diff($got, $expected)) . ' · faltan ' . implode(', ', array_diff($expected, $got)));
            $index = $public->captured['public-index'] ?? '';
            $check(str_ends_with($index, $mode === 'full' ? ':indexView' : ':closedIndexView'), "i2 {$mode}: public-index sigue y " . ($mode === 'full' ? 'es la portada' : 'lleva al acceso'), $index);
            $panelCounts[$mode] = array_keys($admin->captured);
        }
        $check(count($panelCounts['full']) > 0 && $panelCounts['full'] === $panelCounts['headless'] && $panelCounts['full'] === $panelCounts['closed'], 'i3 la parte de panel de Imágenes y Boletín es la misma en los tres modos', (string) count($panelCounts['full']));
        $routesSource = (string) file_get_contents(basepath('app/classes/Publications/PublicationsRoutes.php'));
        $check(str_contains($routesSource, 'PublicationsPublicController::routes($groupPublic, ProjectStart::publicPages(), ProjectStart::publicData());'), 'i4 PublicationsRoutes::routes() pasa los dos interruptores a su parte pública');
        set_config('public_area', true);
        echoTerminal(' ');

        //──── f. Adónde lleva el acceso ──────────────────────────────────────────────────────
        echoTerminal('[f] next > login_redirect_route > admin_url de antes > panel');
        $oldAdminURL = ['relative' => true, 'url' => 'zz-destino'];
        $t = ProjectStart::loginTarget('https://zz.test/a', 'users-list', $oldAdminURL);
        $check($t['kind'] === 'next' && $t['value'] === 'https://zz.test/a', 'f1 next manda sobre todo');
        $t = ProjectStart::loginTarget(null, 'users-list', $oldAdminURL);
        $check($t['kind'] === 'route' && $t['value'] === 'users-list', 'f2 login_redirect_route declarada manda sobre admin_url');
        $t = ProjectStart::loginTarget(null, false, $oldAdminURL);
        $check($t['kind'] === 'admin_url' && $t['value'] === 'zz-destino' && $t['relative'] === true, 'f3 sin la clave nueva (un clon de antes), admin_url con url se respeta');
        $t = ProjectStart::loginTarget(null, false, ['relative' => 'x', 'url' => 'https://zz.test/p']);
        $check($t['kind'] === 'admin_url' && $t['relative'] === true, 'f4 un relative que no es booleano cuenta como true, igual que antes');
        $t = ProjectStart::loginTarget(null, false, ['relative' => true, 'url' => '']);
        $check($t['kind'] === 'panel', 'f5 sin la clave y con admin_url vacía (la de fábrica), el panel');
        $t = ProjectStart::loginTarget(null, ['x'], $oldAdminURL);
        $check($t['kind'] === 'panel' && $t['value'] === 'invalid', 'f6 una login_redirect_route que no vale lleva al panel, no a admin_url');

        $panel = convert_lang_url(AdminPanelController::routeName(''), get_config('default_lang'), get_config('app_lang'));
        set_config('admin_url', ['relative' => true, 'url' => '']);
        set_config('login_redirect_route', 'admin');
        $check(ProjectStart::afterLoginURL(null) === $panel && $panel !== '', 'f7 de fábrica, la misma URL que antes: el panel', $panel);
        set_config('login_redirect_route', 'users-list');
        $list = convert_lang_url((string) get_route('users-list', [], true), get_config('default_lang'), get_config('app_lang'));
        $check(ProjectStart::afterLoginURL(null) === $list && $list !== $panel, 'f8 login_redirect_route = users-list lleva al listado', ProjectStart::afterLoginURL(null));
        set_config('login_redirect_route', 'zz-no-existe');
        $check(ProjectStart::afterLoginURL(null) === $panel, 'f9 un nombre que no es ruta se anota y lleva al panel');
        set_config('login_redirect_route', false);
        set_config('admin_url', $oldAdminURL);
        $check(ProjectStart::afterLoginURL(null) === convert_lang_url(baseurl('zz-destino'), get_config('default_lang'), get_config('app_lang')), 'f10 un clon de antes con admin_url puesto conserva su destino');
        $t = ProjectStart::loginTarget(null, 'users-form-login', $oldAdminURL);
        $check($t['kind'] === 'panel' && $t['value'] === 'loop', 'f11 login_redirect_route = users-form-login sería un bucle: el panel');
        $t = ProjectStart::loginTarget(null, 'public-index', $oldAdminURL, false);
        $check($t['kind'] === 'panel' && $t['value'] === 'loop', 'f12 public-index sin zona pública también sería un bucle: el panel');
        $t = ProjectStart::loginTarget(null, 'public-index', $oldAdminURL, true);
        $check($t['kind'] === 'route' && $t['value'] === 'public-index', 'f13 CANARIO: con zona pública, public-index es un destino válido');
        set_config('admin_url', ['relative' => true, 'url' => '']);
        set_config('login_redirect_route', 'users-form-login');
        $check(ProjectStart::afterLoginURL(null) === $panel, 'f14 afterLoginURL() con users-form-login lleva al panel', ProjectStart::afterLoginURL(null));
        set_config('login_redirect_route', 'admin');
        foreach (['//evil.example/x', 'https://evil.example/x', "javascript:void(0)"] as $index => $next) {
            $check(ProjectStart::afterLoginURL($next) === $panel, 'f15.' . ($index + 1) . ' un next de fuera no se usa ni llamando a afterLoginURL() directamente: ' . $next, ProjectStart::afterLoginURL($next));
        }
        $own = baseurl('zz-propia/');
        $check(ProjectStart::afterLoginURL($own) === $own, 'f16 CANARIO: un next del propio sitio se usa', ProjectStart::afterLoginURL($own));
        //Una dirección completa (P413 2.5, P416): http o https con host, también de otro dominio.
        $t = ProjectStart::loginTarget(null, 'https://zz-otro.example/destino?a=1', $oldAdminURL);
        $check($t['kind'] === 'url' && $t['value'] === 'https://zz-otro.example/destino?a=1', 'f17 DISCRIMINANTE: una dirección https de otro dominio es un destino «url»', json_encode($t) ?: '');
        set_config('login_redirect_route', 'https://zz-otro.example/destino?a=1');
        $check(ProjectStart::afterLoginURL(null) === 'https://zz-otro.example/destino?a=1', 'f18 DISCRIMINANTE: afterLoginURL() la devuelve tal cual, sin base ni idioma', ProjectStart::afterLoginURL(null));
        $t = ProjectStart::loginTarget(null, 'http://zz-otro.example/', $oldAdminURL);
        $check($t['kind'] === 'url' && $t['value'] === 'http://zz-otro.example/', 'f19 una dirección http también', json_encode($t) ?: '');
        $t = ProjectStart::loginTarget(null, 'https://zz otro.example/x', $oldAdminURL);
        $check($t['kind'] === 'panel' && $t['value'] === 'invalid', 'f20 una dirección con espacios no vale: el panel', json_encode($t) ?: '');
        set_config('login_redirect_route', 'https://zz otro.example/x');
        $check(ProjectStart::afterLoginURL(null) === $panel, 'f21 afterLoginURL() con una dirección con espacios lleva al panel', ProjectStart::afterLoginURL(null));
        foreach (['zz-otro.example/x', 'ftp://zz-otro.example/x', 'javascript:void(0)'] as $index => $notURL) {
            $t = ProjectStart::loginTarget(null, $notURL, $oldAdminURL);
            $check($t['kind'] === 'route' && $t['value'] === $notURL, 'f22.' . ($index + 1) . ' sin esquema http o https no es una dirección: se lee como nombre de ruta (y, si no lo es, lleva al panel): ' . $notURL, json_encode($t) ?: '');
        }
        $t = ProjectStart::loginTarget(null, '//zz-otro.example/x', $oldAdminURL);
        $check($t['kind'] === 'panel' && $t['value'] === 'invalid', 'f22.4 «//zz-otro.example/x» empieza por «/»: nunca nombre de ruta; no es relativa, así que no vale', json_encode($t) ?: '');
        set_config('login_redirect_route', 'ftp://zz-otro.example/x');
        $check(ProjectStart::afterLoginURL(null) === $panel, 'f23 una ftp:// no es destino: el panel', ProjectStart::afterLoginURL(null));
        $t = ProjectStart::loginTarget('https://zz.test/a', 'https://zz-otro.example/destino', $oldAdminURL);
        $check($t['kind'] === 'next' && $t['value'] === 'https://zz.test/a', 'f24 next manda también sobre una dirección completa');
        set_config('login_redirect_route', 'https://zz-otro.example/destino');
        $check(ProjectStart::afterLoginURL($own) === $own, 'f25 afterLoginURL(): un next del propio sitio manda sobre la dirección', ProjectStart::afterLoginURL($own));
        set_config('login_redirect_route', 'admin');
        $warnEmpty = ProjectStart::loginRedirectHostWarning('https://zz-otro.example/destino', [], true);
        $warnOut = ProjectStart::loginRedirectHostWarning('https://zz-otro.example/destino', ['zz-propio.example'], true);
        $warnIn = ProjectStart::loginRedirectHostWarning('https://www.zz-propio.example/destino', ['zz-propio.example'], true);
        $warnNoWww = ProjectStart::loginRedirectHostWarning('https://www.zz-propio.example/destino', ['zz-propio.example'], false);
        $check(is_string($warnEmpty) && str_contains($warnEmpty, 'no se pudo comprobar'), 'f26 con allowed_hosts vacía, el aviso dice que no se pudo comprobar', (string) $warnEmpty);
        $check(is_string($warnOut) && str_contains($warnOut, 'zz-otro.example no está'), 'f27 DISCRIMINANTE: un dominio fuera de la lista avisa', (string) $warnOut);
        $check($warnIn === null && is_string($warnNoWww), 'f28 un dominio de la lista (con el www inferido) no avisa; sin inferir el www, sí', var_export([$warnIn, $warnNoWww], true));
        $check(ProjectStart::loginRedirectHostWarning('users-list', [], true) === null && ProjectStart::loginRedirectHostWarning(false, [], true) === null, 'f29 un nombre de ruta, o la clave sin declarar, no avisa');
        //Con usuario o contraseña no vale (parece suplantación), y una dirección que vuelve al acceso haría un bucle (JOY).
        foreach (['https://zz-u:zz-p@zz-otro.example/x', 'https://zz-u@zz-otro.example/x'] as $index => $withUser) {
            $t = ProjectStart::loginTarget(null, $withUser, $oldAdminURL);
            set_config('login_redirect_route', $withUser);
            $check($t['kind'] === 'panel' && $t['value'] === 'invalid' && ProjectStart::afterLoginURL(null) === $panel, 'f3' . $index . ' DISCRIMINANTE: una dirección con ' . ($index === 0 ? 'usuario y contraseña' : 'usuario') . ' no vale: el panel', json_encode($t) ?: '');
        }
        $loginURL = (string) UsersController::routeName('form-login', [], true);
        $basePath = (string) parse_url(baseurl(), PHP_URL_PATH);
        $langs = \PiecesPHP\Core\Config::get_allowed_langs();
        $withLang = preg_replace('#^(https?://[^/]+' . preg_quote(rtrim($basePath, '/'), '#') . ')/#', '$1/' . end($langs) . '/', $loginURL, 1);
        $loginForms = ['tal cual' => $loginURL, 'sin la barra final' => rtrim($loginURL, '/'), 'con la barra final' => rtrim($loginURL, '/') . '/', 'con ?i18n' => rtrim($loginURL, '/') . '/?i18n=es', 'con prefijo de idioma' => (string) $withLang];
        $n = 2;
        foreach ($loginForms as $form => $address) {
            set_config('login_redirect_route', $address);
            $t = ProjectStart::loginTarget(null, $address, $oldAdminURL, true, ProjectStart::loginLoopURLs(), $basePath, $langs);
            $check($loginURL !== '' && $t['kind'] === 'panel' && $t['value'] === 'loop' && ProjectStart::afterLoginURL(null) === $panel, 'f3' . $n++ . ' DISCRIMINANTE: la dirección del acceso ' . $form . ' haría un bucle: el panel', $address . ' · ' . json_encode($t));
        }
        $home = (string) \App\Controller\PublicAreaController::routeName('index', [], true);
        set_config('login_redirect_route', $home);
        set_config('public_area', false);
        $closed = ProjectStart::afterLoginURL(null);
        set_config('public_area', true);
        $open = ProjectStart::afterLoginURL(null);
        $check($home !== '' && $closed === $panel, 'f37 DISCRIMINANTE: la portada sin páginas públicas lleva al acceso, así que es un bucle: el panel', $home . ' → ' . $closed);
        $check($open === $home, 'f38 CANARIO: con páginas públicas, la portada es un destino válido', $open);
        $warnUser = ProjectStart::loginRedirectHostWarning('https://zz-u:zz-p@zz-otro.example/x', [], true);
        $warnLoop = ProjectStart::loginRedirectHostWarning($loginURL, [], true, ProjectStart::loginLoopURLs(), $basePath, $langs);
        $check(is_string($warnUser) && str_contains($warnUser, 'usuario o contraseña') && !str_contains($warnUser, 'lleva al acceso a'), 'f39 el aviso de una dirección con usuario dice que no se usa', (string) $warnUser);
        $check(is_string($warnLoop) && str_contains($warnLoop, 'bucle') && !str_contains($warnLoop, 'lleva al acceso a'), 'f40 el aviso de una dirección que vuelve al acceso dice que haría un bucle', (string) $warnLoop);
        $check(ProjectStart::loginRedirectHostWarning('https://zz-otro.example/x', ['zz-otro.example'], true, ProjectStart::loginLoopURLs(), $basePath, $langs) === null, 'f41 CANARIO: una dirección de la lista que no es el acceso no avisa');
        //Una ruta con parámetros y una dirección relativa a la instalación (P416, JPL).
        $lang = fn(string $url): string => convert_lang_url($url, get_config('default_lang'), get_config('app_lang'));
        $edit = $lang((string) get_route('users-form-edit', ['id' => 7], true));
        set_config('login_redirect_route', ['route' => 'users-form-edit', 'params' => ['id' => 7]]);
        $t = ProjectStart::loginTarget(null, ['route' => 'users-form-edit', 'params' => ['id' => 7]], $oldAdminURL);
        $check($t['kind'] === 'route' && $t['value'] === 'users-form-edit' && $t['params'] === ['id' => 7] && ProjectStart::afterLoginURL(null) === $edit && str_contains($edit, '/7'), 'p1 DISCRIMINANTE: una ruta con parámetros lleva a get_route() con esos parámetros', json_encode($t) . ' → ' . ProjectStart::afterLoginURL(null));
        //El suave se agrupa por archivo y línea: f9 ya lo anotó en esta corrida, así que lo que sube es su contador.
        $routeSoft = function () use ($logFile): int {
            $key = null;
            foreach (explode("\n", (string) @file_get_contents($logFile)) as $line) {
                $entry = json_decode($line, true);
                if (is_array($entry) && str_contains((string) ($entry['message'] ?? ''), "'zz-no-existe' no es una ruta registrada o le faltan parámetros obligatorios") && is_string($entry['softKey'] ?? null)) {
                    $key = $entry['softKey'];
                }
            }
            return $key !== null ? (GenericHandler::softCounter(dirname($logFile), $key)['count'] ?? -1) : -1;
        };
        $before = $routeSoft();
        set_config('login_redirect_route', ['route' => 'users-form-edit']);
        $missing = ProjectStart::afterLoginURL(null);
        $after = $routeSoft();
        $check($missing === $panel && $before > 0 && $after === $before + 1, 'p2 una ruta a la que le falta un parámetro obligatorio lleva al panel y se anota', $missing . ' · ' . $before . ' → ' . $after);
        $badForms = [['route' => 'users-list', 'params' => 'id=7'], ['route' => 'users-list', 'otra' => 1], ['route' => 'users-form-edit', 'params' => ['id' => ['7']]], ['route' => 'users-form-edit', 'params' => [7]], ['route' => 'a b'], ['params' => ['id' => 7]]];
        foreach ($badForms as $index => $bad) {
            $t = ProjectStart::loginTarget(null, $bad, $oldAdminURL);
            $check($t['kind'] === 'panel' && $t['value'] === 'invalid', 'p3.' . ($index + 1) . ' un arreglo mal formado no vale: ' . json_encode($bad), json_encode($t) ?: '');
        }
        set_config('login_redirect_route', $badForms[0]);
        $check(ProjectStart::afterLoginURL(null) === $panel, 'p3 afterLoginURL() con un arreglo mal formado lleva al panel');
        $t = ProjectStart::loginTarget(null, '/mi-pagina', $oldAdminURL);
        set_config('login_redirect_route', '/mi-pagina');
        $check($t['kind'] === 'url' && $t['relative'] === true && ProjectStart::afterLoginURL(null) === baseurl('mi-pagina'), 'p4 DISCRIMINANTE: «/mi-pagina» es relativa a la instalación: baseurl(\'mi-pagina\')', json_encode($t) . ' → ' . ProjectStart::afterLoginURL(null));
        $loginRelative = '/' . ltrim(substr($loginURL, strlen(rtrim(baseurl(), '/'))), '/');
        $t = ProjectStart::loginTarget(null, $loginRelative, $oldAdminURL, true, ProjectStart::loginLoopURLs(), $basePath, $langs, baseurl());
        set_config('login_redirect_route', $loginRelative);
        $check(str_starts_with($loginURL, rtrim(baseurl(), '/')) && $t['kind'] === 'panel' && $t['value'] === 'loop' && ProjectStart::afterLoginURL(null) === $panel, 'p5 DISCRIMINANTE: una relativa que resuelve al propio acceso haría un bucle: el panel', $loginRelative . ' · ' . json_encode($t));
        $baseHost = (string) parse_url(baseurl(), PHP_URL_HOST);
        foreach (['//zz-otro.example/x', '/\\zz-otro.example/x', '/\\/zz-otro.example', "/\tzz", "/x\ny", '/x y', "/\u{3000}zz", 'javascript:zzMalo()', "/\xff\\\\zz-otro.example", "/\xff\r\nzz: x"] as $index => $notRelative) {
            $t = ProjectStart::loginTarget(null, $notRelative, $oldAdminURL);
            set_config('login_redirect_route', $notRelative);
            $after = ProjectStart::afterLoginURL(null);
            $check(ProjectStart::loginRelativeValue($notRelative) === null && $t['kind'] !== 'url' && parse_url($after, PHP_URL_HOST) === $baseHost && $after === $panel, 'p6.' . ($index + 1) . ' DISCRIMINANTE: nunca relativa ni redirección a otro dominio: ' . json_encode($notRelative), json_encode($t) . ' → ' . $after);
        }
        foreach (['/%2F%2Fzz-otro.example', '/%5Czz-otro.example'] as $index => $encoded) {
            set_config('login_redirect_route', $encoded);
            $after = ProjectStart::afterLoginURL(null);
            $check(ProjectStart::loginRelativeValue($encoded) === null && $after === $panel, 'p6.' . ($index + 11) . ' una barra codificada en el camino de la relativa no vale: el panel: ' . $encoded, $after);
        }
        $check(ProjectStart::loginRedirectHostWarning('/mi-pagina', ['zz-propio.example'], true) === null, 'p6.13 una relativa no avisa en verify-integrity: es del propio dominio');
        //Los parámetros se sustituyen sin codificar, y una relativa con «.» o «..» sale de la instalación o esquiva el bucle (H528).
        $label = fn(mixed $value): string => json_encode($value, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE) ?: var_export($value, true);
        foreach (['../x', 'a/b', 'a\\b', 'a b', "a\r\nb", '.', '..', '', "zz\xff"] as $index => $badParam) {
            $check(ProjectStart::loginRouteWithParams(['route' => 'users-form-edit', 'params' => ['id' => $badParam]]) === null, 'p7.' . ($index + 1) . ' DISCRIMINANTE: un parámetro ' . $label($badParam) . ' no vale');
        }
        $check(ProjectStart::loginRouteWithParams(['route' => 'users-form-edit', 'params' => ['id' => 7]]) !== null && ProjectStart::loginRouteWithParams(['route' => 'users-form-edit', 'params' => ['id' => 'zz-limpio_7.a']]) !== null, 'p7.10 CANARIO: un entero y un texto limpio sí valen');
        foreach (['/../../x', '/./login', '/a/../b', '/a/.'] as $index => $dotted) {
            $check(ProjectStart::loginRelativeValue($dotted) === null, 'p8.' . ($index + 1) . ' DISCRIMINANTE: la relativa ' . $dotted . ' no vale');
        }
        foreach (['/x/.y', '/x..y', '/a?b=..'] as $index => $undotted) {
            $check(ProjectStart::loginRelativeValue($undotted) === $undotted, 'p8.' . ($index + 5) . ' CANARIO: la relativa ' . $undotted . ' sí vale');
        }
        foreach (['/./login', '/../../x'] as $index => $dotted) {
            $t = ProjectStart::loginTarget(null, $dotted, $oldAdminURL, true, ProjectStart::loginLoopURLs(), $basePath, $langs, baseurl());
            $check($t['kind'] === 'panel' && $t['value'] === 'invalid', 'p9.' . ($index + 1) . ' DISCRIMINANTE: loginTarget() con ' . $dotted . ' va al panel por no válida, no se lee como nombre de ruta', json_encode($t) ?: '');
        }
        //Lo codificado: un punto o una barra codificados, un bucle escrito de otra forma y los parámetros simples (H528, JPN).
        foreach (['/%2e%2e/x', '/.%2E/x', '/%2e/login', '/a%2Fb', '/a%5cb'] as $index => $encoded) {
            $check(ProjectStart::loginRelativeValue($encoded) === null, 'p10.' . ($index + 1) . ' DISCRIMINANTE: la relativa ' . $encoded . ' no vale');
        }
        $t = ProjectStart::loginTarget(null, '/a%C3%B1o', $oldAdminURL, true, ProjectStart::loginLoopURLs(), $basePath, $langs, baseurl());
        $check(ProjectStart::loginRelativeValue('/a%C3%B1o') === '/a%C3%B1o' && $t['kind'] === 'url' && $t['relative'] === true, 'p10.6 CANARIO: una letra no ASCII codificada (/a%C3%B1o) sí vale', json_encode($t) ?: '');
        $t = ProjectStart::loginTarget(null, '/año', $oldAdminURL, true, ProjectStart::loginLoopURLs(), $basePath, $langs, baseurl());
        $check(ProjectStart::loginRelativeValue('/año') === null && $t['kind'] === 'panel' && $t['value'] === 'invalid', 'p10.7 DISCRIMINANTE: sin codificar (/año) no es ASCII visible: no vale, el panel', json_encode($t, JSON_UNESCAPED_UNICODE) ?: '');
        foreach (['%2e%2e', 'a%2fb', 'x#f', 'a?b', false, 1.5, NAN, 'ñ'] as $index => $badParam) {
            $check(ProjectStart::loginRouteWithParams(['route' => 'users-form-edit', 'params' => ['id' => $badParam]]) === null, 'p11.' . ($index + 1) . ' DISCRIMINANTE: un parámetro ' . $label($badParam) . ' (' . gettype($badParam) . ') no vale');
        }
        $simple = ProjectStart::loginRouteWithParams(['route' => 'users-form-edit', 'params' => ['id' => 7, 'slug' => 'zz-limpio_7.a~']]);
        $check($simple !== null && $simple['params'] === ['id' => 7, 'slug' => 'zz-limpio_7.a~'], 'p11.9 CANARIO: un entero y un texto de [A-Za-z0-9._~-] sí valen', json_encode($simple) ?: '');
        $encodedLogin = (string) preg_replace('~login(/?)$~', 'log%69n$1', $loginURL);
        $check($encodedLogin !== $loginURL && ProjectStart::comparableURL($encodedLogin, $basePath, $langs) !== null && ProjectStart::comparableURL($encodedLogin, $basePath, $langs) === ProjectStart::comparableURL($loginURL, $basePath, $langs), 'p12.1 DISCRIMINANTE: comparableURL() decodifica el camino: log%69n es login', $encodedLogin);
        $encodedRelative = (string) preg_replace('~login(/?)$~', 'log%69n$1', $loginRelative);
        $t = ProjectStart::loginTarget(null, $encodedRelative, $oldAdminURL, true, ProjectStart::loginLoopURLs(), $basePath, $langs, baseurl());
        $check($encodedRelative !== $loginRelative && $t['kind'] === 'panel' && $t['value'] === 'loop', 'p12.2 DISCRIMINANTE: la relativa ' . $encodedRelative . ' es el acceso escrito de otra forma: bucle, el panel', json_encode($t) ?: '');
        //Una ruta con parámetros que da la dirección del acceso: por nombre no se ve, por la dirección sí.
        $aliasLoop = ['route' => 'public-generic-2', 'params' => ['folder' => 'users', 'name' => 'login']];
        $aliasURL = get_route('public-generic-2', $aliasLoop['params'], true);
        $t = ProjectStart::loginTarget(null, $aliasLoop, $oldAdminURL);
        set_config('login_redirect_route', $aliasLoop);
        $aliasAfter = ProjectStart::afterLoginURL(null);
        $check(is_string($aliasURL) && in_array(ProjectStart::comparableURL($aliasURL, $basePath, $langs), ProjectStart::loginLoopURLs(), true), 'p13.1 CANARIO: public-generic-2 con users/login da la dirección del acceso', (string) $aliasURL);
        $check($t['kind'] === 'route' && $aliasAfter === $panel, 'p13.2 DISCRIMINANTE: afterLoginURL() la mide por la dirección y lleva al panel, no al acceso', json_encode($t) . ' → ' . $aliasAfter);
        //La dirección normalizada: sin el validador de URL, sin esquema, sin puerto por omisión, con «\\» como «/» y sin segmentos de punto (JPN-2).
        $check(ProjectStart::comparableURL('http://my_site.local/x', '', []) === '//my_site.local/x', 'p14.1 DISCRIMINANTE: un host con «_» se puede comparar', (string) ProjectStart::comparableURL('http://my_site.local/x', '', []));
        $t = ProjectStart::loginTarget(null, '/mi-pagina', $oldAdminURL, true, ProjectStart::loginLoopURLs(), '', $langs, 'http://web_app/');
        $check($t['kind'] === 'url' && $t['relative'] === true, 'p14.2 DISCRIMINANTE: una relativa con la base http://web_app/ vale: no va al panel', json_encode($t) ?: '');
        $loopOf = fn(string $address): array => ProjectStart::loginTarget(null, $address, $oldAdminURL, true, ProjectStart::loginLoopURLs(), $basePath, $langs);
        $sameHost = [
            'x/..' => str_replace('/users/login', '/x/../users/login', $loginURL),
            '%2e' => str_replace('/users/login', '/users/%2e/login', $loginURL),
            '.' => str_replace('/users/login', '/users/./login', $loginURL),
            'barra invertida' => str_replace('/users/login', '/users\\login', $loginURL),
            ':443 con https' => (string) preg_replace('~^https?://([^/:]+)~', 'https://$1:443', $loginURL),
            'otro esquema' => (string) preg_replace('~^(https?)://~', str_starts_with($loginURL, 'https://') ? 'http://' : 'https://', $loginURL),
        ];
        $n = 3;
        foreach ($sameHost as $form => $address) {
            $t = $loopOf($address);
            $check($address !== $loginURL && $t['kind'] === 'panel' && $t['value'] === 'loop', 'p14.' . $n++ . ' DISCRIMINANTE: la dirección completa del acceso escrita con ' . $form . ' es un bucle: ' . $address, json_encode($t) ?: '');
        }
        $encodedBaseLoop = ProjectStart::comparableURL('http://zz-host.example/my%20dir/users/login', '/my%20dir', ['es', 'en']);
        $t = ProjectStart::loginTarget(null, '/en/users/login', $oldAdminURL, true, $encodedBaseLoop !== null ? [$encodedBaseLoop] : [], '/my%20dir', ['es', 'en'], 'http://zz-host.example/my%20dir/');
        $check($t['kind'] === 'panel' && $t['value'] === 'loop', 'p14.9 DISCRIMINANTE: con la base codificada (/my%20dir/), /en/users/login es el acceso: bucle', json_encode($t) ?: '');
        $check(ProjectStart::loginRelativeValue('/buscar?vuelta=%2Fpanel') === '/buscar?vuelta=%2Fpanel', 'p14.10 CANARIO: una barra codificada en la query sí vale (lo codificado solo se mira en el camino)');
        $check(ProjectStart::loginRelativeValue('/doc%2Epdf') === null, 'p14.11 DISCRIMINANTE: un punto codificado en el camino no vale: /doc%2Epdf');
        $t = ProjectStart::loginTarget(null, '/mi-pagina', $oldAdminURL, true, ProjectStart::loginLoopURLs(), '', $langs, 'http:///');
        $check($t['kind'] === 'panel' && $t['value'] === 'unreadable', 'p14.12 DISCRIMINANTE: con una base que no se puede leer, la relativa da panel/unreadable (no invalid ni loop)', json_encode($t) ?: '');
        $removeDotSegments = new \ReflectionMethod(ProjectStart::class, 'removeDotSegments');
        $check($removeDotSegments->invoke(null, '/a/b/c/./../../g') === '/a/g' && $removeDotSegments->invoke(null, 'mid/content=5/../6') === 'mid/6', 'p14.13 removeDotSegments() con los ejemplos de RFC 3986 §5.2.4', $removeDotSegments->invoke(null, '/a/b/c/./../../g') . ' · ' . $removeDotSegments->invoke(null, 'mid/content=5/../6'));
        set_config('login_redirect_route', 'admin');
        echoTerminal(' ');

        //──── g. El banco de redirecciones ───────────────────────────────────────────────────
        echoTerminal('[g] El banco: exacta, prefijo, apagado');
        $bank = ['enabled' => true, 'paths' => ['/zz-viejo/pagina/' => 'users-list'], 'prefixes' => ['/zz-viejo' => '/zz-nuevo', '/zz-viejo/hondo' => '/zz-otro', '/' => '/nada']];
        $check(ProjectStart::legacyRedirectMatch('/zz-viejo/pagina', $bank) === ['kind' => 'route', 'value' => 'users-list'], 'g1 la dirección exacta da su ruta, con o sin barra final');
        $check(ProjectStart::legacyRedirectMatch('/zz-viejo/a/b', $bank) === ['kind' => 'path', 'value' => '/zz-nuevo/a/b'], 'g2 un prefijo viejo da la dirección nueva');
        $check(ProjectStart::legacyRedirectMatch('/zz-viejo/hondo/x', $bank) === ['kind' => 'path', 'value' => '/zz-otro/x'], 'g3 gana el prefijo más largo');
        $check(ProjectStart::legacyRedirectMatch('/zz-viejox/a', $bank) === null && ProjectStart::legacyRedirectMatch('/otra', $bank) === null, 'g4 un prefijo casa por segmentos, y «/» no cuenta como prefijo');
        $check(ProjectStart::legacyRedirectMatch('/zz-viejo/a', ['enabled' => true, 'prefixes' => ['/zz-viejo' => '/zz-viejo/']]) === null, 'g5 un prefijo que no cambia la dirección no redirige');
        $check(ProjectStart::legacyRedirectMatch('/zz-viejo/pagina', ['enabled' => false] + $bank) === null && ProjectStart::legacyRedirectMatch('/zz-viejo/pagina', false) === null, 'g6 apagado, o sin la clave (un clon de antes), nada');
        $check(ProjectStart::pathWithoutBase('/sub/src/en/zz-viejo/x/', '/sub/src/', '/en') === '/zz-viejo/x', 'g7 la dirección sin la base de la instalación ni el idioma');
        $check(ProjectStart::pathWithoutBase('/enx/a', '/', '/en') === '/enx/a', 'g8 el idioma se quita por segmento entero');
        $rules = ProjectStart::legacyRedirectRules(['enabled' => true, 'prefixes' => ['/a' => '/a/b']]);
        $check($rules['prefixes'] === [] && count($rules['rejected']) === 1 && ProjectStart::legacyRedirectMatch('/a/x', ['enabled' => true, 'prefixes' => ['/a' => '/a/b']]) === null, "g9 '/a' => '/a/b' (cadena sin fin) se descarta", json_encode($rules) ?: '');
        $cycle = ['enabled' => true, 'prefixes' => ['/a' => '/b', '/b' => '/a']];
        $rules = ProjectStart::legacyRedirectRules($cycle);
        $check($rules['prefixes'] === [] && count($rules['rejected']) === 2 && ProjectStart::legacyRedirectMatch('/a/x', $cycle) === null && ProjectStart::legacyRedirectMatch('/b/x', $cycle) === null, "g10 '/a' => '/b' con '/b' => '/a' (bucle) se descartan los dos", json_encode($rules) ?: '');
        $rules = ProjectStart::legacyRedirectRules(['enabled' => true, 'prefixes' => ['/a' => '/c', '/ab' => '/a-b']]);
        $check($rules['prefixes'] === ['/ab' => '/a-b', '/a' => '/c'] && $rules['rejected'] === [], 'g11 CANARIO: los que no vuelven a casar se quedan (por segmentos: «/a-b» no cae en «/a»)', json_encode($rules) ?: '');
        echoTerminal(' ');

        //──── h. El banco, contra el enrutado real ───────────────────────────────────────────
        echoTerminal('[h] El banco no actúa si una ruta reclama la dirección');
        $router = get_router();
        $request = function (string $method, string $path, string $query = '') use ($router): RequestRoute {
            $uri = (new UriFactory())->createUri('http://localhost' . $path . ($query !== '' ? "?{$query}" : ''));
            $req = new RequestRoute($method, $uri, new Headers(), [], [], (new StreamFactory())->createStream(''));
            $results = $router->getRouteResolver()->computeRoutingResults($path, $method);
            $req = $req->withAttribute(RouteContext::ROUTE_PARSER, $router->getRouteCollector()->getRouteParser())->withAttribute(RouteContext::ROUTING_RESULTS, $results);
            if ($results->getRouteStatus() === RoutingResults::FOUND) {
                $req = $req->withAttribute(RouteContext::ROUTE, $router->getRouteResolver()->resolveRoute((string) $results->getRouteIdentifier()));
            }
            return $req;
        };
        $pathOf = fn(string $name): string => '/' . ltrim((string) parse_url((string) get_route($name, [], true), \PHP_URL_PATH), '/');
        $listPath = $pathOf('users-list');
        $loginPath = $pathOf('users-form-login');
        $bank = ['enabled' => true, 'paths' => ['/zz-viejo/a/b' => 'users-list', $loginPath => 'users-list', '/zz-viejo2' => 'zz-no-existe'], 'prefixes' => ['/zz-prefijo' => '/zz-nuevo']];
        $r = ProjectStart::legacyRedirectFor($request('GET', '/zz-viejo/a/b', 'x=1'), $bank);
        $check($r !== null && $r->getStatusCode() === 301 && $r->getHeaderLine('Location') === get_route('users-list', [], true) . '?x=1', 'h1 sin ruta que la reclame: 301 a la URL de la ruta, con la consulta', $r !== null ? $r->getStatusCode() . ' ' . $r->getHeaderLine('Location') : 'null');
        $check(ProjectStart::routeClaims($request('GET', $loginPath)) && ProjectStart::legacyRedirectFor($request('GET', $loginPath), $bank) === null, 'h2 una ruta registrada en esa dirección gana: el banco no la tapa', $loginPath);
        $check(ProjectStart::legacyRedirectFor($request('POST', '/zz-viejo/a/b'), $bank) === null, 'h3 solo GET y HEAD');
        $r = ProjectStart::legacyRedirectFor($request('GET', '/zz-prefijo/a/b'), $bank);
        $check($r !== null && $r->getHeaderLine('Location') === baseurl('zz-nuevo/a/b'), 'h4 un prefijo viejo: 301 a la dirección nueva', $r !== null ? $r->getHeaderLine('Location') : 'null');
        $check(ProjectStart::legacyRedirectFor($request('GET', '/zz-viejo2/a/b'), $bank) === null, 'h5 nada declarado para esa dirección: 404 como siempre');
        $check(ProjectStart::legacyRedirectFor($request('GET', '/zz-viejo2'), $bank) === null, 'h6 una ruta de destino que no existe se anota y deja el 404');
        $comodin = $request('GET', '/zz-viejo/a');
        $casa = RouteContext::fromRequest($comodin)->getRoute();
        $r = ProjectStart::legacyRedirectFor($comodin, ['enabled' => true, 'paths' => ['/zz-viejo/a' => 'users-list']]);
        $check($casa !== null && $casa->getName() === 'public-generic-2' && $r !== null && $r->getStatusCode() === 301, 'h7 un comodín de la zona pública (public-generic-2) casa y no reclama la dirección', ($casa !== null ? (string) $casa->getName() : 'sin ruta') . ' · ' . ($r !== null ? (string) $r->getStatusCode() : 'null'));
        $check($listPath !== '/' && ProjectStart::legacyRedirectsEnabled(['enabled' => 'true']) === false, 'h8 encendido es true, no «true»');
        $loop = ['enabled' => true, 'paths' => ['/zz-viejo/a/b' => 'users-list', $listPath => 'users-form-login']];
        $check(ProjectStart::legacyRedirectFor($request('GET', '/zz-viejo/a/b'), $loop) === null, 'h9 una ruta destino cuya dirección vuelve a casar con el banco no redirige', $listPath);
        $notice = ProjectStart::legacyRedirectNotice('/zz-viejo/a/b', ['kind' => 'route', 'value' => 'users-list']);
        $start = (string) file_get_contents(basepath('app/core/psr4/PiecesPHP/Core/ProjectStart.php'));
        $check(!str_contains($notice, '?') && str_contains($notice, '«/zz-viejo/a/b» redirige a la ruta «users-list»') && str_contains($start, 'new \\ErrorException(self::legacyRedirectNotice($path, $match), 0, \\E_USER_DEPRECATED'), 'h10 el aviso de desuso lleva la dirección vieja y el destino, nunca la consulta (ADR 0060 §6)', $notice);
        $claims = fn(string $path): bool => ProjectStart::routeClaims($request('GET', $path));
        $check($claims('/contact/general'), 'h13 una ruta solo POST de dos segmentos reclama la dirección aunque el GET lo resuelva el comodín');
        $check($claims('/users/verify'), 'h14 /users/verify (solo POST) reclama la dirección');
        $check(!$claims('/zz-generica/'), 'h15 una dirección que solo casa con el comodín no está reclamada');
        $check(!$claims('/zz-no/existe/nada'), 'h16 una dirección sin ruta no está reclamada');
        $check(ProjectStart::legacyRedirectFor($request('GET', '/contact/general'), ['enabled' => true, 'paths' => ['/contact/general' => 'users-list']]) === null, 'h17 el banco no redirige el GET de una dirección que una ruta POST reclama');
        $tildes = ['enabled' => true, 'paths' => ['/quiénes-somos' => 'users-list'], 'prefixes' => ['/noticias-viejas' => '/publicaciones-ñ']];
        $r = ProjectStart::legacyRedirectFor($request('GET', '/qui%C3%A9nes-somos'), $tildes);
        $check($r !== null && $r->getStatusCode() === 301 && $r->getHeaderLine('Location') === get_route('users-list', [], true), 'h11 una entrada con tilde casa con la petición cifrada, como la descifra Slim', $r !== null ? $r->getStatusCode() . ' ' . $r->getHeaderLine('Location') : 'null');
        $r = ProjectStart::legacyRedirectFor($request('GET', '/noticias-viejas/a%C3%B1o'), $tildes);
        $check($r !== null && $r->getStatusCode() === 301 && $r->getHeaderLine('Location') === baseurl('publicaciones-%C3%B1/a%C3%B1o'), 'h12 un prefijo con ñ: 301 con la Location cifrada segmento a segmento', $r !== null ? $r->getStatusCode() . ' ' . $r->getHeaderLine('Location') : 'null');
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        foreach ($previous as $key => $value) {
            set_config($key, $value);
        }
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalData;
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P399: las claves del arranque (public_area, admin_path, login_redirect_route), su validación, adónde lleva el acceso y el banco de redirecciones, en proceso.')->setEffects([CliActions::EFFECT_NONE])->register();
