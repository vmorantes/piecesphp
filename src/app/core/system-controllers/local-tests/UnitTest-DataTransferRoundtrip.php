<?php

//Doble vía declarada: todo exportador que declara importDefinition() genera un archivo que su importador puede leer.

use DataImportExportUtility\DataImportExportUtilityRoutes;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Import\Column;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/data-transfer-roundtrip', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferRoundtrip] Exportadores que declaran su importador\e[39m");
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

    if (!class_exists('ZzRoundtripImport', false)) {
        class ZzRoundtripImport extends ImportDefinition
        {
            public function key(): string { return 'zz-ida'; }
            public function title(): string { return 'Ida'; }
            public function allowedUserTypes(): array { return [0]; }
            public function columns(): array
            {
                return [new Column('name', 'Nombre', true), new Column('age', 'Edad', true), new Column('note', 'Nota')];
            }
            public function persist(array $rows): ?ImportArtifacts { return null; }
        }
        class ZzRoundtripExport extends ExportDefinition
        {
            /** @var ExportColumn[] */
            public static $columns = [];
            /** @var string|null */
            public static $import = null;
            public function key(): string { return 'zz-vuelta'; }
            public function title(): string { return 'Vuelta'; }
            public function allowedUserTypes(): array { return [0]; }
            public function columns(ExportContext $context): array { return self::$columns; }
            public function rows(ExportContext $context): iterable { return []; }
            public function importDefinition(): ?string { return self::$import; }
        }
    }

    try {
        //─── 1 · Los registrados ────────────────────────────────────────────────────────────────────────
        echoTerminal('[1] Exportadores registrados');
        $exporters = DataImportExportUtilityRoutes::exporters();
        $check(count($exporters) > 0, 'r1 hay exportadores registrados en el arranque de la terminal', (string) json_encode(array_keys($exporters)));
        $conImportador = 0;
        foreach ($exporters as $key => $class) {
            /** @var ExportDefinition $definition */
            $definition = new $class();
            if ($definition->importDefinition() === null) {
                continue;
            }
            $conImportador++;
            $problemas = $definition->roundTripProblems(new ExportContext([], null));
            $check($problemas === [], "r2 «{$key}» se puede reimportar con " . $definition->importDefinition(), implode(' | ', $problemas));
        }
        $check($conImportador > 0 && array_key_exists('users', $exporters), 'r3 al menos users declara su importador');
        echoTerminal(' ');

        //─── 2 · Casos sintéticos ───────────────────────────────────────────────────────────────────────
        echoTerminal('[2] Reglas (a)-(d)');
        $ctx = new ExportContext([], null);
        $definicion = new ZzRoundtripExport();
        $completa = [new ExportColumn('name', 'Nombre'), (new ExportColumn('age', 'Edad'))->asInteger(), new ExportColumn('note', 'Nota')];

        ZzRoundtripExport::$import = null;
        ZzRoundtripExport::$columns = [new ExportColumn('zz', 'Z')];
        $check($definicion->roundTripProblems($ctx) === [], 'd0 sin importDefinition() no se comprueba nada');

        ZzRoundtripExport::$import = \stdClass::class;
        $p = $definicion->roundTripProblems($ctx);
        $check(count($p) === 1 && str_contains($p[0], 'no extiende'), 'da importDefinition() que no es ImportDefinition', implode(' | ', $p));

        ZzRoundtripExport::$import = ZzRoundtripImport::class;
        ZzRoundtripExport::$columns = $completa;
        $check($definicion->roundTripProblems($ctx) === [], 'd1 todas las columnas cuadran → []');

        ZzRoundtripExport::$columns = array_merge($completa, [new ExportColumn('extra', 'Extra')]);
        $p = $definicion->roundTripProblems($ctx);
        $check(count($p) === 1 && str_contains($p[0], '«extra»') && str_contains($p[0], 'no existe en el importador'), 'db columna exportada que el importador no tiene', implode(' | ', $p));

        ZzRoundtripExport::$columns = [new ExportColumn('name', 'Nombre'), new ExportColumn('note', 'Nota')];
        $p = $definicion->roundTripProblems($ctx);
        $check(count($p) === 1 && str_contains($p[0], '«age»') && str_contains($p[0], 'obligatoria'), 'dc columna obligatoria del importador sin exportar', implode(' | ', $p));

        ZzRoundtripExport::$columns = [new ExportColumn('name', 'Nombre'), (new ExportColumn('age', 'Edad'))->asInteger(), (new ExportColumn('note', 'Nota'))->formula('={age}*2')];
        $p = $definicion->roundTripProblems($ctx);
        $check(count($p) === 1 && str_contains($p[0], '«note»') && str_contains($p[0], 'fórmula'), 'dd columna exportada con fórmula', implode(' | ', $p));

        ZzRoundtripExport::$columns = [new ExportColumn('extra', 'Extra')];
        $check(count($definicion->roundTripProblems($ctx)) === 3, 'de todos los problemas juntos, no solo el primero');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Todo exportador que declara importDefinition() se puede reimportar; y las cuatro reglas de roundTripProblems().')->setEffects([CliActions::EFFECT_NONE])->register();
