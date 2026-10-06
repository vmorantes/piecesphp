<?php

//Filtros de exportación guardados por usuario en users.meta: reglas, fusión con el resto de meta y aislamiento entre usuarios.
//Crea usuarios zz-prueba-presets-* dentro de una transacción que se revierte: la base queda como estaba.

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\Presets\UserMetaPresetStore;
use PiecesPHP\Core\Config;
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

CliActions::make('unit-tests:core/data-transfer-export-presets', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExportPresets] Filtros guardados por usuario\e[39m");
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

    if (!class_exists('ZzExportPresetsDefinition', false)) {
        class ZzExportPresetsDefinition extends ExportDefinition
        {
            /** @var int */
            public static $level = self::INTERFACE_EXTENDED;
            public function key(): string { return 'zz-presets'; }
            public function title(): string { return 'Presets'; }
            public function allowedUserTypes(): array { return [0]; }
            public function interfaceLevel(): int { return self::$level; }
            public function parameters(): array
            {
                return [ExportParameter::integer('min', 'Mínimo', 0), ExportParameter::dateRange('created', 'Creado')];
            }
            public function columns(ExportContext $context): array
            {
                return [new ExportColumn('name', 'Nombre'), new ExportColumn('qty', 'Cantidad')];
            }
            public function rows(ExportContext $context): iterable { return []; }
        }
    }

    $peticion = fn(array $body) => (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody($body);
    $guardar = function (array $body) use ($peticion): array {
        $r = (new DataTransferController())->presetSave($peticion($body), new ResponseRoute(), ZzExportPresetsDefinition::class);
        return [$r->getStatusCode(), json_decode((string) $r->getBody(), true)];
    };
    $borrar = function (string $name) use ($peticion): array {
        $r = (new DataTransferController())->presetDelete($peticion(['presetName' => $name]), new ResponseRoute(), ZzExportPresetsDefinition::class);
        return [$r->getStatusCode(), json_decode((string) $r->getBody(), true)];
    };
    $como = function (int $id): void {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
    };
    $metaCruda = function (int $id): array {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select()->where(new WhereSegment([WhereItem::isEqual('id', $id)]))->execute();
        $raw = ((array) $m->result())[0]->meta ?? null;
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        return is_array($decoded) ? $decoded : [];
    };

    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
    $marca = 'zz-prueba-presets-' . bin2hex(random_bytes(3));

    $pdo->beginTransaction();
    try {
        $crear = function (string $sufijo, int $type) use ($marca): int {
            $u = new UsersModel();
            $u->username = "{$marca}-{$sufijo}";
            $u->email = "{$marca}-{$sufijo}@example.com";
            $u->password = password_hash('zz-Clave-1', \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->firstLastname = 'Prueba';
            $u->type = $type;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            if (!$u->save()) {
                throw new \RuntimeException("No se pudo crear el usuario {$sufijo}.");
            }
            return (int) $u->id;
        };
        $a = $crear('a', UsersModel::TYPE_USER_ROOT);
        $b = $crear('b', UsersModel::TYPE_USER_ROOT);
        //Otra clave de meta que los filtros no pueden pisar.
        $semilla = UsersModel::model();
        $semilla->resetAll();
        $semilla->update(['meta' => json_encode(['otraClave' => ['valor' => 'intacto']])])->where(new WhereSegment([WhereItem::isEqual('id', $a)]))->execute();

        //─── 1 · Guardar, sustituir y borrar ────────────────────────────────────────────────────────────
        echoTerminal('[1] Guardar, sustituir y borrar');
        $como($a);
        [$estado, $json] = $guardar(['presetName' => '  Mayo  ', 'min' => '3', 'created_from' => '2026-05-01', 'columns' => ['qty', 'name'], 'format' => 'csv', 'hack' => 'x', 'id' => (string) $b]);
        $check($estado === 200 && ($json['presets'] ?? null) === ['Mayo' => ['min' => '3', 'created_from' => '2026-05-01', 'columns' => ['qty', 'name'], 'format' => 'csv']], 'd1 guarda con el nombre recortado y solo las claves declaradas, columns y format', (string) json_encode($json, JSON_UNESCAPED_UNICODE));
        [$estado, $json] = $guardar(['presetName' => 'Mayo', 'min' => '5']);
        $check($estado === 200 && ($json['presets']['Mayo'] ?? null) === ['min' => '5'], 'd2 el mismo nombre sustituye');
        $meta = $metaCruda($a);
        $check(($meta['otraClave'] ?? null) === ['valor' => 'intacto'] && ($meta['dataTransferPresets']['zz-presets']['Mayo'] ?? null) === ['min' => '5'], 'd3 la otra clave de meta sigue intacta', (string) json_encode($meta, JSON_UNESCAPED_UNICODE));
        echoTerminal('   meta: ' . json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        [$estado, $json] = $borrar('Mayo');
        $check($estado === 200 && ($json['presets'] ?? null) === [] && ($metaCruda($a)['otraClave'] ?? null) === ['valor' => 'intacto'], 'd4 borrar lo quita y la otra clave sigue');
        echoTerminal(' ');

        //─── 2 · Reglas ─────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[2] Reglas');
        [$estado] = $guardar(['presetName' => '   ']);
        $check($estado === 400, 'd5 nombre vacío → 400');
        [$estado] = $guardar(['presetName' => str_repeat('n', 61)]);
        [$estado60] = $guardar(['presetName' => str_repeat('ñ', 60)]);
        $check($estado === 400 && $estado60 === 200, 'd6 61 caracteres → 400; 60 (multibyte) → 200');
        [$estado, $json] = $guardar(['presetName' => 'Malo', 'min' => '-1']);
        $check($estado === 400 && !array_key_exists('Malo', (new UserMetaPresetStore())->all($a, 'zz-presets')), 'd7 query inválida → 400 y no se guarda', (string) json_encode($json, JSON_UNESCAPED_UNICODE));
        [$estado] = $guardar(['presetName' => 'Col', 'columns' => ['nada']]);
        [$estadoFormato] = $guardar(['presetName' => 'Fmt', 'format' => 'pdf']);
        $check($estado === 400 && $estadoFormato === 400, 'd8 columna desconocida o formato desconocido → 400');
        for ($i = 2; $i <= 20; $i++) {
            $guardar(['presetName' => "F{$i}"]);
        }
        $cuantos = count((new UserMetaPresetStore())->all($a, 'zz-presets'));
        [$estado21] = $guardar(['presetName' => 'F21']);
        [$estadoSustituir] = $guardar(['presetName' => 'F5', 'min' => '1']);
        $check($cuantos === 20 && $estado21 === 400 && $estadoSustituir === 200, 'd9 20 como mucho: el 21 → 400; sustituir uno existente sí', (string) $cuantos);
        echoTerminal(' ');

        //─── 3 · Aislamiento ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[3] Cada usuario, los suyos');
        $como($b);
        [$estado, $json] = $guardar(['presetName' => 'De B', 'id' => (string) $a, 'userID' => (string) $a]);
        $check($estado === 200 && array_keys($json['presets'] ?? []) === ['De B'], 'd10 B no ve los de A', (string) json_encode(array_keys($json['presets'] ?? [])));
        $check(!array_key_exists('De B', (new UserMetaPresetStore())->all($a, 'zz-presets')), 'd11 el id del cuerpo se ignora: lo de B va a B');
        $borrar('F5');
        $check(array_key_exists('F5', (new UserMetaPresetStore())->all($a, 'zz-presets')), 'd12 B no borra los de A');
        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
        [$estado] = $guardar(['presetName' => 'Nadie']);
        $check($estado === 400, 'd13 sin sesión → 400');
        echoTerminal(' ');

        //─── 4 · Rutas y formulario ─────────────────────────────────────────────────────────────────────
        echoTerminal('[4] Rutas y formulario');
        $nombres = function (int $level): array {
            ZzExportPresetsDefinition::$level = $level;
            $grupo = new RouteGroup('zz-presets');
            DataTransferController::exporterRoutes($grupo, ZzExportPresetsDefinition::class, 'zz-presets', [0]);
            $rutas = array_values(array_filter((array) (new \ReflectionProperty($grupo, 'routes'))->getValue($grupo), fn($r) => $r instanceof Route));
            return array_map(fn(Route $r) => [$r->name(), $r->method(), $r->routeSegment()], $rutas);
        };
        $n2 = $nombres(ExportDefinition::INTERFACE_EXTENDED);
        $n1 = $nombres(ExportDefinition::INTERFACE_AUTO);
        ZzExportPresetsDefinition::$level = ExportDefinition::INTERFACE_EXTENDED;
        $nombres1 = array_column($n1, 0);
        $check(in_array(['data-transfer-export-zz-presets-presets-save', 'POST', '/data-transfer/export/zz-presets/presets[/]'], $n2, true) && in_array(['data-transfer-export-zz-presets-presets-delete', 'POST', '/data-transfer/export/zz-presets/presets/delete[/]'], $n2, true) && !in_array('data-transfer-export-zz-presets-presets-save', $nombres1, true) && !in_array('data-transfer-export-zz-presets-presets-delete', $nombres1, true), 'd14 rutas de filtros solo en nivel 2', (string) json_encode($n2));
        $langGroup = \DataImportExportUtility\DataImportExportUtilityLang::LANG_GROUP;
        $title = 'Presets';
        $breadcrumbs = '';
        $action = 'https://zz.test/export';
        $parameters = [];
        $presets = ['A "b" <c>' => ['min' => '1']];
        $presetSaveURL = 'https://zz/s';
        $presetDeleteURL = 'https://zz/d';
        ob_start();
        include basepath('app/classes/DataImportExportUtility/Views/data-transfer/export-form.php');
        $html = (string) ob_get_clean();
        $check(str_contains($html, 'data-transfer-presets') && str_contains($html, 'value="A &quot;b&quot; &lt;c&gt;"') && str_contains($html, 'data-query="{&quot;min&quot;:&quot;1&quot;}"') && str_contains($html, 'data-transfer-preset-save'), 'd15 el formulario pinta los filtros guardados, escapados');

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
        $restos->select()->where(new WhereSegment([WhereItem::like('username', "{$marca}%")]))->execute();
        $cuantos = count((array) $restos->result());
        $check($cuantos === 0, 'z1 la transacción se revirtió: 0 usuarios de la prueba', "restos {$cuantos}");
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Filtros de exportación guardados por usuario: reglas, fusión con el resto de meta y aislamiento.')->setEffects([CliActions::EFFECT_DATABASE])->register();
