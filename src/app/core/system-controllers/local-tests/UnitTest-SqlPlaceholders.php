<?php

//El valor de una búsqueda viaja como DATO, no como SQL. Ver T150 y la LEY 24.

use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/sql-placeholders';
$cliTaskDescription = 'La búsqueda de países manda el valor por marcador, no concatenado';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:SqlPlaceholders] Iniciando suite...', true, "\r\n", '33');
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

    //Una comilla simple es el carácter que decide: o va como dato, o cierra la cadena del SQL.
    $conComilla = "O'Brien";
    $tabla = 'countries';

    //──── 1. La vía parametrizada ───────────────────────────────────────────────────────
    echoTerminal('[1/7] WhereSegment deja la comilla FUERA del SQL');

    $segmento = new WhereSegment([
        WhereItem::like(
            "UPPER({$tabla}.name)",
            $conComilla . '%',
            '',
            'UPPER(' . WhereItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'
        ),
    ]);

    $sql = $segmento->toString();
    $valores = $segmento->getReplacementValues();

    $check(mb_strpos($sql, "'") === false, 'el SQL generado NO contiene ninguna comilla simple', $sql);
    $check(mb_strpos($sql, $conComilla) === false, 'el SQL generado NO contiene el valor buscado');
    $check(mb_strpos($sql, ':') !== false, 'el SQL generado lleva un MARCADOR con dos puntos');
    $check(
        in_array($conComilla . '%', array_values($valores), true),
        'y el valor, con su comilla intacta, viaja en los valores de reemplazo',
        (string) json_encode($valores, JSON_UNESCAPED_UNICODE)
    );
    echoTerminal(' ');

    //──── 2. La discriminante ───────────────────────────────────────────────────────────
    echoTerminal('[2/7] DISCRIMINANTE: la vía de cadena mete la comilla en el SQL');

    //Esto es lo que hacía `Country::search()`. Solo se compone: no se ejecuta contra nada.
    $comoAntes = "UPPER({$tabla}.name) LIKE UPPER('{$conComilla}%')";
    $check(
        mb_strpos($comoAntes, "'{$conComilla}") !== false,
        'la interpolación deja la comilla DENTRO de la sentencia',
        'Sin esto, «no hay comillas en el SQL» pasaría también con una cadena vacía.'
    );
    echoTerminal(' ');

    //──── 3. `having()` concatena igual, y su segmento también prepara ──────────────────
    echoTerminal('[3/7] HavingSegment deja la comilla FUERA del HAVING');

    //`City::search()` usa `having` y no `where` porque filtra por `countryID`, un alias del
    //SELECT. `having(string)` concatena igual: `"HAVING ({$having})"`.
    $criteria = [
        new HavingItem(
            "UPPER({$tabla}.name)",
            HavingItem::LIKE_OPERATOR,
            $conComilla . '%',
            '',
            'UPPER(' . HavingItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'
        ),
        new HavingItem('countryID', HavingItem::EQUAL_OPERATOR, 5),
    ];
    $criteria[0]->setAfterOperator(HavingItem::AND_OPERATOR);
    $having = new HavingSegment($criteria);

    $sqlHaving = $having->toString();
    $valoresHaving = $having->getReplacementValues();

    $check(mb_strpos($sqlHaving, "'") === false, 'el HAVING generado NO contiene ninguna comilla simple', $sqlHaving);
    $check(
        in_array($conComilla . '%', array_values($valoresHaving), true),
        'y el valor, con su comilla intacta, viaja en los valores de reemplazo'
    );
    $check(
        mb_substr_count($sqlHaving, ':') === 2,
        'los DOS criterios llevan marcador, no solo el del LIKE',
        (string) json_encode($valoresHaving, JSON_UNESCAPED_UNICODE)
    );
    echoTerminal(' ');

    //──── 4. Que los arreglos sigan puestos ─────────────────────────────────────────────
    echoTerminal('[4/7] Las búsquedas arregladas siguen por la vía parametrizada');

    //Se pregunta al censo, que tokeniza. Si vuelve la interpolación, `Country.php` reaparece
    //en la lista CONFIRMADO y esta comprobación se pone roja.
    $raiz = dirname(basepath(''));
    $censo = $raiz . '/bin/censo-sql-concatenado';

    if (!is_file($censo)) {
        $check(false, 'existe bin/censo-sql-concatenado', 'sin el instrumento no se comprueba nada');
    } else {
        $salida = [];
        $estado = 0;
        //RETORNO-IGNORADO: `exec()` devuelve la última línea; aquí se lee $salida entera.
        exec('cd ' . escapeshellarg($raiz) . ' && ' . escapeshellarg($censo) . ' 2>&1', $salida, $estado);

        $arregladas = ['Country.php', 'Point.php', 'State.php', 'City.php'];
        $seccion = '';
        $reaparecidas = [];
        foreach ($salida as $linea) {
            if (mb_strpos($linea, '── CONFIRMADO') === 0) {
                $seccion = 'CONFIRMADO';
                continue;
            }
            if (mb_strpos($linea, '── REVISAR') === 0) {
                $seccion = 'REVISAR';
                continue;
            }
            if ($seccion !== 'CONFIRMADO' || mb_strpos($linea, 'search()') === false) {
                continue;
            }
            foreach ($arregladas as $archivo) {
                if (mb_strpos($linea, 'Locations/Controllers/' . $archivo) !== false) {
                    $reaparecidas[] = mb_substr($archivo, 0, -4);
                }
            }
        }

        //`DocumentsController::searchDropdown` usa `having` y se arregló en AL.
        $seccion = '';
        foreach ($salida as $linea) {
            if (mb_strpos($linea, '── CONFIRMADO') === 0) {
                $seccion = 'CONFIRMADO';
                continue;
            }
            if (mb_strpos($linea, '── ') === 0) {
                $seccion = '';
                continue;
            }
            //POR MÉTODO, NO POR ARCHIVO: `dataTablesExplorer` es otro sujeto y ponía roja la
            //puerta sin que nadie hubiera roto `searchDropdown`. Ver T154.
            if ($seccion === 'CONFIRMADO' && mb_strpos($linea, 'DocumentsController.php') !== false
                && mb_strpos($linea, 'searchDropdown()') !== false) {
                $reaparecidas[] = 'DocumentsController::searchDropdown';
            }
        }

        $check($estado === 0, 'el censo corrió y su canario no cayó');
        $check(
            count($reaparecidas) === 0,
            'ninguna de las CUATRO búsquedas de Locations figura entre los CONFIRMADO',
            count($reaparecidas) > 0
                ? 'VOLVIÓ LA CONCATENACIÓN en: ' . implode(', ', $reaparecidas)
                : 'Country, Point, State y City van por segmento preparado.'
        );
    }
    echoTerminal(' ');

    //──── 5. Las listas `IN (...)`, que no se pueden parametrizar, validan el dominio ───
    echoTerminal('[5/7] Las cuatro listas `IN (...)` siguen validando el dominio');

    //`IN` no lleva marcador: lo que cierra el agujero es la VALIDACIÓN, y quitarla NO mueve el
    //censo. Por eso esto mira la FUENTE. Ver T152.
    $raizSrc = rtrim(str_replace('\\', '/', basepath('')), '/');
    $validaciones = [
        'App/Locations/Controllers/City.php' => ["array_map('intval', \$ids)"],
        'App/Locations/Controllers/State.php' => ["array_map('intval', \$ids)"],
        'App/Locations/Controllers/Country.php' => ["array_map('intval', \$ids)", 'p{L}\\p{N} \\-]{1,60}'],
    ];

    foreach ($validaciones as $relativo => $marcas) {
        $codigo = (string) @file_get_contents($raizSrc . '/app/classes/' . $relativo);
        foreach ($marcas as $marca) {
            $check(
                $codigo !== '' && mb_strpos($codigo, $marca) !== false,
                basename($relativo) . ' conserva la validación «' . mb_substr($marca, 0, 34) . '»',
                $codigo === '' ? 'no se pudo leer el archivo' : null
            );
        }
    }
    echoTerminal(' ');

    //──── 6. `UsersController::searchDropdown` ──────────────────────────────────────────
    echoTerminal('[6/7] El desplegable de usuarios: `having` preparado y `NOT IN` validado');

    //El `having` se arregló de verdad y el `NOT IN` NO puede arreglarse: solo se valida. Quitar
    //la validación NO mueve el censo, así que esto mira la FUENTE. Ver T153.
    $usersController = (string) @file_get_contents($raizSrc . '/app/controller/UsersController.php');

    $marcasUsers = [
        "array_filter(\$ignoreTypes, 'is_numeric')" => '`is_numeric` filtra ANTES de `intval`',
        'TYPES_USER_PRIORITY' => 'la lista blanca son los SIETE tipos declarados',
        'HavingSegment($criteriosHaving)' => 'la búsqueda va por HavingSegment',
    ];
    foreach ($marcasUsers as $marca => $porQue) {
        $check(
            $usersController !== '' && mb_strpos($usersController, $marca) !== false,
            'searchDropdown: ' . $porQue,
            $usersController === '' ? 'no se pudo leer UsersController.php' : null
        );
    }

    //`ActiveRecord::having()` SUSTITUYE, no acumula: con el criterio de `status` en un `having`
    //propio, el de la búsqueda lo borraba y reaparecían los usuarios eliminados.
    $check(
        $usersController !== '' && mb_strpos($usersController, 'having("status !=') === false,
        'searchDropdown: el criterio de `status` YA NO va en un `having` aparte',
        'Si vuelve, el `having` de la búsqueda lo pisa y los eliminados reaparecen al escribir.'
    );

    //La cara complementaria: si `searchDropdown` cae en CONFIRMADO es que se perdió su entrada
    //declarada; si no aparece en NINGUNA de las dos, el censo dejó de verlo.
    if (isset($salida) && is_array($salida)) {
        $seccion = '';
        $donde = '';
        foreach ($salida as $linea) {
            if (mb_strpos($linea, '── ') === 0) {
                $seccion = mb_strpos($linea, '── CONFIRMADO') === 0 ? 'CONFIRMADO'
                    : (mb_strpos($linea, '── DECLARADO') === 0 ? 'DECLARADO' : '');
                continue;
            }
            if ($seccion !== '' && mb_strpos($linea, 'controller/UsersController.php') !== false) {
                $donde = $seccion;
            }
        }
        $check(
            $donde === 'DECLARADO',
            'searchDropdown figura entre los DECLARADO, no entre los CONFIRMADO',
            $donde === '' ? 'el censo ya NO lo ve: revisa el instrumento.' : "el censo lo pone en {$donde}."
        );
    }
    echoTerminal(' ');

    //──── 7. Los fragmentos de DataTables, que no admiten marcador ──────────────────────
    echoTerminal('[7/7] Los fragmentos `where_string`/`having_string` validan su dominio');

    //No hay vía preparada para un fragmento de SQL, así que lo que cierra el agujero es la
    //VALIDACIÓN — y quitarla NO mueve el censo. Por eso esto mira la FUENTE. Ver T155.
    $fragmentos = [
        'classes/App/Locations/Controllers/Country.php' => [
            'regionNameOrNull($region) ?? \'\'' => 'countriesDataTables rechaza a `\'\'`, que no ensancha',
            'self::regionNameOrNull($nombre)' => 'countries() usa el MISMO patrón factorizado',
        ],
        'classes/SystemApprovals/Controllers/SystemApprovalsController.php' => [
            'Validator::isInteger($elapsedDaysFilter)' => 'elapsedDays es entero, no cadena',
        ],
        'classes/Publications/Controllers/PublicationsController.php' => [
            'PublicationMapper::VISIBILITIES)' => 'visibility va contra la lista blanca declarada',
        ],
    ];

    foreach ($fragmentos as $relativo => $marcas) {
        $codigo = (string) @file_get_contents($raizSrc . '/app/' . $relativo);
        foreach ($marcas as $marca => $porQue) {
            $check(
                $codigo !== '' && mb_strpos($codigo, $marca) !== false,
                basename($relativo) . ': ' . $porQue,
                $codigo === '' ? 'no se pudo leer el archivo' : null
            );
        }
    }

    //UNA SOLA COPIA DEL PATRÓN. Si reaparece una segunda, la factorización se deshizo y las
    //dos comparaciones por nombre vuelven a poder divergir.
    $country = (string) @file_get_contents($raizSrc . '/app/classes/App/Locations/Controllers/Country.php');
    $copiasPatron = mb_substr_count($country, 'p{L}');
    $check(
        $copiasPatron === 1,
        'el patrón de región existe UNA sola vez en Country.php',
        "copias encontradas: {$copiasPatron}"
    );

    //`SystemApprovalsController::dataTables` NO puede declararse mientras `referenceAlias` siga
    //sin lista blanca: se declara por método, y su `count` no sabría a cuál de los dos indulta.
    $declaradas = (string) @file_get_contents($raizSrc . '/../files/dev/sql-concat-declared.json');
    $check(
        $declaradas !== '' && mb_strpos($declaradas, 'SystemApprovalsController.php::dataTables') === false,
        'SystemApprovalsController::dataTables sigue SIN declarar',
        'Tiene dos hallazgos y uno sigue abierto; declararlo indultaría al abierto.'
    );
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:SqlPlaceholders] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "El valor de búsqueda viaja como dato ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NONE])->register();
