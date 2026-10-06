<?php

//Niveles de interfaz de la exportación: rutas por nivel, vista previa que no escribe ni lo lee todo, y elección de columnas.
//Escribe dos .php temporales en src/tmp (ignorado por git) y los borra en el finally; solo lee la base.

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use DataImportExportUtility\Definitions\UsersExportDefinition;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\ExportParameter;
use PiecesPHP\Core\DataTransfer\Export\ExportResult;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-export-interface', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExportInterface] Niveles de interfaz, vista previa y columnas\e[39m");
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

    if (!class_exists('ZzExportInterfaceDefinition', false)) {
        class ZzExportInterfaceDefinition extends ExportDefinition
        {
            /** @var int */
            public static $level = self::INTERFACE_EXTENDED;
            /** @var string */
            public static $key = 'zz-niveles';
            /** @var int */
            public static $total = 3;
            /** @var int filas que el generador llegó a producir */
            public static $generated = 0;
            /** @var int */
            public static $afterExportCalls = 0;
            /** @var string|null */
            public static $partial = null;
            /** @var string|null */
            public static $view = null;
            /** @var bool */
            public static $withTotal = false;
            public function key(): string { return self::$key; }
            public function title(): string { return 'Niveles'; }
            public function allowedUserTypes(): array { return [0]; }
            public function interfaceLevel(): int { return self::$level; }
            public function formPartial(): ?string { return self::$partial; }
            public function customView(): ?string { return self::$view; }
            public function parameters(): array
            {
                return [ExportParameter::integer('min', 'Mínimo', 0)];
            }
            public function columns(ExportContext $context): array
            {
                return [
                    new ExportColumn('name', 'Nombre'),
                    (new ExportColumn('qty', 'Cantidad'))->asInteger(),
                    (new ExportColumn('price', 'Precio'))->asDecimal(),
                    (new ExportColumn('amount', 'Importe'))->asDecimal()->formula('={qty}*{price}'),
                ];
            }
            public function rows(ExportContext $context): iterable
            {
                $min = $context->get('min') ?? 0;
                for ($i = 1; $i <= self::$total; $i++) {
                    if ($i < $min) {
                        continue;
                    }
                    self::$generated++;
                    yield ['name' => "n{$i}", 'qty' => $i, 'price' => 1.5];
                }
            }
            public function sheets(ExportContext $context): array
            {
                $main = $this->mainSheet($context);
                if (self::$withTotal) {
                    $main->total('qty', 'SUM')->total('price', 'SUM');
                }
                return [$main];
            }
            public function afterExport(ExportContext $context, ExportResult $result): void
            {
                self::$afterExportCalls++;
            }
        }
    }

    $reset = function (): void {
        ZzExportInterfaceDefinition::$level = ExportDefinition::INTERFACE_EXTENDED;
        ZzExportInterfaceDefinition::$key = 'zz-niveles';
        ZzExportInterfaceDefinition::$total = 3;
        ZzExportInterfaceDefinition::$generated = 0;
        ZzExportInterfaceDefinition::$afterExportCalls = 0;
        ZzExportInterfaceDefinition::$partial = null;
        ZzExportInterfaceDefinition::$view = null;
        ZzExportInterfaceDefinition::$withTotal = false;
    };
    $peticion = fn(string $query) => new RequestRoute('GET', (new UriFactory())->createUri("http://localhost/zz?{$query}"), new Headers(), [], [], (new StreamFactory())->createStream(''));
    $descargar = fn(string $query) => (new DataTransferController())->exportAction($peticion($query), new ResponseRoute(), ZzExportInterfaceDefinition::class);
    $previa = fn(string $query) => (new DataTransferController())->exportPreview($peticion($query), new ResponseRoute(), ZzExportInterfaceDefinition::class);
    $nombres = function (int $level): array {
        ZzExportInterfaceDefinition::$level = $level;
        $grupo = new RouteGroup('zz-niveles');
        DataTransferController::exporterRoutes($grupo, ZzExportInterfaceDefinition::class, 'zz-niveles', [0]);
        $rutas = array_values(array_filter((array) (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo), fn($r) => $r instanceof Route));
        return array_map(fn(Route $r) => $r->name(), $rutas);
    };
    $pintar = function (array $vars): string {
        $langGroup = \DataImportExportUtility\DataImportExportUtilityLang::LANG_GROUP;
        $title = 'Niveles';
        $breadcrumbs = '';
        $action = 'https://zz.test/export';
        $parameters = [];
        $reimport = null;
        $previewURL = null;
        $columns = [];
        $partial = null;
        $definition = new ZzExportInterfaceDefinition();
        extract($vars);
        ob_start();
        include basepath('app/classes/DataImportExportUtility/Views/data-transfer/export-form.php');
        return (string) ob_get_clean();
    };

    $tmp = basepath('tmp');
    $sufijo = bin2hex(random_bytes(4));
    $hueco = "{$tmp}/zz-hueco-{$sufijo}.php";
    $vista = "{$tmp}/zz-vista-{$sufijo}.php";
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');

    try {
        //─── a · Niveles ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Rutas por nivel');
        $reset();
        $base = 'data-transfer-export-zz-niveles';
        $check($nombres(ExportDefinition::INTERFACE_NONE) === [$base], 'a1 nivel 0: solo la descarga');
        $check($nombres(ExportDefinition::INTERFACE_AUTO) === [$base, "{$base}-form"], 'a2 nivel 1: más el formulario');
        $nivel2 = [$base, "{$base}-form", "{$base}-presets-save", "{$base}-presets-delete", "{$base}-preview"];
        $check($nombres(ExportDefinition::INTERFACE_EXTENDED) === $nivel2, 'a3 nivel 2: más la vista previa y los filtros guardados');
        $check($nombres(ExportDefinition::INTERFACE_CUSTOM) === $nivel2, 'a4 nivel 3: las mismas rutas');
        $reset();
        ZzExportInterfaceDefinition::$level = 7;
        ZzExportInterfaceDefinition::$key = 'zz-niveles-mal';
        $check($lanza(fn() => DataImportExportUtilityRoutes::exporter(new RouteGroup('zz'), ZzExportInterfaceDefinition::class)) && !array_key_exists('zz-niveles-mal', DataImportExportUtilityRoutes::exporters()), 'a5 nivel 7 → excepción al registrar, y no queda registrado');
        ZzExportInterfaceDefinition::$level = ExportDefinition::INTERFACE_CUSTOM;
        ZzExportInterfaceDefinition::$key = 'zz-niveles-sin-vista';
        $check($lanza(fn() => DataImportExportUtilityRoutes::exporter(new RouteGroup('zz'), ZzExportInterfaceDefinition::class)), 'a6 nivel 3 sin customView() → excepción');
        ZzExportInterfaceDefinition::$view = '/etc/hostname';
        ZzExportInterfaceDefinition::$key = 'zz-niveles-vista-fuera';
        $check($lanza(fn() => DataImportExportUtilityRoutes::exporter(new RouteGroup('zz'), ZzExportInterfaceDefinition::class)), 'a7 nivel 3 con la vista fuera del proyecto → excepción');
        $reset();
        ZzExportInterfaceDefinition::$level = ExportDefinition::INTERFACE_NONE;
        ZzExportInterfaceDefinition::$key = 'zz-niveles-cero';
        DataImportExportUtilityRoutes::exporter(new RouteGroup('zz'), ZzExportInterfaceDefinition::class);
        $root = UsersModel::model();
        $root->resetAll();
        $root->select()->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
        $filasRoot = (array) $root->result();
        set_config('current_user', (object) ['id' => isset($filasRoot[0]->id) ? (int) $filasRoot[0]->id : 0]);
        set_config('pcsphp_current_user_stored', null);
        $titulos = array_column(DataTransferController::visibleExporters(), 'title');
        //Mientras el registro tiene al de nivel 0, todas las instancias responden 0: se comprueba antes de cambiarlo.
        $check(array_key_exists('zz-niveles-cero', DataImportExportUtilityRoutes::exporters()) && !in_array('Niveles', $titulos, true) && in_array((new UsersExportDefinition())->title(), $titulos, true), 'a8 la portada lista users (nivel 1) y no el de nivel 0', (string) json_encode($titulos, JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //─── b · Vista previa ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Vista previa');
        $reset();
        ZzExportInterfaceDefinition::$total = 25;
        $r = $previa('');
        $json = json_decode((string) $r->getBody(), true);
        $check($r->getStatusCode() === 200 && is_array($json) && count($json['rows']) === 20 && $json['truncated'] === true && $json['columns'] === ['Nombre', 'Cantidad', 'Precio', 'Importe'], 'b1 25 filas → 20 y truncated', (string) json_encode([$r->getStatusCode(), is_array($json) ? count($json['rows']) : null, $json['truncated'] ?? null]));
        $check(ZzExportInterfaceDefinition::$generated <= 21, 'b2 el generador produjo como mucho 21 filas', (string) ZzExportInterfaceDefinition::$generated);
        $check(is_array($json) && $json['rows'][0] === ['n1', '1', '1.5', ''], 'b3 valores como en CSV y la fórmula vacía', (string) json_encode($json['rows'][0] ?? null));
        $reset();
        $json = json_decode((string) $previa('min=2')->getBody(), true);
        $check(is_array($json) && count($json['rows']) === 2 && $json['truncated'] === false && $json['rows'][0][0] === 'n2', 'b4 menos de 20 → truncated false, con el filtro aplicado');
        $r = $previa('min=-1');
        $check($r->getStatusCode() === 400 && ZzExportInterfaceDefinition::$afterExportCalls === 0, 'b5 filtro inválido → 400; y la vista previa nunca llama a afterExport');
        $reset();
        ZzExportInterfaceDefinition::$total = 1;
        $json = json_decode((string) (new DataTransferController())->exportPreview($peticion(''), new ResponseRoute(), ZzExportInterfaceDefinition::class)->getBody(), true);
        $check(is_array($json) && $json['rows'] === [['n1', '1', '1.5', '']] && ZzExportInterfaceDefinition::$afterExportCalls === 0, 'b6 sin afterExport tampoco con una exportación válida');
        ZzExportInterfaceDefinition::$total = 3;
        $json = json_decode((string) $previa('columns[]=price&columns[]=name')->getBody(), true);
        $check(is_array($json) && $json['columns'] === ['Precio', 'Nombre'] && $json['rows'][0] === ['1.5', 'n1'], 'b7 la vista previa respeta la elección y el orden de columnas');
        echoTerminal(' ');

        //─── c · Columnas ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Elegir y ordenar columnas');
        $reset();
        $cuerpo = (string) $descargar('format=csv&columns[]=price&columns[]=name&columns[]=price')->getBody();
        $lineas = array_map(fn($l) => str_getcsv(ltrim($l, "\xEF\xBB\xBF"), ',', '"', ''), preg_split('/\r?\n/', trim($cuerpo)) ?: []);
        $check(($lineas[0] ?? []) === ['Precio', 'Nombre'] && ($lineas[1] ?? []) === ['1.5', 'n1'], 'c1 CSV: subconjunto, en el orden de la URL y sin repetir', (string) json_encode($lineas[0] ?? null, JSON_UNESCAPED_UNICODE));
        $cuerpo = (string) $descargar('format=csv&columns=qty,name')->getBody();
        $check(str_starts_with(ltrim($cuerpo, "\xEF\xBB\xBF"), 'Cantidad,Nombre'), 'c2 también por «columns=a,b»');
        $path = sys_get_temp_dir() . "/zz-niveles-{$sufijo}.xlsx";
        ZzExportInterfaceDefinition::$withTotal = true;
        if (file_put_contents($path, (string) $descargar('format=xlsx&columns[]=qty&columns[]=name')->getBody()) === false) {
            throw new \RuntimeException("No se puede escribir «{$path}».");
        }
        $hoja = IOFactory::load($path)->getActiveSheet();
        //RETORNO-IGNORADO: temporal propio de la prueba.
        @unlink($path);
        $check((string) $hoja->getCell('A1')->getValue() === 'Cantidad' && (string) $hoja->getCell('B1')->getValue() === 'Nombre' && (string) $hoja->getCell('C1')->getValue() === '', 'c3 XLSX: subconjunto y orden');
        $check($hoja->getCell('A5')->getValue() === '=SUM(A2:A4)' && (string) $hoja->getCell('B5')->getValue() === 'Total', 'c4 el total de la columna quitada se omite sin error; el de la elegida sigue', (string) $hoja->getCell('A5')->getValue());
        ZzExportInterfaceDefinition::$withTotal = false;
        $r = $descargar('format=csv&columns[]=name&columns[]=nada');
        $check($r->getStatusCode() === 400 && (json_decode((string) $r->getBody(), true)['errors'] ?? null) === ['La columna «nada» no existe.'], 'c5 desconocida → 400 con su mensaje', mb_substr((string) $r->getBody(), 0, 150));
        $r = $descargar('format=csv&columns[]=amount&columns[]=qty');
        $check($r->getStatusCode() === 400 && str_contains((string) $r->getBody(), 'La columna Importe necesita la columna Precio.'), 'c6 fórmula sin su dependencia → 400', mb_substr((string) $r->getBody(), 0, 200));
        $check($descargar('format=csv&columns[]=amount&columns[]=qty&columns[]=price')->getStatusCode() === 200, 'c7 con sus dependencias, 200');
        ZzExportInterfaceDefinition::$level = ExportDefinition::INTERFACE_AUTO;
        $r = $descargar('format=csv&columns[]=name');
        $check($r->getStatusCode() === 400 && str_contains((string) $r->getBody(), 'Este exportador no permite elegir columnas.'), 'c8 nivel 1 con columns → 400');
        $check($descargar('format=csv&columns=')->getStatusCode() === 200, 'c9 columns vacío → todas, también en nivel 1');
        $reservada = new class extends ExportDefinition {
            public function key(): string { return 'zz-reservada'; }
            public function title(): string { return 'R'; }
            public function allowedUserTypes(): array { return [0]; }
            public function parameters(): array { return [ExportParameter::text('columns', 'Columnas')]; }
            public function columns(ExportContext $context): array { return []; }
            public function rows(ExportContext $context): iterable { return []; }
        };
        $check($lanza(fn() => $reservada->buildContext([], null)), 'c10 un parámetro con key «columns» → excepción');
        echoTerminal(' ');

        //─── d · Hueco y vista propia ───────────────────────────────────────────────────────────────────
        echoTerminal('[d] formPartial y customView');
        $reset();
        if (file_put_contents($hueco, "<?php echo '<p data-zz-hueco>', htmlspecialchars(\$definition->title()), ' ', count(\$parameters), ' ', \$langGroup, '</p>';") === false
            || file_put_contents($vista, "<?php echo '<p data-zz-vista>', htmlspecialchars(\$downloadURL), '|', htmlspecialchars((string) \$previewURL), '|', \$definition->key(), '</p>';") === false) {
            throw new \RuntimeException('No se pueden escribir las vistas de prueba.');
        }
        ZzExportInterfaceDefinition::$partial = '/etc/hostname';
        $check($lanza(fn() => (new DataTransferController())->exportForm($peticion(''), new ResponseRoute(), ZzExportInterfaceDefinition::class)), 'd1 formPartial fuera del proyecto → excepción al pintar');
        $html = $pintar(['partial' => $hueco, 'parameters' => (new ZzExportInterfaceDefinition())->parameters()]);
        $check(str_contains($html, '<p data-zz-hueco>Niveles 1 '), 'd2 dentro, se incluye con $definition, $parameters y $langGroup');
        ZzExportInterfaceDefinition::$level = ExportDefinition::INTERFACE_CUSTOM;
        ZzExportInterfaceDefinition::$view = $vista;
        (new ZzExportInterfaceDefinition())->checkInterface();
        ob_start();
        DataTransferController::renderFile($vista, ['downloadURL' => 'https://zz/d', 'previewURL' => 'https://zz/p', 'definition' => new ZzExportInterfaceDefinition()]);
        $propia = (string) ob_get_clean();
        $check($propia === '<p data-zz-vista>https://zz/d|https://zz/p|zz-niveles</p>', 'd3 nivel 3: la vista propia se pinta con $downloadURL, $previewURL y $definition', $propia);
        echoTerminal(' ');

        //─── e · Formulario ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[e] Formulario por nivel');
        $html = $pintar(['previewURL' => 'https://zz/p?x=1&y=2', 'columns' => [['key' => 'name', 'label' => 'Nombre "N"'], ['key' => 'qty', 'label' => 'Cantidad']]]);
        $check(str_contains($html, 'data-transfer-preview="https://zz/p?x=1&amp;y=2"') && str_contains($html, 'data-transfer-preview-result') && substr_count($html, 'name="columns[]"') === 2 && str_contains($html, 'data-transfer-column-up') && str_contains($html, 'Nombre &quot;N&quot;'), 'e1 nivel 2: vista previa, casillas en orden, subir/bajar y etiquetas escapadas');
        $html = $pintar([]);
        $check(!str_contains($html, 'data-transfer-preview') && !str_contains($html, 'columns[]'), 'e2 nivel 1: ni vista previa ni columnas');
        echoTerminal(' ');

        //─── f · Usuarios ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[f] Usuarios');
        $check((new UsersExportDefinition())->interfaceLevel() === ExportDefinition::INTERFACE_AUTO, 'f1 el exportador de usuarios sigue en nivel 1');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        foreach ([$hueco, $vista] as $file) {
            if (is_file($file)) {
                //RETORNO-IGNORADO: limpieza de la vista temporal propia.
                @unlink($file);
            }
        }
        $check(!is_file($hueco) && !is_file($vista), 'z1 limpieza de las vistas temporales');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Niveles de interfaz de la exportación, vista previa y elección de columnas.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
