<?php

//El esquema declarado (databases/piecesphp_structure.sql, _views.sql y _functions.sql) contra la base local, en los dos
//sentidos. SOLO LEE: information_schema y SHOW CREATE TABLE; el archivo y la base pasan por el MISMO analizador.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/schema-matches-database', function ($args) {

    echoTerminal("\e[33m[TEST:SchemaMatchesDatabase] El esquema declarado no miente: lo que dice el archivo es lo que tiene la base local\e[39m");
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

    $carpeta = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/databases';
    $archivos = [
        'structure' => "{$carpeta}/piecesphp_structure.sql",
        'views' => "{$carpeta}/piecesphp_views.sql",
        'functions' => "{$carpeta}/piecesphp_functions.sql",
    ];
    $leidos = array_map(fn (string $ruta): string => is_file($ruta) ? (string) file_get_contents($ruta) : '', $archivos);
    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null && !in_array('', $leidos, true), 'banco: conexión a la base y los tres archivos de databases/ legibles')) {
        return $balance();
    }

    //Una tabla, de un CREATE TABLE (del archivo o de SHOW CREATE TABLE): columnas, índices y claves foráneas.
    $analizar = function (string $cuerpo): array {
        $tabla = ['columns' => [], 'indexes' => [], 'foreign' => []];
        foreach (preg_split('/\R/', $cuerpo) ?: [] as $linea) {
            $linea = rtrim(trim($linea), ',');
            if (preg_match('/^`([^`]+)`\s+(.*)$/', $linea, $m) === 1) {
                $resto = $m[2];
                //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y la comparación lo delata.
                preg_match('/^([a-z]+(?:\([^)]*\))?(?:\s+unsigned)?(?:\s+zerofill)?)/i', $resto, $tipo);
                $nullable = preg_match('/\bNOT NULL\b/i', $resto) !== 1;
                $default = preg_match("/\\bDEFAULT\\s+('(?:[^'\\\\]|\\\\.|'')*'|[^\\s,]+)/i", $resto, $d) === 1 ? trim($d[1], "'") : null;
                //Sin DEFAULT, una columna que admite NULL vale NULL: así lo escriben unas versiones y otras no.
                $default ??= $nullable ? 'NULL' : null;
                $tabla['columns'][$m[1]] = [
                    'type' => mb_strtolower($tipo[1] ?? $resto),
                    'null' => $nullable ? 'NULL' : 'NOT NULL',
                    'default' => $default ?? '(sin valor por omisión)',
                    'extra' => preg_match('/\bAUTO_INCREMENT\b/i', $resto) === 1 ? 'auto_increment' : '',
                ];
            } elseif (preg_match('/^PRIMARY KEY\s*\((.*)\)/i', $linea, $m) === 1) {
                $tabla['indexes']['PRIMARY'] = 'PRIMARY (' . str_replace('`', '', $m[1]) . ')';
            } elseif (preg_match('/^(UNIQUE |FULLTEXT |SPATIAL )?KEY\s+`([^`]+)`\s*\((.*)\)/i', $linea, $m) === 1) {
                $tabla['indexes'][$m[2]] = trim(mb_strtoupper($m[1]) . 'KEY') . ' (' . str_replace('`', '', $m[3]) . ')';
            } elseif (preg_match('/^CONSTRAINT\s+`([^`]+)`\s+FOREIGN KEY\s*(.*)$/i', $linea, $m) === 1) {
                $tabla['foreign'][$m[1]] = preg_replace('/\s+/', ' ', str_replace('`', '', $m[2]));
            }
        }
        return $tabla;
    };
    $delArchivo = function (string $sql) use ($analizar): array {
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y la comparación lo delata.
        preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\R\)[^;]*;/s', $sql, $m, PREG_SET_ORDER);
        $tablas = [];
        foreach ($m as $bloque) {
            $tablas[$bloque[1]] = $analizar($bloque[2]);
        }
        return $tablas;
    };
    $deLaBase = function () use ($database, $analizar): array {
        $tablas = [];
        $nombres = $database->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($nombres as $nombre) {
            $fila = $database->query('SHOW CREATE TABLE `' . str_replace('`', '``', (string) $nombre) . '`')->fetch(\PDO::FETCH_NUM);
            $crear = is_array($fila) ? (string) ($fila[1] ?? '') : '';
            $cuerpo = preg_match('/^CREATE TABLE `[^`]+` \((.*)\R\)[^)]*$/s', $crear, $m) === 1 ? $m[1] : '';
            $tablas[(string) $nombre] = $analizar($cuerpo);
        }
        return $tablas;
    };
    //Lo que no cuadra, en los dos sentidos, una línea por diferencia.
    $comparar = function (array $declaradas, array $presentes): array {
        $diferencias = [];
        foreach (array_diff_key($declaradas, $presentes) as $nombre => $_) {
            $diferencias[] = "tabla {$nombre}: declarada y AUSENTE de la base";
        }
        foreach (array_diff_key($presentes, $declaradas) as $nombre => $_) {
            $diferencias[] = "tabla {$nombre}: en la base y NO declarada";
        }
        foreach (array_intersect_key($declaradas, $presentes) as $nombre => $archivo) {
            $base = $presentes[$nombre];
            foreach (['columns' => 'columna', 'indexes' => 'índice', 'foreign' => 'clave foránea'] as $parte => $texto) {
                foreach (array_diff_key($archivo[$parte], $base[$parte]) as $clave => $_) {
                    $diferencias[] = "{$nombre}.{$clave}: {$texto} declarada y AUSENTE de la base";
                }
                foreach (array_diff_key($base[$parte], $archivo[$parte]) as $clave => $_) {
                    $diferencias[] = "{$nombre}.{$clave}: {$texto} en la base y NO declarada";
                }
                foreach (array_intersect_key($archivo[$parte], $base[$parte]) as $clave => $declarado) {
                    if ($declarado !== $base[$parte][$clave]) {
                        $diferencias[] = "{$nombre}.{$clave}: {$texto} distinta — archivo " . json_encode($declarado, JSON_UNESCAPED_UNICODE) . ' · base ' . json_encode($base[$parte][$clave], JSON_UNESCAPED_UNICODE);
                    }
                }
            }
        }
        return $diferencias;
    };
    $nombresDe = function (string $sql, string $tipo): array {
        //RETORNO-IGNORADO: cuenta la captura; si no casa, queda vacía y la comparación lo delata.
        preg_match_all('/CREATE\s+(?:OR REPLACE\s+)?(?:DEFINER=\S+\s+)?' . $tipo . '\s+`?([A-Za-z0-9_]+)`?/i', $sql, $m);
        $nombres = array_values(array_unique($m[1]));
        sort($nombres);
        return $nombres;
    };
    $enLaBase = function (string $consulta) use ($database): array {
        $nombres = array_map('strval', $database->query($consulta)->fetchAll(\PDO::FETCH_COLUMN));
        sort($nombres);
        return $nombres;
    };

    $declaradas = $delArchivo($leidos['structure']);
    $presentes = $deLaBase();
    //El universo de lo comparado (LEY 15): tablas, columnas, índices y claves que ve el analizador en cada lado.
    $universo = function (array $tablas): string {
        $suma = fn (string $parte): int => array_sum(array_map(fn (array $t): int => count($t[$parte]), $tablas));
        return count($tablas) . ' tablas, ' . $suma('columns') . ' columnas, ' . $suma('indexes') . ' índices, ' . $suma('foreign') . ' claves foráneas';
    };
    echoTerminal('      archivo: ' . $universo($declaradas));
    echoTerminal('      base:    ' . $universo($presentes));
    $check(count($declaradas) > 0 && count($presentes) > 0, 'banco: el analizador ve tablas en los dos lados', count($declaradas) . ' declaradas · ' . count($presentes) . ' en la base');

    //──── [c] CANARIO: un desajuste puesto en memoria sale nombrado; sin esto, «0 diferencias» no probaría nada ────
    echoTerminal('[c] Canario: una columna quitada y una tabla inventada, en memoria, salen nombradas');
    $alterado = $declaradas;
    $tablaCanario = (string) array_key_first($alterado);
    $columnaCanario = (string) array_key_last($alterado[$tablaCanario]['columns']);
    unset($alterado[$tablaCanario]['columns'][$columnaCanario]);
    $alterado['zz_tabla_inventada'] = ['columns' => ['id' => ['type' => 'int(11)', 'null' => 'NOT NULL', 'default' => '(sin valor por omisión)', 'extra' => '']], 'indexes' => [], 'foreign' => []];
    $diferenciasCanario = $comparar($alterado, $presentes);
    $comparadaSinCanario = $comparar($declaradas, $presentes);
    $nuevas = array_values(array_diff($diferenciasCanario, $comparadaSinCanario));
    $check(in_array("{$tablaCanario}.{$columnaCanario}: columna en la base y NO declarada", $nuevas, true) && in_array('tabla zz_tabla_inventada: declarada y AUSENTE de la base', $nuevas, true) && count($nuevas) === 2, 'c1 las dos alteraciones, y solo ellas, salen en la lista', implode(' | ', $nuevas));
    echoTerminal(' ');

    //──── [t] Tablas, columnas, índices y claves ───────────────────────────────────────────────────────
    echoTerminal('[t] Tablas: columnas (tipo, NULL, valor por omisión), índices y claves foráneas');
    foreach ($comparadaSinCanario as $diferencia) {
        echoTerminal("      · {$diferencia}");
    }
    $check($comparadaSinCanario === [], 't1 piecesphp_structure.sql y la base local cuadran en los dos sentidos', count($comparadaSinCanario) . ' diferencia(s)');
    echoTerminal(' ');

    //──── [v] Vistas y funciones, por nombre ───────────────────────────────────────────────────────────
    echoTerminal('[v] Vistas y funciones, por nombre');
    $vistasArchivo = $nombresDe($leidos['views'], 'VIEW');
    $vistasBase = $enLaBase('SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE()');
    $check($vistasArchivo === $vistasBase, 'v1 las vistas de piecesphp_views.sql son las de la base', 'archivo [' . implode(', ', $vistasArchivo) . '] · base [' . implode(', ', $vistasBase) . ']');
    $funcionesArchivo = $nombresDe($leidos['functions'], 'FUNCTION');
    $funcionesBase = $enLaBase("SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'FUNCTION'");
    $check($funcionesArchivo === $funcionesBase, 'v2 las funciones de piecesphp_functions.sql son las de la base', 'archivo [' . implode(', ', $funcionesArchivo) . '] · base [' . implode(', ', $funcionesBase) . ']');

    return $balance();

})->setDescription('El esquema declarado en databases/ (tablas con sus columnas, índices y claves; vistas y funciones por nombre) contra la base local, en los dos sentidos. Solo lee: information_schema y SHOW CREATE TABLE.')->setEffects([CliActions::EFFECT_DATABASE])->register();
