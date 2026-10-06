<?php

//afterExport va tras generar y su fallo impide la descarga; el enlace de reimportación exige usuario con permiso.
//Escribe un usuario zz-prueba-after-* en una transacción revertida, una entrada en el log de errores y temporales propios.

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\Definitions\UsersExportDefinition;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\ExportResult;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/data-transfer-export-after', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExportAfter] Acción tras exportar y enlace de reimportación\e[39m");
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

    if (!class_exists('ZzExportAfterDefinition', false)) {
        class ZzExportAfterDefinition extends ExportDefinition
        {
            /** @var bool */
            public static $withSheetExtras = false;
            /** @var bool */
            public static $failInRows = false;
            /** @var bool */
            public static $failInAfter = false;
            /** @var array<int,array{result:ExportResult,fileExisted:bool,fileSize:int|false}> */
            public static $calls = [];
            /** @var string[] temporales de exportación vistos mientras se generaba */
            public static $seenTemp = [];
            /** @var string[] los que ya había antes: no son de la prueba */
            public static $before = [];
            public function key(): string { return 'zz-after'; }
            public function title(): string { return 'Tras exportar'; }
            public function allowedUserTypes(): array { return [0]; }
            public function columns(ExportContext $context): array
            {
                return [new ExportColumn('n', 'Nombre'), (new ExportColumn('q', 'Cantidad'))->asInteger()];
            }
            public function rows(ExportContext $context): iterable
            {
                self::$seenTemp = glob(sys_get_temp_dir() . '/' . DataTransferController::EXPORT_TEMP_PREFIX . '*') ?: [];
                yield ['n' => 'a', 'q' => 1];
                yield ['n' => 'b', 'q' => 2];
                if (self::$failInRows) {
                    throw new \RuntimeException('zz fallo a mitad de rows()');
                }
                yield ['n' => 'c', 'q' => 3];
            }
            public function sheets(ExportContext $context): array
            {
                $main = $this->mainSheet($context);
                if (self::$withSheetExtras) {
                    $main->heading('Título')->subheading('Sub')->total('q', 'SUM');
                }
                return [$main];
            }
            public function afterExport(ExportContext $context, ExportResult $result): void
            {
                //La ruta del temporal no se expone: se busca el propio entre los vistos al generar.
                $own = array_values(array_filter(array_diff(self::$seenTemp, self::$before), 'is_file'));
                self::$calls[] = ['result' => $result, 'fileExisted' => count($own) === 1, 'fileSize' => count($own) === 1 ? filesize($own[0]) : false];
                if (self::$failInAfter) {
                    throw new \RuntimeException('zz detalle interno que no debe salir');
                }
            }
        }
    }

    $exportar = function (string $format, string $class = 'ZzExportAfterDefinition') {
        $peticion = new RequestRoute('GET', (new UriFactory())->createUri("http://localhost/zz?format={$format}"), new Headers(), [], [], (new StreamFactory())->createStream(''));
        return (new DataTransferController())->exportAction($peticion, new ResponseRoute(), $class);
    };
    $reset = function (): void {
        ZzExportAfterDefinition::$withSheetExtras = false;
        ZzExportAfterDefinition::$failInRows = false;
        ZzExportAfterDefinition::$failInAfter = false;
        ZzExportAfterDefinition::$calls = [];
        ZzExportAfterDefinition::$seenTemp = [];
    };
    $antes = [];
    $propios = function () use (&$antes): array {
        return array_values(array_diff(ZzExportAfterDefinition::$seenTemp, $antes));
    };
    $foto = function () use (&$antes): void {
        $antes = glob(sys_get_temp_dir() . '/' . DataTransferController::EXPORT_TEMP_PREFIX . '*') ?: [];
        ZzExportAfterDefinition::$before = $antes;
    };

    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
    $marca = 'zz-prueba-after-' . bin2hex(random_bytes(3));

    try {
        //─── a · Se llama una vez, después de escribir ──────────────────────────────────────────────────
        echoTerminal('[a] afterExport tras generar, con el resultado correcto');
        foreach (['xlsx', 'csv'] as $format) {
            $reset();
            $foto();
            $respuesta = $exportar($format);
            $cuerpo = (string) $respuesta->getBody();
            $llamadas = ZzExportAfterDefinition::$calls;
            $r = $llamadas[0]['result'] ?? null;
            $check(count($llamadas) === 1 && $r !== null && $llamadas[0]['fileExisted'] && $llamadas[0]['fileSize'] === strlen($cuerpo), "a1 {$format}: una llamada, con el archivo ya escrito y del tamaño que se envía", (string) json_encode([count($llamadas), $llamadas[0]['fileSize'] ?? null, strlen($cuerpo)]));
            $check($r !== null && $r->format() === $format && $r->bytes() === strlen($cuerpo) && $r->rowCount() === 3 && preg_match('/^zz-after-\d{8}-\d{6}\.' . $format . '$/', $r->fileName()) === 1, "a2 {$format}: format, bytes, rowCount 3 y nombre final con extensión", $r !== null ? "{$r->format()} {$r->bytes()} {$r->rowCount()} {$r->fileName()}" : 'sin resultado');
        }
        $reset();
        ZzExportAfterDefinition::$withSheetExtras = true;
        $exportar('xlsx');
        $r = ZzExportAfterDefinition::$calls[0]['result'] ?? null;
        $check($r !== null && $r->rowCount() === 3, 'a3 rowCount no cuenta título, subtítulo, fila vacía, encabezado ni totales', $r !== null ? (string) $r->rowCount() : '');
        echoTerminal(' ');

        //─── b · rows() falla a mitad ───────────────────────────────────────────────────────────────────
        echoTerminal('[b] La generación falla');
        foreach (['xlsx', 'csv'] as $format) {
            $reset();
            $foto();
            ZzExportAfterDefinition::$failInRows = true;
            $lanzada = null;
            try {
                $exportar($format);
            } catch (\RuntimeException $e) {
                $lanzada = $e->getMessage();
            }
            $mios = $propios();
            $check($lanzada === 'zz fallo a mitad de rows()' && ZzExportAfterDefinition::$calls === [] && count($mios) === 1 && !file_exists($mios[0]), "b1 {$format}: el error sigue su curso, afterExport no se llama y el temporal propio ya no está", (string) json_encode([$lanzada, count(ZzExportAfterDefinition::$calls), $mios]));
        }
        echoTerminal(' ');

        //─── c · afterExport falla ──────────────────────────────────────────────────────────────────────
        echoTerminal('[c] afterExport falla');
        $reset();
        $foto();
        ZzExportAfterDefinition::$failInAfter = true;
        $respuesta = $exportar('csv');
        $cuerpo = (string) $respuesta->getBody();
        $json = json_decode($cuerpo, true);
        $mios = $propios();
        $check($respuesta->getStatusCode() === 500 && is_array($json) && ($json['error'] ?? null) === 'No se pudo completar la exportación. No se descargó nada.', 'c1 500 con el mensaje genérico', $respuesta->getStatusCode() . ' ' . mb_substr($cuerpo, 0, 160));
        $check(!str_contains($cuerpo, 'zz detalle interno') && !str_contains($cuerpo, 'Nombre,Cantidad') && $respuesta->getHeaderLine('Content-Disposition') === '', 'c2 sin el mensaje interno y sin el archivo');
        $check(count($mios) === 1 && !file_exists($mios[0]), 'c3 el temporal propio ya no está', (string) json_encode($mios));
        echoTerminal(' ');

        //─── e · Enlace de reimportación en el formulario ───────────────────────────────────────────────
        echoTerminal('[e] Formulario: enlace al importador');
        $pintar = function (?array $reimport): string {
            $langGroup = \DataImportExportUtility\DataImportExportUtilityLang::LANG_GROUP;
            $title = 'Usuarios';
            $breadcrumbs = '';
            $action = 'https://zz.test/export';
            $parameters = [];
            ob_start();
            include basepath('app/classes/DataImportExportUtility/Views/data-transfer/export-form.php');
            return (string) ob_get_clean();
        };
        $usuarios = new UsersExportDefinition();

        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
        $check(DataTransferController::reimportLink($usuarios) === null, 'e1 sin usuario no hay enlace (routeName() concedería)');

        $root = UsersModel::model();
        $root->resetAll();
        $root->select()->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
        $filasRoot = (array) $root->result();
        set_config('current_user', (object) ['id' => isset($filasRoot[0]->id) ? (int) $filasRoot[0]->id : 0]);
        set_config('pcsphp_current_user_stored', null);
        $enlace = DataTransferController::reimportLink($usuarios);
        $html = $pintar($enlace);
        $check($enlace !== null && str_contains($enlace['url'], '/data-transfer/import/users') && str_contains($html, 'data-transfer-reimport') && str_contains($html, 'Este archivo se puede volver a importar con') && str_contains($html, 'href="' . htmlspecialchars($enlace['url'], ENT_QUOTES) . '"'), 'e2 root: el enlace sale con la URL del importador', (string) json_encode($enlace, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $check(str_contains($pintar(['title' => 'A "b" <c>', 'url' => 'https://x/?a=1&b=2']), '“A &quot;b&quot; &lt;c&gt;”') && str_contains($pintar(['title' => 't', 'url' => 'https://x/?a=1&b=2']), 'href="https://x/?a=1&amp;b=2"'), 'e3 título y URL escapados');

        $pdo->beginTransaction();
        $general = new UsersModel();
        $general->username = "{$marca}-general";
        $general->email = "{$marca}@example.com";
        $general->password = password_hash('zz-Clave-1', \PASSWORD_DEFAULT);
        $general->firstname = 'Zz';
        $general->firstLastname = 'Prueba';
        $general->type = UsersModel::TYPE_USER_GENERAL;
        $general->status = UsersModel::STATUS_USER_ACTIVE;
        $general->failedAttempts = 0;
        $general->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $general->createdAt = new \DateTime();
        $general->modifiedAt = $general->createdAt;
        $guardado = $general->save();
        set_config('current_user', (object) ['id' => (int) $general->id]);
        set_config('pcsphp_current_user_stored', null);
        $sinPermiso = DataTransferController::reimportLink($usuarios);
        $check($guardado && getLoggedFrameworkUser() !== null && $sinPermiso === null && !str_contains($pintar($sinPermiso), 'data-transfer-reimport'), 'e4 usuario general: sin permiso, no se pinta');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        $restos = UsersModel::model();
        $restos->resetAll();
        $restos->select()->where(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([\PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::like('username', "{$marca}%")]))->execute();
        $cuantos = count((array) $restos->result());
        $check($cuantos === 0, 'z1 la transacción se revirtió: 0 usuarios de la prueba', "restos {$cuantos}");
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('afterExport se llama tras generar y un fallo suyo impide la descarga; el enlace de reimportación exige permiso.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
