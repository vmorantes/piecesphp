<?php

//Avisos del sistema y mantenimiento: el registro, el aviso de app_key con su marca vieja, y el borrado que solo toca enlaces rotos.
//Escribe de verdad las opciones system_alerts_hidden y hide_app_key_warning y las repone en el finally: db-backup antes.

use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\SystemStatus\ServerDelegatedLinks;
use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\SystemStatus\SystemStatusRoutes;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/system-status', function ($args) {

    echoTerminal("\e[33m[TEST:SystemStatus] Avisos del sistema y mantenimiento\e[39m");
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
    $lanza = function (callable $fn): bool {
        try {
            $fn();
            return false;
        } catch (\InvalidArgumentException $e) {
            return true;
        }
    };

    //Estado previo: las dos opciones que la prueba escribe y el registro real.
    $opciones = [SystemAlertRegistry::HIDDEN_CONFIG, 'hide_app_key_warning'];
    $previas = [];
    foreach ($opciones as $opcion) {
        $previas[$opcion] = [SettingsModel::optionExists($opcion), SettingsModel::getConfigValue($opcion)];
    }
    $configPrevia = get_config('hide_app_key_warning');
    SystemStatusRoutes::registerCoreAlerts();
    $registroReal = SystemAlertRegistry::swapForTesting();
    $base = sys_get_temp_dir() . '/zz-system-status-' . bin2hex(random_bytes(4));
    //La constante la define el arranque; PHPStan no la ve.
    $log = (string) constant('LOG_ERRORS_PATH') . '/error.log.json';

    try {
        //─── a · Registro ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Registro');
        $aviso = fn(string $key, bool $activo = true, bool $ocultable = true, array $para = [UsersModel::TYPE_USER_ROOT]) => new SystemAlert($key, SystemAlert::SEVERITY_INFO, fn() => "Mensaje {$key}", fn() => $activo, $ocultable, $para);
        SystemAlertRegistry::register($aviso('zz-uno'));
        $check($lanza(fn() => SystemAlertRegistry::register($aviso('zz-uno'))), 'a1 key repetida → excepción');
        $check($lanza(fn() => new SystemAlert('zz-dos', 'grave', fn() => '', fn() => true, true, [0])) && $lanza(fn() => new SystemAlert('Zz Mal', 'info', fn() => '', fn() => true, true, [0])) && $lanza(fn() => new SystemAlert('zz-tres', 'info', fn() => '', fn() => true, true, [])), 'a2 gravedad inválida, key que no es kebab o sin audiencia → excepción');
        SystemAlertRegistry::register($aviso('zz-admin', true, true, [UsersModel::TYPE_USER_ADMIN_GRAL]));
        SystemAlertRegistry::register($aviso('zz-inactivo', false));
        SystemAlertRegistry::register($aviso('zz-fijo', true, false));
        $claves = fn(array $avisos) => array_map(fn(SystemAlert $a) => $a->key(), $avisos);
        $check($claves(SystemAlertRegistry::visibleFor(UsersModel::TYPE_USER_ROOT)) === ['zz-uno', 'zz-fijo'] && $claves(SystemAlertRegistry::visibleFor(UsersModel::TYPE_USER_ADMIN_GRAL)) === ['zz-admin'], 'a3 visibleFor por tipo, sin los inactivos');
        $check(SystemAlertRegistry::hide('zz-uno') && !SystemAlertRegistry::hide('zz-fijo') && !SystemAlertRegistry::hide('zz-nada'), 'a4 solo se oculta lo ocultable y existente');
        $check($claves(SystemAlertRegistry::visibleFor(UsersModel::TYPE_USER_ROOT)) === ['zz-fijo'] && SystemAlertRegistry::hidden() === ['zz-uno'], 'a5 oculto, deja de verse y queda en la configuración');
        $check(SystemAlertRegistry::show('zz-uno') && $claves(SystemAlertRegistry::visibleFor(UsersModel::TYPE_USER_ROOT)) === ['zz-uno', 'zz-fijo'], 'a6 mostrar lo devuelve');
        $tamanoLog = is_file($log) ? (int) filesize($log) : 0;
        SystemAlertRegistry::register(new SystemAlert('zz-roto', SystemAlert::SEVERITY_DANGER, fn() => 'roto', function (): bool {
            throw new \RuntimeException('zz aviso roto a propósito');
        }, false, [UsersModel::TYPE_USER_ROOT], true));
        clearstatcache();
        $visibles = $claves(SystemAlertRegistry::visibleFor(UsersModel::TYPE_USER_ROOT));
        clearstatcache();
        $check(!in_array('zz-roto', $visibles, true) && (is_file($log) ? (int) filesize($log) : 0) !== $tamanoLog, 'a7 un isActive que lanza cuenta como no activo y queda en el log', (string) json_encode($visibles));
        $check($claves(SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT)) === [], 'a8 nagsFor solo los que van como aviso flotante (el roto no cuenta)');
        echoTerminal(' ');

        //─── b · app_key ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Aviso de app_key');
        SystemAlertRegistry::swapForTesting($registroReal);
        $placeholder = \PiecesPHP\Core\Config::app_key_is_placeholder();
        set_config('hide_app_key_warning', false);
        $nagsRoot = $claves(SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT));
        $check($placeholder && in_array('app-key-placeholder', $nagsRoot, true) && in_array('app-key-placeholder', $claves(SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ADMIN_GRAL)), true) && $claves(SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_GENERAL)) === [], 'b1 con la clave de relleno, activo para root y admin general y como nag; no para el general', (string) json_encode($nagsRoot));
        set_config('hide_app_key_warning', true);
        $check(!in_array('app-key-placeholder', $claves(SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT)), true) && SystemAlertRegistry::isHidden('app-key-placeholder'), 'b2 la marca vieja hide_app_key_warning lo oculta');
        $check(SystemAlertRegistry::show('app-key-placeholder') && get_config('hide_app_key_warning') === false && SettingsModel::getConfigValue('hide_app_key_warning') === false && in_array('app-key-placeholder', $claves(SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT)), true), 'b3 «Mostrar» desde el registro desmarca también la marca vieja');
        $check(!in_array('server-delegated-broken-links', $claves(SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT)), true) && !SystemAlertRegistry::get('server-delegated-broken-links')?->showAsNag(), 'b4 el de enlaces rotos no va como nag');
        echoTerminal(' ');

        //─── c · Enlaces rotos ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Borrar enlaces rotos, en una raíz temporal propia');
        $raiz = "{$base}/server-delegated";
        $destino = "{$base}/destino/bueno.txt";
        foreach (["{$raiz}/a", "{$raiz}/solo-roto", "{$base}/destino"] as $dir) {
            if (!mkdir($dir, 0775, true)) {
                throw new \RuntimeException("No se puede crear «{$dir}».");
            }
        }
        if (file_put_contents($destino, 'zz') === false || file_put_contents("{$raiz}/a/normal.txt", 'zz') === false
            || !symlink($destino, "{$raiz}/a/bueno.lnk") || !symlink("{$base}/no-existe.txt", "{$raiz}/a/roto.lnk")
            || !symlink("{$base}/tampoco.txt", "{$raiz}/solo-roto/roto2.lnk")) {
            throw new \RuntimeException('No se pudo montar el árbol de prueba.');
        }
        ServerDelegatedLinks::useRootForTesting($raiz);
        $antes = ServerDelegatedLinks::scan();
        $check($antes === ['total' => 3, 'broken' => ['a/roto.lnk', 'solo-roto/roto2.lnk']], 'c1 scan: 3 enlaces, 2 rotos', (string) json_encode($antes));
        $resultado = ServerDelegatedLinks::deleteBroken();
        $check($resultado === ['deleted' => ['a/roto.lnk', 'solo-roto/roto2.lnk'], 'emptiedDirectories' => ['solo-roto'], 'failed' => 0], 'c2 borra solo los rotos y la carpeta que queda vacía', (string) json_encode($resultado));
        $check(is_link("{$raiz}/a/bueno.lnk") && is_file($destino) && is_file("{$raiz}/a/normal.txt") && is_dir($raiz) && is_dir("{$raiz}/a"), 'c3 el enlace bueno, su destino, el archivo normal, la carpeta con contenido y la raíz siguen');
        $check(ServerDelegatedLinks::scan() === ['total' => 1, 'broken' => []], 'c4 después: 1 enlace, 0 rotos');
        ServerDelegatedLinks::useRootForTesting(null);
        echoTerminal(' ');

        //─── d · Páginas ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Páginas, con usuario fijado');
        $grupo = new RouteGroup('zz-system-status');
        SystemStatusController::routes($grupo);
        $rutas = [];
        foreach ((array) (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo) as $r) {
            if ($r instanceof Route) {
                $rutas[] = [$r->name(), $r->method(), $r->rolesAllowed()];
            }
        }
        $check($rutas === [
            ['system-status-alerts', 'GET', [UsersModel::TYPE_USER_ROOT, UsersModel::TYPE_USER_ADMIN_GRAL]],
            ['system-status-alerts-toggle', 'POST', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-maintenance', 'GET', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-maintenance-broken-links', 'POST', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-maintenance-clean', 'POST', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-site-maintenance', 'GET', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-site-maintenance-save', 'POST', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-mail-log', 'GET', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-mail-log-datatables', 'GET', [UsersModel::TYPE_USER_ROOT]],
            ['system-status-mail-log-body', 'GET', [UsersModel::TYPE_USER_ROOT]],
        ], 'd1 rutas y roles: avisos para root y admin general; el resto solo root', (string) json_encode($rutas));
        $check(Roles::hasPermissions('system-status-maintenance', UsersModel::TYPE_USER_ADMIN_GRAL) === false && Roles::hasPermissions('system-status-maintenance', UsersModel::TYPE_USER_ROOT) === true && Roles::hasPermissions('system-status-alerts', UsersModel::TYPE_USER_ADMIN_GRAL) === true, 'd2 el administrador general no entra en mantenimiento (403 por ruta) y sí en avisos');
        $pintar = function (bool $isRoot, array $rows): string {
            $langGroup = SystemStatusController::LANG_GROUP;
            $title = 'Avisos del sistema';
            $breadcrumbs = '';
            $toggleURL = 'https://zz/toggle';
            ob_start();
            include basepath('app/classes/PiecesPHP/SystemStatus/Views/alerts.php');
            return (string) ob_get_clean();
        };
        $filasRoot = SystemStatusController::alertRows(UsersModel::TYPE_USER_ROOT);
        $filasAdmin = SystemStatusController::alertRows(UsersModel::TYPE_USER_ADMIN_GRAL);
        $htmlRoot = $pintar(true, $filasRoot);
        $htmlAdmin = $pintar(false, $filasAdmin);
        $check(str_contains($htmlRoot, 'data-system-alert-toggle data-key="app-key-placeholder"') && !str_contains($htmlAdmin, 'data-system-alert-toggle') && str_contains($htmlAdmin, 'data-system-alert-row="app-key-placeholder"'), 'd3 root ve los interruptores; el administrador general ve los avisos sin interruptor');
        $check(!str_contains($htmlRoot, 'href=""') && !str_contains($htmlAdmin, 'href=""'), 'd4 ningún href vacío');
        $check(str_contains($pintar(true, []), 'No hay avisos activos.'), 'd5 sin avisos: «No hay avisos activos.»');
        $estado = SystemStatusController::maintenanceStatus();
        $check(is_int($estado['linksTotal']) && is_int($estado['webpCacheBytes']) && $estado['staticsStamp'] !== '' && count($estado['brokenShown']) <= SystemStatusController::BROKEN_LINKS_SHOWN, 'd6 el estado de mantenimiento se calcula', (string) json_encode(array_diff_key($estado, ['brokenShown' => 1])));
        echoTerminal(' ');

        //─── e · bin/cli system-alerts ──────────────────────────────────────────────────────────────────
        echoTerminal('[e] bin/cli system-alerts: las líneas de la terminal');
        $registroD = SystemAlertRegistry::swapForTesting();
        try {
            $check(\Terminal\Tasks\SystemAlertsTask::lines() === ['Sin avisos activos.'], 'e1 registro vacío → «Sin avisos activos.»');
            SystemAlertRegistry::register(new SystemAlert('zz-peligro', SystemAlert::SEVERITY_DANGER, fn() => 'Mensaje zz-peligro', fn() => true, true, [UsersModel::TYPE_USER_ROOT], false, 'zz-ruta', 'zz'));
            SystemAlertRegistry::register(new SystemAlert('zz-inactivo', SystemAlert::SEVERITY_WARNING, fn() => 'Mensaje zz-inactivo', fn() => false, true, [UsersModel::TYPE_USER_ROOT]));
            SystemAlertRegistry::register(new SystemAlert('zz-lanza', SystemAlert::SEVERITY_INFO, fn() => 'Mensaje zz-lanza', function (): bool {
                throw new \RuntimeException('zz-system-alerts');
            }, true, [UsersModel::TYPE_USER_ROOT]));
            SystemAlertRegistry::register(new SystemAlert('zz-oculto', SystemAlert::SEVERITY_INFO, fn() => 'Mensaje zz-oculto', fn() => true, true, [UsersModel::TYPE_USER_ROOT]));
            $check(SystemAlertRegistry::hide('zz-oculto'), 'e2 se oculta zz-oculto (escribe system_alerts_hidden)');
            $lineas = \Terminal\Tasks\SystemAlertsTask::lines();
            SystemAlertRegistry::show('zz-oculto');
            $check(in_array('[PELIGRO] zz-peligro — Mensaje zz-peligro → ruta: zz-ruta', $lineas, true), 'e3 activo de peligro: «[PELIGRO] zz-peligro — …» con su ruta', (string) json_encode($lineas, \JSON_UNESCAPED_UNICODE));
            $check(count(array_filter($lineas, fn($l) => str_contains($l, 'zz-inactivo'))) === 0, 'e4 el inactivo no sale');
            $check(count(array_filter($lineas, fn($l) => str_contains($l, 'zz-lanza'))) === 0 && count($lineas) === 2, 'e5 el que lanza al evaluarse no sale ni tumba la lista');
            $check(in_array('[INFO] zz-oculto — Mensaje zz-oculto (oculto en el panel)', $lineas, true), 'e6 el oculto sale con «(oculto en el panel)»');
            $check(!SystemAlertRegistry::isHidden('zz-oculto'), 'e7 show() lo deja visible otra vez');
        } finally {
            SystemAlertRegistry::swapForTesting($registroD);
        }
        echoTerminal(' ');

        //─── f · Borrados por separado ──────────────────────────────────────────────────────────────────
        echoTerminal('[f] Los cuatro destinos, y deleteAll() en una raíz temporal propia');
        //f.1 · El CAMINO DE FALLO primero: un destino que no está en la lista no se toca.
        $noValidos = ['publications', '', null, 0, 'STATICS-STAMP', ['statics-stamp']];
        $colados = [];
        foreach ($noValidos as $candidato) {
            if (SystemStatusController::isCleanTarget($candidato)) {
                $colados[] = (string) json_encode($candidato);
            }
        }
        //El 0 y el 'STATICS-STAMP' están a propósito: prueban que la comparación es estricta y sensible
        //a mayúsculas. Con in_array() no estricto, el 0 pasaría.
        $check(count($colados) === 0, 'f1 ningún destino inválido pasa la validación', 'colados: ' . implode(', ', $colados));
        $rechazados = [];
        foreach (SystemStatusController::CLEAN_TARGETS as $valido) {
            if (!SystemStatusController::isCleanTarget($valido)) {
                $rechazados[] = $valido;
            }
        }
        $check(count($rechazados) === 0 && count(SystemStatusController::CLEAN_TARGETS) === 4,
            'f2 los cuatro destinos declarados sí pasan', 'rechazados: ' . implode(', ', $rechazados)
            . ' · declarados: ' . count(SystemStatusController::CLEAN_TARGETS));

        //f.2 · deleteAll() sobre una raíz propia: borra TODOS los enlaces, y nada más que enlaces.
        $raizF = "{$base}/todos";
        $destinoF = "{$base}/destino-f/bueno.txt";
        foreach (["{$raizF}/a", "{$raizF}/solo-enlace", "{$base}/destino-f"] as $dir) {
            if (!mkdir($dir, 0775, true)) {
                throw new \RuntimeException("No se puede crear «{$dir}».");
            }
        }
        if (file_put_contents($destinoF, 'zz') === false || file_put_contents("{$raizF}/a/normal.txt", 'zz') === false
            || !symlink($destinoF, "{$raizF}/a/bueno.lnk") || !symlink($destinoF, "{$raizF}/bueno2.lnk")
            || !symlink("{$base}/no-existe-f.txt", "{$raizF}/a/roto.lnk")
            || !symlink($destinoF, "{$raizF}/solo-enlace/dentro.lnk")) {
            throw new \RuntimeException('No se pudo montar el árbol de deleteAll().');
        }
        ServerDelegatedLinks::useRootForTesting($raizF);
        try {
            $antesF = ServerDelegatedLinks::scan();
            $check($antesF === ['total' => 4, 'broken' => ['a/roto.lnk']], 'f3 antes: 4 enlaces, 1 roto', (string) json_encode($antesF));
            $todos = ServerDelegatedLinks::deleteAll();
            $check($todos === ['deleted' => ['a/bueno.lnk', 'a/roto.lnk', 'bueno2.lnk', 'solo-enlace/dentro.lnk'], 'emptiedDirectories' => ['solo-enlace'], 'failed' => 0],
                'f4 borra los CUATRO enlaces y la carpeta que queda vacía', (string) json_encode($todos));
            //Lo que NO se puede haber tocado: un archivo normal, el destino de los enlaces sanos, y la raíz.
            $check(is_file("{$raizF}/a/normal.txt"), 'f5 el archivo normal, que no es enlace, sigue intacto');
            $check(is_file($destinoF), 'f6 el destino de los enlaces sanos sigue intacto: se borra el enlace, no lo que apunta');
            $check(is_dir($raizF) && is_dir("{$raizF}/a"), 'f7 la raíz en pie, y la carpeta con contenido también');
            $check(ServerDelegatedLinks::scan() === ['total' => 0, 'broken' => []], 'f8 después: ningún enlace');
        } finally {
            ServerDelegatedLinks::useRootForTesting(null);
        }

        //f.3 · Lo que no se puede borrar se CUENTA: una carpeta sin permiso de escritura (0555), sin sudo.
        $raizG = "{$base}/sin-permiso";
        $cerrada = "{$raizG}/cerrada";
        if (!mkdir($cerrada, 0775, true) || !symlink($destinoF, "{$cerrada}/sano.lnk") || !symlink("{$base}/no-existe-g.txt", "{$cerrada}/roto.lnk")
            || !symlink("{$base}/no-existe-g2.txt", "{$raizG}/roto-abierto.lnk") || !chmod($cerrada, 0555)) {
            throw new \RuntimeException('No se pudo montar el árbol sin permiso.');
        }
        clearstatcache();
        ServerDelegatedLinks::useRootForTesting($raizG);
        try {
            $rotos = ServerDelegatedLinks::deleteBroken();
            $check($rotos['deleted'] === ['roto-abierto.lnk'] && $rotos['failed'] === 1, 'f9 deleteBroken(): borra el roto que puede y CUENTA el que no puede', (string) json_encode($rotos));
            $todosG = ServerDelegatedLinks::deleteAll();
            $check($todosG['deleted'] === [] && $todosG['failed'] === 2, 'f10 deleteAll(): los dos enlaces de la carpeta cerrada, contados como no borrados', (string) json_encode($todosG));
            $check(is_link("{$cerrada}/sano.lnk") && is_link("{$cerrada}/roto.lnk"), 'f11 y siguen ahí: no se intentó borrarlos ni se dieron por borrados');
        } finally {
            ServerDelegatedLinks::useRootForTesting(null);
            //RETORNO-IGNORADO: se abre para que la limpieza general pueda borrar la carpeta; si no se pudiera, lo diría z.
            chmod($cerrada, 0775);
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        ServerDelegatedLinks::useRootForTesting(null);
        SystemAlertRegistry::swapForTesting($registroReal);
        //La configuración vuelve a como estaba: si la opción no existía, se borra la fila que creó la prueba.
        foreach ($previas as $opcion => [$existia, $valor]) {
            if ($existia) {
                SettingsModel::setConfigValue($opcion, $valor);
            } else {
                $borrar = SettingsModel::model();
                $borrar->resetAll();
                $borrar->delete(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([\PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::isEqual('name', $opcion)]))->execute();
            }
        }
        set_config('hide_app_key_warning', $configPrevia);
        if (is_dir($base)) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $f) {
                $p = $f->getPathname();
                //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
                (is_link($p) || !$f->isDir()) ? @unlink($p) : @rmdir($p);
            }
            //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
            @rmdir($base);
        }
        $repuesta = true;
        foreach ($previas as $opcion => [$existia, $valor]) {
            $repuesta = $repuesta && SettingsModel::optionExists($opcion) === $existia;
        }
        $check($repuesta && !is_dir($base), 'z1 configuración repuesta y temporales borrados');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Avisos del sistema y mantenimiento: registro, app_key, borrado de enlaces rotos y los cuatro borrados por separado.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
