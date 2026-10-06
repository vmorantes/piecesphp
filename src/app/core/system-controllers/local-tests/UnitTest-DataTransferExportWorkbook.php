<?php

//El libro XLSX de informe: hojas, títulos, filtros aplicados, totales, fórmulas, imágenes, estilo y vía de escape.
//Un dato nunca llega a fórmula. Escribe temporales propios (prefijo zz-libro-) y los borra en el finally; solo lee la base.

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\Definitions\UsersExportDefinition;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\ExportParameter;
use PiecesPHP\Core\DataTransfer\Export\ExportSheet;
use PiecesPHP\Core\DataTransfer\Export\ExportStyle;
use PiecesPHP\Core\DataTransfer\Export\SpreadsheetExportWriter;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-export-workbook', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExportWorkbook] Libro de informe XLSX\e[39m");
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
    $lanza = function (callable $fn): bool {
        try {
            $fn();
            return false;
        } catch (\InvalidArgumentException $e) {
            return true;
        }
    };

    if (!class_exists('ZzExportWorkbookDefinition', false)) {
        class ZzExportWorkbookDefinition extends ExportDefinition
        {
            /** @var ExportColumn[] */
            public static $columns = [];
            /** @var array<int,array<string,mixed>> */
            public static $rows = [];
            /** @var (callable(ExportContext,ExportDefinition):array)|null */
            public static $sheets = null;
            /** @var ExportStyle|null */
            public static $style = null;
            /** @var int */
            public static $afterBuildCalls = 0;
            /** @var Spreadsheet|null */
            public static $afterBuildBook = null;
            public function key(): string { return 'zz-libro'; }
            public function title(): string { return 'Principal'; }
            public function allowedUserTypes(): array { return [0]; }
            public function parameters(): array
            {
                return [
                    ExportParameter::choice('state', 'Estado', ['on' => 'Activo', 'off' => 'Inactivo']),
                    ExportParameter::dateRange('created', 'Creado'),
                    ExportParameter::multiChoice('types', 'Tipos', [1 => 'Uno', 2 => 'Dos']),
                    ExportParameter::boolean('vip', 'VIP'),
                    ExportParameter::text('name', 'Nombre'),
                ];
            }
            public function columns(ExportContext $context): array { return self::$columns; }
            public function rows(ExportContext $context): iterable { return self::$rows; }
            public function sheets(ExportContext $context): array
            {
                return self::$sheets !== null ? (self::$sheets)($context, $this) : parent::sheets($context);
            }
            public function style(ExportContext $context): ExportStyle
            {
                return self::$style ?? parent::style($context);
            }
            public function afterBuild(Spreadsheet $book, ExportContext $context): void
            {
                self::$afterBuildCalls++;
                self::$afterBuildBook = $book;
            }
        }
    }

    $dir = sys_get_temp_dir() . '/zz-libro-' . bin2hex(random_bytes(4));
    if (!mkdir($dir)) {
        throw new \RuntimeException("No se puede crear «{$dir}».");
    }
    $definition = new ZzExportWorkbookDefinition();
    $writer = new SpreadsheetExportWriter();
    $vacio = new ExportContext([], null);
    $libro = function (?ExportContext $context = null) use ($writer, $definition, $vacio, $dir): Spreadsheet {
        $path = "{$dir}/libro.xlsx";
        $writer->toXlsx($definition, $context ?? $vacio, $path);
        return IOFactory::load($path);
    };
    $csv = function () use ($writer, $definition, $vacio, $dir): array {
        $path = "{$dir}/libro.csv";
        $writer->toCsv($definition, $vacio, $path);
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        return array_map(fn($l) => str_getcsv(ltrim($l, "\xEF\xBB\xBF"), ',', '"', ''), $lines);
    };
    $reset = function (): void {
        ZzExportWorkbookDefinition::$columns = [new ExportColumn('a', 'A')];
        ZzExportWorkbookDefinition::$rows = [['a' => 'x']];
        ZzExportWorkbookDefinition::$sheets = null;
        ZzExportWorkbookDefinition::$style = null;
        ZzExportWorkbookDefinition::$afterBuildCalls = 0;
        ZzExportWorkbookDefinition::$afterBuildBook = null;
    };
    $texto = fn(Worksheet $s, string $c): string => (string) $s->getCell($c)->getValue();

    try {
        //─── a · Hojas ──────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Hojas: orden, activa y títulos');
        $reset();
        $largo = str_repeat('x', 40);
        ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [
            $d->mainSheet($c),
            new ExportSheet('Otra/hoja: [1]', [new ExportColumn('b', 'B')], [['b' => 'y']]),
            new ExportSheet($largo, [new ExportColumn('b', 'B')], []),
            new ExportSheet($largo, [new ExportColumn('b', 'B')], []),
            new ExportSheet('principal', [new ExportColumn('b', 'B')], []),
            new ExportSheet(' ?*/ ', [new ExportColumn('b', 'B')], []),
        ];
        $book = $libro();
        $nombres = $book->getSheetNames();
        $check($nombres[0] === 'Principal' && $book->getActiveSheetIndex() === 0 && $texto($book->getSheet(1), 'A2') === 'y', 'a1 orden de sheets(), la primera activa', (string) json_encode($nombres, JSON_UNESCAPED_UNICODE));
        $check($nombres[1] === 'Otrahoja 1', 'a2 se quitan : / [ ]', $nombres[1]);
        $check($nombres[2] === str_repeat('x', 31) && $nombres[3] === str_repeat('x', 27) . ' (2)', 'a3 recorte a 31 y repetido con « (2)» dentro de 31', $nombres[3]);
        $check($nombres[4] === 'principal (2)', 'a4 repetido sin distinguir mayúsculas', $nombres[4]);
        $check($nombres[5] === 'Hoja 6', 'a5 vacío tras limpiar → «Hoja N»', $nombres[5]);
        ZzExportWorkbookDefinition::$sheets = fn() => [];
        $check($lanza(fn() => $libro()), 'a6 sheets() vacío → InvalidArgumentException');
        echoTerminal(' ');

        //─── b · Encima de la tabla ─────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Título, subtítulos, filtros aplicados y fecha');
        $reset();
        ZzExportWorkbookDefinition::$columns = [new ExportColumn('a', 'A'), new ExportColumn('b', 'B'), new ExportColumn('c', 'C')];
        ZzExportWorkbookDefinition::$rows = [['a' => 1, 'b' => 2, 'c' => 3]];
        $contexto = $definition->buildContext(['state' => 'off', 'created_from' => '2026-01-05', 'types' => ['2', '1'], 'vip' => 'yes', 'name' => ''], null);
        ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [
            $d->mainSheet($c)->heading('Informe')->subheading('Uno')->subheading('Dos')->appliedFilters($c)->generatedAt(),
        ];
        $hoja = $libro($contexto)->getActiveSheet();
        $check($texto($hoja, 'A1') === 'Informe' && $hoja->getStyle('A1')->getFont()->getBold() && $texto($hoja, 'A2') === 'Uno' && $texto($hoja, 'A3') === 'Dos', 'b1 título en negrita y los subtítulos en orden');
        $filtros = $texto($hoja, 'A4');
        $check($filtros === 'Filtros: Estado: Inactivo; Creado: desde el 05/01/2026; Tipos: Dos, Uno; VIP: Sí', 'b2 filtros con etiqueta de choice, rango de un extremo, multiChoice y booleano; el vacío se omite', $filtros);
        $check(preg_match('/^Generado el \d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', $texto($hoja, 'A5')) === 1, 'b3 fila de fecha', $texto($hoja, 'A5'));
        $combinadas = array_values($hoja->getMergeCells());
        $check($combinadas === ['A1:C1', 'A2:C2', 'A3:C3', 'A4:C4', 'A5:C5'], 'b4 cada línea combinada a lo ancho de la tabla', (string) json_encode($combinadas));
        $check($texto($hoja, 'A6') === '' && $texto($hoja, 'A7') === 'A' && (int) $hoja->getCell('A8')->getValue() === 1, 'b5 fila vacía, encabezado y datos');
        $ambos = $definition->buildContext(['created_from' => '2026-03-01', 'created_to' => '2026-01-10'], null);
        $check((string) (new ExportSheet('x', [], []))->appliedFilters($ambos)->topLines()[0]['text'] === 'Filtros: Creado: del 10/01/2026 al 01/03/2026', 'b6 rango completo «del … al …»');
        $check((new ExportSheet('x', [], []))->appliedFilters($vacio)->topLines() === [] && (new ExportSheet('x', [], []))->appliedFilters($definition->buildContext([], null))->topLines() === [], 'b7 sin parámetros o sin valores, no hay fila de filtros');
        $check($lanza(fn() => $contexto->parameter('nada')) && $contexto->parameter('state')->label() === 'Estado' && array_keys($contexto->parameters()) === ['state', 'created', 'types', 'vip', 'name'], 'b8 parameter() de una key no declarada lanza; parameters() en orden');
        echoTerminal(' ');

        //─── c · Totales ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Totales');
        $reset();
        ZzExportWorkbookDefinition::$columns = [new ExportColumn('n', 'Nombre'), (new ExportColumn('q', 'Cantidad'))->asInteger(), (new ExportColumn('p', 'Precio'))->asMoney('COP')];
        ZzExportWorkbookDefinition::$rows = [['n' => 'a', 'q' => 2, 'p' => 10], ['n' => 'b', 'q' => 3, 'p' => 20.5], ['n' => 'c', 'q' => 5, 'p' => 'n/a']];
        ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [
            $d->mainSheet($c)->heading('T')->total('q', 'SUM')->total('p', 'AVERAGE')->total('n', 'COUNT')->totalsLabel('Totales'),
        ];
        $hoja = $libro()->getActiveSheet();
        $check($hoja->getCell('B7')->getValue() === '=SUM(B4:B6)' && $hoja->getCell('C7')->getValue() === '=AVERAGE(C4:C6)' && $hoja->getCell('A7')->getValue() === '=COUNTA(A4:A6)', 'c1 fórmulas exactas sobre el rango de datos', $texto($hoja, 'B7') . ' ' . $texto($hoja, 'C7') . ' ' . $texto($hoja, 'A7'));
        $check((int) $hoja->getCell('B7')->getCalculatedValue() === 10 && abs((float) $hoja->getCell('C7')->getCalculatedValue() - 15.25) < 1e-9 && (int) $hoja->getCell('A7')->getCalculatedValue() === 3, 'c2 valores calculados: 10, 15.25 (el texto no cuenta) y 3');
        $check($hoja->getStyle('C7')->getNumberFormat()->getFormatCode() === '#,##0.00 "COP"' && $hoja->getStyle('A7')->getNumberFormat()->getFormatCode() === '#,##0', 'c3 el total lleva el formato de su columna; COUNT, entero');
        ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [$d->mainSheet($c)->total('q', 'SUM')];
        $hoja = $libro()->getActiveSheet();
        $check($texto($hoja, 'A5') === 'Total', 'c4 etiqueta por defecto en la primera columna sin total', $texto($hoja, 'A5'));
        $check($lanza(function () use ($libro) {
            ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [$d->mainSheet($c)->total('n', 'SUM')];
            $libro();
        }) && $lanza(function () use ($libro) {
            ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [$d->mainSheet($c)->total('zz', 'COUNT')];
            $libro();
        }) && $lanza(function () use ($libro) {
            ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [$d->mainSheet($c)->total('q', 'MEDIAN')];
            $libro();
        }), 'c5 SUM sobre texto, columna inexistente o agregado desconocido → InvalidArgumentException');
        ZzExportWorkbookDefinition::$rows = [];
        ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [$d->mainSheet($c)->total('q', 'SUM')->total('p', 'AVERAGE')];
        $hoja = $libro()->getActiveSheet();
        $check($hoja->getCell('B3')->getValue() === '=SUM(B2:B2)' && (int) $hoja->getCell('B3')->getCalculatedValue() === 0 && $hoja->getCell('C3')->getCalculatedValue() === '#DIV/0!', 'c6 sin filas: una fila vacía, SUM 0 y AVERAGE #DIV/0!', var_export($hoja->getCell('C3')->getCalculatedValue(), true));
        echoTerminal(' ');

        //─── d · Fórmula por columna ────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Fórmula por columna');
        $reset();
        ZzExportWorkbookDefinition::$columns = [
            (new ExportColumn('amount', 'Monto'))->asDecimal(),
            (new ExportColumn('rate', 'Tasa'))->asPercent(),
            (new ExportColumn('total', 'Total'))->asDecimal()->formula('={amount}*{rate}')->transform(fn() => 'IGNORADO'),
        ];
        ZzExportWorkbookDefinition::$rows = [['amount' => 100, 'rate' => 0.5, 'total' => '=HYPERLINK("x")'], ['amount' => 10, 'rate' => 0.25, 'total' => 999]];
        $hoja = $libro()->getActiveSheet();
        $check($hoja->getCell('C2')->getValue() === '=A2*B2' && $hoja->getCell('C3')->getValue() === '=A3*B3' && $hoja->getCell('C2')->getDataType() === DataType::TYPE_FORMULA, 'd1 referencias de la misma fila en dos filas', $texto($hoja, 'C2') . ' ' . $texto($hoja, 'C3'));
        $check(abs((float) $hoja->getCell('C3')->getCalculatedValue() - 2.5) < 1e-9 && $hoja->getStyle('C3')->getNumberFormat()->getFormatCode() === '#,##0.00', 'd2 calcula y lleva el formato del tipo');
        $check($lanza(fn() => (new ExportColumn('t', 'T'))->formula('{a}*2')), 'd3 plantilla sin «=» → InvalidArgumentException');
        ZzExportWorkbookDefinition::$columns[] = (new ExportColumn('bad', 'Mala'))->formula('={amount}+{nada}');
        $check($lanza(fn() => $libro()), 'd4 {clave} que no es columna de la hoja → InvalidArgumentException');
        array_pop(ZzExportWorkbookDefinition::$columns);
        $filas = $csv();
        $check(($filas[1] ?? []) === ['100', '0.5', ''] && ($filas[2] ?? []) === ['10', '0.25', ''], 'd5 en CSV la columna con fórmula va vacía', (string) json_encode($filas));
        echoTerminal(' ');

        //─── e · Un dato nunca es fórmula ───────────────────────────────────────────────────────────────
        echoTerminal('[e] Un dato con forma de fórmula');
        $reset();
        ZzExportWorkbookDefinition::$rows = [['a' => '=HYPERLINK("x")']];
        $hoja = $libro()->getActiveSheet();
        $check($hoja->getCell('A2')->getDataType() === DataType::TYPE_STRING && $texto($hoja, 'A2') === '=HYPERLINK("x")', 'e1 XLSX: cadena, no fórmula');
        $check(($csv()[1][0] ?? '') === "'=HYPERLINK(\"x\")", 'e2 CSV: neutralizado');
        echoTerminal(' ');

        //─── f · Imágenes ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[f] Imágenes');
        $reset();
        $png = basepath('statics/images/default-avatar.png');
        ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [$d->mainSheet($c)->image($png, 'B2', 40)];
        $dibujos = $libro()->getActiveSheet()->getDrawingCollection();
        $primero = $dibujos[0] ?? null;
        $check(count($dibujos) === 1 && $primero !== null && $primero->getCoordinates() === 'B2' && $primero->getHeight() === 40, 'f1 imagen del proyecto anclada en B2 con 40 px de alto');
        $fuera = basepath('statics') . '/' . str_repeat('../', 12) . 'usr/share/kdocker/question.png';
        $hoja = new ExportSheet('x', [], []);
        $check(is_file($fuera) && $lanza(fn() => $hoja->image($fuera)), 'f2 ruta con ../ que sale del proyecto → excepción', $fuera);
        $check($lanza(fn() => $hoja->image('/etc/hostname')), 'f3 /etc/hostname → excepción');
        $check($lanza(fn() => $hoja->image(basepath('statics/images/loader-animation.gif'))), 'f4 .gif → excepción');
        $check($lanza(fn() => $hoja->image(basepath('statics/images/zz-no-existe.png'))) && $lanza(fn() => $hoja->image($png, 'b2')), 'f5 inexistente o celda inválida → excepción');
        $check($lanza(fn() => $hoja->merge('A1-B2')) && $hoja->merge('A1:B2')->merges() === ['A1:B2'], 'f6 merge() valida el rango');
        echoTerminal(' ');

        //─── g · Estilo ─────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[g] Estilo');
        $reset();
        ZzExportWorkbookDefinition::$columns = [new ExportColumn('a', 'A'), new ExportColumn('b', 'B')];
        ZzExportWorkbookDefinition::$rows = [['a' => str_repeat('palabra ', 20), 'b' => 'x'], ['a' => 'y', 'b' => 'z']];
        $hoja = $libro()->getActiveSheet();
        $cabecera = $hoja->getStyle('A1');
        $check($cabecera->getFill()->getFillType() === Fill::FILL_SOLID && $cabecera->getFill()->getStartColor()->getRGB() === '263238' && $cabecera->getFont()->getBold() && $cabecera->getFont()->getColor()->getRGB() === 'FFFFFF', 'g1 report(): encabezado blanco en negrita sobre 263238');
        $check($hoja->getFreezePane() === 'A2' && $hoja->getAutoFilter()->getRange() === 'A1:B3' && $hoja->getStyle('B3')->getBorders()->getBottom()->getColor()->getRGB() === 'D7DEE2' && $hoja->getStyle('A2')->getAlignment()->getWrapText(), 'g2 panel congelado, autofiltro, bordes y ajuste de texto', (string) $hoja->getFreezePane() . ' ' . $hoja->getAutoFilter()->getRange());
        $check(abs($hoja->getColumnDimension('A')->getWidth() - 40) < 0.01, 'g3 ancho automático con tope 40', (string) $hoja->getColumnDimension('A')->getWidth());
        ZzExportWorkbookDefinition::$style = ExportStyle::plain();
        $hoja = $libro()->getActiveSheet();
        $check($hoja->getStyle('A1')->getFill()->getFillType() === Fill::FILL_NONE && !$hoja->getStyle('A1')->getFont()->getBold() && $hoja->getFreezePane() === null && $hoja->getAutoFilter()->getRange() === '' && $hoja->getColumnDimension('A')->getWidth() > 40, 'g4 plain(): nada de eso y ancho sin tope');
        ZzExportWorkbookDefinition::$style = null;
        ZzExportWorkbookDefinition::$sheets = fn(ExportContext $c, ExportDefinition $d) => [$d->mainSheet($c)->style(ExportStyle::plain()->headerColors('000000', 'FFEE00'))];
        $check($libro()->getActiveSheet()->getStyle('A1')->getFill()->getStartColor()->getRGB() === 'FFEE00', 'g5 el estilo de la hoja sustituye al de la definición');
        $check($lanza(fn() => ExportStyle::report()->headerColors('#FFFFFF', '000000')) && $lanza(fn() => ExportStyle::report()->borderColor('red')), 'g6 color inválido → InvalidArgumentException');
        echoTerminal(' ');

        //─── h · afterBuild ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[h] afterBuild');
        $reset();
        $libro();
        $check(ZzExportWorkbookDefinition::$afterBuildCalls === 1 && ZzExportWorkbookDefinition::$afterBuildBook instanceof Spreadsheet && ZzExportWorkbookDefinition::$afterBuildBook->getSheetCount() === 1, 'h1 se llama una vez y ve el libro');
        echoTerminal(' ');

        //─── i · Exportador de usuarios ─────────────────────────────────────────────────────────────────
        echoTerminal('[i] Exportador de usuarios: estilo de informe');
        $previoUsuario = get_config('current_user');
        $previoGuardado = get_config('pcsphp_current_user_stored');
        try {
            $root = UsersModel::model();
            $root->resetAll();
            $root->select()->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
            $filasRoot = (array) $root->result();
            set_config('current_user', (object) ['id' => isset($filasRoot[0]->id) ? (int) $filasRoot[0]->id : 0]);
            set_config('pcsphp_current_user_stored', null);
            $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz?format=xlsx'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            $respuesta = (new DataTransferController())->exportAction($peticion, new ResponseRoute(), UsersExportDefinition::class);
            $path = "{$dir}/usuarios.xlsx";
            if (file_put_contents($path, (string) $respuesta->getBody()) === false) {
                throw new \RuntimeException("No se puede escribir «{$path}».");
            }
            $hoja = IOFactory::load($path)->getActiveSheet();
            $check($hoja->getStyle('A1')->getFill()->getStartColor()->getRGB() === '263238' && $hoja->getFreezePane() === 'A2' && $hoja->getAutoFilter()->getRange() !== '' && $texto($hoja, 'A1') !== '', 'i1 el XLSX de usuarios hereda el estilo de informe, sin título encima');
        } finally {
            set_config('current_user', $previoUsuario);
            set_config('pcsphp_current_user_stored', $previoGuardado);
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        foreach (glob("{$dir}/*") ?: [] as $file) {
            //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
            @unlink($file);
        }
        //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
        @rmdir($dir);
        $check(!is_dir($dir), 'z1 limpieza del directorio temporal propio');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El libro XLSX de informe: hojas, títulos, filtros, totales, fórmulas, imágenes, estilo y afterBuild; un dato nunca es fórmula.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
