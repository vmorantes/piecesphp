<?php

//El motor de importación y exportación del núcleo (ADR 0022): todo o nada por archivo, solo columnas declaradas, sin HTML.

use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\SpreadsheetExportWriter;
use PiecesPHP\Core\DataTransfer\Import\Column;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportPersistException;
use PiecesPHP\Core\DataTransfer\Import\ImportReport;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Import\ParsedRow;
use PiecesPHP\Core\DataTransfer\Source\ArrayRowSource;
use PiecesPHP\Core\DataTransfer\Source\SpreadsheetRowSource;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/data-transfer', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransfer] Motor de importación y exportación del núcleo\e[39m");
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

    //Definición de prueba: cuenta las llamadas a persist() y puede lanzar lo que se le pida.
    $definicion = new class extends ImportDefinition {
        /** @var int */
        public $persistCalls = 0;
        /** @var ParsedRow[] */
        public $persisted = [];
        /** @var \Throwable|null */
        public $throw = null;
        /** @var int */
        public $max = 5000;
        public function key(): string { return 'zz-prueba'; }
        public function title(): string { return 'Prueba'; }
        public function allowedUserTypes(): array { return [0]; }
        public function maxRows(): int { return $this->max; }
        public function columns(): array
        {
            return [
                new Column('email', 'Correo electrónico', true, ['mail'], fn(?string $v, ParsedRow $r) => $v !== null && str_contains($v, '@') ? null : "Correo no válido: {$v}"),
                new Column('name', 'Nombre', true),
                new Column('note', 'Nota'),
            ];
        }
        public function validateAll(array $rows): array
        {
            $errors = [];
            $seen = [];
            foreach ($rows as $row) {
                $email = (string) $row->get('email');
                if (isset($seen[$email])) {
                    $errors[$row->position()] = ["Correo repetido en la fila {$seen[$email]}"];
                } else {
                    $seen[$email] = $row->position();
                }
            }
            return $errors;
        }
        public function persist(array $rows): ?ImportArtifacts
        {
            $this->persistCalls++;
            if ($this->throw !== null) {
                throw $this->throw;
            }
            $this->persisted = $rows;
            return new ImportArtifacts('credenciales.csv', 'text/csv', 'zz-secreto-de-artefacto');
        }
    };
    $correr = function (array $headers, array $rows) use ($definicion): ImportReport {
        $definicion->persistCalls = 0;
        $definicion->persisted = [];
        return (new ImportRunner())->run($definicion, new ArrayRowSource($headers, $rows));
    };
    $temporales = [];
    $temporal = function (string $extension) use (&$temporales): string {
        $ruta = sys_get_temp_dir() . '/zz-data-transfer-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $temporales[] = $ruta;
        return $ruta;
    };

    try {
        //─── 1/9 · Cabeceras ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[1/9] Cabeceras');
        $informe = $correr(['  CORREO   electrónico ', 'nombre', 'MAIL', 'zz_no_declarada'], [['a@x.test', 'Ana', 'b@x.test', 'ignorar']]);
        $fila = $definicion->persisted[0] ?? null;
        $check($informe->persisted() && $fila !== null && $fila->get('email') === 'a@x.test' && $fila->get('name') === 'Ana', 'h1 casa por etiqueta con mayúsculas y espacios, y por key; el primer casamiento de una columna gana');
        $check($fila !== null && array_keys($fila->values()) === ['email', 'name', 'note'], 'h2 la columna no declarada se ignora', $fila !== null ? json_encode(array_keys($fila->values()), JSON_THROW_ON_ERROR) : '');
        $informe = $correr(['mail', 'nombre'], [['c@x.test', 'Carla']]);
        $check($informe->persisted() && ($definicion->persisted[0] ?? null)?->get('email') === 'c@x.test', 'h3 casa por alias');
        $informe = $correr(['correo electrónico'], [['a@x.test']]);
        $check(!$informe->persisted() && $definicion->persistCalls === 0 && count($informe->headerErrors()) === 1 && $informe->totalRows() === 0, 'h4 falta una required en las cabeceras → headerErrors, sin filas y sin persist', json_encode($informe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        echoTerminal(' ');

        //─── 2/9 · Celdas y filas vacías ────────────────────────────────────────────────────────────────
        echoTerminal('[2/9] Celdas y filas vacías');
        $informe = $correr(['email', 'name', 'note'], [['a@x.test', 'Ana', '   '], ['', null, ''], ['b@x.test', 'Beto', null]]);
        $check($informe->persisted() && $informe->totalRows() === 2 && count($definicion->persisted) === 2, 'e1 la fila enteramente vacía se salta y no cuenta', "total {$informe->totalRows()}");
        $check(($definicion->persisted[0] ?? null)?->get('note') === null && ($definicion->persisted[0] ?? null)?->has('note') === false, "e2 la celda vacía o de espacios es null");
        $check(($definicion->persisted[1] ?? null)?->position() === 3, 'e3 la posición es la del archivo: la fila vacía ocupa su número');
        echoTerminal(' ');

        //─── 3/9 · Todo o nada ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[3/9] Todo o nada por archivo');
        $informe = $correr(['email', 'name'], [['a@x.test', 'Ana'], ['no-es-correo', 'Beto'], ['c@x.test', '']]);
        $check(!$informe->persisted() && $definicion->persistCalls === 0, 'a1 una fila inválida entre válidas: persisted false y persist NO se llama', "llamadas {$definicion->persistCalls}");
        $check($informe->validRows() === 1 && $informe->invalidRows() === 2, 'a2 el informe cuenta 1 válida y 2 inválidas');
        $errores = array_map(fn($r) => $r->errors(), $informe->rowResults());
        $check(($errores[2] ?? []) === ['Nombre: obligatorio'], "a3 la required vacía da «Nombre: obligatorio»", json_encode($errores, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $informe = $correr(['email', 'name'], [['a@x.test', 'Ana'], ['b@x.test', 'Beto']]);
        $check($informe->persisted() && $definicion->persistCalls === 1 && count($definicion->persisted) === 2, 'a4 todas válidas: persist una vez con todas las filas');
        echoTerminal(' ');

        //─── 4/9 · validateAll ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[4/9] validateAll');
        $informe = $correr(['email', 'name'], [['a@x.test', 'Ana'], ['a@x.test', 'Otra']]);
        $check(!$informe->persisted() && $definicion->persistCalls === 0 && ($informe->rowResults()[1] ?? null)?->errors() === ['Correo repetido en la fila 1'], 'v1 los errores entre filas se suman y bloquean la persistencia');
        echoTerminal(' ');

        //─── 5/9 · maxRows ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[5/9] maxRows');
        $definicion->max = 1;
        $informe = $correr(['email', 'name'], [['no-es-correo', 'Ana'], ['b@x.test', 'Beto']]);
        $definicion->max = 5000;
        $check(!$informe->persisted() && count($informe->headerErrors()) === 1 && count($informe->rowResults()) === 0 && $definicion->persistCalls === 0, 'r1 más filas que el máximo: headerErrors, sin validar filas y sin persist', json_encode($informe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        echoTerminal(' ');

        //─── 6/9 · Excepciones de persist ───────────────────────────────────────────────────────────────
        echoTerminal('[6/9] Excepciones al persistir');
        $definicion->throw = new ImportPersistException('El correo a@x.test ya existe.');
        $informe = $correr(['email', 'name'], [['a@x.test', 'Ana']]);
        $check(!$informe->persisted() && $informe->headerErrors() === ['El correo a@x.test ya existe.'] && $informe->artifacts() === null, 'p1 ImportPersistException: su mensaje, persisted false');
        $log = basepath('app/logs/error.plain.log');
        $antes = is_file($log) ? count((array) file($log)) : 0;
        $definicion->throw = new \LogicException('zz-detalle-interno-que-no-sale');
        $informe = $correr(['email', 'name'], [['a@x.test', 'Ana']]);
        $definicion->throw = null;
        $despues = is_file($log) ? count((array) file($log)) : 0;
        $check(!$informe->persisted() && count($informe->headerErrors()) === 1 && !str_contains($informe->headerErrors()[0], 'zz-detalle'), 'p2 otra excepción: mensaje genérico, sin el detalle interno', $informe->headerErrors()[0] ?? '');
        $check($despues > $antes, 'p3 y la excepción va al log', "líneas {$antes} → {$despues}");
        echoTerminal(' ');

        //─── 7/9 · JSON y artefactos ────────────────────────────────────────────────────────────────────
        echoTerminal('[7/9] JSON sin HTML y artefactos que no se filtran');
        $informe = $correr(['email', 'name'], [['<b>x</b>', 'Ana']]);
        $json = json_encode($informe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $check(str_contains($json, 'Correo no válido: <b>x</b>') && substr_count($json, '<') === 2 && !str_contains($json, '<br') && !str_contains($json, '</br'), 'j1 el valor sale tal cual en texto y el motor no añade etiquetas', $json);
        $informe = $correr(['email', 'name'], [['a@x.test', 'Ana']]);
        $json = json_encode($informe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $check($informe->artifacts() !== null && !str_contains($json, 'zz-secreto') && !str_contains($json, 'credenciales') && array_keys(json_decode($json, true, 512, JSON_THROW_ON_ERROR)) === ['total', 'valid', 'invalid', 'persisted', 'headerErrors', 'rows'], 'j2 el JSON tiene total, valid, invalid, persisted, headerErrors y rows, y ningún artefacto', $json);
        $artefacto = new ImportArtifacts('c.csv', 'text/csv', 'zz-secreto-de-artefacto');
        $check(!str_contains(serialize($artefacto), 'zz-secreto') && unserialize(serialize($artefacto))->content() === '', 'j3 serialize() no lleva el contenido del artefacto');
        $check(!str_contains(print_r($artefacto, true), 'zz-secreto') && !str_contains(var_export($artefacto->__debugInfo(), true), 'zz-secreto') && $artefacto->content() === 'zz-secreto-de-artefacto', 'j4 print_r y __debugInfo no lo muestran; content() sí');
        echoTerminal(' ');

        //─── 8/9 · Lectura de archivos ──────────────────────────────────────────────────────────────────
        echoTerminal('[8/9] SpreadsheetRowSource');
        $esperado = [['correo', 'nombre'], [['a@x.test', 'Ana'], ['b@x.test', null]]];
        $csvComa = $temporal('csv');
        $csvPuntoYComa = $temporal('csv');
        $escritoComa = file_put_contents($csvComa, "correo,nombre\na@x.test,Ana\nb@x.test,\n");
        $escritoPuntoYComa = file_put_contents($csvPuntoYComa, "correo;nombre\na@x.test;Ana\nb@x.test;\n");
        $check($escritoComa !== false && $escritoPuntoYComa !== false, 'banco: los CSV temporales se escriben');
        $leer = function (string $ruta, string $extension): array {
            $fuente = SpreadsheetRowSource::fromFile($ruta, $extension);
            return [$fuente->headers(), iterator_to_array((function () use ($fuente) { yield from $fuente->rows(); })(), false)];
        };
        $check($leer($csvComa, 'csv') === $esperado, 's1 CSV con coma', json_encode($leer($csvComa, 'csv'), JSON_THROW_ON_ERROR));
        $check($leer($csvPuntoYComa, 'csv') === $esperado, 's2 CSV con punto y coma', json_encode($leer($csvPuntoYComa, 'csv'), JSON_THROW_ON_ERROR));
        $xlsx = $temporal('xlsx');
        $libro = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->fromArray([['correo', 'nombre'], ['a@x.test', 'Ana'], ['b@x.test', null]]);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro))->save($xlsx);
        $check($leer($xlsx, 'xlsx') === $esperado, 's3 XLSX se lee igual', json_encode($leer($xlsx, 'xlsx'), JSON_THROW_ON_ERROR));
        $formatos = $temporal('xlsx');
        $libro = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->fromArray([['fecha', 'fecha_hora', 'numero', 'formula', 'booleano']]);
        $hoja->setCellValue('A2', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('2026-09-17')));
        $hoja->getStyle('A2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $hoja->setCellValue('B2', \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime('2026-09-17 13:45:30')));
        $hoja->getStyle('B2')->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm:ss');
        $hoja->setCellValue('C2', 1234.5);
        $hoja->getStyle('C2')->getNumberFormat()->setFormatCode('#,##0.00');
        $hoja->setCellValue('D2', '=1+1');
        $hoja->setCellValue('E2', true);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($libro))->save($formatos);
        $filaFormatos = $leer($formatos, 'xlsx')[1][0] ?? [];
        $check($filaFormatos === ['2026-09-17', '2026-09-17 13:45:30', '1234.5', '2', '1'], 's4 XLSX: fechas en ISO; número, fórmula y booleano en crudo', json_encode($filaFormatos, JSON_THROW_ON_ERROR));
        foreach (['html', 'xls'] as $extension) {
            try {
                SpreadsheetRowSource::fromFile($csvComa, $extension);
                $check(false, "s5 .{$extension} → InvalidArgumentException", 'no lanzó');
            } catch (\InvalidArgumentException $e) {
                $check(true, "s5 .{$extension} → InvalidArgumentException");
            }
        }
        echoTerminal(' ');

        //─── 9/9 · Exportación ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[9/9] SpreadsheetExportWriter');
        $exportacion = new class extends ExportDefinition {
            public function key(): string { return 'zz-export'; }
            public function title(): string { return 'Prueba'; }
            public function allowedUserTypes(): array { return [0]; }
            public function columns(): array { return [new ExportColumn('formula', 'Fórmula'), new ExportColumn('texto', 'Texto')]; }
            public function rows(): iterable { yield ['formula' => '=1+1', 'texto' => 'normal']; yield ['formula' => "@SUM(A1)", 'texto' => '-2']; }
        };
        $csvSalida = $temporal('csv');
        (new SpreadsheetExportWriter())->toCsv($exportacion, $csvSalida);
        $contenido = (string) file_get_contents($csvSalida);
        $check(str_starts_with($contenido, "\xEF\xBB\xBF") && str_contains($contenido, "'=1+1,normal") && str_contains($contenido, "'@SUM(A1),'-2"), "x1 CSV con BOM y «=1+1» sale como «'=1+1» (también @ y -)", json_encode($contenido, JSON_THROW_ON_ERROR));
        $xlsxSalida = $temporal('xlsx');
        (new SpreadsheetExportWriter())->toXlsx($exportacion, $xlsxSalida);
        $releida = (new \PhpOffice\PhpSpreadsheet\Reader\Xlsx())->load($xlsxSalida)->getActiveSheet()->getCell('A2');
        $check($releida->getDataType() === \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING && $releida->getValue() === '=1+1', 'x2 XLSX: «=1+1» se relee como texto, no como fórmula', $releida->getDataType() . ' ' . var_export($releida->getValue(), true));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($temporales as $ruta) {
            if (is_file($ruta)) {
                //RETORNO-IGNORADO: limpieza del banco de la suite; se comprueba abajo con is_file.
                unlink($ruta);
            }
        }
        $check(count(array_filter($temporales, 'is_file')) === 0, 'z1 limpieza: 0 temporales');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El motor DataTransfer: todo o nada por archivo, solo columnas declaradas, sin HTML y sin fórmulas en la exportación.')->setEffects([CliActions::EFFECT_FILES])->register();
