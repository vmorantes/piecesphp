<?php

//Portada de Importar y exportar e interruptor por definición: lo apagado no entra por ninguna puerta, ni para root.
//Escribe de verdad la opción data_transfer_disabled y entradas del registro de acciones, y lo repone todo en el finally: db-backup antes.

use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\Definitions\UsersExportDefinition;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use DataImportExportUtility\Examples\ExampleUserNamesImportDefinition;
use DataImportExportUtility\Examples\ExampleUsersReportExportDefinition;
use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\ExportParameter;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-hub', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferHub] Portada e interruptor por importador y exportador\e[39m");
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

    $config = DataTransferController::DISABLED_CONFIG;
    $existiaConfig = SettingsModel::optionExists($config);
    $configPrevia = SettingsModel::getConfigValue($config);
    $logs = LogsMapper::model();
    $logs->resetAll();
    $logs->select(['MAX(id) AS maxID'])->execute();
    $maxLogPrevio = (int) (((array) (((array) $logs->result())[0] ?? []))['maxID'] ?? 0);
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $dir = sys_get_temp_dir() . '/zz-hub-' . bin2hex(random_bytes(4));
    if (!mkdir($dir)) {
        throw new \RuntimeException("No se puede crear «{$dir}».");
    }

    $como = function (int $id): void {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
    };
    $unoDeTipo = function (int $type): int {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select(['id'])->where(new WhereSegment([WhereItem::isEqual('type', $type), WhereItem::isEqual('status', UsersModel::STATUS_USER_ACTIVE)]))->execute(false, 1, 1);
        return (int) (((array) (((array) $m->result())[0] ?? []))['id'] ?? 0);
    };
    $peticion = fn(string $method, array $query = [], array $body = []) => (new RequestRoute($method, (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withQueryParams($query)->withParsedBody($body);
    $pintar = function () use ($dir): string {
        $langGroup = \DataImportExportUtility\DataImportExportUtilityLang::LANG_GROUP;
        $title = 'Importar y exportar';
        $breadcrumbs = '';
        $importers = DataTransferController::visibleImporters();
        $exporters = DataTransferController::visibleExporters();
        $user = getLoggedFrameworkUser();
        $isRoot = $user !== null && (int) $user->type === UsersModel::TYPE_USER_ROOT;
        $toggleURL = 'https://zz/toggle';
        ob_start();
        include basepath('app/classes/DataImportExportUtility/Views/data-transfer/hub.php');
        return (string) ob_get_clean();
    };
    $apagar = function (array $import, array $export) use ($config): void {
        if (!SettingsModel::setConfigValue($config, ['import' => $import, 'export' => $export])) {
            throw new \RuntimeException('No se pudo escribir la configuración.');
        }
    };
    $terminal = function (string $csv, int $asUser): array {
        $comando = escapeshellarg(dirname(basepath()) . '/bin/cli') . ' data-transfer-import ' . escapeshellarg('definition=users')
            . ' ' . escapeshellarg("file={$csv}") . ' ' . escapeshellarg("as-user={$asUser}") . ' ' . escapeshellarg('dry-run=yes');
        $salida = [];
        $codigo = -1;
        //RETORNO-IGNORADO: el resultado va en $salida y $codigo; el retorno es solo la última línea.
        exec($comando . ' 2>&1', $salida, $codigo);
        return [$codigo, (string) preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $salida))];
    };

    try {
        $root = $unoDeTipo(UsersModel::TYPE_USER_ROOT);
        $admin = $unoDeTipo(UsersModel::TYPE_USER_ADMIN_GRAL);
        $check($root > 0 && $admin > 0, 'banco: hay un root y un administrador general activos', "root {$root}, admin {$admin}");
        $apagar([], []);

        //─── a · Portada ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Portada');
        $como($root);
        $html = $pintar();
        $check(str_contains($html, 'Carga datos desde una hoja de cálculo, o descarga los del sistema con los filtros que elijas.') && !str_contains($html, 'Elige qué quieres importar.'), 'a1 subtítulo exacto');
        $check(str_contains($html, 'class="tabs-controls"') && str_contains($html, 'data-tab="importar"') && str_contains($html, 'data-tab="exportar"') && substr_count($html, 'class="ui tab tab-element') === 2, 'a2 pestañas Importar y Exportar con la pieza del panel');
        $check(str_contains($html, 'Descarga la plantilla, llénala y súbela. Puedes validarla antes de guardar.') && str_contains($html, 'Elige los filtros y el formato, y descarga el archivo.'), 'a3 la línea de cada pestaña');
        $check(str_contains($html, 'data-transfer-row="import-users"') && str_contains($html, 'data-transfer-row="export-users"') && str_contains($html, htmlspecialchars((new UsersImportDefinition())->description(), ENT_QUOTES)), 'a4 una fila por entidad, con su descripción');
        $filaUsers = DataTransferController::importerHubRow('users', new UsersImportDefinition(), true);
        $filaEjemplo = DataTransferController::importerHubRow('example-user-names', new ExampleUserNamesImportDefinition(), true);
        $check($filaUsers['templateCsv'] !== null && str_ends_with($filaUsers['templateCsv'], 'format=csv') && str_ends_with($filaUsers['templateXlsx'], 'format=xlsx') && $filaEjemplo['templateCsv'] === null, 'a5 plantilla CSV solo si el importador la acepta', (string) json_encode([$filaUsers['templateCsv'], $filaEjemplo['templateCsv']]));
        $obligatorio = new class extends ExportDefinition {
            public function key(): string { return 'zz-obligatorio'; }
            public function title(): string { return 'Obligatorio'; }
            public function allowedUserTypes(): array { return [0]; }
            public function parameters(): array { return [ExportParameter::integer('year', 'Año')->required()]; }
            public function columns(ExportContext $context): array { return [new ExportColumn('a', 'A')]; }
            public function rows(ExportContext $context): iterable { return []; }
        };
        $sinObligatorios = DataTransferController::exporterHubRow('example-users-report', new ExampleUsersReportExportDefinition(), true);
        $conObligatorio = DataTransferController::exporterHubRow('zz-obligatorio', $obligatorio, true);
        $check($sinObligatorios['downloadXlsx'] !== null && $sinObligatorios['downloadCsv'] !== null && $conObligatorio['downloadXlsx'] === null && $conObligatorio['downloadCsv'] === null, 'a6 descargas directas solo sin filtros obligatorios');
        echoTerminal(' ');

        //─── b · Quién ve qué ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] root y administrador general');
        $apagar(['users'], ['users']);
        $como($root);
        $html = $pintar();
        $check(str_contains($html, '>Activo</th>') && str_contains($html, 'data-transfer-toggle data-kind="import" data-key="users"') && !str_contains($html, 'data-key="users" checked') && str_contains($html, '<td class="disabled">'), 'b1 root ve la columna Activo y las apagadas, en gris y con el interruptor apagado');
        $como($admin);
        $html = $pintar();
        $check(!str_contains($html, '>Activo</th>') && !str_contains($html, 'data-transfer-row="import-users"') && !str_contains($html, 'data-transfer-row="export-users"') && str_contains($html, 'No hay importadores disponibles para tu usuario.'), 'b2 el administrador general no ve la columna ni las apagadas');
        $apagar([], []);
        $check(str_contains($pintar(), 'data-transfer-row="import-users"') && !str_contains($pintar(), '>Activo</th>'), 'b3 encendidas, el administrador general las ve sin columna Activo');
        echoTerminal(' ');

        //─── c · Apagado en cada puerta ─────────────────────────────────────────────────────────────────
        echoTerminal('[c] Apagado: 403 en cada punto de entrada, también para root');
        $como($root);
        $apagar(['users'], ['users']);
        $controlador = new DataTransferController();
        //Las rutas de página usan throw403(): lanza, y el middleware de errores del framework pinta la vista 403.
        $estado = function (callable $llamada): int {
            try {
                return $llamada()->getStatusCode();
            } catch (\Slim\Exception\HttpForbiddenException $e) {
                return (int) $e->getCode();
            }
        };
        $estados = [
            'importForm' => $estado(fn() => $controlador->importForm($peticion('GET'), new ResponseRoute(), UsersImportDefinition::class)),
            'importAction' => $estado(fn() => $controlador->importAction($peticion('POST'), new ResponseRoute(), UsersImportDefinition::class, true)),
            'importTemplate' => $estado(fn() => $controlador->importTemplate($peticion('GET'), new ResponseRoute(), UsersImportDefinition::class)),
            'exportAction' => $estado(fn() => $controlador->exportAction($peticion('GET', ['format' => 'csv']), new ResponseRoute(), UsersExportDefinition::class)),
            'exportForm' => $estado(fn() => $controlador->exportForm($peticion('GET'), new ResponseRoute(), UsersExportDefinition::class)),
            'exportPreview' => $estado(fn() => $controlador->exportPreview($peticion('GET'), new ResponseRoute(), UsersExportDefinition::class)),
            'presetSave' => $estado(fn() => $controlador->presetSave($peticion('POST', [], ['presetName' => 'zz']), new ResponseRoute(), UsersExportDefinition::class)),
            'presetDelete' => $estado(fn() => $controlador->presetDelete($peticion('POST', [], ['presetName' => 'zz']), new ResponseRoute(), UsersExportDefinition::class)),
        ];
        $check(array_unique(array_values($estados)) === [403], 'c1 las ocho puertas web responden 403', (string) json_encode($estados));
        $cuerpo = json_decode((string) $controlador->exportPreview($peticion('GET'), new ResponseRoute(), UsersExportDefinition::class)->getBody(), true);
        $check(($cuerpo['error'] ?? null) === 'Este exportador está apagado.', 'c2 las rutas JSON dicen por qué', (string) json_encode($cuerpo, JSON_UNESCAPED_UNICODE));
        $csv = "{$dir}/usuarios.csv";
        if (file_put_contents($csv, "username,email,firstname,first_lastname\nzz-hub-nadie,zz-hub-nadie@example.com,Zz,Prueba\n") === false) {
            throw new \RuntimeException("No se puede escribir «{$csv}».");
        }
        [$codigo, $salida] = $terminal($csv, $root);
        $check($codigo === 1 && str_contains($salida, 'Este importador está apagado.'), 'c3 la terminal sale con 1 y el mensaje', $salida);
        $apagar([], []);
        $encendidos = [
            'importTemplate' => $controlador->importTemplate($peticion('GET'), new ResponseRoute(), UsersImportDefinition::class)->getStatusCode(),
            'exportAction' => $controlador->exportAction($peticion('GET', ['format' => 'csv']), new ResponseRoute(), UsersExportDefinition::class)->getStatusCode(),
            'exportPreview' => $controlador->exportPreview($peticion('GET'), new ResponseRoute(), UsersExportDefinition::class)->getStatusCode(),
        ];
        [$codigo, $salida] = $terminal($csv, $root);
        $check(array_unique(array_values($encendidos)) === [200] && $codigo === 0, 'c4 encendidos de nuevo, vuelven a responder (y la terminal valida)', (string) json_encode($encendidos) . " terminal {$codigo}");
        echoTerminal(' ');

        //─── d · Interruptor ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Ruta del interruptor');
        $grupo = new RouteGroup('zz-hub');
        DataTransferController::routes($grupo);
        $rutas = array_values(array_filter((array) (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo), fn($r) => $r instanceof Route && $r->name() === 'data-transfer-toggle'));
        $check(count($rutas) === 1 && $rutas[0]->method() === 'POST' && $rutas[0]->routeSegment() === '/data-transfer/toggle[/]' && $rutas[0]->rolesAllowed() === [UsersModel::TYPE_USER_ROOT] && $rutas[0]->requireLogin() === true, 'd1 POST /data-transfer/toggle, solo root y con login');
        $toggle = fn(array $body) => $controlador->toggle($peticion('POST', [], $body), new ResponseRoute());
        $malos = [
            $toggle(['kind' => 'otro', 'key' => 'users', 'enabled' => 'no'])->getStatusCode(),
            $toggle(['kind' => 'import', 'key' => 'nada', 'enabled' => 'no'])->getStatusCode(),
            $toggle(['kind' => 'export', 'key' => 'users', 'enabled' => 'quizá'])->getStatusCode(),
        ];
        $check($malos === [400, 400, 400] && !DataTransferController::isDisabled('import', 'users'), 'd2 kind, key o enabled inválidos → 400 y nada cambia');
        $r = $toggle(['kind' => 'import', 'key' => 'users', 'enabled' => 'no']);
        $check($r->getStatusCode() === 200 && json_decode((string) $r->getBody(), true) === ['kind' => 'import', 'key' => 'users', 'enabled' => false] && DataTransferController::isDisabled('import', 'users') && !DataTransferController::isDisabled('export', 'users'), 'd3 apaga, responde y guarda solo lo pedido');
        $r = $toggle(['kind' => 'import', 'key' => 'users', 'enabled' => 'yes']);
        $check($r->getStatusCode() === 200 && !DataTransferController::isDisabled('import', 'users'), 'd4 y vuelve a encender');
        $logs = LogsMapper::model();
        $logs->resetAll();
        $logs->select(['id', 'textMessageVariables'])->where(new WhereSegment([new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $maxLogPrevio)]))->execute();
        $registrados = array_filter((array) $logs->result(), fn($l) => str_contains((string) (((array) $l)['textMessageVariables'] ?? ''), 'de Importar y exportar'));
        $check(count($registrados) === 2, 'd5 cada cambio queda en el registro de acciones', (string) count($registrados));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        //La configuración vuelve a como estaba: nada queda apagado por la prueba.
        if ($existiaConfig) {
            SettingsModel::setConfigValue($config, $configPrevia);
        } else {
            $borrar = SettingsModel::model();
            $borrar->resetAll();
            $borrar->delete(new WhereSegment([WhereItem::isEqual('name', $config)]))->execute();
        }
        $logs = LogsMapper::model();
        $logs->resetAll();
        $logs->delete(new WhereSegment([new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $maxLogPrevio), WhereItem::like('textMessageVariables', '%de Importar y exportar%')]))->execute();
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        foreach (glob("{$dir}/*") ?: [] as $file) {
            //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
            @unlink($file);
        }
        //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
        @rmdir($dir);
        $repuesta = SettingsModel::optionExists($config) === $existiaConfig && json_encode(SettingsModel::getConfigValue($config)) === json_encode($configPrevia);
        $check($repuesta && !is_dir($dir), 'z1 configuración repuesta y temporales borrados', (string) json_encode(SettingsModel::getConfigValue($config)));
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Portada con pestañas y tabla por entidad; el interruptor por definición cierra todas las puertas.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
