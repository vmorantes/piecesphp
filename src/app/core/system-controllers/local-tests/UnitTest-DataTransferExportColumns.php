<?php

//Columnas con tipo: cada valor se escribe tipado si encaja y como texto si no; nunca excepción por un dato.
//Escribe temporales propios (prefijo zz-cols-) y los borra en el finally.

use DataImportExportUtility\Controllers\DataTransferController;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\SpreadsheetExportWriter;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-export-columns', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExportColumns] Columnas con tipo, formato y nombre de archivo\e[39m");
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

    if (!class_exists('ZzExportColumnsDefinition', false)) {
        class ZzExportColumnsDefinition extends ExportDefinition
        {
            /** @var array<int,array<string,mixed>> */
            public static $rows = [];
            /** @var ExportColumn[] */
            public static $columns = [];
            /** @var string|null */
            public static $name = null;
            /** @var string[] temporales de exportación vistos mientras se generaba */
            public static $seenTemp = [];
            public function key(): string { return 'zz-columnas'; }
            public function title(): string { return 'Prueba'; }
            public function allowedUserTypes(): array { return [0]; }
            public function columns(ExportContext $context): array { return self::$columns; }
            public function rows(ExportContext $context): iterable
            {
                self::$seenTemp = glob(sys_get_temp_dir() . '/' . DataTransferController::EXPORT_TEMP_PREFIX . '*') ?: [];
                return self::$rows;
            }
            public function fileName(ExportContext $context): string
            {
                return self::$name ?? parent::fileName($context);
            }
        }
    }

    $dir = sys_get_temp_dir() . '/zz-cols-' . bin2hex(random_bytes(4));
    if (!mkdir($dir)) {
        throw new \RuntimeException("No se puede crear «{$dir}».");
    }
    $context = new ExportContext([], null);
    $definition = new ZzExportColumnsDefinition();
    $writer = new SpreadsheetExportWriter();
    $xlsx = function () use ($writer, $definition, $context, $dir) {
        $path = "{$dir}/libro.xlsx";
        $writer->toXlsx($definition, $context, $path);
        return IOFactory::load($path)->getActiveSheet();
    };
    $csv = function () use ($writer, $definition, $context, $dir): array {
        $path = "{$dir}/libro.csv";
        $writer->toCsv($definition, $context, $path);
        $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
        return array_map(fn($l) => str_getcsv(ltrim($l, "\xEF\xBB\xBF"), ',', '"', ''), $lines);
    };

    try {
        //─── a · Tipos en XLSX ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Cada tipo en XLSX: tipo de celda y formato');
        ZzExportColumnsDefinition::$columns = [
            new ExportColumn('t', 'Texto'),
            (new ExportColumn('i', 'Entero'))->asInteger(),
            (new ExportColumn('d', 'Decimal'))->asDecimal(3),
            (new ExportColumn('m', 'Dinero'))->asMoney('CO"P'),
            (new ExportColumn('p', 'Porcentaje'))->asPercent(1),
            (new ExportColumn('f', 'Fecha'))->asDate(),
            (new ExportColumn('h', 'Fecha y hora'))->asDateTime(),
            (new ExportColumn('b', 'Booleano'))->asBoolean(),
            (new ExportColumn('x', 'Con formato'))->asDecimal()->format('0.0000')->width(30)->align('right'),
        ];
        ZzExportColumnsDefinition::$rows = [
            ['t' => '=1+1', 'i' => 1234, 'd' => '1234.5', 'm' => 99.9, 'p' => 0.25, 'f' => '2026-09-18', 'h' => new \DateTimeImmutable('2026-09-18 10:30:00'), 'b' => true, 'x' => 1],
        ];
        $hoja = $xlsx();
        //Por referencia: la hoja se recarga en cada bloque.
        $tipo = function (string $c) use (&$hoja): string {
            return $hoja->getCell($c)->getDataType();
        };
        $formato = function (string $c) use (&$hoja): string {
            return (string) $hoja->getStyle($c)->getNumberFormat()->getFormatCode();
        };
        $check($tipo('A2') === DataType::TYPE_STRING && $hoja->getCell('A2')->getValue() === '=1+1', 'a1 texto como cadena, sin evaluar', $tipo('A2'));
        $check($tipo('B2') === DataType::TYPE_NUMERIC && $formato('B2') === '#,##0', 'a2 entero numérico con #,##0', $formato('B2'));
        $check($tipo('C2') === DataType::TYPE_NUMERIC && $formato('C2') === '#,##0.000', 'a3 decimal numérico con #,##0.000', $formato('C2'));
        $check($tipo('D2') === DataType::TYPE_NUMERIC && $formato('D2') === '#,##0.00 "CO""P"', 'a4 dinero con la moneda entre comillas y la comilla doblada', $formato('D2'));
        $check($tipo('E2') === DataType::TYPE_NUMERIC && $hoja->getCell('E2')->getValue() == 0.25 && $formato('E2') === '0.0%', 'a5 porcentaje: 0.25 con 0.0%', $formato('E2'));
        $check($tipo('F2') === DataType::TYPE_NUMERIC && (int) $hoja->getCell('F2')->getValue() === 46283 && $formato('F2') === 'dd/mm/yyyy', 'a6 fecha como serie de Excel con dd/mm/yyyy', (string) $hoja->getCell('F2')->getValue());
        $check($tipo('G2') === DataType::TYPE_NUMERIC && abs((float) $hoja->getCell('G2')->getValue() - 46283.4375) < 1e-6 && $formato('G2') === 'dd/mm/yyyy hh:mm', 'a7 fecha y hora con su fracción del día', (string) $hoja->getCell('G2')->getValue());
        $check($tipo('H2') === DataType::TYPE_STRING && $hoja->getCell('H2')->getValue() === 'Sí', 'a8 booleano con su etiqueta');
        $check($formato('I2') === '0.0000' && abs($hoja->getColumnDimension('I')->getWidth() - 30) < 0.01 && $hoja->getStyle('I2')->getAlignment()->getHorizontal() === 'right', 'a9 format() sustituye, width() y align() se aplican');
        //El autoajuste se calcula al guardar y queda como bestFit; al releer no vuelve como autoSize.
        $zip = new \ZipArchive();
        $zip->open("{$dir}/libro.xlsx");
        $xml = (string) $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        $check(preg_match('/<col min="1" max="1"[^>]*bestFit="true"/', $xml) === 1 && preg_match('/<col min="9" max="9" width="30"/', $xml) === 1 && preg_match('/<col min="9" max="9"[^>]*bestFit/', $xml) === 0, 'a10 sin width(), ancho automático; con width(), fijo');
        echoTerminal(' ');

        //─── b/c · Valores raros y null ─────────────────────────────────────────────────────────────────
        echoTerminal('[b-c] Valores que no encajan y null');
        ZzExportColumnsDefinition::$rows = [
            ['t' => null, 'i' => 'abc', 'd' => [1], 'm' => null, 'p' => 'n/a', 'f' => '2026-02-30', 'h' => 'ayer', 'b' => 'x', 'x' => null],
        ];
        $hoja = $xlsx();
        $check($tipo('B2') === DataType::TYPE_STRING && $hoja->getCell('B2')->getValue() === 'abc', 'b1 «abc» en entero → texto tal cual', $tipo('B2'));
        $check($tipo('F2') === DataType::TYPE_STRING && $hoja->getCell('F2')->getValue() === '2026-02-30', 'b2 «2026-02-30» en fecha → texto tal cual');
        $check($tipo('H2') === DataType::TYPE_STRING && $hoja->getCell('H2')->getValue() === 'x', 'b3 «x» en booleano → texto tal cual');
        $check($tipo('E2') === DataType::TYPE_STRING && $hoja->getCell('G2')->getValue() === 'ayer', 'b4 porcentaje y fecha-hora no válidos → texto');
        $check($hoja->getCell('C2')->getValue() === null || $hoja->getCell('C2')->getValue() === '', 'b5 un array → vacío, sin excepción');
        $check($hoja->getCell('A2')->getValue() === null && $hoja->getCell('D2')->getValue() === null && $hoja->getCell('I2')->getValue() === null, 'c1 null → celda vacía en texto, dinero y decimal');
        $filas = $csv();
        $check(($filas[1] ?? []) === ['', 'abc', '', '', 'n/a', '2026-02-30', 'ayer', 'x', ''], 'c2 en CSV igual: raros como texto y null vacío', (string) json_encode($filas[1] ?? null, JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //─── d · CSV ────────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] CSV');
        ZzExportColumnsDefinition::$rows = [
            ['t' => '=1+1', 'i' => -5, 'd' => 1234.5, 'm' => '1000', 'p' => 0.25, 'f' => '2026-09-18', 'h' => '2026-09-18 10:30:00', 'b' => 0, 'x' => '-3'],
        ];
        $filas = $csv();
        $check(($filas[1] ?? []) === ["'=1+1", '-5', '1234.5', '1000', '0.25', '2026-09-18', '2026-09-18 10:30:00', 'No', '-3'], 'd1 números en crudo con punto, fechas ISO, texto neutralizado, -5 sin neutralizar', (string) json_encode($filas[1] ?? null, JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //─── e · transform ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[e] transform');
        $visto = null;
        $columna = (new ExportColumn('total', 'Total'))->asInteger()->transform(function (array $row, ExportContext $c) use (&$visto) {
            $visto = [$row, $c];
            return $row['a'] * 2;
        });
        $valor = $columna->value(['a' => 21], $context);
        $check($valor === 42 && is_array($visto) && $visto[0] === ['a' => 21] && $visto[1] === $context, 'e1 recibe la fila y el contexto');
        $check((new ExportColumn('a', 'A'))->value(['a' => 'z'], $context) === 'z' && (new ExportColumn('q', 'Q'))->value([], $context) === null, 'e2 sin transform, la key de la fila o null');
        echoTerminal(' ');

        //─── f · Rechazos ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[f] Configuración inválida');
        $lanza = function (callable $fn): bool {
            try {
                $fn();
                return false;
            } catch (\InvalidArgumentException $e) {
                return true;
            }
        };
        $check($lanza(fn() => (new ExportColumn('a', 'A'))->asDecimal(11)) && $lanza(fn() => (new ExportColumn('a', 'A'))->asPercent(-1)) && $lanza(fn() => (new ExportColumn('a', 'A'))->asMoney('COP', 11)), 'f1 decimales fuera de 0..10 → InvalidArgumentException');
        $check($lanza(fn() => (new ExportColumn('a', 'A'))->align('diagonal')), 'f2 align(«diagonal») → InvalidArgumentException');
        $simple = new ExportColumn('a', 'A');
        $check($simple->type() === ExportColumn::TYPE_TEXT && $simple->numberFormat() === null && $simple->widthValue() === null && $simple->alignment() === null, 'f3 new ExportColumn($key, $label) sigue siendo texto sin formato');
        echoTerminal(' ');

        //─── g · Nombre de archivo ──────────────────────────────────────────────────────────────────────
        echoTerminal('[g] Nombre de archivo');
        $nombre = DataTransferController::exportFileName('Histórico de pagos / 2026', 'defecto');
        $cabecera = DataTransferController::contentDisposition($nombre, 'xlsx');
        $check($nombre === 'Histórico de pagos  2026', 'g1 se quita «/»', $nombre);
        $check($cabecera === "attachment; filename=\"Hist_rico de pagos  2026.xlsx\"; filename*=UTF-8''Hist%C3%B3rico%20de%20pagos%20%202026.xlsx", 'g2 filename ASCII con «_» y filename* con percent-encoding', $cabecera);
        $check(DataTransferController::exportFileName(" \t/:*?\"<>| ", 'defecto') === 'defecto' && mb_strlen(DataTransferController::exportFileName(str_repeat('á', 200), 'd')) === 150, 'g3 vacío → por defecto; máximo 150 caracteres');
        echoTerminal(' ');

        //─── h · exportAction ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[h] exportAction responde con stream');
        ZzExportColumnsDefinition::$name = 'Informe ñ';
        $antes = glob(sys_get_temp_dir() . '/' . DataTransferController::EXPORT_TEMP_PREFIX . '*') ?: [];
        $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz?format=csv'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $respuesta = (new DataTransferController())->exportAction($peticion, new ResponseRoute(), ZzExportColumnsDefinition::class);
        $propios = array_values(array_diff(ZzExportColumnsDefinition::$seenTemp, $antes));
        $cuerpo = (string) $respuesta->getBody();
        $check(count($propios) === 1 && !file_exists($propios[0]), 'h1 el temporal propio existía al generar y ya no está en disco', (string) json_encode($propios));
        $check($respuesta->getBody()->getMetadata('wrapper_type') === 'plainfile' && $respuesta->getHeaderLine('Content-Length') === (string) strlen($cuerpo) && strlen($cuerpo) > 0, 'h2 cuerpo stream de archivo y Content-Length exacto', $respuesta->getHeaderLine('Content-Length') . ' / ' . strlen($cuerpo));
        $check(str_contains($respuesta->getHeaderLine('Content-Disposition'), "filename*=UTF-8''Informe%20%C3%B1.csv") && str_contains($cuerpo, "'=1+1"), 'h3 el nombre de fileName() llega a la cabecera y el contenido es el CSV');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        ZzExportColumnsDefinition::$name = null;
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

})->setDescription('Columnas de exportación con tipo, formato, ancho y transformación; nombre de archivo y descarga por stream.')->setEffects([CliActions::EFFECT_FILES])->register();
