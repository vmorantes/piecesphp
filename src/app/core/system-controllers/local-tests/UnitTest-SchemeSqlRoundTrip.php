<?php

//El SQL que la regla 7 promete generado: crear TODO el esquema y deshacerlo. Ver T52.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\SchemeCreator;
use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\SchemeCreateTask;

CliActions::make('unit-tests:core/scheme-sql-round-trip', function ($args) {

    echoTerminal("\e[33m[TEST:scheme-sql] Crear el esquema entero y deshacerlo\e[39m");
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

    //UNA SUITE OMITIDA ES UNA PUERTA FALLADA (LEY 13). Esto se omitió a sí mismo durante un
    //día entero por un paquete viejo, y la omisión se leyó dos veces como si fuera un 8/8.
    if (!method_exists(SchemeCreator::class, 'createScript') || !method_exists(SchemeCreator::class, 'dropScript')) {
        $check(false, 'el paquete instalado trae createScript() y dropScript()', 'hace falta piecesphp/database >= 3.4.0: LA SUITE NO PUDO CORRER');
        echoTerminal('');
        echoTerminal("   Total: 1 | \e[32mPasaron: 0\e[39m | \e[31mFallaron: 1\e[39m");
        return ['success' => false, 'message' => 'la suite no pudo correr'];
    }

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        echoTerminal("   \e[31mSin conexión: la suite no puede correr.\e[39m");
        return ['success' => false, 'message' => 'sin conexión'];
    }

    //Los comentarios se quitan por líneas ANTES de partir: la cabecera va pegada a la 1ª sentencia.
    $statements = static function (string $script): array {
        $clean = [];
        foreach (explode("\n", $script) as $line) {
            if (str_starts_with(trim($line), '--')) {
                continue;
            }
            $clean[] = $line;
        }
        $out = [];
        foreach (explode(';', implode("\n", $clean)) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk !== '') {
                $out[] = $chunk . ';';
            }
        }
        return $out;
    };

    $discovered = \Closure::bind(
        static fn () => SchemeCreateTask::discover('all'),
        null,
        SchemeCreateTask::class
    )();
    $creators = $discovered['creators'];

    $check(count($creators) > 0, 'el descubrimiento encuentra mappers', count($creators) . ' mapper(s)');
    $check(count($discovered['skipped']) === 0, 'ningún mapper se queda fuera en silencio', implode('; ', $discovered['skipped']));

    //El errno 150 no dice qué columna es: esto la nombra leyendo los mappers. Ver T52.
    $fieldsOf = static function ($mapper): array {
        $reflection = new \ReflectionClass($mapper);
        //Sin setAccessible(): no hace nada desde 8.1 y en 8.5 es una deprecación, que aquí aborta.
        $value = $reflection->getProperty('fields')->getValue($mapper);
        return is_array($value) ? $value : [];
    };
    //El nombre de tabla se lee del propio mapper: EntityMapper no lo expone con un accesor.
    $tableOfIndex = [];
    foreach ($discovered['mappers'] as $index => $mapper) {
        $tableOfIndex[$index] = (string) (new \ReflectionClass($mapper))->getProperty('table')->getValue($mapper);
    }
    $primaryTypeByTable = [];
    foreach ($discovered['mappers'] as $index => $mapper) {
        foreach ($fieldsOf($mapper) as $name => $definition) {
            if (is_array($definition) && ($definition['primary_key'] ?? false) === true) {
                $primaryTypeByTable[$tableOfIndex[$index] ?? ''] = (string) ($definition['type'] ?? '');
            }
        }
    }
    $mismatches = [];
    foreach ($discovered['mappers'] as $index => $mapper) {
        foreach ($fieldsOf($mapper) as $name => $definition) {
            if (!is_array($definition) || !isset($definition['reference_table'])) {
                continue;
            }
            $referenced = (string) $definition['reference_table'];
            if (!isset($primaryTypeByTable[$referenced])) {
                continue;
            }
            $own = (string) ($definition['type'] ?? '');
            if ($own !== $primaryTypeByTable[$referenced]) {
                $mismatches[] = ($tableOfIndex[$index] ?? '?') . '.' . $name . " es {$own} y {$referenced} es " . $primaryTypeByTable[$referenced];
            }
        }
    }
    $check(
        count($mismatches) === 0,
        'cada clave ajena declara el MISMO tipo que la columna que referencia',
        count($mismatches) > 0
            ? count($mismatches) . " desajuste(s):\n        - " . implode("\n        - ", array_slice($mismatches, 0, 8))
              . (count($mismatches) > 8 ? "\n        - … y " . (count($mismatches) - 8) . ' más' : '')
            : ''
    );

    $createScript = SchemeCreator::createScript($creators);
    $dropScript = SchemeCreator::dropScript($creators);
    $createStatements = $statements($createScript);
    $dropStatements = $statements($dropScript);

    $check(
        count($createStatements) === count($dropStatements),
        'la ida y la vuelta cubren las MISMAS tablas',
        count($createStatements) . ' crea, ' . count($dropStatements) . ' borra'
    );

    $tmpDatabase = 'pcs_zz_scheme_round_trip';
    $originalDatabase = (string) $db->getDatabaseName();
    $expected = count($createStatements);

    try {
        $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`");
        $db->exec("CREATE DATABASE `{$tmpDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");
        $db->exec("USE `{$tmpDatabase}`");

        /**
         * EL JUEZ ES MARIADB. Si el orden estuviera mal o un tipo de clave ajena no
         * casara con el de la columna referenciada, esto para aquí con errno 150.
         */
        //NO se para en el primer fallo: una cifra parcial no dice cuánto esquema está roto.
        $failures = [];
        $applied = 0;
        foreach ($createStatements as $statement) {
            try {
                $db->exec($statement);
                $applied++;
            } catch (\Throwable $exception) {
                preg_match('/CREATE TABLE IF NOT EXISTS `([^`]+)`/', $statement, $matched);
                $failures[] = ($matched[1] ?? '?') . ': ' . mb_substr(str_replace("\n", ' ', $exception->getMessage()), 0, 80);
            }
        }
        $check(
            count($failures) === 0,
            'el script de creación se aplica ENTERO en el orden que emite',
            count($failures) > 0
                ? count($failures) . " de {$expected} sentencias rechazadas por la base:\n        - " . implode("\n        - ", $failures)
                : ''
        );

        $created = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$tmpDatabase}'")->fetchColumn();
        $check($created === $expected, 'quedan creadas todas las tablas del script', "{$created} de {$expected}");

        $dropFailure = null;
        foreach ($dropStatements as $statement) {
            try {
                $db->exec($statement);
            } catch (\Throwable $exception) {
                $dropFailure = mb_substr(str_replace("\n", ' ', $exception->getMessage()), 0, 140);
                break;
            }
        }
        $check($dropFailure === null, 'el script de borrado se aplica ENTERO sin violar claves ajenas', (string) $dropFailure);

        $left = (int) $db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$tmpDatabase}'")->fetchColumn();
        $check($left === 0, 'no queda ninguna tabla', "quedan {$left}");

    } catch (\Throwable $exception) {
        $check(false, 'la base de usar y tirar se pudo preparar', $exception->getMessage());
    } finally {
        try {
            $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`");
            $db->exec("USE `{$originalDatabase}`");
        } catch (\Throwable $exception) {
        }
    }

    echoTerminal('');
    echoTerminal("   Total: " . ($passed + $failed) . " | \e[32mPasaron: {$passed}\e[39m | \e[31mFallaron: {$failed}\e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/" . ($passed + $failed)];

})->setDescription('Crea el esquema entero desde los mappers y lo deshace, con MariaDB de juez.')->register();
