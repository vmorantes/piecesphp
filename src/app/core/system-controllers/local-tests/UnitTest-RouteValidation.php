<?php

//P57: cada ruta se comprueba al registrarse. En local, un patrón que FastRoute no analiza revienta ahí, con su nombre y
//su archivo; fuera de local se apunta, se descarta y el panel avisa. No registra nada en el enrutador de la aplicación.

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\InvalidRoutes;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/route-validation', function ($args) {

    echoTerminal("\e[33m[TEST:RouteValidation] El patrón de cada ruta se comprueba al registrarla\e[39m");
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

    //Las rutas de la aplicación ya están registradas: se cuentan antes y después (z1).
    $rutasApp = fn(): int => count(get_config('slim_app')->getRouteCollector()->getRoutes());
    $antes = $rutasApp();
    $terminalPrevio = $_SERVER['PCSPHP_TERMINAL_DATA'];
    $invalidasPrevias = InvalidRoutes::all();
    SystemStatusRoutes::registerCoreAlerts();

    $malo = '/zz-p57/forms/edit[/]{id}';

    try {
        //─── a · En local ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] En local, el patrón malo revienta donde se escribe');
        InvalidRoutes::clear();
        $valida = new Route('/zz-p57/{id}[/]', 'Zz:zz', 'zz-p57-valida');
        $patron = $valida->routeSegment();
        $patron = is_string($patron) ? $patron : '';
        $check($patron === '/zz-p57/{id}[/]' && count(InvalidRoutes::all()) === 0, 'a1 un patrón válido con {id} y [/] no se queja', $patron);

        $lanzada = null;
        try {
            new Route($malo, 'Zz:zz', 'zz-p57-mala');
        } catch (\Throwable $e) {
            $lanzada = $e;
        }
        $mensaje = $lanzada !== null ? $lanzada->getMessage() : '';
        $check(
            $lanzada instanceof \InvalidArgumentException
                && str_contains($mensaje, 'zz-p57-mala') && str_contains($mensaje, $malo)
                && preg_match('~Declarada en .+UnitTest-RouteValidation\.php:\d+~', $mensaje) === 1,
            'a2 en local lanza InvalidArgumentException con nombre, patrón y archivo:línea',
            mb_substr($mensaje, 0, 220)
        );
        $check(count(InvalidRoutes::all()) === 0, 'a3 en local NO se apunta: revienta y se ve');
        echoTerminal(' ');

        //─── b · Fuera de local ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Fuera de local se descarta y se apunta');
        $_SERVER['PCSPHP_TERMINAL_DATA']['local'] = false;
        $descartada = new Route($malo, 'Zz:zz', 'zz-p57-descartada');
        $apuntadas = InvalidRoutes::all();
        $check(count($apuntadas) === 1 && $apuntadas[0]['name'] === 'zz-p57-descartada' && $apuntadas[0]['pattern'] === $malo, 'b1 fuera de local no lanza y queda apuntada', (string) json_encode($apuntadas));
        $check(str_contains((string) ($apuntadas[0]['declaredIn'] ?? ''), 'UnitTest-RouteValidation.php:'), 'b2 con el archivo:línea que la declaró', (string) ($apuntadas[0]['declaredIn'] ?? ''));

        //register() se la salta: se registra sobre el enrutador real y el total no cambia.
        $totalAntesRegistrar = $rutasApp();
        $descartada->register(get_config('slim_app'));
        $check($rutasApp() === $totalAntesRegistrar, 'b3 register() no la mete en el enrutador', $totalAntesRegistrar . ' → ' . $rutasApp());
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalPrevio;
        echoTerminal(' ');

        //─── c · El aviso ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] El aviso del panel');
        $aviso = SystemAlertRegistry::get('invalid-route-pattern');
        $check($aviso !== null && SystemAlertRegistry::isActive($aviso), 'c1 con rutas descartadas, el aviso está activo');
        $texto = $aviso !== null ? $aviso->message() : '';
        $check(str_contains($texto, 'zz-p57-descartada') && str_contains($texto, '1'), 'c2 su mensaje nombra la ruta y cuántas son', mb_substr($texto, 0, 200));
        $check($aviso !== null && $aviso->severity() === \PiecesPHP\SystemStatus\SystemAlert::SEVERITY_DANGER && $aviso->audience() === [UsersModel::TYPE_USER_ROOT] && $aviso->showAsNag() && !$aviso->isDismissible(), 'c3 peligro, solo root, flotante y no descartable');
        InvalidRoutes::clear();
        $check($aviso !== null && !SystemAlertRegistry::isActive($aviso), 'c4 clear() apaga el aviso');
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $terminalPrevio;
        InvalidRoutes::clear();
        foreach ($invalidasPrevias as $previa) {
            InvalidRoutes::add($previa['name'], $previa['pattern'], $previa['error'], $previa['declaredIn']);
        }
        $check($rutasApp() === $antes, 'z1 el enrutador de la aplicación queda con las mismas rutas', $antes . ' → ' . $rutasApp());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P57: el patrón de cada ruta se comprueba al registrarla; fuera de local se descarta y avisa.')->setEffects([CliActions::EFFECT_NONE])->register();
