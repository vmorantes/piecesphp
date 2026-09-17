<?php

//Exportadores con ruta propia: el de usuarios lleva las columnas del importador y su archivo se puede volver a importar.
//Escribe usuarios zz-prueba-exp-* (y 501 en una transacción revertida) y los borra en el finally: db-backup antes.

use App\Model\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use DataImportExportUtility\Definitions\UsersExportDefinition;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
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

CliActions::make('unit-tests:core/data-transfer-export', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExport] Exportadores sobre DataTransfer\e[39m");
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

    if (!class_exists('ZzDataTransferExportDefinition', false)) {
        class ZzDataTransferExportDefinition extends ExportDefinition
        {
            public function key(): string { return 'zz-export'; }
            public function title(): string { return 'Prueba'; }
            public function allowedUserTypes(): array { return [0]; }
            public function columns(): array { return [new ExportColumn('code', 'Código')]; }
            public function rows(): iterable { return [['code' => 'A1']]; }
        }
    }

    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-prueba-exp-{$marca}";
    $temporales = [];
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);

    $crear = function (string $sufijo, string $nombre = 'Zz') use ($prefijo): void {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        $u->password = 'zz-no-es-un-hash';
        $u->firstname = $nombre;
        $u->first_lastname = 'Prueba';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failed_attempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->created_at = new \DateTime();
        $u->modified_at = $u->created_at;
        if (!$u->save()) {
            throw new \RuntimeException("No se creó {$u->username}");
        }
    };
    $delPrefijo = function (string $patron): array {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select()->where(new WhereSegment([WhereItem::like('username', "{$patron}%")]))->execute();
        return array_values((array) $m->result());
    };
    $borrar = function (string $patron) use ($delPrefijo): void {
        $ids = array_map(fn($u) => (int) $u->id, $delPrefijo($patron));
        if (count($ids) === 0) {
            return;
        }
        $perfiles = UserProfileMapper::model();
        $perfiles->resetAll();
        $perfiles->delete(new WhereSegment([new WhereItem('belongsTo', WhereItem::IN_OPERATOR, '(' . implode(',', $ids) . ')')]))->execute();
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$patron}%")]))->execute();
    };
    $exportar = function (string $format) {
        $peticion = new RequestRoute('GET', (new UriFactory())->createUri("http://localhost/zz?format={$format}"), new Headers(), [], [], (new StreamFactory())->createStream(''));
        return (new DataTransferController())->exportAction($peticion, new ResponseRoute(), UsersExportDefinition::class);
    };
    $aArchivo = function (string $contenido, string $extension) use (&$temporales): string {
        $ruta = sys_get_temp_dir() . '/zz-data-transfer-export-' . bin2hex(random_bytes(4)) . '.' . $extension;
        $temporales[] = $ruta;
        if (file_put_contents($ruta, $contenido) === false) {
            throw new \RuntimeException("No se pudo escribir {$ruta}");
        }
        return $ruta;
    };
    $etiquetasImportador = array_values(array_map(fn($c) => $c->label(), array_filter((new UsersImportDefinition())->columns(), fn($c) => $c->key() !== 'password')));

    try {
        //─── 1/4 · Registro ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[1/4] DataImportExportUtilityRoutes::exporter()');
        $grupo = new RouteGroup('zz-data-transfer-export');
        DataImportExportUtilityRoutes::exporter($grupo, ZzDataTransferExportDefinition::class);
        $rutas = array_values(array_filter((array) (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo), fn($r) => $r instanceof Route));
        $check(count($rutas) === 1 && $rutas[0]->name() === 'data-transfer-export-zz-export' && $rutas[0]->method() === 'GET' && $rutas[0]->routeSegment() === '/data-transfer/export/zz-export[/]' && $rutas[0]->rolesAllowed() === [0], 'e1 registra una ruta GET con su nombre, URL y roles');
        $check(array_key_exists('zz-export', DataImportExportUtilityRoutes::exporters()) && array_key_exists('users', DataImportExportUtilityRoutes::exporters()), 'e2 queda en el registro, junto al de usuarios');
        foreach ([['e3 una clase que no extiende ExportDefinition', \stdClass::class], ['e4 una key ya registrada', ZzDataTransferExportDefinition::class]] as [$nombre, $clase]) {
            try {
                DataImportExportUtilityRoutes::exporter(new RouteGroup('zz-data-transfer-export-2'), $clase);
                $check(false, "{$nombre} → InvalidArgumentException", 'no lanzó');
            } catch (\InvalidArgumentException $e) {
                $check(true, "{$nombre} → InvalidArgumentException");
            }
        }
        echoTerminal(' ');

        //─── 2/4 · Exportación de usuarios ──────────────────────────────────────────────────────────────
        echoTerminal('[2/4] Exportación de usuarios en CSV y XLSX');
        $crear('uno');
        foreach (['csv', 'xlsx'] as $formato) {
            $respuesta = $exportar($formato);
            $ruta = $aArchivo((string) $respuesta->getBody(), $formato);
            $fuente = SpreadsheetRowSource::fromFile($ruta, $formato);
            $usuarios = [];
            foreach ($fuente->rows() as $fila) {
                $usuarios[] = $fila[0] ?? null;
            }
            $check($respuesta->getStatusCode() === 200 && $fuente->headers() === $etiquetasImportador, "x1 {$formato}: las cabeceras son las etiquetas del importador sin la contraseña", json_encode($fuente->headers(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $check(in_array("{$prefijo}-uno", $usuarios, true) && str_contains($respuesta->getHeaderLine('Content-Disposition'), "users-") && $respuesta->getHeaderLine('Cache-Control') === 'no-store', "x2 {$formato}: contiene el usuario de la prueba, con nombre users-… y no-store");
        }
        $check($exportar('pdf')->getStatusCode() === 400, 'x3 format=pdf → 400');
        $borrar($prefijo);
        echoTerminal(' ');

        //─── 3/4 · Paginación ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[3/4] Paginación: 501 usuarios en una transacción revertida');
        $pdo->beginTransaction();
        try {
            for ($i = 1; $i <= 501; $i++) {
                $crear("p{$i}");
            }
            $contados = 0;
            $total = 0;
            foreach ((new UsersExportDefinition())->rows() as $fila) {
                $total++;
                if (str_starts_with((string) $fila['username'], "{$prefijo}-p")) {
                    $contados++;
                }
            }
            $check($contados === 501 && $total > 500, 'p1 con páginas de 500 salen los 501 de la prueba', "prueba {$contados}, total {$total}");
        } finally {
            $pdo->rollBack();
        }
        $check(count($delPrefijo("{$prefijo}-p")) === 0, 'p2 y la transacción los retiró');
        echoTerminal(' ');

        //─── 4/4 · Ida y vuelta ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[4/4] Ida y vuelta: exportar, borrar e importar el CSV');
        $root = UsersModel::model();
        $root->resetAll();
        $root->select()->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
        $idRoot = (int) (((array) $root->result())[0]->id ?? 0);
        set_config('current_user', (object) ['id' => $idRoot]);
        set_config('pcsphp_current_user_stored', null);
        //El «+» del nombre lo neutraliza el exportador CSV: si el lector no lo deshace, vuelve como «'+Zz».
        $crear('iv1', '+Zz');
        $crear('iv2');
        $csv = (string) $exportar('csv')->getBody();
        //El export trae todos los usuarios; los que no son de la prueba darían «ya existe» al reimportar.
        $lineas = preg_split('/\r?\n/', $csv) ?: [];
        $propias = array_filter($lineas, fn($l, $i) => $i === 0 || str_contains($l, "{$prefijo}-iv"), ARRAY_FILTER_USE_BOTH);
        $borrar($prefijo);
        $check(count($delPrefijo("{$prefijo}-iv")) === 0 && count($propias) === 3, 'v1 exportados y borrados los 2 usuarios de la prueba');
        $informe = (new ImportRunner())->run(new UsersImportDefinition(), SpreadsheetRowSource::fromFile($aArchivo(implode("\n", $propias) . "\n", 'csv'), 'csv'));
        $recreados = $delPrefijo("{$prefijo}-iv");
        $artefacto = $informe->artifacts();
        $check($informe->persisted() && count($recreados) === 2 && $artefacto !== null && substr_count($artefacto->content(), 'class="card"') === 2, 'v2 el CSV exportado se importa: 2 recreados, con credenciales generadas', json_encode($informe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $nombres = array_map(fn($u) => $u->firstname, $delPrefijo("{$prefijo}-iv1"));
        $check($nombres === ['+Zz'], "v2b el nombre «+Zz» vuelve igual, sin la «'» del exportador", json_encode($nombres, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        //Por XLSX: se exporta y se relee con el lector, dejando solo las filas de la prueba, como en el CSV.
        $xlsx = $aArchivo((string) $exportar('xlsx')->getBody(), 'xlsx');
        $fuenteXlsx = SpreadsheetRowSource::fromFile($xlsx, 'xlsx');
        $filasPropias = [];
        foreach ($fuenteXlsx->rows() as $filaXlsx) {
            if (str_starts_with((string) ($filaXlsx[0] ?? ''), "{$prefijo}-iv")) {
                $filasPropias[] = $filaXlsx;
            }
        }
        $borrar($prefijo);
        $informeXlsx = (new ImportRunner())->run(new UsersImportDefinition(), new \PiecesPHP\Core\DataTransfer\Source\ArrayRowSource($fuenteXlsx->headers(), $filasPropias));
        $check(count($filasPropias) === 2 && $informeXlsx->persisted() && count($delPrefijo("{$prefijo}-iv")) === 2, 'v3 por XLSX también: 2 filas propias, borradas y recreadas', json_encode($informeXlsx, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        $borrar($prefijo);
        foreach ($temporales as $ruta) {
            if (is_file($ruta)) {
                //RETORNO-IGNORADO: limpieza del banco de la suite; se comprueba abajo con is_file.
                unlink($ruta);
            }
        }
        $restos = count($delPrefijo('zz-prueba-exp-'));
        $check($restos === 0 && count(array_filter($temporales, 'is_file')) === 0, 'z1 limpieza: 0 usuarios zz-prueba-exp-* y 0 temporales', "restos {$restos}");
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los exportadores registran su ruta; el de usuarios exporta las columnas del importador, pagina y se puede reimportar.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
