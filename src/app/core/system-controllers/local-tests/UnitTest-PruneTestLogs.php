<?php

//prune-test-logs: en copias de prueba de los dos registros, el simulacro no borra, escribir borra solo las filas que
//empiezan por la marca, nunca otra tabla, y fuera de local se niega. Crea y borra sus tablas `…_zz_…`.
//@areas nucleo

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\PruneTestLogsTask;

CliActions::make('unit-tests:core/prune-test-logs', function ($args) {

    echoTerminal("\e[33m[TEST:PruneTestLogs] Los restos de prueba de los registros de correos y de soporte\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null && PruneTestLogsTask::refusal() === null, 'banco: conexión a la base y la instalación es la local')) {
        return $balance();
    }
    [$correos, $soporte] = PruneTestLogsTask::tables();
    $sufijo = '_zz_' . bin2hex(random_bytes(3));
    $copias = ["{$correos}{$sufijo}", "{$soporte}{$sufijo}"];
    $creadas = [];
    $produccion = (string) tempnam(sys_get_temp_dir(), 'zz-entorno-');

    //Una fila con lo mínimo que pide la tabla y el texto dado en una columna.
    $insertar = function (string $tabla, string $columna, string $texto) use ($database): int {
        $consulta = $database->prepare('SELECT `column_name`, `data_type`, `is_nullable`, `column_default`, `extra` FROM `information_schema`.`columns` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        $consulta->execute([$tabla]);
        $valores = [];
        foreach ($consulta->fetchAll(\PDO::FETCH_ASSOC) as $c) {
            $nombre = (string) $c['column_name'];
            if (str_contains((string) $c['extra'], 'auto_increment') || $nombre === $columna || $c['is_nullable'] === 'YES' || $c['column_default'] !== null) {
                continue;
            }
            $tipo = (string) $c['data_type'];
            $valores[$nombre] = match (true) {
                in_array($tipo, ['int', 'bigint', 'tinyint', 'smallint', 'mediumint', 'decimal', 'double', 'float'], true) => 0,
                in_array($tipo, ['datetime', 'timestamp', 'date'], true) => date('Y-m-d H:i:s'),
                $tipo === 'json' => '{}',
                default => 'jgi-prueba',
            };
        }
        $valores[$columna] = $texto;
        $nombres = implode(', ', array_map(fn ($n) => "`{$n}`", array_keys($valores)));
        $huecos = implode(', ', array_fill(0, count($valores), '?'));
        $database->prepare("INSERT INTO `{$tabla}` ({$nombres}) VALUES ({$huecos})")->execute(array_values($valores));
        return (int) $database->lastInsertId();
    };
    $ids = function (string $tabla) use ($database): array {
        return array_map('intval', $database->query("SELECT `id` FROM `{$tabla}` ORDER BY `id`")->fetchAll(\PDO::FETCH_COLUMN));
    };

    try {
        foreach ([$correos, $soporte] as $indice => $real) {
            $database->exec("CREATE TABLE `{$copias[$indice]}` LIKE `{$real}`");
            $creadas[] = $copias[$indice];
        }
        //En cada copia: dos con la marca (guion y guion bajo), dos sin ella (la marca en medio, y nada).
        $marcadas = [];
        $limpias = [];
        foreach ([[$copias[0], 'recipients', 'subject'], [$copias[1], 'email', 'message']] as [$tabla, $columnaA, $columnaB]) {
            $marcadas[$tabla] = [$insertar($tabla, $columnaA, 'zz-jgi@example.invalid'), $insertar($tabla, $columnaB, 'zz_jgi texto')];
            $limpias[$tabla] = [$insertar($tabla, $columnaA, 'jgi-sin-marca zz-en-medio'), $insertar($tabla, $columnaB, 'jgi texto normal')];
        }
        $esperadas = [];
        foreach ($marcadas as $tabla => $lista) {
            foreach ($lista as $id) {
                $esperadas[] = "{$tabla}#{$id}";
            }
        }
        sort($esperadas);

        echoTerminal('[s] El simulacro dice qué borraría y no borra');
        $vistas = array_map(fn (array $fila): string => "{$fila['table']}#{$fila['id']}", PruneTestLogsTask::leftovers($copias));
        sort($vistas);
        $check($vistas === $esperadas, 's1 DISCRIMINANTE: las filas que EMPIEZAN por zz- o zz_, y ninguna más', implode(', ', $vistas));
        $columnas = array_column(PruneTestLogsTask::leftovers($copias), 'columns');
        $check(in_array(['recipients'], $columnas, true) && in_array(['message'], $columnas, true), 's2 dice la columna que la marca, no el contenido', (string) json_encode($columnas));
        $check(count($ids($copias[0])) === 4 && count($ids($copias[1])) === 4, 's3 DISCRIMINANTE: el simulacro no borra nada');

        echoTerminal(' ');
        echoTerminal('[e] escribir=yes borra solo las marcadas');
        $borradas = PruneTestLogsTask::prune($copias);
        $check(count($borradas) === 4, 'e1 devuelve las cuatro que borró', (string) count($borradas));
        $check($ids($copias[0]) === $limpias[$copias[0]] && $ids($copias[1]) === $limpias[$copias[1]], 'e2 DISCRIMINANTE: quedan exactamente las que no llevan la marca al principio');
        $check(PruneTestLogsTask::leftovers($copias) === [], 'e3 y ya no queda ningún resto');

        echoTerminal(' ');
        echoTerminal('[t] Solo los dos registros');
        $otra = null;
        try {
            PruneTestLogsTask::leftovers(['pcsphp_users']);
        } catch (\InvalidArgumentException $e) {
            $otra = $e->getMessage();
        }
        $check($otra !== null, 't1 DISCRIMINANTE: otra tabla se rechaza', (string) $otra);

        echoTerminal(' ');
        echoTerminal('[l] Fuera de local se niega y no toca nada');
        $nueva = $insertar($copias[1], 'email', 'zz-jgi-otra@example.invalid');
        $check(file_put_contents($produccion, "<?php\nreturn 'production';\n") !== false, 'banco: un entorno de producción de prueba');
        AppEnvironment::useForTesting($produccion);
        $negada = null;
        try {
            PruneTestLogsTask::prune($copias);
        } catch (\RuntimeException $e) {
            $negada = $e->getMessage();
        }
        $rechazo = PruneTestLogsTask::refusal();
        AppEnvironment::useForTesting(null);
        $check($rechazo !== null && $negada !== null && str_starts_with($negada, 'PARA'), 'l1 DISCRIMINANTE: con el entorno de producción, PARA', (string) $negada);
        $check(in_array($nueva, $ids($copias[1]), true), 'l2 y la fila marcada sigue ahí');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        AppEnvironment::useForTesting(null);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ($creadas as $tabla) {
            $database->exec("DROP TABLE IF EXISTS `{$tabla}`");
        }
        if (is_file($produccion)) {
            //RETORNO-IGNORADO: el temporal es propio.
            unlink($produccion);
        }
        $quedan = 0;
        foreach ($copias as $tabla) {
            $consulta = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
            $consulta->execute([$tabla]);
            $quedan += (int) $consulta->fetchColumn();
        }
        $check($quedan === 0 && PruneTestLogsTask::refusal() === null, 'z1 no quedan las copias y el entorno vuelve a local');
    }

    return $balance();

})->setDescription('prune-test-logs: en copias de prueba de los dos registros, el simulacro no borra, escribir borra solo las filas cuyo texto empieza por zz- o zz_, otra tabla se rechaza y fuera de local se niega. Crea y borra sus tablas.')->setEffects([CliActions::EFFECT_DATABASE])->register();
