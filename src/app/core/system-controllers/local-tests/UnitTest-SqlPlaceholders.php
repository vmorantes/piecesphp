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
    echoTerminal('[1/14] WhereSegment deja la comilla FUERA del SQL');

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
    echoTerminal('[2/14] DISCRIMINANTE: la vía de cadena mete la comilla en el SQL');

    //Esto es lo que hacía `Country::search()`. Solo se compone: no se ejecuta contra nada.
    $comoAntes = "UPPER({$tabla}.name) LIKE UPPER('{$conComilla}%')";
    $check(
        mb_strpos($comoAntes, "'{$conComilla}") !== false,
        'la interpolación deja la comilla DENTRO de la sentencia',
        'Sin esto, «no hay comillas en el SQL» pasaría también con una cadena vacía.'
    );
    echoTerminal(' ');

    //──── 3. `having()` concatena igual, y su segmento también prepara ──────────────────
    echoTerminal('[3/14] HavingSegment deja la comilla FUERA del HAVING');

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
    echoTerminal('[4/14] Las búsquedas arregladas siguen por la vía parametrizada');

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
    echoTerminal('[5/14] Las cuatro listas `IN (...)` siguen validando el dominio');

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
    echoTerminal('[6/14] El desplegable de usuarios: `having` preparado y `NOT IN` validado');

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
    echoTerminal('[7/14] Los fragmentos `where_string`/`having_string` validan su dominio');

    //No hay vía preparada para un fragmento de SQL, así que lo que cierra el agujero es la
    //VALIDACIÓN — y quitarla NO mueve el censo. Por eso esto mira la FUENTE. Ver T155.
    $fragmentos = [
        'classes/App/Locations/Controllers/Country.php' => [
            "'where_segment' => \$regionSegment" => 'countriesDataTables ya va POR MARCADOR',
            'self::regionNameOrNull($nombre)' => 'countries(), que sigue en `IN (...)`, conserva el patrón',
        ],
        'classes/SystemApprovals/Controllers/SystemApprovalsController.php' => [
            'Validator::isInteger($elapsedDaysFilter)' => 'elapsedDays es entero, no cadena',
        ],
        'classes/Publications/Controllers/PublicationsController.php' => [
            'PublicationMapper::VISIBILITIES)' => 'visibility va contra la lista blanca declarada',
        ],
    ];

    //LAS MIGRADAS: si alguna volviera a `where_string`, su valor volvería a concatenarse y el
    //censo NO lo diría solo, porque su entrada declarada ya no existe.
    $migradas = [
        'classes/App/Locations/Controllers/Country.php' => 'countriesDataTables',
        'classes/App/Locations/Controllers/State.php' => 'statesDataTables',
        'classes/Documents/Controllers/DocumentsController.php' => 'dataTablesExplorer',
        'controller/UsersController.php' => 'dataTablesRequestUsers',
    ];
    foreach ($migradas as $relativo => $metodo) {
        $codigo = (string) @file_get_contents($raizSrc . '/app/' . $relativo);
        $check(
            $codigo !== '' && mb_strpos($codigo, "'where_segment' =>") !== false,
            basename($relativo) . "::{$metodo} sigue en `where_segment`",
            $codigo === '' ? 'no se pudo leer el archivo' : null
        );
    }

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

    //EN AO EXIGÍA LO CONTRARIO, y la regla dejó de aplicar sola. Ver T162.
    $declaradas = (string) @file_get_contents($raizSrc . '/../files/dev/sql-concat-declared.json');
    $aprobaciones = (string) @file_get_contents($raizSrc . '/app/classes/SystemApprovals/Controllers/SystemApprovalsController.php');
    $check(
        $declaradas !== '' && mb_strpos($declaradas, 'SystemApprovalsController.php::dataTables') !== false,
        'SystemApprovalsController::dataTables está declarado, con su `elapsedDays` validado'
    );
    $check(
        $aprobaciones !== '' && mb_strpos($aprobaciones, "'where_segment' => \$whereSegment") !== false,
        'y su `referenceAlias` va POR MARCADOR, no en el fragmento de cadena'
    );
    echoTerminal(' ');

    //──── 8. Las claves de segmento de DataTablesHelper ─────────────────────────────────
    echoTerminal('[8/14] `where_segment` prepara, y sin él la vía de cadena sigue intacta');

    //La forma EXACTA que usa `Country::countriesDataTables` tras migrar. Si el valor dejara de
    //viajar por reemplazo, la comilla volvería a la sentencia. Ver T156.
    $segmentoRegion = new WhereSegment([
        new WhereItem(
            'UPPER(region)',
            WhereItem::EQUAL_OPERATOR,
            $conComilla,
            '',
            'UPPER(' . WhereItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'
        ),
    ]);
    $sqlRegion = $segmentoRegion->toString();
    $valoresRegion = $segmentoRegion->getReplacementValues();

    $check(mb_strpos($sqlRegion, "'") === false, 'el WHERE de región NO lleva ninguna comilla', $sqlRegion);
    $check(mb_strpos($sqlRegion, $conComilla) === false, 'el valor con comilla NO está en la sentencia');
    $check(in_array($conComilla, array_values($valoresRegion), true), 'el valor viaja ENTERO en los reemplazos');
    $check(mb_strpos($sqlRegion, 'UPPER(:') !== false, 'el marcador va envuelto en UPPER(), como la cadena de antes');

    //COLUMNA REPETIDA, ALIAS DISTINTO. `dataTablesRequestUsers` compara `status` y `type` dos
    //veces cada una; si dos alias colisionaran, se perdería un valor SIN ruido.
    $itemsRepetidos = [];
    foreach ([['id', 9], ['status', 6], ['type', 0], ['type', 1], ['status', 2]] as $par) {
        $itemsRepetidos[] = new WhereItem($par[0], WhereItem::NOT_EQUAL_OPERATOR, $par[1], WhereItem::AND_OPERATOR);
    }
    $segmentoRepetidos = new WhereSegment($itemsRepetidos);
    $check(
        count($segmentoRepetidos->getReplacementValues()) === $segmentoRepetidos->countCriteria(),
        'cinco criterios con columnas repetidas dan CINCO alias distintos',
        'criterios: ' . $segmentoRepetidos->countCriteria() . ', alias: ' . count($segmentoRepetidos->getReplacementValues())
    );

    //LO QUE PROTEGE A LAS QUE NO HAN MIGRADO: sin las claves nuevas, la vía de cadena tiene
    //que seguir ahí, en los DOS modelos y para las DOS cláusulas.
    $helper = (string) @file_get_contents($raizSrc . '/app/core/psr4/PiecesPHP/Core/Utilities/Helpers/DataTablesHelper.php');
    $ramas = [
        '} elseif (mb_strlen($where) > 0) {' => 2,
        '} elseif (mb_strlen($having) > 0) {' => 2,
    ];
    foreach ($ramas as $rama => $esperadas) {
        $vistas = mb_substr_count($helper, $rama);
        $check(
            $vistas === $esperadas,
            'la vía de cadena sigue en pie: ' . $esperadas . ' rama(s) de «' . mb_substr($rama, 9, 22) . '»',
            "encontradas: {$vistas}"
        );
    }

    //LAS DOS GUARDAS DE EXCLUSIÓN MUTUA. La tercera de AP se retiró en AX y su sitio lo ocupa
    //la sección 11, que EJECUTA: una guarda no se borra, se sustituye. Ver T163.
    $guardas = [
        '`where_string` y `where_segment` son excluyentes',
        '`having_string` y `having_segment` son excluyentes',
    ];
    foreach ($guardas as $guarda) {
        $check(
            mb_strpos($helper, $guarda) !== false,
            'process() se niega a: ' . mb_substr($guarda, 0, 46)
        );
    }
    echoTerminal(' ');

    //──── 9. LA QUE EJECUTA (LEY 29) ────────────────────────────────────────────────────
    echoTerminal('[9/14] El SQL de `process()` con segmento se EJECUTA de verdad');

    //LEY 29: las ocho secciones de arriba comparan CADENAS, y ninguna vio la 665. Ver T160.
    $modelo = \App\Locations\Mappers\CountryMapper::model();
    $segmentoEjecutable = new WhereSegment([
        new WhereItem('UPPER(region)', WhereItem::EQUAL_OPERATOR, 'NONE', '',
            'UPPER(' . WhereItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')'),
    ]);
    $modelo->select(['id'])->where($segmentoEjecutable);

    //LA MISMA ENVOLTURA QUE ARMA `process()` para su conteo filtrado.
    $sqlDerivado = 'SELECT COUNT(*) AS total FROM (' . $modelo->getCompiledSQL(true) . ') AS table_derivate';

    $ejecutado = false;
    $motivo = '';
    try {
        $sentencia = $modelo->prepare($sqlDerivado);
        $sentencia->execute();
        $sentencia->fetchAll();
        $sentencia->closeCursor();
        $ejecutado = true;
    } catch (\Throwable $errorEjecucion) {
        $motivo = $errorEjecucion->getMessage();
    }

    //SIN BASE NO HAY VEREDICTO, Y SE DICE. Un verde que no ejecutó es LEY 18.
    $check(
        $ejecutado,
        'el SQL de conteo filtrado con segmento SE EJECUTA contra la base',
        $ejecutado ? mb_substr($sqlDerivado, 0, 110) : "NO PUEDO EJECUTAR AQUÍ: {$motivo}"
    );

    //DISCRIMINANTE: sin esto, la comprobación de arriba no sabría qué está probando.
    $sqlDepuracion = $modelo->getCompiledSQL();
    $check(
        mb_strpos($sqlDepuracion, ':WH') === false && mb_strpos($sqlDepuracion, 'WH') !== false,
        'DISCRIMINANTE: `getCompiledSQL()` sin argumento deja el alias SIN los dos puntos',
        'Por eso `process()` tiene que llamarla SIEMPRE con `true`.'
    );
    echoTerminal(' ');

    //──── 10. EL FILTRO DE APROBACIONES, EJECUTADO EN DOS IDIOMAS ───────────────────────
    echoTerminal('[10/14] El desplegable de aprobaciones manda el CRUDO en cualquier idioma');

    //LEY 29: esto CONSULTA. Y la clave es `app_lang`, no `lang`. Ver T162.
    $idiomaPrevio = get_config('app_lang');
    $clavesPorIdioma = [];
    $textosPorIdioma = [];
    foreach (['es', 'en'] as $idioma) {
        set_config('app_lang', $idioma);
        $opciones = \SystemApprovals\Mappers\SystemApprovalsMapper::getReferencesAliases();
        $clavesPorIdioma[$idioma] = array_keys($opciones);
        $textosPorIdioma[$idioma] = array_values($opciones);
    }
    set_config('app_lang', $idiomaPrevio);

    //DISCRIMINANTE: sin traducción efectiva, «claves iguales» pasaría siempre.
    $check(
        $textosPorIdioma['es'] !== $textosPorIdioma['en'],
        'DISCRIMINANTE: los TEXTOS sí cambian entre `es` y `en`',
        'en: ' . (string) json_encode($textosPorIdioma['en'], \JSON_UNESCAPED_UNICODE)
    );
    $check(
        $clavesPorIdioma['es'] === $clavesPorIdioma['en'],
        'las CLAVES del desplegable son las mismas en `es` y en `en`',
        'es: ' . json_encode($clavesPorIdioma['es'], \JSON_UNESCAPED_UNICODE)
            . ' | en: ' . json_encode($clavesPorIdioma['en'], \JSON_UNESCAPED_UNICODE)
    );

    //LA LISTA BLANCA SALE DEL CONTRATO, no de la base: cada handler declara sus textos.
    $tiposDeclarados = \SystemApprovals\Util\SystemApprovalManager::getInstance()->getContentTypes();
    $check(
        in_array('Usuario independiente', $tiposDeclarados, true),
        'la lista blanca incluye el segundo texto de UsersApprovalHandler',
        (string) json_encode($tiposDeclarados, \JSON_UNESCAPED_UNICODE)
    );
    $fueraDeLista = array_diff($clavesPorIdioma['es'], $tiposDeclarados);
    $check(
        count($fueraDeLista) === 0,
        'todo alias guardado en la base está DECLARADO por algún handler',
        count($fueraDeLista) > 0
            ? 'SIN DECLARAR: ' . json_encode(array_values($fueraDeLista), \JSON_UNESCAPED_UNICODE)
            : 'la lista blanca cubre lo que hay.'
    );
    echoTerminal(' ');

    //──── 11. LO QUE SUSTITUYE A LA GUARDA DE AP (LEY 30) ───────────────────────────────
    echoTerminal('[11/14] El grupo de búsqueda se une con AND, no con OR');

    //AP prohibía `having_segment` con búsqueda activa porque `HavingSegment` no agrupaba. Con
    //v4.1.0 agrupa, y esto es lo que ocupa el sitio de aquella guarda. Ver T163.
    $criterioPrograma = new HavingItem('organizationID', HavingItem::EQUAL_OPERATOR, 12, HavingItem::AND_OPERATOR);
    $segmentoConGrupo = new HavingSegment([$criterioPrograma]);

    //Sin `setAccessible()`: desde 8.1 no hace nada y en 8.5 es deprecacion, que aqui es fatal.
    $reflexion = new \ReflectionMethod(\PiecesPHP\Core\Utilities\Helpers\DataTablesHelper::class, 'generateHavingGroup');
    $grupoBusqueda = $reflexion->invokeArgs(null, [
        ['title', 'autor'],
        [['searchable' => 'true'], ['searchable' => 'true']],
        ['value' => 'a'],
        'tabla',
        [],
    ]);
    $segmentoConGrupo->addGroup($grupoBusqueda);
    //EL GRUPO NO PUEDE IR EL ULTIMO, y ahi estaba mi error: `HavingSegment::toString()` suprime
    //el operador del ultimo, asi que en esa posicion el defecto no se ve. Se pone uno detras.
    $segmentoConGrupo->addCriteria([new HavingItem('status', HavingItem::EQUAL_OPERATOR, 1)]);
    $sqlGrupo = $segmentoConGrupo->toString();

    $check(mb_strpos($sqlGrupo, ') AND (') !== false, 'los dos grupos se unen con `) AND (`', $sqlGrupo);
    $check(mb_strpos($sqlGrupo, ') OR (') === false, 'y NO con `) OR (`, que anularía el criterio anterior');
    $check(
        mb_substr_count($sqlGrupo, ':WH') === $segmentoConGrupo->countCriteria()
            && count($segmentoConGrupo->getReplacementValues()) === $segmentoConGrupo->countCriteria(),
        'hay tantos marcadores y tantos valores como criterios',
        'criterios: ' . $segmentoConGrupo->countCriteria() . ' | marcadores: ' . mb_substr_count($sqlGrupo, ':WH')
    );
    $check(mb_strpos($sqlGrupo, '%A%') === false, 'el valor buscado NO aparece literal en el SQL');

    //Y SE EJECUTA CONTRA LA BASE, con un valor normal. LEY 29.
    $modeloGrupo = \App\Locations\Mappers\CountryMapper::model();
    $modeloGrupo->select(['id'])->having(new HavingSegment([
        new HavingItem('id', HavingItem::NOT_EQUAL_OPERATOR, -1, HavingItem::AND_OPERATOR),
    ]));
    $ejecutoGrupo = false;
    $motivoGrupo = '';
    try {
        $sentenciaGrupo = $modeloGrupo->prepare($modeloGrupo->getCompiledSQL(true));
        $sentenciaGrupo->execute();
        $sentenciaGrupo->fetchAll();
        $sentenciaGrupo->closeCursor();
        $ejecutoGrupo = true;
    } catch (\Throwable $errorGrupo) {
        $motivoGrupo = $errorGrupo->getMessage();
    }
    $check(
        $ejecutoGrupo,
        'un HAVING con grupo SE EJECUTA contra la base',
        $ejecutoGrupo ? null : "NO PUEDO EJECUTAR AQUÍ: {$motivoGrupo}"
    );
    echoTerminal(' ');

    //──── 12. MySpace: precedencia, y la búsqueda de la tabla derivada ──────────────────
    echoTerminal('[12/14] `AllProfiles` parentiza, y `processFromQuery` va por marcador');

    //`A AND B OR C` se lee `(A AND B) OR C`: sin los paréntesis, los usuarios se listaban sin
    //comprobar su aprobación. Ver T166.
    $perfiles = (string) @file_get_contents($raizSrc . '/app/classes/MySpace/Controllers/AllProfilesController.php');
    $check(
        $perfiles !== '' && mb_strpos($perfiles, 'AND (userType IS NULL OR userType IN') !== false,
        'AllProfiles: el criterio de tipo va PARENTIZADO',
        'Sin el paréntesis, `AND` liga más fuerte y la aprobación solo filtra organizaciones.'
    );

    //`processFromQuery` compone SQL crudo: su búsqueda es la última que concatenaba.
    $helperAz = (string) @file_get_contents($raizSrc . '/app/core/psr4/PiecesPHP/Core/Utilities/Helpers/DataTablesHelper.php');
    $check(
        $helperAz !== '' && mb_strpos($helperAz, '$havingGroup = self::generateHavingGroup(') !== false,
        'processFromQuery: la búsqueda usa `generateHavingGroup()`'
    );
    $check(
        $helperAz !== '' && mb_strpos($helperAz, '$limitPrepared->execute($havingValores)') !== false
            && mb_strpos($helperAz, '$totalCountPrepared->execute();') !== false,
        'y los valores se atan SOLO a las dos sentencias que llevan el HAVING',
        'La tercera no lo lleva: atarle un marcador que no tiene es HY093.'
    );

    //El grupo se convierte a texto SOLO, sin `HavingSegment` que le suprima el operador.
    $check(
        $helperAz !== '' && mb_strpos($helperAz, '$havingGroup->withAfterOperator(false)') !== false,
        'y el grupo apaga su operador de cola antes de convertirse a texto',
        'Sin esto queda un `AND` colgando delante del `ORDER BY`.'
    );

    //El log SMTP no vuelve al cliente por una ruta pública.
    $contacto = (string) @file_get_contents($raizSrc . '/app/controller/ContactFormsController.php');
    $check(
        $contacto !== '' && mb_strpos($contacto, "setValue('logMailer'") === false,
        'ContactForms: el log SMTP NO sale en la respuesta',
        'La ruta es pública y `SMTPDebug = 2` mete el banner del servidor en el log.'
    );
    echoTerminal(' ');

    //──── 13. La dirección de `custom_order` ────────────────────────────────────────────
    echoTerminal('[13/14] La dirección de `custom_order` se normaliza a ASC o DESC');

    //Sin la normalización, la dirección de `custom_order` entra en el ORDER BY tal cual: es
    //SQL de quien la escriba. `$table = ''` evita depender de `setTablePrefixOnOrder()`.
    $ordenar = new \ReflectionMethod(\PiecesPHP\Core\Utilities\Helpers\DataTablesHelper::class, 'generateOrderBy');
    foreach ([
        ['DESC; DROP TABLE x', 'id DESC', 'una dirección con un DROP detrás sale como `DESC`'],
        ['asc', 'id ASC', 'una dirección en minúsculas sale como `ASC`'],
        ['DESC', 'id DESC', '`DESC` se queda como `DESC`'],
    ] as [$direccion, $esperado, $nombre]) {
        $obtenido = $ordenar->invokeArgs(null, [[], null, ['id' => $direccion], '']);
        $check($obtenido === $esperado, "custom_order: {$nombre}", 'obtenido: ' . var_export($obtenido, true));
    }
    echoTerminal(' ');

    //──── 14. Los listados paginados van por marcador ────────────────────────────────
    echoTerminal('[14/14] Los listados paginados (`PageQuery`) mandan la petición por marcador');

    //PageQuery ejecuta el SQL tal cual: sin valores ligados, lo que se interpole llega crudo.
    $tablaPaises = \App\Locations\Mappers\CountryMapper::PREFIX_TABLE . \App\Locations\Mappers\CountryMapper::TABLE;
    $contarDirecto = function (string $sql, array $valores = []): int {
        $sentencia = (new \PiecesPHP\Core\BaseModel())->prepare($sql);
        $sentencia->execute($valores);
        $filas = $sentencia->fetchAll(\PDO::FETCH_OBJ);
        $sentencia->closeCursor();
        return count($filas) > 0 ? (int) $filas[0]->total : 0;
    };
    try {
        $totalPaises = $contarDirecto("SELECT COUNT(id) AS total FROM {$tablaPaises}");
        $conA = $contarDirecto("SELECT COUNT(id) AS total FROM {$tablaPaises} WHERE name LIKE :patron", [':patron' => '%a%']);
        $consulta = new \PiecesPHP\Core\Pagination\PageQuery(
            "SELECT id FROM {$tablaPaises} WHERE name LIKE :patron AND id > :minimo",
            "SELECT COUNT(id) AS total FROM {$tablaPaises} WHERE name LIKE :patron",
            1, 3, 'total', [':patron' => '%a%', ':minimo' => 0]
        );
        $totalConsulta = $consulta->getTotal();
        $filasConsulta = $consulta->getResult();
        $check($totalPaises > 0, 'DISCRIMINANTE: la tabla de países tiene filas', "filas: {$totalPaises}");
        $check($totalConsulta === $conA, 'getTotal() liga su marcador y cuenta lo mismo que la consulta directa',
            "PageQuery: {$totalConsulta} · directa: {$conA}");
        $check(count($filasConsulta) === min(3, $conA), 'getResult() liga los suyos, y `:minimo`, que el conteo no lleva, no lo rompe',
            'filas: ' . count($filasConsulta));
        //`:p` no puede casar dentro de `:p1`: con una clave de más, PDO da HY093.
        $prefijo = new \PiecesPHP\Core\Pagination\PageQuery(
            "SELECT id FROM {$tablaPaises} WHERE id > :p1",
            "SELECT COUNT(id) AS total FROM {$tablaPaises} WHERE id > :p1",
            1, 1, 'total', [':p' => 999999999, ':p1' => 0]
        );
        $check($prefijo->getTotal() === $totalPaises, 'un marcador que es prefijo de otro (`:p` y `:p1`) no se liga de más');
    } catch (\Throwable $errorPageQuery) {
        $check(false, 'PageQuery con valores ligados se ejecuta contra la base', 'NO PUEDO EJECUTAR AQUÍ: ' . $errorPageQuery->getMessage());
    }

    //DOS CARGAS POR VÍA. La comilla suelta discrimina SIN FILAS: concatenada rompe el SQL, por
    //marcador es un dato. La otra, concatenada, cambiaría el resultado, y solo discrimina con filas.
    $cargaLike = "%') OR 1=1 OR ('";
    $cargaNotIn = 'x") AND 1=0 AND ("';
    $viaRechazo = function (string $nombre, callable $contar, string $carga, string $comilla, bool $esLista) use ($check): void {
        try {
            $conComilla = $contar($comilla);
            $todos = $contar(null);
            $check($esLista ? $conComilla === $todos : $conComilla <= $todos, "{$nombre}: una comilla suelta va como dato y no rompe el SQL",
                "sin filtro: {$todos} · con la comilla: {$conComilla}");
        } catch (\Throwable $errorComilla) {
            $check(false, "{$nombre}: una comilla suelta va como dato y no rompe el SQL", 'EXCEPCIÓN: ' . mb_substr($errorComilla->getMessage(), 0, 140));
            return;
        }
        try {
            $conCarga = $contar($carga);
        } catch (\Throwable $errorVia) {
            $check(false, "{$nombre}: la carga se toma literal", 'EXCEPCIÓN: ' . mb_substr($errorVia->getMessage(), 0, 140));
            return;
        }
        if ($todos === 0) {
            //SIN FILAS NO HAY VEREDICTO: la carga concatenada tampoco cambiaría nada.
            echoTerminal("   [NO DISCRIMINA] {$nombre}: la base local no tiene filas con las que la carga cambiaría el resultado");
            return;
        }
        $esperado = $esLista ? $todos : 0;
        $check($conCarga === $esperado, "{$nombre}: la carga se toma literal",
            "sin filtro: {$todos} · con la carga: {$conCarga} · tomada literal: {$esperado}");
    };
    $viaRechazo('publications · title', fn (?string $v): int => \Publications\Controllers\PublicationsController::_all(1, 50, null, null, null, $v)->totalElements(), $cargaLike, "'", false);
    $viaRechazo('publications · ignoreSlugs', fn (?string $v): int => \Publications\Controllers\PublicationsController::_all(1, 50, null, null, null, null, false, false, $v === null ? [] : [$v])->totalElements(), $cargaNotIn, 'x"', true);
    $viaRechazo('banner · title', fn (?string $v): int => \PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController::_all(1, 50, null, $v)->totalElements(), $cargaLike, "'", false);
    $viaRechazo('news · newsTitle', fn (?string $v): int => \News\Controllers\NewsController::_all(1, 50, null, null, $v)->totalElements(), $cargaLike, "'", false);
    $viaRechazo('news · ignoreSlugs', fn (?string $v): int => \News\Controllers\NewsController::_all(1, 50, null, null, null, false, false, $v === null ? [] : [$v])->totalElements(), $cargaNotIn, 'x"', true);
    $viaRechazo('organizations · name', fn (?string $v): int => \Organizations\Controllers\OrganizationsController::_all(1, 50, null, $v)->totalElements(), $cargaLike, "'", false);
    //EN GEOJSON EL VALOR SALE DOS VECES en el mismo HAVING: dos comillas sueltas se emparejan y el
    //SQL concatenado sigue siendo válido. `'(` no se empareja.
    $contarRasgos = static fn ($coleccion): int => is_countable($coleccion) ? count($coleccion) : (is_iterable($coleccion) ? iterator_count($coleccion) : -1);
    $viaRechazo('geojson · search (personas)', fn (?string $v): int => $contarRasgos(\GeoJSONManager\Controllers\GeoJsonManagerController::withPersonsProfiles(new \GeoJSONManager\Util\FeaturesCollection(), $v === null ? [] : ['search' => $v])), $cargaLike, "'(", false);
    $viaRechazo('geojson · search (organizaciones)', fn (?string $v): int => $contarRasgos(\GeoJSONManager\Controllers\GeoJsonManagerController::withOrganizationsProfiles(new \GeoJSONManager\Util\FeaturesCollection(), $v === null ? [] : ['search' => $v])), $cargaLike, "'(", false);

    //SIN BASE: el grupo de búsqueda de GeoJSON deja la comilla fuera del SQL y el valor en los reemplazos.
    try {
        $grupoGeo = (new \ReflectionMethod(\GeoJSONManager\Controllers\GeoJsonManagerController::class, 'searchHavingGroup'))->invokeArgs(null, [['fullname', 'fullLocation'], $conComilla]);
        $sqlGeo = $grupoGeo->toString();
        $check(mb_strpos($sqlGeo, "'") === false && in_array('%' . $conComilla . '%', array_values($grupoGeo->getReplacementValues()), true),
            'geojson · search, sin base: la comilla queda FUERA del SQL y viaja en los reemplazos', $sqlGeo);
        $check(mb_substr_count($sqlGeo, ' OR ') === 1, 'geojson · search, sin base: fullname y fullLocation siguen unidos con OR', $sqlGeo);
    } catch (\Throwable $errorGeo) {
        $check(false, 'geojson · search, sin base: la comilla queda FUERA del SQL y viaja en los reemplazos', 'EXCEPCIÓN: ' . $errorGeo->getMessage());
    }
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
