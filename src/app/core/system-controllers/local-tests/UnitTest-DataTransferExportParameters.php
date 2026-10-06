<?php

//Los filtros de exportación: cada tipo valida o falla con su mensaje, nunca con un valor silencioso.

use DataImportExportUtility\Controllers\DataTransferController;
use PiecesPHP\Core\DataTransfer\Export\DateRange;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\ExportParameter;
use PiecesPHP\Core\DataTransfer\Export\ExportParameterException;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-export-parameters', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExportParameters] Filtros de exportación\e[39m");
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

    //El valor o el mensaje de error, para comprobar las dos cosas en una línea.
    $parse = function (ExportParameter $parameter, array $query): array {
        try {
            return ['ok', $parameter->parse($query)];
        } catch (ExportParameterException $e) {
            return ['error', $e->getMessage()];
        }
    };
    $fecha = fn(?\DateTimeImmutable $d) => $d?->format('Y-m-d H:i:s');

    if (!class_exists('ZzExportParametersDefinition', false)) {
        class ZzExportParametersDefinition extends ExportDefinition
        {
            public function key(): string { return 'zz-parametros'; }
            public function title(): string { return 'Prueba'; }
            public function allowedUserTypes(): array { return [0]; }
            public function parameters(): array
            {
                return [
                    ExportParameter::integer('age', 'Edad', 0, 120)->required(),
                    ExportParameter::choice('state', 'Estado', ['on' => 'Activo', 'off' => 'Inactivo'])->required(),
                    ExportParameter::dateRange('created', 'Creado'),
                ];
            }
            public function columns(ExportContext $context): array { return [new ExportColumn('a', 'A')]; }
            public function rows(ExportContext $context): iterable { return [['a' => 'x']]; }
        }
    }

    try {
        //─── a · Tipos ──────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Cada tipo: válido, inválido, ausente, por defecto y obligatorio vacío');
        $texto = ExportParameter::text('name', 'Nombre');
        $check($parse($texto, ['name' => '  Ana  ']) === ['ok', 'Ana'], 'a1 text recorta');
        $check($parse($texto, ['name' => str_repeat('x', 501)])[0] === 'error', 'a2 text de 501 caracteres → error');
        $check($parse($texto, []) === ['ok', null], 'a3 text ausente → null');
        $check($parse(ExportParameter::text('name', 'Nombre')->defaultValue('zz'), ['name' => '   ']) === ['ok', 'zz'], 'a4 text vacío con default → default');
        $check($parse(ExportParameter::text('name', 'Nombre')->required(), ['name' => '']) === ['error', 'Nombre: obligatorio'], 'a5 text obligatorio vacío → «Nombre: obligatorio»');

        $entero = ExportParameter::integer('age', 'Edad', 0, 120);
        $check($parse($entero, ['age' => '-0']) === ['ok', 0] && $parse($entero, ['age' => '42']) === ['ok', 42], 'a6 integer válido');
        $check($parse($entero, ['age' => '4.5'])[0] === 'error' && $parse($entero, ['age' => '121'])[0] === 'error' && $parse($entero, ['age' => '-1'])[0] === 'error', 'a7 integer no entero o fuera de min/max → error');

        $booleano = ExportParameter::boolean('active', 'Activo');
        $check($parse($booleano, ['active' => 'yes']) === ['ok', true] && $parse($booleano, ['active' => '0']) === ['ok', false] && $parse($booleano, []) === ['ok', null], 'a8 boolean de tres estados: sí, no y sin filtro');
        $check($parse($booleano, ['active' => 'quizás'])[0] === 'error', 'a9 boolean con otro valor → error');

        $unaFecha = ExportParameter::date('day', 'Día');
        $dia = $parse($unaFecha, ['day' => '2026-09-18']);
        $check($dia[0] === 'ok' && $fecha($dia[1]) === '2026-09-18 00:00:00', 'a10 date estricto a las 00:00:00');
        $check($parse($unaFecha, ['day' => '18/09/2026'])[0] === 'error', 'a11 date en otro formato → error');
        echoTerminal(' ');

        //─── b · Rango ──────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] dateRange');
        $rango = ExportParameter::dateRange('created', 'Creado');
        $r = $parse($rango, ['created_from' => '2026-01-10']);
        $check($r[0] === 'ok' && $fecha($r[1]->from()) === '2026-01-10 00:00:00' && $r[1]->to() === null, 'b1 abierto por el final');
        $r = $parse($rango, ['created_to' => '2026-01-10']);
        $check($r[0] === 'ok' && $r[1]->from() === null && $fecha($r[1]->to()) === '2026-01-10 23:59:59', 'b2 abierto por el inicio');
        $r = $parse($rango, ['created_from' => '2026-03-01', 'created_to' => '2026-01-10']);
        $check($r[0] === 'ok' && $fecha($r[1]->from()) === '2026-01-10 00:00:00' && $fecha($r[1]->to()) === '2026-03-01 23:59:59', 'b3 al revés se intercambia y las horas van con el papel', $r[0] === 'ok' ? $fecha($r[1]->from()) . ' / ' . $fecha($r[1]->to()) : (string) $r[1]);
        $check($parse($rango, ['created_from' => '2026-02-30'])[0] === 'error', 'b4 2026-02-30 → error');
        $check($parse($rango, [])[0] === 'ok' && $parse($rango, [])[1]->isEmpty(), 'b5 sin extremos → rango vacío');
        $check($parse(ExportParameter::dateRange('created', 'Creado')->required(), [])[0] === 'error', 'b6 obligatorio sin extremos → error');
        echoTerminal(' ');

        //─── c · multiChoice ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] multiChoice');
        $varios = ExportParameter::multiChoice('types', 'Tipos', [1 => 'Uno', 2 => 'Dos', 'x' => 'Equis']);
        $check($parse($varios, ['types' => ['1', 'x', '1']]) === ['ok', [1, 'x']], 'c1 por array, sin duplicados y con las claves declaradas');
        $check($parse($varios, ['types' => ' 2 , x ']) === ['ok', [2, 'x']], 'c2 por «a,b»');
        $check($parse($varios, ['types' => '1,9'])[0] === 'error', 'c3 un elemento fuera de la lista → error');
        $check($parse($varios, []) === ['ok', []], 'c4 ausente → []');
        echoTerminal(' ');

        //─── d · choice ─────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] choice');
        $una = ExportParameter::choice('state', 'Estado', ['on' => 'Activo', 'off' => 'Inactivo']);
        $check($parse($una, ['state' => 'on']) === ['ok', 'on'], 'd1 una clave declarada');
        $check($parse($una, ['state' => "on' OR '1'='1"])[0] === 'error' && $parse($una, ['state' => 'Activo'])[0] === 'error', 'd2 lista blanca: fuera de las claves (ni la etiqueta vale) → error');
        echoTerminal(' ');

        //─── e/f · buildContext ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[e-f] buildContext');
        $definicion = new ZzExportParametersDefinition();
        try {
            $definicion->buildContext(['age' => '999', 'state' => 'zz', 'created_from' => 'no'], null);
            $check(false, 'e1 varios errores → una ExportParameterException', 'no lanzó');
        } catch (ExportParameterException $e) {
            $check(count($e->errors()) === 3 && $e->getMessage() === implode("\n", $e->errors()), 'e1 varios errores → UNA excepción con los tres', json_encode($e->errors(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        }
        $contexto = $definicion->buildContext(['age' => '30', 'state' => 'off'], null);
        $check($contexto->get('age') === 30 && $contexto->get('state') === 'off' && $contexto->get('created') instanceof DateRange && $contexto->user() === null, 'e2 contexto con los valores tipados');
        foreach ([
            'f1 dos filtros con la misma clave de URL' => [ExportParameter::text('a', 'A'), ExportParameter::integer('a', 'A2')],
            'f2 un rango que choca con otro filtro' => [ExportParameter::dateRange('d', 'D'), ExportParameter::text('d_from', 'X')],
            'f3 un filtro llamado format' => [ExportParameter::text('format', 'F')],
        ] as $nombre => $parametros) {
            $mala = new class($parametros) extends ExportDefinition {
                /**
                 * @param ExportParameter[] $declared
                 */
                public function __construct(private array $declared) {}
                public function key(): string { return 'zz-mala'; }
                public function title(): string { return 'Mala'; }
                public function allowedUserTypes(): array { return [0]; }
                public function parameters(): array { return $this->declared; }
                public function columns(ExportContext $context): array { return []; }
                public function rows(ExportContext $context): iterable { return []; }
            };
            try {
                $mala->buildContext([], null);
                $check(false, "{$nombre} → InvalidArgumentException", 'no lanzó');
            } catch (ExportParameterException $e) {
                $check(false, "{$nombre} → InvalidArgumentException de programador, no de usuario", $e->getMessage());
            } catch (\InvalidArgumentException $e) {
                $check(true, "{$nombre} → InvalidArgumentException");
            }
        }
        foreach (['f4 key con mayúscula' => fn() => ExportParameter::text('Age', 'X'), 'f5 choice sin opciones' => fn() => ExportParameter::choice('s', 'S', [])] as $nombre => $crear) {
            try {
                $crear();
                $check(false, "{$nombre} → InvalidArgumentException", 'no lanzó');
            } catch (\InvalidArgumentException $e) {
                $check(true, "{$nombre} → InvalidArgumentException");
            }
        }
        echoTerminal(' ');

        //─── g · ExportContext::get ─────────────────────────────────────────────────────────────────────
        echoTerminal('[g] ExportContext::get');
        try {
            $contexto->get('no_declarada');
            $check(false, 'g1 key no declarada → InvalidArgumentException', 'no lanzó');
        } catch (\InvalidArgumentException $e) {
            $check(true, 'g1 key no declarada → InvalidArgumentException');
        }
        echoTerminal(' ');

        //─── h · exportAction ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[h] exportAction');
        $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz?format=csv&age=abc&state=on'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $respuesta = (new DataTransferController())->exportAction($peticion, new ResponseRoute(), ZzExportParametersDefinition::class);
        $cuerpo = json_decode((string) $respuesta->getBody(), true);
        $check($respuesta->getStatusCode() === 400 && ($cuerpo['errors'] ?? null) === ['Edad: debe ser un número entero'] && is_string($cuerpo['error'] ?? null), 'h1 un filtro inválido → 400 con "error" y "errors"', (string) $respuesta->getBody());
        $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz?format=csv&age=30&state=on'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $respuesta = (new DataTransferController())->exportAction($peticion, new ResponseRoute(), ZzExportParametersDefinition::class);
        $check($respuesta->getStatusCode() === 200 && str_contains((string) $respuesta->getBody(), 'x'), 'h2 con filtros válidos → 200 y el CSV');

        echoTerminal(' ');

        //─── i · Formulario ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[i] Vista export-form.php con los siete tipos');
        $siete = [
            ExportParameter::text('name', 'Nombre "con comillas"')->defaultValue('zz"<b>')->help('Ayuda <i>')->required(),
            ExportParameter::integer('age', 'Edad', 0, 120),
            ExportParameter::boolean('active', 'Activo'),
            ExportParameter::choice('state', 'Estado', ['on' => 'Activo', 'o"ff' => 'Inac<tivo']),
            ExportParameter::multiChoice('types', 'Tipos', [1 => 'Uno', 2 => 'Dos']),
            ExportParameter::date('day', 'Día'),
            ExportParameter::dateRange('created', 'Creado'),
        ];
        $html = (function (array $parameters): string {
            $langGroup = \DataImportExportUtility\DataImportExportUtilityLang::LANG_GROUP;
            $title = 'Prueba';
            $breadcrumbs = '';
            $action = 'https://zz.test/export';
            ob_start();
            include basepath('app/classes/DataImportExportUtility/Views/data-transfer/export-form.php');
            return (string) ob_get_clean();
        })($siete);
        $nombres = [];
        if (preg_match_all('/\bname="([^"]+)"/', $html, $m) > 0) {
            $nombres = $m[1];
        }
        $esperados = ['name', 'age', 'active', 'state', 'types[]', 'day', 'created_from', 'created_to', 'format'];
        $check($nombres === $esperados, 'i1 un campo por parámetro, el rango en dos y el formato', json_encode($nombres, JSON_THROW_ON_ERROR));
        $check(str_contains($html, 'value="zz&quot;&lt;b&gt;"') && str_contains($html, 'Nombre &quot;con comillas&quot;') && str_contains($html, 'value="o&quot;ff"') && str_contains($html, 'Inac&lt;tivo') && str_contains($html, 'Ayuda &lt;i&gt;') && !str_contains($html, 'zz"<b>'), 'i2 valores, etiquetas, opciones y ayuda escapados');
        $check(str_contains($html, 'min="0"') && str_contains($html, 'max="120"') && str_contains($html, 'action="https://zz.test/export"') && str_contains($html, 'method="GET"'), 'i3 min/max del entero y el GET hacia la descarga');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los filtros de exportación validan cada tipo y juntan los errores en un 400.')->setEffects([CliActions::EFFECT_NONE])->register();
