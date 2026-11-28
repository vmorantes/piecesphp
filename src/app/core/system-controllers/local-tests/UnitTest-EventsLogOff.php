<?php

//H381: apagado el registro de acciones, addLog() no toca su tabla y devuelve un mapper sin id. Una TEMPORARY TABLE de sesión
//con su nombre la tapa solo en esta conexión (toda lectura falla); se quita en finally y la real no se toca.
//@areas src/app/classes/EventsLog/

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Seeds\UsersSeeder;

CliActions::make('unit-tests:core/events-log-off', function ($args) {

    echoTerminal("\e[33m[TEST:events-log-off] Con el registro de acciones apagado, su tabla ni se lee ni se escribe\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };
    $sinError = function (callable $paso) {
        try {
            return ['ok' => true, 'valor' => $paso(), 'error' => ''];
        } catch (\Throwable $e) {
            return ['ok' => false, 'valor' => null, 'error' => get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200)];
        }
    };

    $database = (new BaseModel())->getDatabase();
    $pdoDelMapper = LogsMapper::model()->getDatabase();
    if (!$check($database !== null && $pdoDelMapper !== null, 'c1 hay conexión a la base')) {
        return $balance();
    }
    $conexion = fn ($pdo): int => (int) $pdo->query('SELECT CONNECTION_ID()')->fetchColumn();
    //La temporal solo tapa en su conexión: el mapper y esta prueba tienen que compartirla.
    if (!$check($conexion($database) === $conexion($pdoDelMapper), 'c2 CANARIO: el mapper del registro de acciones y la prueba usan la misma conexión')) {
        return $balance();
    }

    $tabla = LogsMapper::TABLE;
    //Sin la tabla (el módulo apagado desde el principio), la temporal la crea en vez de taparla: la prueba vale igual.
    $existia = $sinError(fn () => $database->query("SELECT 1 FROM `{$tabla}` LIMIT 1")->fetchAll())['ok'];
    $tapada = false;
    $antes = LogsRoutes::swapEnabledForTesting(null);
    try {
        $database->exec("CREATE TEMPORARY TABLE `{$tabla}` (zz_sin_columnas INT)");
        $tapada = true;
        $lectura = $sinError(fn () => $database->query("SELECT textMessage FROM `{$tabla}` LIMIT 1")->fetchAll());
        $check(!$lectura['ok'], 'c3 CANARIO: con la tabla tapada, leerla falla', $lectura['ok'] ? 'no falló' : '');
        LogsRoutes::swapEnabledForTesting(true);
        $encendido = $sinError(fn () => LogsMapper::addLog(LogsMapper::MSG_MAIL_TEST_SENT, ['to' => 'zz-agente-jpw@example.com']));
        $check(!$encendido['ok'] && str_contains($encendido['error'], 'textMessage'), 'c4 CANARIO: encendido, addLog() sí escribe la tabla (falla contra la tapada, por su columna)', $encendido['ok'] ? 'no falló' : $encendido['error']);

        LogsRoutes::swapEnabledForTesting(false);
        echoTerminal('');
        echoTerminal('[1] Apagado');
        $r = $sinError(fn () => LogsMapper::addLog(LogsMapper::MSG_MAIL_TEST_SENT, ['to' => 'zz-agente-jpw@example.com']));
        $check($r['ok'] && $r['valor'] instanceof LogsMapper && $r['valor']->id === null, '1a addLog() no la toca y devuelve un mapper sin guardar (sin id)', $r['error']);
        $r = $sinError(fn () => LogsMapper::addLogSafe(LogsMapper::MSG_MAIL_TEST_SENT, ['to' => 'zz-agente-jpw@example.com']));
        $check($r['ok'] && $r['valor'] instanceof LogsMapper && $r['valor']->id === null, '1b addLogSafe() tampoco: devuelve el mismo mapper sin id, no null', $r['error'] ?: var_export($r['valor'], true));
        $check(!array_key_exists($tabla, (new UsersSeeder())->ownedRows()), '1c el seeder de usuarios no la cuenta como suya');

        //La ruta del historial de tokens lee la tabla y se registra al arrancar: el conmutador no la cubre, se mira el código.
        $codigo = '';
        foreach (token_get_all((string) file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Controllers/UserTokensController.php'))) as $token) {
            $codigo .= is_array($token) ? ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT ? $token[1] : '') : $token;
        }
        $codigo = (string) preg_replace('/\s+/', ' ', $codigo);
        $guarda = '...(LogsRoutes::enabled() ? [new Route("{$startRoute}/history[/]", self::class . \':history\', self::$baseRouteName . \'-history\',';
        $check(str_contains($codigo, $guarda) && substr_count($codigo, "'-history'") === 1, '1d la ruta del historial de tokens solo se registra con el registro de acciones encendido', 'UserTokensController.php no lleva: ' . $guarda);
    } catch (\Throwable $e) {
        $check(false, 'c0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        LogsRoutes::swapEnabledForTesting($antes);
        if ($tapada) {
            $database->exec("DROP TEMPORARY TABLE IF EXISTS `{$tabla}`");
        }
        $real = $sinError(fn () => $database->query("SELECT COUNT(*) FROM `{$tabla}`")->fetchColumn());
        $check($existia ? $real['ok'] : !$real['ok'], $existia ? 'z1 la tabla real vuelve a verse en esta conexión' : 'z1 no había tabla, y la temporal ya no está', $real['error']);
    }

    return $balance();

})->setDescription('H381: con el registro de acciones apagado, addLog(), addLogSafe() y el seeder de usuarios no tocan su tabla (tapada en la sesión por una temporal); addLog devuelve un mapper sin id.')->setEffects([CliActions::EFFECT_DATABASE])->register();
