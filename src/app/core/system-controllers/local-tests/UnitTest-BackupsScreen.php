<?php

//La pantalla de respaldos: solo el principal la abre y la guarda, y una política inválida no escribe nada.
//Crea dos usuarios zz-agente-backups-* y devuelve la opción `backup_policy` como estaba.

use PiecesPHP\Core\Backups\BackupPolicy;
use PiecesPHP\Core\Backups\BackupRotation;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/backups-screen', function ($args) {

    echoTerminal("\e[33m[TEST:BackupsScreen] La pantalla de respaldos: solo el principal, y lo inválido no escribe\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que injected-scripts.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $check($base !== '', 'c1 hay una base HTTP para pedir', $base);
    if ($base === '') {
        return $balance();
    }

    $ruta = function (string $nombre): ?string {
        $url = get_route($nombre, [], true);
        if (!is_string($url) || $url === '') {
            return null;
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $nombres = [
        'vista' => 'configurations-system-backups',
        'accion' => 'configurations-system-backups-save',
    ];
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
    $guardado = fn(): mixed => SettingsModel::getConfigValue(BackupPolicy::CONFIG_NAME);

    $prefijo = 'zz-agente-backups-' . bin2hex(random_bytes(3));
    $crear = function (string $sufijo, int $tipo) use ($prefijo): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
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

    $existia = SettingsModel::optionExists(BackupPolicy::CONFIG_NAME);
    $previo = $guardado();
    $ids = [];

    try {
        $ids['root'] = $crear('root', UsersModel::TYPE_USER_ROOT);
        $ids['admin'] = $crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL);
        $root = $token($ids['root']);
        $admin = $token($ids['admin']);
        $check($ids['root'] > 0 && $ids['admin'] > 0, 'banco: un principal y un administrador general de la prueba');

        //─── A · Solo el principal ──────────────────────────────────────────────────────────────
        echoTerminal('[A] Solo el usuario principal entra');
        $comoRoot = $pedir('GET', $rutas['vista'], $root);
        $check($comoRoot['status'] === 200, 'a1 el principal abre la pantalla (sin esto, todo 403 sería gratis)', "HTTP {$comoRoot['status']}");
        $check(str_contains($comoRoot['body'], 'data-backups-view'), 'a2 y la pantalla es la de respaldos');

        $comoAdmin = $pedir('GET', $rutas['vista'], $admin);
        $check($comoAdmin['status'] === 403, 'a3 un administrador general recibe 403 en la vista', "HTTP {$comoAdmin['status']}");
        $sinEntrar = $pedir('GET', $rutas['vista']);
        $check($sinEntrar['status'] !== 200, 'a4 sin sesión no se abre', "HTTP {$sinEntrar['status']}");

        $politicaValida = array_merge(BackupPolicy::defaults(), ['keep_recent' => 7, 'keep_daily' => 14]);
        $guardarComoAdmin = $pedir('POST', $rutas['accion'], $admin, ['policy' => json_encode($politicaValida)]);
        $check($guardarComoAdmin['status'] === 403, 'a5 un administrador general recibe 403 en el guardado', "HTTP {$guardarComoAdmin['status']}");
        $check($guardado() == $previo, 'a6 y la opción guardada no cambió');

        //─── B · Lo inválido no escribe ─────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[B] Una política inválida da 400 y no escribe nada');
        $antes = $guardado();
        $invalidas = [
            'keep_recent = 0' => array_merge(BackupPolicy::defaults(), ['keep_recent' => 0]),
            'una tabla que no existe' => array_merge(BackupPolicy::defaults(), ['data_excluded_tables' => ['zz_no_existe_esta_tabla']]),
            'intervalo por debajo del mínimo' => array_merge(BackupPolicy::defaults(), ['interval_minutes' => BackupPolicy::MIN_INTERVAL_MINUTES - 1]),
            'enabled con texto' => array_merge(BackupPolicy::defaults(), ['enabled' => 'yes']),
        ];
        foreach ($invalidas as $motivo => $politica) {
            $r = $pedir('POST', $rutas['accion'], $root, ['policy' => json_encode($politica)]);
            $check($r['status'] === 400, "b1 {$motivo}: 400", "HTTP {$r['status']} " . mb_substr($r['body'], 0, 120));
            $check($guardado() == $antes, "b2 {$motivo}: la opción guardada sigue igual");
        }

        //─── C · Lo válido sí escribe ───────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[C] Una política válida se guarda y es la que rige');
        $r = $pedir('POST', $rutas['accion'], $root, ['policy' => json_encode($politicaValida)]);
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) === true, 'c3 el principal guarda la política', "HTTP {$r['status']} " . mb_substr($r['body'], 0, 160));

        //Se lee de la BASE y se normaliza, que es lo que hará `current()` en el proceso siguiente:
        //este proceso de terminal arrancó ANTES del guardado y `get_config()` sirve lo cargado entonces.
        $guardadoAhora = BackupPolicy::normalize($guardado());
        $check(is_array($guardadoAhora) && $guardadoAhora['keep_recent'] === 7 && $guardadoAhora['keep_daily'] === 14, 'c4 lo que quedó en la base es la política enviada, y normaliza bien', (string) json_encode($guardadoAhora));

        //Y de punta a punta: otra petición (otro proceso) enseña ya el valor nuevo en el formulario.
        $htmlTrasGuardar = $pedir('GET', $rutas['vista'], $root)['body'];
        $check(str_contains($htmlTrasGuardar, 'data-backup-field="keep_recent"') && preg_match('/data-backup-field="keep_recent"\s*\n?\s*value="7"/', $htmlTrasGuardar) === 1, 'c4b la pantalla recargada trae keep_recent = 7', 'no se encontró el valor 7 en el campo');

        //Las tablas que existen sí se aceptan: si no, b1 pasaría por el motivo equivocado.
        $conTabla = array_merge($politicaValida, ['data_excluded_tables' => [UsersModel::TABLE]]);
        $r = $pedir('POST', $rutas['accion'], $root, ['policy' => json_encode($conTabla)]);
        $check($r['status'] === 200, 'c5 una tabla que SÍ existe se acepta: ' . UsersModel::TABLE, "HTTP {$r['status']} " . mb_substr($r['body'], 0, 120));
        $conTablaGuardada = BackupPolicy::normalize($guardado());
        $check(is_array($conTablaGuardada) && in_array(UsersModel::TABLE, $conTablaGuardada['data_excluded_tables'], true), 'c6 y queda en las tablas sin filas', (string) json_encode($conTablaGuardada['data_excluded_tables'] ?? null));

        //─── D · La pantalla enseña el plan ─────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[D] La pantalla enseña el estado y el plan de conservación');
        $html = $pedir('GET', $rutas['vista'], $root)['body'];
        $check(str_contains($html, 'data-backup-plan'), 'd1 la pantalla trae el plan de conservación');
        $check(str_contains($html, 'data-backup-field="interval_minutes"'), 'd2 y el formulario de la política');
        $check(str_contains($html, 'data-backup-table'), 'd3 y las casillas de las tablas');

        //Los números de la pantalla salen del mismo plan que `bin/cli db-backup-rotate`: si
        //divergieran, quien administra decidiría con una cifra que no es la que se va a aplicar.
        $planReal = BackupRotation::plan(BackupRotation::namesIn(BackupPolicy::dumpsDirectory()), BackupPolicy::normalize($guardado()) ?? BackupPolicy::defaults());
        $esperado = sprintf('se conservarían %d respaldo(s) y se borrarían %d', count($planReal['keep']), count($planReal['delete']));
        $check(str_contains($html, $esperado), 'd4 las cifras de la pantalla son las del plan de verdad', $esperado);
        $check(str_contains($html, sprintf('Otros %d no se tocan nunca', count($planReal['ignored']))), 'd5 y los ignorados también');

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    //─── z · Limpieza ───────────────────────────────────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[z] Limpieza: los usuarios y la opción, como estaban');
    try {
        if ($existia) {
            SettingsModel::setConfigValue(BackupPolicy::CONFIG_NAME, $previo);
        } else {
            //RETORNO-IGNORADO: z1 comprueba que la opción quedó como estaba.
            SettingsModel::model()->delete(['name' => BackupPolicy::CONFIG_NAME])->execute();
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $deLaPrueba = array_map(fn($u) => (int) $u->id, (array) $usuarios->result());
        foreach ($deLaPrueba as $id) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
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
        //RETORNO-IGNORADO: lo comprueba z2 contando los usuarios que quedan.
        $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        echoTerminal('      - ' . count($deLaPrueba) . " usuarios {$prefijo}-*; " . BackupPolicy::CONFIG_NAME . ' ' . ($existia ? 'restaurada' : 'retirada'));
    } catch (\Throwable $e) {
        $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    $usuarios = UsersModel::model();
    $usuarios->resetAll();
    $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
    $check(count((array) $usuarios->result()) === 0, 'z2 no queda ningún usuario de la prueba');
    $check(
        SettingsModel::optionExists(BackupPolicy::CONFIG_NAME) === $existia && json_encode($guardado()) === json_encode($previo),
        'z1 la opción backup_policy quedó como estaba',
        'existía ' . ($existia ? 'sí' : 'no') . ', ahora ' . (SettingsModel::optionExists(BackupPolicy::CONFIG_NAME) ? 'sí' : 'no')
    );

    return $balance();

})->setDescription('La pantalla de respaldos: 403 para quien no es el principal en la vista y en el guardado, 400 sin escribir con una política inválida, y lo válido guardado y vigente.')->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_DATABASE])->register();
