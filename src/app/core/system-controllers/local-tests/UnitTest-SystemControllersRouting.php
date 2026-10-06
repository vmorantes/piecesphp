<?php

//Los cinco controladores del sistema con ControllerRoutingTrait: cada uno resuelve con su prefijo, la herencia no se lo cambia,
//y un usuario no aprobado conserva la edición de su perfil y los ajustes de su cuenta, pero no la gestión de usuarios.

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Tokens\Controllers\GenericTokenController;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\TimerController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Terminal\CliActions;
use SystemApprovals\SystemApprovalsMiddleware;

CliActions::make('unit-tests:core/system-controllers-routing', function ($args) {

    echoTerminal("\e[33m[TEST:SystemControllersRouting] Controladores del sistema al estándar de rutas\e[39m");
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

    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');

    try {
        //Con un root fijado: sin usuario, routeName() concede y no probaría nada del permiso.
        $root = UsersModel::model();
        $root->resetAll();
        $root->select(['id'])->where(['type' => UsersModel::TYPE_USER_ROOT])->execute(false, 1, 1);
        $idRoot = (int) (((array) (((array) $root->result())[0] ?? []))['id'] ?? 0);
        set_config('current_user', (object) ['id' => $idRoot]);
        set_config('pcsphp_current_user_stored', null);

        //─── a · Cada controlador, su prefijo ───────────────────────────────────────────────────────────
        echoTerminal('[a] routeName() con el prefijo de cada controlador');
        $casos = [
            'users-list' => fn() => UsersController::routeName('list'),
            'users-recovery-form' => fn() => UsersController::routeName('recovery-form'),
            'login-attempts-reports' => fn() => LoginAttemptsController::routeName('reports'),
            'admin-error-log' => fn() => AdminPanelController::routeName('error-log'),
            'timing-add' => fn() => TimerController::routeName('add'),
        ];
        foreach ($casos as $nombre => $llamada) {
            $esperada = get_route($nombre);
            $obtenida = $llamada();
            $check($idRoot > 0 && is_string($esperada) && $esperada !== '' && $obtenida === $esperada, "a1 {$nombre}", "{$obtenida} frente a {$esperada}");
        }
        $parametros = ['handler' => 'commentary', 'token' => 'zz-selector'];
        $check(GenericTokenController::routeName('view', $parametros) === get_route('generic-token-view', $parametros) && str_contains(GenericTokenController::routeName('view', $parametros), '/commentary/zz-selector'), 'a2 generic-token-view con sus parámetros');
        $check(RecoveryPasswordController::routeName('recovery-form') === get_route('users-recovery-form') && UserProblemsController::routeName('problems-list') === get_route('users-problems-list'), 'a3 los hijos de UsersController heredan su prefijo users');
        echoTerminal(' ');

        //─── b · La trampa de la herencia ───────────────────────────────────────────────────────────────
        echoTerminal('[b] Herencia');
        $check(UsersController::routeName('list') === get_route('users-list') && get_route('admin-list', [], true) === null, 'b1 UsersController::routeName(«list») no da admin-list aunque AdminPanelController tenga el trait');
        echoTerminal(' ');

        //─── c · Usuario no aprobado ────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Filtro de SystemApprovalsMiddleware');
        $conserva = fn(string $ruta) => SystemApprovalsMiddleware::keepsWhenNotApproved($ruta);
        $check($conserva('users-edit-request'), 'c1 conserva users-edit-request');
        $check($conserva('user-system-features-security') && $conserva('user-system-features-otp'), 'c2 conserva las user-system-features-*');
        $check(!$conserva('users-list') && !$conserva('users-form-edit') && !$conserva('users-form-create') && !$conserva('users-selection-create'), 'c3 no obtiene users-list ni el resto de la gestión de usuarios');
        $check(!$conserva('user-edit-request') && !$conserva('user-algo'), 'c4 el prefijo viejo «user-» ya no concede nada');
        echoTerminal(' ');

        //─── d · Nombres viejos ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Los 28 nombres viejos ya no existen');
        $viejos = [
            'recovery-form', 'new-password-create', 'user-forget-form', 'user-blocked-form', 'other-problems-form',
            'user-problems-list', 'login-request', 'verify-login-request', 'delete-account-request', 'register-request',
            'user-edit-request', 'recovery-password-request', 'recovery-password-request-code', 'new-password-create-code',
            'new-password-verify-code', 'user-forget-request-code', 'user-blocked-request-code', 'user-forget-get',
            'user-blocked-resolve', 'other-problems-send',
            'informes-acceso', 'attempts-export', 'not-logged-export', 'logged-export', 'informes-acceso-ajax',
            'about-framework', 'cropper-testing', 'tickets-create',
        ];
        $existen = array_values(array_filter($viejos, fn($n) => get_route($n, [], true) !== null));
        $check(count($viejos) === 28 && $existen === [], 'd1 ninguno de los 28 resuelve', implode(', ', $existen));
        echoTerminal(' ');

        //─── e · Cada informe de accesos exporta lo suyo ────────────────────────────────────────────────
        echoTerminal('[e] exportUrl de los informes de acceso');
        $exportaciones = [
            'logged' => get_route('login-attempts-export-logged'),
            'not-logged' => get_route('login-attempts-export-not-logged'),
            'attempts' => get_route('login-attempts-export-attempts'),
        ];
        foreach ($exportaciones as $informe => $esperada) {
            $obtenida = LoginAttemptsController::exportURLFor($informe);
            $check($obtenida === $esperada && $obtenida !== '', "e1 {$informe} exporta con su ruta", "{$obtenida} frente a {$esperada}");
        }
        $check(count(array_unique($exportaciones)) === 3, 'e2 las tres exportaciones son distintas');
        try {
            LoginAttemptsController::exportURLFor('otro');
            $check(false, 'e3 un informe desconocido → InvalidArgumentException', 'no lanzó');
        } catch (\InvalidArgumentException $e) {
            $check(true, 'e3 un informe desconocido → InvalidArgumentException');
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los controladores del sistema resuelven con su prefijo, la herencia no se lo cambia y el no aprobado conserva solo lo suyo.')->setEffects([CliActions::EFFECT_NONE])->register();
