<?php

//Sin usuario, routeName() da la dirección de todo (correos, cron, zona pública): allowedRoute() y el control de acceso
//de los módulos tienen que negar al visitante lo que declara require_login, o «podría» todo el panel. Ver D6.
//@areas src/app/classes/API/

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\RoutingUtils\DefaultAccessControlModules;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/anonymous-route-access';
$cliTaskDescription = 'Sin usuario, allowedRoute() y DefaultAccessControlModules niegan lo que pide sesión; routeName() sigue dando la dirección, y lo libre sigue abierto. Una ruta pedida en silencio sin sus parámetros no lanza.';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:AnonymousRouteAccess] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');

    $sinUsuario = function (): void {
        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
    };
    $conUsuario = function (int $id): void {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
    };
    $requiereSesion = function (string $ruta): ?bool {
        $info = get_route_info($ruta);
        return is_array($info) ? (($info['require_login'] ?? null) === true) : null;
    };

    //El manejador lanza una excepción propia: verla es la prueba de que el control dejó pasar.
    $manejador = new class implements \Psr\Http\Server\RequestHandlerInterface {
        public function handle(\Psr\Http\Message\ServerRequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            throw new \LogicException('zz-manejador-alcanzado');
        }
    };
    //El validador concede siempre: lo que niega, si niega, es la rama sin usuario.
    $control = new DefaultAccessControlModules('admin', fn () => 'zz-concede');
    $peticion = function (string $nombreRuta): \PiecesPHP\Core\Routing\RequestRoute {
        $ruta = new \Slim\Routing\Route(['GET'], '/zz-' . $nombreRuta, fn () => null, new \Slim\Psr7\Factory\ResponseFactory(), new \Slim\CallableResolver());
        $ruta->setName($nombreRuta);
        $base = new \PiecesPHP\Core\Routing\RequestRoute(
            'GET',
            (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-' . $nombreRuta),
            new \Slim\Psr7\Headers(),
            [],
            [],
            (new \Slim\Psr7\Factory\StreamFactory())->createStream('')
        );
        return $base->withAttribute('route', $ruta);
    };
    $pasar = function (string $nombreRuta) use ($control, $manejador, $peticion): string {
        try {
            $control->getResponse($peticion($nombreRuta), $manejador);
            return 'no lanzó';
        } catch (\LogicException $e) {
            return $e->getMessage();
        } catch (\Throwable $e) {
            return get_class($e);
        }
    };

    try {

        //──── 1. allowedRoute() sin usuario ─────────────────────────────────────────────────
        echoTerminal('[1/4] allowedRoute() sin usuario: niega lo que pide sesión y concede lo libre');
        $sinUsuario();

        if ($check($requiereSesion('admin') === true, 'p1 PRE: la ruta «admin» declara require_login')) {
            $check(AdminPanelController::allowedRoute('') === false, 'a1 sin usuario, AdminPanelController::allowedRoute(\'\') es false');
            $check(AdminPanelController::routeName('') !== '', 'a2 sin usuario, routeName(\'\') sigue dando la dirección (routeName NO cambia)');
        }

        if ($check($requiereSesion('public-index') === false, 'p2 PRE: la ruta libre «public-index» no declara require_login')) {
            $check(\App\Controller\PublicAreaController::allowedRoute('index') === true, 'a3 sin usuario, PublicAreaController::allowedRoute(\'index\') es true');
        }

        $infoCron = get_route_info('api-admin-cron-jobs');
        $rolesCron = is_array($infoCron) ? ($infoCron['roles_allowed'] ?? []) : [];
        if ($check($requiereSesion('api-admin-cron-jobs') === false && is_array($rolesCron) && count($rolesCron) > 0, 'p3 PRE: «api-admin-cron-jobs» declara roles y no require_login')) {
            $check(\API\Controllers\APIController::allowedRoute('cron-jobs', ['actionType' => 'run']) === true, 'a4 sin usuario, APIController::allowedRoute(\'cron-jobs\') es true (abierta a sabiendas, plan §2.4)');
        }
        echoTerminal(' ');

        //──── 2. allowedRoute() con usuario ─────────────────────────────────────────────────
        echoTerminal('[2/4] allowedRoute() con usuario: root sin regresión; un usuario que no carga cuenta como visitante');
        $conUsuario(1);
        $root = getLoggedFrameworkUser();
        if ($check($root !== null && (int) $root->type === UsersModel::TYPE_USER_ROOT, 'p4 PRE: con el id 1, getLoggedFrameworkUser() es root')) {
            $check(AdminPanelController::allowedRoute('') === true, 'a5 con root, AdminPanelController::allowedRoute(\'\') es true');
        }

        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select(['MAX(id) AS maxID'])->execute();
        $inexistente = (int) (((array) (((array) $usuarios->result())[0] ?? []))['maxID'] ?? 0) + 1000;
        $conUsuario($inexistente);
        if ($check(getLoggedFrameworkUser() === null, "p5 PRE: con el id inexistente {$inexistente}, getLoggedFrameworkUser() es null")) {
            $check(AdminPanelController::allowedRoute('') === false, 'a6 con un usuario que no carga, AdminPanelController::allowedRoute(\'\') es false');
        }
        echoTerminal(' ');

        //──── 3. DefaultAccessControlModules ────────────────────────────────────────────────
        echoTerminal('[3/4] DefaultAccessControlModules: sin usuario niega lo que pide sesión antes del validador');
        $sinUsuario();
        $obtenido = $pasar('admin');
        $check($obtenido === \Slim\Exception\HttpForbiddenException::class, 'm1 sin usuario, la ruta «admin» lanza HttpForbiddenException y el manejador NO se alcanza', $obtenido);

        if ($check($requiereSesion('admin-global-variables-css') === false, 'PRE: «admin-global-variables-css» no declara require_login')) {
            $obtenido = $pasar('admin-global-variables-css');
            $check($obtenido === 'zz-manejador-alcanzado', 'm2 sin usuario, la ruta libre del mismo prefijo llega al manejador', $obtenido);
        }

        $conUsuario(1);
        $obtenido = $pasar('admin');
        $check($obtenido === 'zz-manejador-alcanzado', 'm3 con root, la ruta «admin» llega al manejador', $obtenido);
        echoTerminal(' ');

        //──── 4. Una ruta con parámetros, sin ellos ─────────────────────────────────────────
        //Slim lanza si faltan segmentos; pedida en silencio, es el mismo «no hay» que una ruta inexistente (G7-2).
        echoTerminal('[4/4] Una ruta con parámetros, pedida sin ellos: en silencio no lanza; sin silencio, sí');
        $infoCron = get_route_info('api-admin-cron-jobs');
        $sinValorPorDefecto = is_array($infoCron) && array_key_exists('actionType', $infoCron['parameters'] ?? []) && $infoCron['parameters']['actionType'] === null;
        if ($check($sinValorPorDefecto, 'p6 PRE: «api-admin-cron-jobs» tiene el parámetro actionType sin valor por defecto')) {
            $intentar = function (callable $llamada): string {
                try {
                    return 'no lanzó: ' . var_export($llamada(), true);
                } catch (\Throwable $e) {
                    return get_class($e) . ': ' . $e->getMessage();
                }
            };
            foreach (['con root' => fn () => $conUsuario(1), 'sin usuario' => $sinUsuario] as $quien => $poner) {
                $poner();
                $obtenido = $intentar(fn () => \API\Controllers\APIController::allowedRoute('cron-jobs'));
                $check($obtenido === 'no lanzó: false', "s1 {$quien}, APIController::allowedRoute('cron-jobs') sin actionType es false y no lanza", $obtenido);
                $obtenido = $intentar(fn () => \API\Controllers\APIController::routeName('cron-jobs', [], true));
                $check($obtenido === "no lanzó: ''", "s2 {$quien}, routeName('cron-jobs', [], true) sin actionType es '' y no lanza", $obtenido);
            }
            $obtenido = $intentar(fn () => get_route('api-admin-cron-jobs', [], true));
            $check($obtenido === 'no lanzó: NULL', "s3 get_route('api-admin-cron-jobs', [], true) es null", $obtenido);
            $obtenido = $intentar(fn () => get_route('api-admin-cron-jobs'));
            $check(str_starts_with($obtenido, \InvalidArgumentException::class . ':'), 's4 sin silencio, get_route(\'api-admin-cron-jobs\') sigue lanzando InvalidArgumentException', $obtenido);
            $obtenido = $intentar(fn () => \API\Controllers\APIController::allowedRoute('cron-jobs', ['actionType' => 'run']));
            $check($obtenido === 'no lanzó: true', 's5 con el parámetro, allowedRoute(\'cron-jobs\') sigue siendo true', $obtenido);
        }
        echoTerminal(' ');

    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
    }

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:AnonymousRouteAccess] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Al visitante no se le concede lo que pide sesión ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
