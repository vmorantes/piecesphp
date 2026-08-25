<?php

//Fija que ningún buscador de mapper escriba al no encontrar. Ver T33.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/mapper-finders';
$cliTaskDescription = 'Contrato de los buscadores de mapper: getBy, lastModifiedElement, getByMultipleCriteries';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:MapperFinders] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $skipped = 0;

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
    $skip = function (string $name, string $reason) use (&$skipped) {
        $skipped++;
        echoTerminal("   \e[33m[OMITIDA]\e[39m {$name}");
        echoTerminal("      - {$reason}");
    };

    /**
     * Un id que con toda seguridad no existe. Se usa el negativo para no depender de
     * cuántas filas haya ni de los AUTO_INCREMENT.
     */
    $missingID = -987654321;

    //Los mappers se eligen por cubrir las formas de buscador que hay, no por módulo.
    $mappers = [
        'App\Locations\Mappers\CountryMapper',
        'Publications\Mappers\PublicationMapper',
        'News\Mappers\NewsMapper',
        'EventsLog\Mappers\LogsMapper',
    ];

    /**
     * SEMILLA PROPIA. Esta suite dependía de que las tablas tuvieran datos, y el día que el
     * propietario vació tres de ellas revisando otra cosa, CUATRO COMPROBACIONES DEJARON DE
     * MIRAR NADA sin que nadie se enterara: pasó de 2 omitidas a 6.
     *
     * Un salto permanente informa tan poco como un rojo permanente. Así que ahora la suite
     * **crea su propio dato y lo borra pase lo que pase**, igual que `otp-fresh-user`.
     *
     * La fila se arma leyendo el ESQUEMA —columnas NOT NULL sin valor por defecto— en vez de
     * conocer cada mapper: así vale para cualquiera y no envejece cuando cambien los campos.
     * Si aun así no se puede insertar, esa comprobación se OMITE CON SU RAZÓN y diciendo qué
     * haría falta, nunca en silencio.
     *
     * @var array<string,int|string> ids creados por la suite, por tabla, para borrarlos luego
     */
    $seeded = [];

    $database = (new BaseModel())->getDatabase();

    /**
     * Inserta una fila mínima en la tabla de un mapper. Devuelve el id, o null con la razón.
     *
     * @param string $mapperClass
     * @return array{0:int|string|null,1:string}
     */
    $seedRow = function (string $mapperClass) use ($database) {
        try {
            $table = constant($mapperClass . '::TABLE');
        } catch (\Throwable $e) {
            return [null, 'el mapper no declara TABLE'];
        }

        try {
            //Claves ajenas de esta tabla: columna => [tabla referenciada, columna referenciada].
            $foreignKeys = [];
            $statement = $database->prepare(
                'SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME'
                . ' FROM information_schema.KEY_COLUMN_USAGE'
                . ' WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL'
            );
            $statement->execute([$table]);
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $foreignKeys[$row['COLUMN_NAME']] = [$row['REFERENCED_TABLE_NAME'], $row['REFERENCED_COLUMN_NAME']];
            }

            $columns = [];
            $statement = $database->query('SHOW COLUMNS FROM `' . $table . '`');
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                if ($row['Null'] === 'YES' || $row['Default'] !== null || mb_strpos((string) $row['Extra'], 'auto_increment') !== false) {
                    continue;
                }
                $type = mb_strtolower((string) $row['Type']);
                if (isset($foreignKeys[$row['Field']])) {
                    //Columna con clave ajena: un 0 la viola, así que se toma un id real de la tabla referida.
                    [$referencedTable, $referencedColumn] = $foreignKeys[$row['Field']];
                    $referenced = $database->query(
                        'SELECT `' . $referencedColumn . '` FROM `' . $referencedTable . '` LIMIT 1'
                    )->fetchColumn();
                    if ($referenced === false) {
                        return [null, "la tabla referenciada `{$referencedTable}` está vacía: haría falta una fila ahí para poder sembrar aquí"];
                    }
                    $value = $referenced;
                } elseif (preg_match('~int|decimal|float|double|bit~', $type) === 1) {
                    $value = 0;
                } elseif (preg_match('~datetime|timestamp~', $type) === 1) {
                    $value = '2000-01-01 00:00:00';
                } elseif (preg_match('~^date~', $type) === 1) {
                    $value = '2000-01-01';
                } else {
                    $value = 'zz-mapper-finders';
                }
                $columns[$row['Field']] = $value;
            }

            if (count($columns) === 0) {
                //Sin columnas obligatorias, un INSERT vacío sigue siendo válido.
                $statement = $database->prepare('INSERT INTO `' . $table . '` () VALUES ()');
                $statement->execute();
            } else {
                $names = '`' . implode('`, `', array_keys($columns)) . '`';
                $marks = implode(', ', array_fill(0, count($columns), '?'));
                $statement = $database->prepare('INSERT INTO `' . $table . '` (' . $names . ') VALUES (' . $marks . ')');
                $statement->execute(array_values($columns));
            }

            return [$database->lastInsertId(), ''];
        } catch (\Throwable $e) {
            return [null, 'no se pudo insertar una fila de prueba: ' . mb_substr($e->getMessage(), 0, 90)];
        }
    };

    /**
     * Descubre un id existente, y si la tabla está vacía SIEMBRA uno.
     *
     * @param string $mapperClass
     * @return int|string|null
     */
    $sampleID = function (string $mapperClass) use ($seedRow, &$seeded) {
        try {
            $model = $mapperClass::model();
            $model->select(['id']);
            $model->execute();
            $rows = $model->result();
            if (is_array($rows) && count($rows) > 0) {
                return $rows[0]->id ?? null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        $table = defined($mapperClass . '::TABLE') ? constant($mapperClass . '::TABLE') : null;
        if ($table === null || array_key_exists($table, $seeded)) {
            return $seeded[$table] ?? null;
        }

        [$id, $reason] = $seedRow($mapperClass);
        if ($id === null) {
            echoTerminal("      \e[33m·\e[39m {$mapperClass}: {$reason}");
            return null;
        }
        $seeded[$table] = $id;
        return $id;
    };

    /**
     * ¿El id de este mapper lo sembró la suite? Una fila sintética vale para comprobar el
     * CONTRATO del buscador —null, \stdClass, instancia— pero no para hidratar el mapper.
     *
     * @param string $mapperClass
     * @return bool
     */
    $isSeeded = function (string $mapperClass) use (&$seeded): bool {
        $table = defined($mapperClass . '::TABLE') ? constant($mapperClass . '::TABLE') : null;
        return $table !== null && array_key_exists($table, $seeded);
    };

    //──── 1. getBy: no encontrado devuelve null ─────────────────────────────────────────
    echoTerminal('[1/5] getBy() con un id inexistente debe devolver null...');
    foreach ($mappers as $mapperClass) {
        if (!class_exists($mapperClass)) {
            $skip("getBy null — {$mapperClass}", 'la clase no existe en esta instalación');
            continue;
        }
        try {
            $r = $mapperClass::getBy($missingID, 'id');
            $check($r === null, "getBy(inexistente) === null — " . basename(str_replace('\\', '/', $mapperClass)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $check(false, "getBy(inexistente) — {$mapperClass}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 2. getBy con el flag en false devuelve la fila cruda ──────────────────────────
    echoTerminal('[2/5] getBy() sin el flag debe devolver \stdClass...');
    foreach ($mappers as $mapperClass) {
        if (!class_exists($mapperClass)) { continue; }
        $id = $sampleID($mapperClass);
        if ($id === null) {
            $skip('getBy stdClass — ' . basename(str_replace('\\', '/', $mapperClass)), 'la tabla no tiene filas');
            continue;
        }
        try {
            $r = $mapperClass::getBy($id, 'id', false);
            $check($r instanceof \stdClass, 'getBy(id, false) devuelve \stdClass — ' . basename(str_replace('\\', '/', $mapperClass)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $check(false, "getBy(id, false) — {$mapperClass}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 3. getBy con el flag en true devuelve una instancia del mapper ────────────────
    echoTerminal('[3/5] getBy() con el flag debe devolver una instancia del propio mapper...');
    foreach ($mappers as $mapperClass) {
        if (!class_exists($mapperClass)) { continue; }
        $id = $sampleID($mapperClass);
        if ($id === null) {
            $skip('getBy mapper — ' . basename(str_replace('\\', '/', $mapperClass)), 'la tabla no tiene filas');
            continue;
        }
        if ($isSeeded($mapperClass)) {
            $skip('getBy mapper — ' . basename(str_replace('\\', '/', $mapperClass)), 'la fila es SINTÉTICA —sembrada desde el esquema— y no satisface al hidratador del mapper, que espera meta-propiedades y relaciones con forma. Para que corra hace falta un alta REAL por el módulo: se cubre en el ciclo CRUD de E2');
            continue;
        }
        try {
            $r = $mapperClass::getBy($id, 'id', true);
            $check($r instanceof $mapperClass, 'getBy(id, true) devuelve ' . basename(str_replace('\\', '/', $mapperClass)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $check(false, "getBy(id, true) — {$mapperClass}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 4. lastModifiedElement respeta el mismo contrato ──────────────────────────────
    echoTerminal('[4/5] lastModifiedElement() debe respetar el mismo contrato de flag...');
    foreach ($mappers as $mapperClass) {
        if (!class_exists($mapperClass) || !method_exists($mapperClass, 'lastModifiedElement')) {
            continue;
        }
        if ($isSeeded($mapperClass)) {
            $skip('lastModifiedElement — ' . basename(str_replace('\\', '/', $mapperClass)), 'la fila es SINTÉTICA —sembrada desde el esquema— y no satisface al hidratador del mapper, que espera meta-propiedades y relaciones con forma. Para que corra hace falta un alta REAL por el módulo: se cubre en el ciclo CRUD de E2');
            continue;
        }
        try {
            $rawRow = $mapperClass::lastModifiedElement(false);
            $mapper = $mapperClass::lastModifiedElement(true);
            $name = basename(str_replace('\\', '/', $mapperClass));

            $rawIsOk = $rawRow === null || $rawRow instanceof \stdClass;
            $mapperIsOk = $mapper === null || $mapper instanceof $mapperClass;

            $check($rawIsOk, "lastModifiedElement(false) es \stdClass o null — {$name}",
                'Obtenido: ' . (is_object($rawRow) ? get_class($rawRow) : gettype($rawRow)));
            $check($mapperIsOk, "lastModifiedElement(true) es {$name} o null",
                'Obtenido: ' . (is_object($mapper) ? get_class($mapper) : gettype($mapper)));

            //Coherencia entre las dos ramas: o las dos encuentran algo, o ninguna.
            $check(($rawRow === null) === ($mapper === null),
                "las dos ramas coinciden en si hay resultado — {$name}");
        } catch (\Throwable $e) {
            $check(false, "lastModifiedElement — {$mapperClass}", 'Excepción: ' . $e->getMessage());
        }
    }
    echoTerminal(' ');

    //──── 5. getByMultipleCriteries ─────────────────────────────────────────────────────
    echoTerminal('[5/5] getByMultipleCriteries() sin criterios que casen debe devolver null...');
    $byCriteria = [
        'SystemApprovals\Mappers\SystemApprovalsMapper',
    ];
    foreach ($byCriteria as $mapperClass) {
        if (!class_exists($mapperClass) || !method_exists($mapperClass, 'getByMultipleCriteries')) {
            $skip("getByMultipleCriteries — {$mapperClass}", 'la clase o el método no existen');
            continue;
        }
        try {
            $r = $mapperClass::getByMultipleCriteries([
                ['column' => 'id', 'value' => $missingID],
            ]);
            $check($r === null, 'getByMultipleCriteries(sin coincidencia) === null — ' . basename(str_replace('\\', '/', $mapperClass)),
                'Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $check(false, "getByMultipleCriteries — {$mapperClass}", 'Excepción: ' . $e->getMessage());
        }
    }

    //Estos aceptan $currentUser nulo y caen a la sesión: sin sesión no prueban lo que dicen probar.
    foreach ([
        'App\\Model\\UsersModel',
    ] as $mapperClass) {
        if (!class_exists($mapperClass) || !method_exists($mapperClass, 'getByMultipleCriteries')) {
            $skip("getByMultipleCriteries — {$mapperClass}", 'la clase o el método no existen');
            continue;
        }
        try {
            $r = $mapperClass::getByMultipleCriteries([
                ['column' => 'id', 'value' => $missingID],
            ]);
            $check($r === null, 'getByMultipleCriteries SIN SESIÓN devuelve null — ' . basename(str_replace('\\', '/', $mapperClass)),
                'Antes reventaba leyendo ->organization sobre null. Obtenido: ' . (is_object($r) ? get_class($r) : gettype($r)));
        } catch (\Throwable $e) {
            $check(false, "getByMultipleCriteries sin sesión — {$mapperClass}", 'Excepción: ' . $e->getMessage());
        }
    }

    /**
     * Los otros tres métodos de UsersModel con la misma forma. all() y
     * allByMultipleCriteries() devuelven colecciones, no un elemento.
     */
    if (class_exists('App\\Model\\UsersModel')) {
        foreach (['all', 'allByMultipleCriteries'] as $method) {
            if (!method_exists('App\\Model\\UsersModel', $method)) { continue; }
            try {
                $r = $method === 'all'
                    ? \App\Model\UsersModel::all()
                    : \App\Model\UsersModel::allByMultipleCriteries([['column' => 'id', 'value' => $missingID]]);
                $check(is_array($r), "UsersModel::{$method}() SIN SESIÓN devuelve un array",
                    'Obtenido: ' . gettype($r) . (is_array($r) ? ' de ' . count($r) . ' elementos' : ''));
            } catch (\Throwable $e) {
                $check(false, "UsersModel::{$method}() sin sesión", 'Excepción: ' . $e->getMessage());
            }
        }
    }
    echoTerminal(' ');

    //──── Limpieza: lo que sembró la suite se va, pase lo que pase ──────────────────────
    foreach ($seeded as $table => $id) {
        try {
            $statement = $database->prepare('DELETE FROM `' . $table . '` WHERE id = ?');
            $statement->execute([$id]);
            echoTerminal("   sembrado y borrado en {$table}: id={$id}");
        } catch (\Throwable $e) {
            echoTerminal("   \e[31mNO SE PUDO BORRAR\e[39m la fila sembrada en {$table} (id={$id}): " . mb_substr($e->getMessage(), 0, 80));
        }
    }
    if (count($seeded) > 0) {
        echoTerminal(' ');
    }
    //──── Balance ───────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS, {$skipped} OMITIDAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:MapperFinders] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Contrato de los buscadores de mapper verificado ({$passed} comprobaciones, {$skipped} omitidas)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_DATABASE])->register();
