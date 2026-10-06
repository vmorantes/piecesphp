<?php

//El volcado lo produce db-backup: NO lo fabrique esta prueba. Ver T96 y T46 caso 4.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\DbBackupTask;
use Terminal\Tasks\DbRestoreTask;

CliActions::make('unit-tests:core/db-restore', function ($args) {

    echoTerminal("\e[33m[TEST:db-restore] El viaje de ida y vuelta, con volcado de verdad\e[39m");
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
    $trace = basepath('../' . DbRestoreTask::TRACE_RELATIVE_PATH);
    $traceBefore = is_file($trace) ? (string) file_get_contents($trace) : null;
    $cli = escapeshellcmd(basepath('../bin/cli'));
    $dump = null;
    $gz = null;

    try {

        //─── IDA: el volcado lo produce db-backup, NO esta prueba ────────────────────────
        exec("{$cli} db-backup 2>&1", $salidaBackup, $codigoBackup);
        $check($codigoBackup === 0, 'db-backup produce un volcado', implode(' | ', array_slice($salidaBackup, -1)));

        $candidatos = glob(basepath('dumps/*.sql.gz')) ?: [];
        usort($candidatos, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $gz = $candidatos[0] ?? null;
        $check(is_string($gz) && is_file($gz), 'y queda en disco donde se espera', (string) $gz);

        if (!is_string($gz)) {
            throw new \RuntimeException('sin volcado con el que seguir');
        }

        $dump = mb_substr($gz, 0, -3);
        $contenido = (string) file_get_contents('compress.zlib://' . $gz);
        if ($contenido === '') {
            $crudo = gzopen($gz, 'rb');
            $contenido = '';
            while ($crudo !== false && !gzeof($crudo)) { $contenido .= (string) gzread($crudo, 262144); }
            if ($crudo !== false) { gzclose($crudo); }
        }
        file_put_contents($dump, $contenido);

        //Si el volcado no trae rutinas, esta suite no mide el caso de T94: falla en vez de pasar.
        $tieneDelimiter = mb_strpos($contenido, 'DELIMITER') !== false;
        $check($tieneDelimiter, 'el volcado trae un bloque DELIMITER: hay algo dificil que restaurar',
            $tieneDelimiter ? '' : 'sin rutinas en la base, esta suite NO estaria midiendo el caso de T94');

        $rutinasEsperadas = preg_match_all('/CREATE\s+(?:AGGREGATE\s+)?FUNCTION/i', $contenido);
        $tablasEsperadas = preg_match_all('/CREATE TABLE/i', $contenido);
        $check($tablasEsperadas > 0, 'y trae tablas', "tablas={$tablasEsperadas} rutinas={$rutinasEsperadas}");

        //La lista de «qué soporta el partidor» de T96 sale de AQUÍ: no la dupliques en prosa.
        $partir = function (string $sql): int {
            //Nada de setAccessible(): no hace nada desde 8.1 y en 8.5 esta deprecado.
            $metodo = new \ReflectionMethod(DbRestoreTask::class, 'statementsOf');
            return count((array) $metodo->invoke(null, $sql));
        };
        $casos = [
            ['dos sentencias corrientes',            "SELECT 1;\nSELECT 2;\n", 2],
            ['`;` dentro de una cadena',             "INSERT INTO `t` VALUES ('uno; dos');\n", 1],
            ['`;` en una cadena de varias líneas',   "INSERT INTO `t` VALUES ('uno; dos;\ntres');\n", 1],
            ['`;` dentro de un identificador',       "SELECT `a;b` FROM `t`;\n", 1],
            ['comilla escapada con barra',           "INSERT INTO `t` VALUES ('a\\';b');\n", 1],
            ['comentario `--` con `;` dentro',       "-- ojo; aqui\nSELECT 1;\n", 1],
            ['comentario `#` con `;` dentro',        "# ojo; aqui\nSELECT 1;\n", 1],
            ['comentario de bloque',                 "/* uno;\ndos */\nSELECT 1;\n", 1],
            ['comentario ejecutable `/*!…*/`',       "/*!40101 SET NAMES utf8mb4 */;\n", 1],
            ['DELIMITER con cuerpo de rutina',       "DELIMITER ;;\nCREATE FUNCTION f() RETURNS int\nBEGIN\n DECLARE x int;\n SET x = 1;\n RETURN x;\nEND;;\nDELIMITER ;\n", 1],
            ['última sentencia sin `;` final',       "SELECT 1;\nSELECT 2", 2],
            ['LOCK/UNLOCK de mysqldump',             "LOCK TABLES `t` WRITE;\nUNLOCK TABLES;\n", 2],
        ];
        $malos = [];
        foreach ($casos as [$nombre, $sql, $esperadas]) {
            $obtenidas = $partir($sql);
            if ($obtenidas !== $esperadas) {
                $malos[] = "{$nombre}: {$obtenidas} en vez de {$esperadas}";
            }
        }
        $check($malos === [], 'el partidor entiende las ' . count($casos) . ' construcciones declaradas',
            implode(' | ', $malos));

        //─── Y lo que se decide NO aplicar, que tampoco puede callarse ──────────────────
        $porQue = function (string $sql): ?string {
            $metodo = new \ReflectionMethod(DbRestoreTask::class, 'whyIgnored');
            $valor = $metodo->invoke(null, $sql);
            return is_string($valor) ? $valor : null;
        };
        $ignorables = [
            'USE `otra`;'              => true,
            'CREATE DATABASE `otra`;'  => true,
            'DROP DATABASE `otra`;'    => true,
            'SELECT 1;'                => false,
            "INSERT INTO `use_log` VALUES (1);" => false,
        ];
        $malIgnoradas = [];
        foreach ($ignorables as $sql => $debeIgnorarse) {
            $razon = $porQue($sql);
            if (($razon !== null) !== $debeIgnorarse) {
                $malIgnoradas[] = $sql . ' -> ' . var_export($razon, true);
            }
        }
        $check($malIgnoradas === [], 'las que cambian de base se ignoran, y solo esas',
            implode(' | ', $malIgnoradas));

        //─── VUELTA: se restaura ese volcado en una base de usar y tirar ─────────────────
        $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`");
        $db->exec("CREATE DATABASE `{$tmpDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");

        //─── SIN confirmación NO hace nada ──────────────────────────────────────────────
        exec("{$cli} db-restore file=" . escapeshellarg($dump) . " database={$tmpDatabase} 2>&1", $salidaSin, $codigoSin);
        $tablasSin = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $db->quote($tmpDatabase))->fetchColumn();
        $check($codigoSin !== 0 && $tablasSin === 0, 'SIN confirm=yes no toca nada y sale con error',
            'código=' . $codigoSin . ' tablas=' . $tablasSin);

        exec("{$cli} db-restore file=" . escapeshellarg($dump) . " database={$tmpDatabase} confirm=yes 2>&1", $salida, $codigo);
        $check($codigo === 0, 'la restauración del volcado REAL termina sin una sola sentencia fallida',
            implode(' | ', array_slice($salida, -3)));

        //─── Y llegó TODO, no solo las tablas. Esta es la que habría cazado T94 ──────────
        $rutinas = (int) $db->query("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = " . $db->quote($tmpDatabase))->fetchColumn();
        $check($rutinas === $rutinasEsperadas, 'las rutinas almacenadas llegaron',
            "en la base={$rutinas}, en el volcado={$rutinasEsperadas}");

        $tablas = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $db->quote($tmpDatabase) . " AND TABLE_TYPE = 'BASE TABLE'")->fetchColumn();
        $vistas = (int) $db->query("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = " . $db->quote($tmpDatabase))->fetchColumn();
        //El origen menos lo que db-backup declara excluir. La lista se LEE de la tarea, no se copia.
        $excluidas = $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $db->quote($original)
            . " AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME IN (" . implode(',', array_map([$db, 'quote'], DbBackupTask::EXCLUDED_TABLES)) . ")")->fetchColumn();
        $tablasOrigen = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $db->quote($original) . " AND TABLE_TYPE = 'BASE TABLE'")->fetchColumn();
        $vistasOrigen = (int) $db->query("SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA = " . $db->quote($original))->fetchColumn();
        $esperadas = $tablasOrigen - (int) $excluidas;
        $check($tablas === $esperadas && $vistas === $vistasOrigen, 'tablas y vistas coinciden con el origen menos lo excluido',
            "copia={$tablas}t/{$vistas}v origen={$tablasOrigen}t/{$vistasOrigen}v excluidas={$excluidas}");

        //─── El viaje con un valor: pisar y comprobar que vuelve ────────────────────────
        $db->exec("DROP TABLE IF EXISTS `{$tmpDatabase}`.`zz_marca`");
        $db->exec("CREATE TABLE `{$tmpDatabase}`.`zz_marca` (`id` int NOT NULL, `v` varchar(40) NOT NULL) ENGINE=InnoDB");
        $db->exec("INSERT INTO `{$tmpDatabase}`.`zz_marca` (`id`, `v`) VALUES (1, 'PISADO')");
        exec("{$cli} db-restore file=" . escapeshellarg($dump) . " database={$tmpDatabase} confirm=yes 2>&1", $salida2, $codigo2);
        $sobrevive = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = " . $db->quote($tmpDatabase) . " AND TABLE_NAME = 'zz_marca'")->fetchColumn();
        $check($codigo2 === 0 && $sobrevive === 1,
            'restaurar dos veces seguidas no falla, y lo ajeno al volcado sigue ahí',
            "código={$codigo2} zz_marca={$sobrevive}");

        //─── EL RASTRO, que es lo que la LEY 12 necesita ────────────────────────────────
        $check(is_file($trace), 'deja rastro de la restauración');
        $datos = is_file($trace) ? json_decode((string) file_get_contents($trace), true) : null;
        $check(is_array($datos) && ($datos['file'] ?? '') === $dump && ($datos['database'] ?? '') === $tmpDatabase,
            'y el rastro dice QUÉ archivo y QUÉ base', (string) json_encode($datos));
        $check(is_array($datos) && (int) ($datos['timestamp'] ?? 0) > time() - 600, 'con una marca de tiempo reciente');
        $check(is_array($datos) && (int) ($datos['statementsFailed'] ?? -1) === 0,
            'y el rastro dice que no falló ninguna sentencia',
            'fallidas=' . (is_array($datos) ? var_export($datos['statementsFailed'] ?? null, true) : 'sin rastro'));

    } catch (\Throwable $exception) {
        $check(false, 'la suite corre sin excepciones', $exception->getMessage());
    } finally {
        try { $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`"); $db->exec("USE `{$original}`"); } catch (\Throwable $e) { }
        if (is_string($dump)) { @unlink($dump); }
        //El .gz se queda: es un respaldo de verdad y borrarlo seria tirar trabajo hecho.
        //El rastro se deja como estaba: esta suite no debe hacer creer al recorredor que la base es nueva.
        if ($traceBefore !== null) { file_put_contents($trace, $traceBefore); } else { @unlink($trace); }
    }

    $total = $passed + $failed;
    echoTerminal('');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('db-restore: respaldar con db-backup, restaurar ESE volcado y comprobar que llegó todo.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
