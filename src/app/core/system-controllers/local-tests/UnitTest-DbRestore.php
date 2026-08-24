<?php

//El inverso de db-backup, probado de verdad: respaldar, cambiar, restaurar, comprobar. Ver T71.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\DbRestoreTask;

CliActions::make('unit-tests:core/db-restore', function ($args) {

    echoTerminal("\e[33m[TEST:db-restore] El viaje de ida y vuelta\e[39m");
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

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        echoTerminal("   \e[31mSin conexión: la suite no puede correr.\e[39m");
        return ['success' => false, 'message' => 'sin conexión'];
    }

    $tmpDatabase = 'pcs_zz_restore_trip';
    $original = (string) $db->getDatabaseName();
    $dump = basepath('../files/dev/zz-restore-trip.sql');
    $trace = basepath('../' . DbRestoreTask::TRACE_RELATIVE_PATH);
    $traceBefore = is_file($trace) ? (string) file_get_contents($trace) : null;
    $cli = escapeshellcmd(basepath('../bin/cli'));

    try {
        $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`");
        $db->exec("CREATE DATABASE `{$tmpDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");
        $db->exec("USE `{$tmpDatabase}`");
        $db->exec("CREATE TABLE `zz_viaje` (`id` int NOT NULL AUTO_INCREMENT, `valor` varchar(80) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB");
        $db->exec("INSERT INTO `zz_viaje` (`valor`) VALUES ('EL-ORIGINAL')");

        $valor = static function () use ($db, $tmpDatabase): ?string {
            $r = $db->query("SELECT `valor` FROM `{$tmpDatabase}`.`zz_viaje` WHERE id = 1")->fetchColumn();
            return $r === false ? null : (string) $r;
        };
        $check($valor() === 'EL-ORIGINAL', 'se parte de un valor conocido');

        //─── RESPALDAR: el volcado de esta tabla, con la forma que produce el exportador ───
        file_put_contents($dump, implode("\n", [
            '-- Volcado de prueba',
            'DROP TABLE IF EXISTS `zz_viaje`;',
            'CREATE TABLE `zz_viaje` (`id` int NOT NULL AUTO_INCREMENT, `valor` varchar(80) NOT NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB;',
            "INSERT INTO `zz_viaje` (`id`, `valor`) VALUES (1, 'EL-ORIGINAL');",
            '',
        ]));
        $check(is_file($dump), 'hay un volcado del que restaurar');

        //─── CAMBIAR ────────────────────────────────────────────────────────────────────
        $db->exec("UPDATE `{$tmpDatabase}`.`zz_viaje` SET `valor` = 'PISADO' WHERE id = 1");
        $check($valor() === 'PISADO', 'se cambia el valor, para que restaurar signifique algo');

        //─── SIN confirmación NO hace nada ──────────────────────────────────────────────
        exec("{$cli} db-restore file=" . escapeshellarg($dump) . " database={$tmpDatabase} 2>&1", $salidaSin, $codigoSin);
        $check($codigoSin !== 0 && $valor() === 'PISADO',
            'SIN confirm=yes no toca nada y sale con error',
            'código=' . $codigoSin . ' valor=' . var_export($valor(), true));

        //─── RESTAURAR ──────────────────────────────────────────────────────────────────
        exec("{$cli} db-restore file=" . escapeshellarg($dump) . " database={$tmpDatabase} confirm=yes 2>&1", $salida, $codigo);
        $check($codigo === 0, 'la restauración termina sin fallos', implode(' | ', array_slice($salida, -2)));
        $check($valor() === 'EL-ORIGINAL', 'y el valor VOLVIÓ', 'ahora=' . var_export($valor(), true));

        //─── EL RASTRO, que es lo que la LEY 12 necesita ────────────────────────────────
        $check(is_file($trace), 'deja rastro de la restauración');
        $datos = is_file($trace) ? json_decode((string) file_get_contents($trace), true) : null;
        $check(is_array($datos) && ($datos['file'] ?? '') === $dump && ($datos['database'] ?? '') === $tmpDatabase,
            'y el rastro dice QUÉ archivo y QUÉ base', (string) json_encode($datos));
        $check(is_array($datos) && (int) ($datos['timestamp'] ?? 0) > time() - 300, 'con una marca de tiempo reciente');

    } catch (\Throwable $exception) {
        $check(false, 'la suite corre sin excepciones', $exception->getMessage());
    } finally {
        try { $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`"); $db->exec("USE `{$original}`"); } catch (\Throwable $e) { }
        @unlink($dump);
        //El rastro se deja como estaba: esta suite no debe hacer creer al recorredor que la base es nueva.
        if ($traceBefore !== null) { file_put_contents($trace, $traceBefore); } else { @unlink($trace); }
    }

    $total = $passed + $failed;
    echoTerminal('');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('db-restore: respaldar, cambiar, restaurar y comprobar que volvió.')->register();
