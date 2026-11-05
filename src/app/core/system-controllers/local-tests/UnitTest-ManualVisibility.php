<?php

//Quién ve cada capítulo del manual (ADR 0072, JDY): `todos`, el permiso sobre alguna de `rutas` o, si la hay, de `ve`.
//En proceso, sin red y sin escribir: un índice SINTÉTICO, el usuario actual en memoria y Roles::hasPermissions().
//@areas src/app/classes/MySpace/

use MySpace\Controllers\MySpaceController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/manual-visibility';
$cliTaskDescription = 'El manual: root ve todo, un general ve lo de todos y no lo administrativo, y `ve` separa un capítulo mixto';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:ManualVisibility] Iniciando suite...', true, "\r\n", '33');
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

    //La administración que el general no puede, y una ruta que sí puede (la edición de su organización).
    $soloAdministracion = 'organizations-admin-list';
    $laPuedenTodos = 'organizations-admin-forms-edit';
    $lang = Config::get_lang();
    $capitulo = fn(string $slug, $rutas, ?array $ve = null): array => ['slug' => $slug, 'titulo' => $slug, 'rutas' => $rutas]
         + ($ve !== null ? ['ve' => $ve] : []) + ['idioma' => $lang, 'origen' => 'framework', 'fecha' => '2026-11-04'];
    $indice = [
        $capitulo('zz-10-todos', 'todos'),
        $capitulo('zz-20-administracion', [$soloAdministracion]),
        $capitulo('zz-30-mixto-con-ve', [$soloAdministracion, $laPuedenTodos], [$soloAdministracion]),
    ];
    $sinVe = [$capitulo('zz-30-mixto-sin-ve', [$soloAdministracion, $laPuedenTodos])];

    //──── 0. Lo que la prueba da por hecho ────────────────────────────────────────────────────
    echoTerminal('[0/2] Las premisas: los permisos de las rutas y los usuarios de la base');
    $general = UsersModel::TYPE_USER_GENERAL;
    $check(!Roles::hasPermissions($soloAdministracion, $general) && Roles::hasPermissions($laPuedenTodos, $general), "p1 el general no puede {$soloAdministracion} y sí {$laPuedenTodos}");
    $check(Roles::hasPermissions($soloAdministracion, UsersModel::TYPE_USER_ROOT), "p2 root puede {$soloAdministracion}");
    $database = (new BaseModel())->getDatabase();
    $tabla = UsersModel::TABLE;
    $unoDe = function (int $tipo) use ($database, $tabla): int {
        $id = $database !== null ? $database->query("SELECT id FROM `{$tabla}` WHERE type = {$tipo} AND status = " . UsersModel::STATUS_USER_ACTIVE . ' ORDER BY id LIMIT 1')->fetchColumn() : false;
        return $id === false ? 0 : (int) $id;
    };
    $idRoot = $unoDe(UsersModel::TYPE_USER_ROOT);
    $idGeneral = $unoDe($general);
    if (!$check($idRoot > 0 && $idGeneral > 0, 'p3 hay un root y un general activos en la base (solo se leen)', "root {$idRoot}, general {$idGeneral}")) {
        return ['success' => false, 'message' => 'Sin un root y un general activos la suite no puede mirar nada.'];
    }
    echoTerminal(' ');

    //──── 1. Quién ve qué ─────────────────────────────────────────────────────────────────────
    echoTerminal('[1/2] Lo que devuelve manualChapters() con el índice sintético');
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $previoPendientes = get_config('manual_for_pending');
    $comoUsuario = function (int $id, ?array $indice): array {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
        return array_column(MySpaceController::manualChapters($indice), 'slug');
    };
    try {
        //Una cuenta sin aprobar no ve nada con manual_for_pending en false: aquí no se mide eso.
        set_config('manual_for_pending', true);

        $root = $comoUsuario($idRoot, $indice);
        $check($root === ['zz-10-todos', 'zz-20-administracion', 'zz-30-mixto-con-ve'], 'v1 root ve los tres', json_encode($root) ?: '');

        $vistos = $comoUsuario($idGeneral, $indice);
        $check(in_array('zz-10-todos', $vistos, true), 'v2 el general ve el capítulo de todos', json_encode($vistos) ?: '');
        $check(!in_array('zz-20-administracion', $vistos, true), 'v3 DISCRIMINANTE: el general NO ve el de administración (cae si se quita el filtro de permiso)');
        $check(!in_array('zz-30-mixto-con-ve', $vistos, true), 'v4 DISCRIMINANTE: el general NO ve el mixto con `ve` (cae si se ignora `ve`)');

        $mixto = $comoUsuario($idGeneral, $sinVe);
        $check($mixto === ['zz-30-mixto-sin-ve'], 'v5 sin `ve`, el mixto lo ve el general: deciden todas sus rutas', json_encode($mixto) ?: '');

        $real = $comoUsuario($idRoot, null);
        $check(count($real) > 3 && array_filter($real, fn(string $slug): bool => str_starts_with($slug, 'zz-')) === [], 'v6 sin índice, el real: el sintético no se queda pegado', count($real) . ' capítulo(s)');
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        set_config('manual_for_pending', $previoPendientes);
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:ManualVisibility] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Cada capítulo del manual lo ve quien debe ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
