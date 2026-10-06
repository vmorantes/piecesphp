<?php

//Los scripts inyectados por HTTP: guardados por la acción real, salen en su zona y su punto, y solo el principal los escribe.
//Crea dos usuarios zz-prueba-scripts-*, escribe `injected_scripts` y corre la migración; lo devuelve todo como estaba.

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Tasks\SettingsMigrateExtraScriptsTask;

CliActions::make('unit-tests:core/injected-scripts', function ($args) {

    echoTerminal("\e[33m[TEST:InjectedScripts] Los scripts inyectados salen en su zona y su punto, y solo el principal los escribe\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que session-revocation.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $ruta = function (string $nombre): ?string {
        $url = get_route($nombre, [], true);
        if (!is_string($url) || $url === '') {
            return null;
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $nombres = [
        'vista' => 'configurations-integrations-scripts',
        'accion' => 'configurations-integrations-scripts-save',
        'panel' => 'configurations-index',
        'acceso' => 'users-form-login',
    ];

    echoTerminal('[canario] Lo que esta prueba da por hecho');
    $check($base !== '', 'c1 hay una base con la que pedir');
    //Se resuelven UNA vez: una ruta vacía pediría la portada, y un 200 de la portada no prueba nada.
    $rutas = [];
    foreach ($nombres as $clave => $nombre) {
        $rutas[$clave] = (string) $ruta($nombre);
        $check($rutas[$clave] !== '' && $rutas[$clave] !== '/', "c2 existe la ruta {$nombre}", $rutas[$clave]);
    }
    if ($failed > 0) {
        return $balance();
    }
    echoTerminal(' ');

    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $metodo, string $path, ?string $jwt = null, array $datos = []) use ($base, $cabeceraToken): array {
        $cabeceras = $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [];
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cabeceras,
        ]);
        if ($metodo === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    $token = fn(int $id): string => SessionToken::generateToken(['id' => $id], null, null, false);
    $guardado = function (): mixed {
        return SettingsModel::getConfigValue(ExtraScripts::CONFIG_NAME);
    };
    //Lo que queda entre dos puntos del documento, sin espacios: vacío es «justo antes» o «justo después».
    $entre = function (string $html, string $desde, string $hasta): ?string {
        $a = mb_strpos($html, $desde);
        $b = $a !== false ? mb_strpos($html, $hasta, $a + mb_strlen($desde)) : false;
        return $a !== false && $b !== false ? trim(mb_substr($html, $a + mb_strlen($desde), $b - $a - mb_strlen($desde))) : null;
    };
    $delimitado = fn(string $marca): string => "<!-- Extra scripts -->\r\n<script>/*{$marca}*/</script>\r\n<!-- Close Extra scripts -->";

    $prefijo = 'zz-prueba-scripts-' . bin2hex(random_bytes(3));
    $crear = function (string $sufijo, int $tipo) use ($prefijo): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Prueba';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        return (int) $u->id;
    };

    $existia = SettingsModel::optionExists(ExtraScripts::CONFIG_NAME);
    $previo = $guardado();
    $viejaExistia = SettingsModel::optionExists(SettingsMigrateExtraScriptsTask::OLD_OPTION);
    $ids = [];

    try {
        $ids['root'] = $crear('root', UsersModel::TYPE_USER_ROOT);
        $ids['admin'] = $crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL);
        $root = $token($ids['root']);
        $admin = $token($ids['admin']);
        $check($ids['root'] > 0 && $ids['admin'] > 0, 'banco: un principal y un administrador general de la prueba');
        $check($pedir('GET', $rutas['vista'], $root)['status'] === 200, 'banco: el principal abre la pantalla (sin él, todo 403 sería gratis)');
        echoTerminal(' ');

        //─── A · Guardadas por la acción real, salen donde toca ─────────────────────────────────
        echoTerminal('[A] Cada entrada, en su zona y su punto');
        $entradas = [
            ['label' => 'zz panel final', 'zone' => 'panel', 'position' => 'body_end', 'active' => true, 'code' => '<script>/*zz-cf-panel-end*/</script>'],
            ['label' => 'zz público principio', 'zone' => 'public', 'position' => 'body_start', 'active' => true, 'code' => '<script>/*zz-cf-public-start*/</script>'],
            ['label' => 'zz inactiva', 'zone' => 'both', 'position' => 'head', 'active' => false, 'code' => '<script>/*zz-cf-off*/</script>'],
            ['label' => 'zz ambas cabecera', 'zone' => 'both', 'position' => 'head', 'active' => true, 'code' => '<script>/*zz-cf-both-head*/</script>'],
        ];
        $r = $pedir('POST', $rutas['accion'], $root, ['entries' => json_encode($entradas)]);
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) === true, 'a0 el principal guarda la lista por la acción', "HTTP {$r['status']} " . mb_substr($r['body'], 0, 160));
        $check(is_array($guardado()) && count((array) $guardado()) === 4, 'a0 y queda guardada entera', (string) json_encode($guardado()));
        $panel = $pedir('GET', $rutas['panel'], $root)['body'];
        $portada = $pedir('GET', '/')['body'];
        $acceso = $pedir('GET', $rutas['acceso'])['body'];
        $check(str_contains($panel, '</body>') && str_contains($portada, '</body>') && str_contains($acceso, '</body>'), 'a1 banco: el panel, la portada y el acceso responden con su documento entero');
        $antesDeCerrar = $entre($panel, '/*zz-cf-panel-end*/</script>', '</body>');
        $check($antesDeCerrar === '<!-- Close Extra scripts -->', 'a2 panel·body_end aparece en el panel JUSTO antes de </body>', var_export($antesDeCerrar, true));
        $check(!str_contains($portada, 'zz-cf-panel-end'), 'a3 y NO en la portada');
        $trasAbrir = preg_match('/<body[^>]*>\s*' . preg_quote($delimitado('zz-cf-public-start'), '/') . '/', $portada) === 1;
        $check($trasAbrir, 'a4 public·body_start aparece en la portada JUSTO tras <body>');
        $check(!str_contains($panel, 'zz-cf-public-start'), 'a5 y NO en el panel');
        $check(!str_contains($panel . $portada . $acceso, 'zz-cf-off'), 'a6 una inactiva, en ninguna');
        $cabeceraPanel = $entre($panel, '<head>', '</head>') ?? '';
        $cabeceraPortada = $entre($portada, '<head>', '</head>') ?? '';
        $check(str_contains($cabeceraPanel, 'zz-cf-both-head') && str_contains($cabeceraPortada, 'zz-cf-both-head'), 'a7 both·head en la cabecera del panel y en la de la portada');
        $check(!str_contains($acceso, 'zz-cf-'), 'a8 y NADA en la pantalla de acceso: ahí un script leería la contraseña');
        echoTerminal(' ');

        //─── B · Solo el principal ──────────────────────────────────────────────────────────────
        echoTerminal('[B] El administrador general no entra ni escribe');
        $antes = json_encode($guardado());
        $check($pedir('GET', $rutas['vista'], $admin)['status'] === 403, 'b1 rol 1: 403 en la vista');
        $rb = $pedir('POST', $rutas['accion'], $admin, ['entries' => json_encode([])]);
        $check($rb['status'] === 403 && json_encode($guardado()) === $antes, 'b2 rol 1: 403 en la acción, y nada escrito', "HTTP {$rb['status']}");
        echoTerminal(' ');

        //─── C · Lo inválido no se escribe ──────────────────────────────────────────────────────
        echoTerminal('[C] Un valor inválido: 400 y nada escrito');
        $invalidas = [
            'zona inventada' => [['label' => 'zz', 'zone' => 'otra', 'position' => 'head', 'active' => true, 'code' => '']],
            'punto inventado' => [['label' => 'zz', 'zone' => 'panel', 'position' => 'footer', 'active' => true, 'code' => '']],
            'rótulo vacío' => [['label' => '  ', 'zone' => 'panel', 'position' => 'head', 'active' => true, 'code' => '']],
            'activa que no es booleana' => [['label' => 'zz', 'zone' => 'panel', 'position' => 'head', 'active' => 'sí', 'code' => '']],
            'una buena y una mala' => [$entradas[0], ['label' => 'zz', 'zone' => 'x', 'position' => 'head', 'active' => true, 'code' => '']],
        ];
        foreach ($invalidas as $nombre => $lista) {
            $rc = $pedir('POST', $rutas['accion'], $root, ['entries' => json_encode($lista)]);
            $check($rc['status'] === 400 && json_encode($guardado()) === $antes, "c {$nombre}: 400 y nada escrito", "HTTP {$rc['status']}");
        }
        $rc = $pedir('POST', $rutas['accion'], $root, ['entries' => '{no es json']);
        $check($rc['status'] === 400 && json_encode($guardado()) === $antes, 'c JSON roto: 400 y nada escrito', "HTTP {$rc['status']}");
        echoTerminal(' ');

        //─── D · La migración, dos veces ────────────────────────────────────────────────────────
        echoTerminal('[D] settings-migrate-extra-scripts dos veces seguidas: una sola entrada');
        $tarea = function () use ($proyecto): array {
            $salida = [];
            $codigo = -1;
            //RETORNO-IGNORADO: el resultado va en $salida y $codigo; el retorno es solo la última línea.
            exec(escapeshellarg("{$proyecto}/bin/cli") . ' settings-migrate-extra-scripts 2>&1', $salida, $codigo);
            return [$codigo, (string) preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $salida))];
        };
        $codigoMigrado = '<script>/*zz-cf-migrada*/</script>';
        SettingsModel::setConfigValue(SettingsMigrateExtraScriptsTask::OLD_OPTION, $codigoMigrado);
        [$c1, $s1] = $tarea();
        [$c2, $s2] = $tarea();
        $migradas = array_filter(array_map(fn($e) => ExtraScripts::normalizeEntry($e), (array) $guardado()), fn($e) => ($e['code'] ?? null) === $codigoMigrado);
        $check($c1 === 0 && $c2 === 0, 'd1 las dos terminan con 0', "{$c1} / {$c2}");
        $check(count($migradas) === 1 && (array_values($migradas)[0]['active'] ?? null) === true, 'd2 una sola entrada, activa', (string) json_encode(array_values($migradas)));
        $check(!SettingsModel::optionExists(SettingsMigrateExtraScriptsTask::OLD_OPTION), 'd3 y la opción vieja retirada');
        $check(str_contains($s2, 'nada que migrar'), 'd4 la segunda lo dice', $s2);

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[Z] Limpieza');
        try {
            if ($existia) {
                SettingsModel::setConfigValue(ExtraScripts::CONFIG_NAME, $previo);
            } else {
                SettingsModel::model()->delete(['name' => ExtraScripts::CONFIG_NAME])->execute();
            }
            if (!$viejaExistia) {
                SettingsModel::model()->delete(['name' => SettingsMigrateExtraScriptsTask::OLD_OPTION])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            $deLaPrueba = array_map(fn($u) => (int) $u->id, (array) $usuarios->result());
            foreach ($deLaPrueba as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                $perfiles->delete(['belongsTo' => $id])->execute();
                //`SystemApprovalManager::init()` anota cada usuario nuevo al servir: esa fila no cae con él.
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
            }
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            echoTerminal('      - ' . count($deLaPrueba) . " usuarios {$prefijo}-*; " . ExtraScripts::CONFIG_NAME . ' ' . ($existia ? 'restaurada' : 'retirada'));
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(
            count((array) $usuarios->result()) === 0 && SettingsModel::optionExists(ExtraScripts::CONFIG_NAME) === $existia
            && json_encode($guardado()) === json_encode($previo) && SettingsModel::optionExists(SettingsMigrateExtraScriptsTask::OLD_OPTION) === $viejaExistia,
            'z1 limpieza: 0 usuarios de la prueba, y injected_scripts y extra_scripts como estaban'
        );
    }

    return $balance();

})->setDescription('Los scripts inyectados por HTTP: zona y punto en el panel, la portada y el acceso; solo el principal; 400 sin escribir; la migración dos veces.')->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_DATABASE])->register();
