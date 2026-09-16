<?php

//Escribe en la base local y en disco: crea filas zz en login_attempts, una marca zz en pcsphp_app_config y un
//directorio temporal, y lo borra todo en el finally. Ver la ruptura 30 del CHANGELOG.

use App\Model\LoginAttemptsModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\RepairEscapedTextTask;

CliActions::make('unit-tests:core/escaped-text-repair', function ($args) {

    echoTerminal("\e[33m[TEST:EscapedTextRepair] La reparación del escape cuenta, aplica una sola vez y se niega sin respaldo reciente\e[39m");
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
    //Una excepción es un fallo con su detalle y no aborta la suite.
    $probar = function (string $name, callable $prueba) use ($check): void {
        try {
            [$condicion, $detalle] = $prueba();
            $check((bool) $condicion, $name, (string) $detalle);
        } catch (\Throwable $exception) {
            $check(false, $name, get_class($exception) . ': ' . $exception->getMessage());
        }
    };

    $database = (new BaseModel())->getDatabase();
    if ($database === null) {
        echoTerminal("\e[31m BALANCE FINAL: 0/11 PASADAS, 11 FALLIDAS \e[39m — sin conexión a base de datos");
        return ['success' => false, 'message' => 'sin conexión a base de datos'];
    }
    $marca = 'zz-reparacion-' . bin2hex(random_bytes(4));
    $nombreMarca = $marca . '-marca';
    $directorio = sys_get_temp_dir() . '/zz-dumps-' . bin2hex(random_bytes(4));
    $tabla = LoginAttemptsModel::TABLE;

    $crudo = function (int $id) use ($database, $tabla): ?string {
        $statement = $database->prepare('SELECT message FROM ' . $tabla . ' WHERE id = ?');
        $statement->execute([$id]);
        $valor = $statement->fetchColumn();
        return is_string($valor) ? $valor : null;
    };
    $ver = fn($valor): string => var_export($valor, true);

    try {
        $insertar = $database->prepare('INSERT INTO ' . $tabla . ' (user_id, username_attempt, success, ip, message, date) VALUES (NULL, ?, 1, ?, ?, NOW())');
        $ids = [];
        foreach ([1 => "O\\'Brien", 2 => 'comillas \\"dobles\\"', 3 => 'sin escape', 5 => "x\\'y"] as $n => $mensaje) {
            $insertar->execute([$marca . '-' . $n, '0.0.0.0', $mensaje]);
            $ids[$n] = (int) $database->lastInsertId();
        }
        $acotados = [$ids[1], $ids[2], $ids[3]];

        //─── 1/4 · Columnas de texto del mapper ────────────────────────────────────────────
        echoTerminal('[1/4] textColumns() lee las columnas de texto del mapper');
        $probar('t1. login_attempts: username_attempt, ip y message; ni id, ni success, ni date, ni extra_data', function () {
            $info = RepairEscapedTextTask::textColumns(new LoginAttemptsModel());
            $columnas = $info['columns'];
            $ok = $info['table'] === 'login_attempts' && $info['primaryKey'] === 'id'
                && count(array_intersect(['username_attempt', 'ip', 'message'], $columnas)) === 3
                && count(array_intersect(['id', 'success', 'date', 'extra_data'], $columnas)) === 0;
            return [$ok, json_encode($info, \JSON_UNESCAPED_UNICODE | \JSON_PARTIAL_OUTPUT_ON_ERROR)];
        });
        echoTerminal(' ');

        //─── 2/4 · Contar y aplicar ────────────────────────────────────────────────────────
        echoTerminal('[2/4] repairColumn() cuenta sin tocar, aplica una vez y respeta onlyIds');
        $probar('d1. sin apply: 2 candidatas, 0 actualizadas y nada cambia', function () use ($database, $tabla, $acotados, $crudo, $ids) {
            $resultado = RepairEscapedTextTask::repairColumn($database, $tabla, 'id', 'message', false, $acotados);
            $intactos = $crudo($ids[1]) === "O\\'Brien" && $crudo($ids[2]) === 'comillas \\"dobles\\"' && $crudo($ids[3]) === 'sin escape';
            return [$resultado === ['candidates' => 2, 'updated' => 0] && $intactos, json_encode($resultado) . ($intactos ? '' : ', los valores cambiaron')];
        });
        $probar('d2. con apply: 2 candidatas y 2 actualizadas', function () use ($database, $tabla, $acotados) {
            $resultado = RepairEscapedTextTask::repairColumn($database, $tabla, 'id', 'message', true, $acotados);
            return [$resultado === ['candidates' => 2, 'updated' => 2], json_encode($resultado)];
        });
        $probar('d3. los valores quedan sin escape', function () use ($crudo, $ids, $ver) {
            $valores = [$crudo($ids[1]), $crudo($ids[2]), $crudo($ids[3])];
            return [$valores === ["O'Brien", 'comillas "dobles"', 'sin escape'], $ver($valores)];
        });
        $probar('d4. una segunda aplicación no encuentra candidatas', function () use ($database, $tabla, $acotados) {
            $resultado = RepairEscapedTextTask::repairColumn($database, $tabla, 'id', 'message', true, $acotados);
            return [$resultado['candidates'] === 0, json_encode($resultado)];
        });
        $probar('d5. la fila fuera de onlyIds no se toca', function () use ($crudo, $ids, $ver) {
            return [$crudo($ids[5]) === "x\\'y", $ver($crudo($ids[5]))];
        });
        echoTerminal(' ');

        //─── 3/4 · Respaldo reciente ───────────────────────────────────────────────────────
        echoTerminal('[3/4] hasRecentDump() exige un volcado de la última hora');
        $ahora = time();
        mkdir($directorio, 0700); //RETORNO-IGNORADO: si falla, h1 a h3 fallan con su detalle
        $probar('h1. directorio vacío → false', function () use ($directorio, $ahora) {
            $resultado = RepairEscapedTextTask::hasRecentDump($directorio, $ahora);
            return [$resultado === false, var_export($resultado, true)];
        });
        $probar('h2. solo un volcado de hace dos horas → false', function () use ($directorio, $ahora) {
            touch($directorio . '/a.sql.gz', $ahora - 7200); //RETORNO-IGNORADO: si falla, el volcado no existe y h2 sigue discriminando
            $resultado = RepairEscapedTextTask::hasRecentDump($directorio, $ahora);
            return [$resultado === false, var_export($resultado, true)];
        });
        $probar('h3. con un volcado de hace diez minutos → true', function () use ($directorio, $ahora) {
            touch($directorio . '/b.sql.gz', $ahora - 600); //RETORNO-IGNORADO: si falla, h3 falla
            $resultado = RepairEscapedTextTask::hasRecentDump($directorio, $ahora);
            return [$resultado === true, var_export($resultado, true)];
        });
        echoTerminal(' ');

        //─── 4/4 · La marca ────────────────────────────────────────────────────────────────
        echoTerminal('[4/4] La marca de una reparación anterior');
        $probar('k1. sin marca → isMarked false', function () use ($nombreMarca) {
            $resultado = RepairEscapedTextTask::isMarked($nombreMarca);
            return [$resultado === false, var_export($resultado, true)];
        });
        $probar('k2. mark() → true, y después isMarked true', function () use ($nombreMarca) {
            $marcado = RepairEscapedTextTask::mark(['zz' => true], $nombreMarca);
            $despues = RepairEscapedTextTask::isMarked($nombreMarca);
            return [$marcado === true && $despues === true, 'mark ' . var_export($marcado, true) . ', isMarked ' . var_export($despues, true)];
        });

    } finally {
        $database->prepare('DELETE FROM ' . $tabla . ' WHERE username_attempt LIKE ?')->execute([$marca . '%']);
        $database->prepare('DELETE FROM pcsphp_app_config WHERE name = ?')->execute([$nombreMarca]);
        if (is_dir($directorio)) {
            foreach (glob($directorio . '/*') ?: [] as $archivo) {
                unlink($archivo); //RETORNO-IGNORADO: el resto de directorio se cuenta y se imprime tras el finally
            }
            rmdir($directorio); //RETORNO-IGNORADO: el resto de directorio se cuenta y se imprime tras el finally
        }
    }

    $contar = function (string $sql, string $valor) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute([$valor]);
        return (int) $statement->fetchColumn();
    };
    echoTerminal(' ');
    echoTerminal('   restos tras la limpieza:'
        . ' filas=' . $contar('SELECT COUNT(*) FROM ' . $tabla . ' WHERE username_attempt LIKE ?', $marca . '%')
        . ', marcas=' . $contar('SELECT COUNT(*) FROM pcsphp_app_config WHERE name = ?', $nombreMarca)
        . ', directorio=' . (is_dir($directorio) ? 1 : 0));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La reparación del escape cuenta, aplica una sola vez y se niega sin respaldo reciente.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
