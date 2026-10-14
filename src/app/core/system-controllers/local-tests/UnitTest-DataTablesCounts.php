<?php

//Los dos recuentos de DataTablesHelper::process() contra filas propias de la base LOCAL: recordsFiltered y recordsTotal
//coinciden con lo contado aparte, con enlace, en cada forma de listado. Las filas llevan prefijo y se borran por id.

use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/datatables-counts', function ($args) {

    echoTerminal("\e[33m[TEST:DataTablesCounts] Los totales de los listados coinciden con las filas reales, también con contrabarras\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $table = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;
    $prefix = 'zz-dtcounts-';
    $count = function (string $sql, array $values = []) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute($values);
        return (int) $statement->fetchColumn();
    };
    $request = function (string $term): RequestRoute {
        $request = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/datatables'), new Headers(['X-Requested-With' => 'XMLHttpRequest']), [], [], (new StreamFactory())->createStream(''));
        $withQuery = $request->withQueryParams([
            'draw' => 1, 'start' => 0, 'length' => 10,
            'columns' => [['searchable' => 'true']],
            'search' => ['value' => $term, 'regex' => 'false'],
        ]);
        return $withQuery instanceof RequestRoute ? $withQuery : $request;
    };
    $totals = function (array $options): array {
        $values = DataTablesHelper::process($options)->getValues();
        return [(int) ($values['recordsFiltered'] ?? -1), (int) ($values['recordsTotal'] ?? -1)];
    };
    //El patrón que arma DataTablesHelper para la búsqueda (generateHavingGroup): la referencia usa el mismo, enlazado.
    $pattern = fn (string $term): string => '%' . mb_strtoupper(trim($term)) . '%';

    echoTerminal('[canario] El punto de partida es limpio');
    $leftover = $count("SELECT COUNT(*) FROM `{$table}` WHERE name LIKE ?", [$prefix . '%']);
    $check($leftover === 0, 'c1 no hay filas con el prefijo antes de empezar', "{$leftover} fila(s)");
    if ($leftover !== 0) {
        echoTerminal("\e[31m Quedan filas de una corrida anterior: bórralas por id antes de seguir. \e[39m");
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    echoTerminal(' ');

    $tricky = $prefix . "a\\b \$1 'q";
    $double = $prefix . 'doble  espacio';
    $rows = [
        [$prefix . 'alfa', 'zz-dtc-r1', 1],
        [$prefix . 'alfa-2', 'zz-dtc-r1', 0],
        [$prefix . 'beta', 'zz-dtc-r2', 1],
        [$tricky, 'zz-dtc-r2', 1],
        [$tricky . '-2', "zz-dtc-a\\b \$1 'q", 0],
        [$double, 'zz-dtc-doble  region', 1],
    ];
    $ids = [];
    try {
        $insert = $database->prepare("INSERT INTO `{$table}` (name, region, active) VALUES (?, ?, ?)");
        foreach ($rows as $row) {
            $insert->execute($row);
            $ids[] = (int) $database->lastInsertId();
        }
        echoTerminal('   INFO: ' . count($ids) . " filas propias en {$table}, ids " . implode(', ', $ids));
        echoTerminal(' ');

        echoTerminal('[0] Pedir el SQL preparado no altera lo que el modelo compila después');
        $probe = new CountryMapper();
        $model = $probe->getModel();
        $where = new WhereSegment();
        $where->addCritery(new WhereItem('region', WhereItem::EQUAL_OPERATOR, 'zz-dtc-r1'));
        $having = new HavingSegment();
        $having->addCritery(new HavingItem('active', HavingItem::EQUAL_OPERATOR, 1));
        $model->select()->where($where)->having($having);
        $before = $model->getCompiledSQL(true);
        $prepared = $model->getPreparedSQL();
        $check($model->getCompiledSQL(true) === $before && $model->getPreparedSQL() === $prepared, '0a compilar y preparar, una y otra vez, da siempre lo mismo', $before);
        echoTerminal(' ');

        $cases = [
            'simple' => [
                'options' => ['columns_order' => ['name']],
                'filtered' => fn (string $term) => $count("SELECT COUNT(*) FROM `{$table}` WHERE UPPER(name) LIKE ?", [$pattern($term)]),
                'total' => fn () => $count("SELECT COUNT(*) FROM `{$table}`"),
            ],
            'con GROUP BY' => [
                'options' => ['columns_order' => ['region'], 'group_string' => 'region', 'select_fields' => ['region']],
                'filtered' => fn (string $term) => $count("SELECT COUNT(*) FROM (SELECT region FROM `{$table}` GROUP BY region HAVING UPPER(region) LIKE ?) AS t", [$pattern($term)]),
                'total' => fn () => $count("SELECT COUNT(*) FROM (SELECT region FROM `{$table}` GROUP BY region) AS t"),
            ],
            'con HAVING fijo y el de la búsqueda' => [
                'options' => ['columns_order' => ['name'], 'having_segment' => fn () => new HavingSegment([new HavingItem('active', HavingItem::EQUAL_OPERATOR, 1)])],
                'filtered' => fn (string $term) => $count("SELECT COUNT(*) FROM `{$table}` WHERE active = 1 AND UPPER(name) LIKE ?", [$pattern($term)]),
                'total' => fn () => $count("SELECT COUNT(*) FROM `{$table}` WHERE active = 1"),
            ],
            'con filtros fijos' => [
                'options' => ['columns_order' => ['name'], 'where_segment' => fn () => new WhereSegment([new WhereItem('region', WhereItem::EQUAL_OPERATOR, 'zz-dtc-r1')])],
                'filtered' => fn (string $term) => $count("SELECT COUNT(*) FROM `{$table}` WHERE region = ? AND UPPER(name) LIKE ?", ['zz-dtc-r1', $pattern($term)]),
                'total' => fn () => $count("SELECT COUNT(*) FROM `{$table}` WHERE region = ?", ['zz-dtc-r1']),
            ],
        ];
        $terms = [
            'normal' => $prefix . 'alfa',
            'con contrabarra, «$1» y comilla' => $tricky,
            'con dos espacios seguidos' => $double,
        ];
        $groupTerms = ['normal' => 'zz-dtc-r1', 'con contrabarra, «$1» y comilla' => "zz-dtc-a\\b \$1 'q", 'con dos espacios seguidos' => 'zz-dtc-doble  region'];

        $number = 0;
        foreach ($cases as $name => $case) {
            $number++;
            echoTerminal("[{$number}] Listado {$name}");
            foreach ($terms as $termName => $term) {
                $term = $name === 'con GROUP BY' ? $groupTerms[$termName] : $term;
                $options = ['request' => $request($term), 'mapper' => new CountryMapper()];
                foreach ($case['options'] as $key => $value) {
                    //Los segmentos se construyen de nuevo en cada llamada: process() les añade la búsqueda.
                    $options[$key] = $value instanceof \Closure ? $value() : $value;
                }
                [$filtered, $total] = $totals($options);
                $expectedFiltered = ($case['filtered'])($term);
                $expectedTotal = ($case['total'])();
                $check($filtered === $expectedFiltered && $total === $expectedTotal, "{$number} · búsqueda {$termName}: recordsFiltered y recordsTotal = las filas reales", "listado {$filtered} / {$total} · contadas {$expectedFiltered} / {$expectedTotal}");
                if ($termName === 'normal') {
                    $check($expectedFiltered > 0, "{$number} · la búsqueda normal encuentra algo (si no, la comparación no prueba nada)", "{$expectedFiltered}");
                }
            }
            echoTerminal(' ');
        }
        $realDouble = $count("SELECT COUNT(*) FROM `{$table}` WHERE UPPER(name) LIKE ?", [$pattern($double)]);
        $check($realDouble === 1, 'z0 la fila con dos espacios seguidos la encuentra la búsqueda real: el caso que caza el texto compilado', "{$realDouble}");
    } finally {
        $delete = $database->prepare("DELETE FROM `{$table}` WHERE id = ? AND name LIKE ?");
        $deleted = 0;
        foreach ($ids as $id) {
            $delete->execute([$id, $prefix . '%']);
            $deleted += $delete->rowCount();
        }
        echoTerminal('   INFO: borradas ' . $deleted . ' de ' . count($ids) . ' filas propias, por id');
    }
    $check($count("SELECT COUNT(*) FROM `{$table}` WHERE name LIKE ?", [$prefix . '%']) === 0, 'z1 no queda ninguna fila con el prefijo');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los dos recuentos de los listados (recordsFiltered y recordsTotal) coinciden con las filas reales en cada forma de listado, también con una búsqueda con contrabarras, «$1», comillas o dos espacios seguidos.')->setEffects([CliActions::EFFECT_DATABASE])->register();
