<?php

//Importador: XLSX por defecto (P47), plantilla con ayuda, niveles de interfaz y simulacro que nunca guarda.
//Lanza bin/cli data-transfer-import en simulacro; si por un fallo creara usuarios zz-prueba-simulacro-*, los borra. Temporales propios.

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Import\Column;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Core\DataTransfer\Import\ImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Source\SpreadsheetRowSource;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-import-interface', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferImportInterface] Formato, plantilla, niveles y simulacro del importador\e[39m");
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

    if (!class_exists('ZzImportInterfaceDefinition', false)) {
        class ZzImportInterfaceDefinition extends ImportDefinition
        {
            /** @var int */
            public static $level = self::INTERFACE_EXTENDED;
            /** @var string */
            public static $key = 'zz-importa';
            /** @var string[]|null null: las de por defecto */
            public static $extensions = null;
            /** @var string|null */
            public static $view = null;
            /** @var int */
            public static $persistCalls = 0;
            public function key(): string { return self::$key; }
            public function title(): string { return 'Importa'; }
            public function allowedUserTypes(): array { return [0]; }
            public function interfaceLevel(): int { return self::$level; }
            public function customView(): ?string { return self::$view; }
            public function acceptedExtensions(): array { return self::$extensions ?? parent::acceptedExtensions(); }
            public function mayProduceArtifacts(): bool { return true; }
            public function columns(): array
            {
                return [
                    (new Column('code', 'Código', true))->help('Único por fila')->example('A-001'),
                    (new Column('name', 'Nombre'))->example('Ana'),
                    new Column('note', 'Nota'),
                ];
            }
            public function persist(array $rows): ?ImportArtifacts
            {
                self::$persistCalls++;
                return new ImportArtifacts('zz-credenciales.csv', 'text/csv', 'zz-contenido');
            }
        }
    }

    $reset = function (): void {
        ZzImportInterfaceDefinition::$level = ImportDefinition::INTERFACE_EXTENDED;
        ZzImportInterfaceDefinition::$key = 'zz-importa';
        ZzImportInterfaceDefinition::$extensions = null;
        ZzImportInterfaceDefinition::$view = null;
        ZzImportInterfaceDefinition::$persistCalls = 0;
    };
    $temporales = [];
    $archivo = function (string $contenido, string $extension) use (&$temporales): string {
        $ruta = sys_get_temp_dir() . '/zz-import-interface-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $temporales[] = $ruta;
        if (file_put_contents($ruta, $contenido) === false) {
            throw new \RuntimeException("No se pudo escribir {$ruta}");
        }
        return $ruta;
    };
    $peticion = fn(array $query = [], array $body = []) => (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withQueryParams($query)->withParsedBody($body);
    //La acción lee $_FILES; ignorePOSTUploaded porque la CLI no puede hacer una subida HTTP de verdad.
    $importar = function (string $ruta, string $nombre, array $body = []) use ($peticion): array {
        $_FILES = ['file' => ['name' => $nombre, 'type' => 'text/csv', 'size' => (int) filesize($ruta), 'tmp_name' => $ruta, 'error' => UPLOAD_ERR_OK, 'full_path' => $nombre]];
        $respuesta = (new DataTransferController())->importAction($peticion([], $body), new ResponseRoute(), ZzImportInterfaceDefinition::class, true);
        return [$respuesta->getStatusCode(), json_decode((string) $respuesta->getBody(), true)];
    };
    $nombres = function (int $level): array {
        ZzImportInterfaceDefinition::$level = $level;
        $grupo = new RouteGroup('zz-importa');
        DataTransferController::importerRoutes($grupo, ZzImportInterfaceDefinition::class, 'zz-importa', [0]);
        $rutas = array_values(array_filter((array) (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo), fn($r) => $r instanceof Route));
        return array_map(fn(Route $r) => $r->name(), $rutas);
    };

    $filesOriginal = $_FILES;
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-prueba-simulacro-{$marca}";
    $delPrefijo = function () use ($prefijo): array {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        return array_values((array) $m->result());
    };

    try {
        //─── a · Formato y plantilla ────────────────────────────────────────────────────────────────────
        echoTerminal('[a] XLSX por defecto y plantilla');
        $reset();
        $csv = $archivo("code,name\nA-1,Ana\n", 'csv');
        [$estado, $json] = $importar($csv, 'datos.csv');
        $check((new ZzImportInterfaceDefinition())->acceptedExtensions() === ['xlsx'] && $estado === 400 && ($json['headerErrors'] ?? null) === ['Solo se admiten archivos xlsx.'] && ZzImportInterfaceDefinition::$persistCalls === 0, 'a1 por defecto solo xlsx: un .csv → 400', (string) json_encode($json, JSON_UNESCAPED_UNICODE));
        $check((new UsersImportDefinition())->acceptedExtensions() === ['xlsx', 'csv'], 'a2 users añade csv');
        $respuesta = (new DataTransferController())->importTemplate($peticion(), new ResponseRoute(), ZzImportInterfaceDefinition::class);
        $plantilla = $archivo((string) $respuesta->getBody(), 'xlsx');
        $hoja = IOFactory::load($plantilla)->getActiveSheet();
        $check(str_contains($respuesta->getHeaderLine('Content-Type'), 'spreadsheetml') && str_contains($respuesta->getHeaderLine('Content-Disposition'), 'plantilla-zz-importa.xlsx'), 'a3 sin formato, la plantilla es XLSX');
        $check((string) $hoja->getCell('A1')->getValue() === 'Código' && (string) $hoja->getCell('B1')->getValue() === 'Nombre' && $hoja->getHighestRow() === 1, 'a4 solo la fila de etiquetas, sin fila de ejemplo', (string) $hoja->getHighestRow());
        $check($hoja->getStyle('A1')->getFont()->getBold() && !$hoja->getStyle('B1')->getFont()->getBold(), 'a5 las obligatorias en negrita');
        $comentarioA = $hoja->getComment('A1')->getText()->getPlainText();
        $comentarioB = $hoja->getComment('B1')->getText()->getPlainText();
        $check($comentarioA === "Único por fila\nEjemplo: A-001" && $comentarioB === 'Ejemplo: Ana' && $hoja->getComment('C1')->getText()->getPlainText() === '', 'a6 comentario con la ayuda y el ejemplo; sin ellos, ninguno', (string) json_encode([$comentarioA, $comentarioB], JSON_UNESCAPED_UNICODE));
        $respuesta = (new DataTransferController())->importTemplate($peticion(['format' => 'csv']), new ResponseRoute(), ZzImportInterfaceDefinition::class);
        $check($respuesta->getStatusCode() === 400, 'a7 plantilla csv en una definición sin csv → 400');
        ZzImportInterfaceDefinition::$extensions = ['xlsx', 'csv'];
        $respuesta = (new DataTransferController())->importTemplate($peticion(['format' => 'csv']), new ResponseRoute(), ZzImportInterfaceDefinition::class);
        $check($respuesta->getStatusCode() === 200 && (string) $respuesta->getBody() === "\xEF\xBB\xBFCódigo,Nombre,Nota\n", 'a8 y con csv declarado, la plantilla csv de siempre');
        $check((new Column('k', 'K'))->helpText() === null && (new Column('k', 'K'))->exampleValue() === null, 'a9 Column sin help() ni example() → null');
        echoTerminal(' ');

        //─── b · Niveles ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Rutas por nivel');
        $reset();
        $base = 'data-transfer-import-zz-importa';
        $check($nombres(ImportDefinition::INTERFACE_NONE) === [], 'b1 nivel 0: ninguna ruta web');
        $todas = [$base, "{$base}-action", "{$base}-template"];
        $check($nombres(ImportDefinition::INTERFACE_AUTO) === $todas && $nombres(ImportDefinition::INTERFACE_EXTENDED) === $todas && $nombres(ImportDefinition::INTERFACE_CUSTOM) === $todas, 'b2 niveles 1, 2 y 3: formulario, acción y plantilla');
        $E = \PiecesPHP\Core\DataTransfer\Export\ExportDefinition::class;
        $check([ImportDefinition::INTERFACE_NONE, ImportDefinition::INTERFACE_AUTO, ImportDefinition::INTERFACE_EXTENDED, ImportDefinition::INTERFACE_CUSTOM] === [$E::INTERFACE_NONE, $E::INTERFACE_AUTO, $E::INTERFACE_EXTENDED, $E::INTERFACE_CUSTOM], 'b3 las constantes son las mismas que las de la exportación');
        ZzImportInterfaceDefinition::$level = 9;
        ZzImportInterfaceDefinition::$key = 'zz-importa-mal';
        $check($lanza(fn() => DataImportExportUtilityRoutes::importer(new RouteGroup('zz'), ZzImportInterfaceDefinition::class)) && !array_key_exists('zz-importa-mal', DataImportExportUtilityRoutes::importers()), 'b4 nivel 9 → excepción al registrar');
        ZzImportInterfaceDefinition::$level = ImportDefinition::INTERFACE_CUSTOM;
        ZzImportInterfaceDefinition::$key = 'zz-importa-sin-vista';
        $check($lanza(fn() => DataImportExportUtilityRoutes::importer(new RouteGroup('zz'), ZzImportInterfaceDefinition::class)), 'b5 nivel 3 sin customView() → excepción');
        $reset();
        ZzImportInterfaceDefinition::$level = ImportDefinition::INTERFACE_NONE;
        ZzImportInterfaceDefinition::$key = 'zz-importa-cero';
        DataImportExportUtilityRoutes::importer(new RouteGroup('zz'), ZzImportInterfaceDefinition::class);
        $root = UsersModel::model();
        $root->resetAll();
        $root->select()->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
        $idRoot = (int) (((array) $root->result())[0]->id ?? 0);
        set_config('current_user', (object) ['id' => $idRoot]);
        set_config('pcsphp_current_user_stored', null);
        $titulos = array_column(DataTransferController::visibleImporters(), 'title');
        $check(array_key_exists('zz-importa-cero', DataImportExportUtilityRoutes::importers()) && !in_array('Importa', $titulos, true) && in_array((new UsersImportDefinition())->title(), $titulos, true), 'b6 la portada lista users (nivel 1) y no el de nivel 0', (string) json_encode($titulos, JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //─── c · Simulacro ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Simulacro');
        $reset();
        ZzImportInterfaceDefinition::$extensions = ['xlsx', 'csv'];
        $valido = $archivo("code,name\nA-1,Ana\nA-2,Luis\n", 'csv');
        [$estado, $json] = $importar($valido, 'datos.csv', ['dryRun' => 'yes']);
        $check($estado === 200 && ZzImportInterfaceDefinition::$persistCalls === 0 && ($json['dryRun'] ?? null) === true && ($json['persisted'] ?? null) === false && ($json['valid'] ?? null) === 2 && !array_key_exists('artifact', $json ?? []), 'c1 nivel 2: valida todo, persist no se llama, informe de simulacro y sin entregable', (string) json_encode($json, JSON_UNESCAPED_UNICODE));
        $invalido = $archivo("code,name\n,Ana\n", 'csv');
        [$estado, $json] = $importar($invalido, 'datos.csv', ['dryRun' => 'yes']);
        $check(ZzImportInterfaceDefinition::$persistCalls === 0 && ($json['dryRun'] ?? null) === true && ($json['invalid'] ?? null) === 1, 'c2 con errores también reporta, sin guardar');
        [$estado, $json] = $importar($valido, 'datos.csv');
        $check($estado === 200 && ZzImportInterfaceDefinition::$persistCalls === 1 && ($json['dryRun'] ?? null) === false && ($json['persisted'] ?? null) === true, 'c3 sin dryRun, se guarda como siempre (persist 1 vez)');
        ZzImportInterfaceDefinition::$level = ImportDefinition::INTERFACE_AUTO;
        ZzImportInterfaceDefinition::$persistCalls = 0;
        [$estado, $json] = $importar($valido, 'datos.csv', ['dryRun' => 'yes']);
        $check($estado === 400 && ($json['headerErrors'] ?? null) === ['Este importador no permite simular.'] && ZzImportInterfaceDefinition::$persistCalls === 0, 'c4 nivel 1 con dryRun → 400');
        $informe = (new ImportRunner())->run(new ZzImportInterfaceDefinition(), SpreadsheetRowSource::fromFile($valido, 'csv'), true);
        $check($informe->isDryRun() && !$informe->persisted() && $informe->artifacts() === null && ZzImportInterfaceDefinition::$persistCalls === 0, 'c5 ImportRunner::run(…, true): isDryRun y sin persist, también en nivel 1 (la terminal)');

        //La terminal: users produce credenciales, pero en simulacro no se pide credentials-out.
        $usuarios = $archivo("username,email,firstname,first_lastname\n{$prefijo}-a,{$prefijo}-a@example.com,Zz,Prueba\n", 'csv');
        $antes = glob(sys_get_temp_dir() . '/*') ?: [];
        $comando = escapeshellarg(dirname(basepath()) . '/bin/cli') . ' data-transfer-import'
            . ' ' . escapeshellarg('definition=users') . ' ' . escapeshellarg("file={$usuarios}")
            . ' ' . escapeshellarg("as-user={$idRoot}") . ' ' . escapeshellarg('dry-run=yes');
        $salida = [];
        $codigo = -1;
        //RETORNO-IGNORADO: el resultado va en $salida y $codigo; el retorno es solo la última línea.
        exec($comando . ' 2>&1', $salida, $codigo);
        $texto = (string) preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $salida));
        $nuevos = array_values(array_diff(glob(sys_get_temp_dir() . '/*') ?: [], $antes));
        $check($codigo === 0 && str_contains($texto, 'Simulacro: todo válido. No se guardó nada.') && count($delPrefijo()) === 0, 'c6 terminal dry-run=yes sin credentials-out → 0, y ningún usuario creado', $texto);
        $check(!str_contains($texto, 'Credenciales en') && array_filter($nuevos, fn($f) => str_contains($f, 'cred')) === [], 'c7 y no escribe credenciales', (string) json_encode($nuevos));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        $_FILES = $filesOriginal;
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        foreach ($temporales as $ruta) {
            if (is_file($ruta)) {
                //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
                @unlink($ruta);
            }
        }
        //Si el simulacro llegó a guardar (un fallo), z1 lo dice y aquí se borra: la prueba no deja residuos.
        $creados = array_map(fn($u) => (int) $u->id, $delPrefijo());
        if (count($creados) > 0) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            $perfiles->delete(new WhereSegment([new WhereItem('belongsTo', WhereItem::IN_OPERATOR, '(' . implode(',', $creados) . ')')]))->execute();
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        }
        $check(count(array_filter($temporales, 'is_file')) === 0 && count($creados) === 0, 'z1 limpieza: 0 temporales y el simulacro no creó usuarios', (string) json_encode($creados));
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Importador: XLSX por defecto, plantilla con ayuda, niveles de interfaz y simulacro que nunca guarda.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
