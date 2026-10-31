<?php

//P133 en Registros › Accesos y en el registro de acciones: root ve todo; los demás, lo suyo y lo de rangos menores (el de
//organización, de la suya); en acciones cuenta quien actuó de verdad. Por HTTP. Crea y borra todo lo que siembra.
//@areas src/app/classes/EventsLog/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/SystemApprovals/

use EventsLog\Controllers\LogsController as ActionsLogController;
use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\LoginAttemptsController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/logs-hierarchy', function ($args) {

    echoTerminal("\e[33m[TEST:LogsHierarchy] Accesos y acciones: cada uno ve lo suyo y lo de rangos menores (P133)\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'c1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $ruta = function (string $url): string {
        if ($url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $sinPrefijo = fn(string $path): string => $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    $acciones = $sinPrefijo($ruta(ActionsLogController::routeName('datatables', [], true)));
    $intentos = $sinPrefijo($ruta(LoginAttemptsController::routeName('reports-ajax', ['type' => 'attempts'], true)));
    $ingresos = $sinPrefijo($ruta(LoginAttemptsController::routeName('reports-ajax', ['type' => 'logged'], true)));
    $check($acciones !== '' && $intentos !== '' && $ingresos !== '' && $ruta(ActionsLogController::routeName('detail', ['id' => 1], true)) !== '', 'c2 existen los listados de acciones, intentos e ingresos y el detalle', "{$acciones} · {$intentos}");
    if ($failed > 0) {
        return $balance();
    }

    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    $listado = fn(string $path, string $jwt): array => $pedir("{$path}?draw=1&start=0&length=5000", $jwt);

    $prefijo = 'zz-agente-jerarquia-' . bin2hex(random_bytes(3));
    $marca = 'zz-jerarquia-' . bin2hex(random_bytes(4));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaAcciones = LogsMapper::TABLE;
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $usuarios = [];
    $accionDe = [];
    $organizacion = 0;
    $crear = function (string $sufijo, int $tipo, int $org) use ($prefijo, &$usuarios): int {
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
        $u->organization = $org;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $plantar = function (int $createdBy, string $texto, array $meta = []) use ($database, $tablaAcciones): int {
        $database->prepare("INSERT INTO `{$tablaAcciones}` (textMessage, textMessageVariables, createdBy, createdAt, meta) VALUES (?, ?, ?, ?, ?)")
            ->execute([LogsMapper::MESSAGES[LogsMapper::MSG_GENERIC], json_encode(['%message%' => $texto]), $createdBy, date('Y-m-d H:i:s'), json_encode((object) $meta)]);
        return (int) $database->lastInsertId();
    };
    //Qué marcas de la prueba trae una respuesta: los sufijos, en orden.
    $vistos = function (string $body, array $sufijos, string $prefijoMarca): array {
        $encontrados = [];
        foreach ($sufijos as $sufijo) {
            if (str_contains($body, "{$prefijoMarca}-{$sufijo}\"") || str_contains($body, "{$prefijoMarca}-{$sufijo}<") || str_contains($body, "{$prefijoMarca}-{$sufijo}\\u")) {
                $encontrados[] = $sufijo;
            }
        }
        return $encontrados;
    };
    $todos = ['root', 'general-1', 'general-2', 'org-1', 'org-2', 'usuario'];

    try {
        $idRoot = $crear('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $crear('general-1', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $crear('general-2', UsersModel::TYPE_USER_ADMIN_GRAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-org", 'zz-', "{$prefijo}-org", date('Y-m-d H:i:s'), $idRoot, 1, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $organizacion = (int) $database->lastInsertId();
        $crear('org-1', UsersModel::TYPE_USER_ADMIN_ORG, $organizacion);
        $crear('org-2', UsersModel::TYPE_USER_ADMIN_ORG, $organizacion);
        $crear('usuario', UsersModel::TYPE_USER_GENERAL, $organizacion);
        $tokens = [];
        foreach ($usuarios as $sufijo => $id) {
            $tokens[$sufijo] = UserSessions::issueToken($id);
            $database->prepare("INSERT INTO `{$tablaIntentos}` (userID, usernameAttempt, success, ip, message, date) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$id, "{$marca}-{$sufijo}", LoginAttemptsModel::SUCCESS_ATTEMPT, '0.0.0.0', 'zz-', date('Y-m-d H:i:s')]);
            $accionDe[$sufijo] = $plantar($id, "{$marca}-{$sufijo}");
        }
        //Root actuando en nombre del de organización: createdBy es org-1, pero quien actuó fue root.
        $accionDe['suplantada'] = $plantar($usuarios['org-1'], "{$marca}-suplantada", [LogsMapper::META_ACTOR => ['kind' => LogsMapper::ACTOR_IMPERSONATION, 'id' => $idRoot, 'onBehalfOf' => $usuarios['org-1']]]);
        $check($organizacion > 0 && count($usuarios) === 6 && count($accionDe) === 7, 'banco: una organización, seis usuarios (root, dos generales, dos de organización, uno normal), sus intentos y sus acciones');

        //─── a · Intentos de acceso ───────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] Intentos de acceso');
        $deRoot = $listado($intentos, $tokens['root']);
        $check($deRoot['status'] === 200 && $vistos($deRoot['body'], $todos, $marca) === $todos, 'a1 CANARIO: root ve los seis', implode(',', $vistos($deRoot['body'], $todos, $marca)));
        $deGeneral = $listado($intentos, $tokens['general-1']);
        $check($vistos($deGeneral['body'], $todos, $marca) === ['general-1', 'org-1', 'org-2', 'usuario'], 'a2 el general ve lo suyo y lo de rangos menores, NO lo del otro general ni lo de root', implode(',', $vistos($deGeneral['body'], $todos, $marca)));
        $deOrg = $listado($intentos, $tokens['org-1']);
        $check($vistos($deOrg['body'], $todos, $marca) === ['org-1', 'usuario'], 'a3 el de organización ve lo suyo y lo del usuario de su organización, NO lo del otro administrador de su organización', implode(',', $vistos($deOrg['body'], $todos, $marca)));
        $deGeneral2 = $listado($intentos, $tokens['general-2']);
        $check(in_array('general-2', $vistos($deGeneral2['body'], $todos, $marca), true), 'a4 cada uno ve lo suyo: el otro general ve su intento');

        //Lo que cuentan las cifras y las exportaciones: LoginAttemptsModel::all(), con la misma regla.
        $usuarioPrevio = get_config('current_user');
        $guardadoPrevio = get_config('pcsphp_current_user_stored');
        try {
            set_config('current_user', (object) ['id' => $usuarios['general-1']]);
            set_config('pcsphp_current_user_stored', null);
            $nombres = array_map(fn($fila) => (string) $fila->usernameAttempt, LoginAttemptsModel::all());
            $deAll = array_values(array_filter($todos, fn($sufijo) => in_array("{$marca}-{$sufijo}", $nombres, true)));
            $check($deAll === ['general-1', 'org-1', 'org-2', 'usuario'], 'a5 las cifras y la exportación de intentos (all()) siguen la misma regla', implode(',', $deAll));
        } finally {
            set_config('current_user', $usuarioPrevio);
            set_config('pcsphp_current_user_stored', $guardadoPrevio);
        }

        //─── b · Registro de ingreso ──────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Registro de ingreso (usuarios)');
        $usuariosGeneral = $listado($ingresos, $tokens['general-1']);
        $check($usuariosGeneral['status'] === 200 && $vistos($usuariosGeneral['body'], $todos, $prefijo) === ['general-1', 'org-1', 'org-2', 'usuario'], 'b1 el general no ve en la lista al otro general ni a root', implode(',', $vistos($usuariosGeneral['body'], $todos, $prefijo)));
        $usuariosOrg = $listado($ingresos, $tokens['org-1']);
        $check($vistos($usuariosOrg['body'], $todos, $prefijo) === ['org-1', 'usuario'], 'b2 el de organización no ve al otro administrador de su organización', implode(',', $vistos($usuariosOrg['body'], $todos, $prefijo)));

        //─── c · Registro de acciones ─────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Registro de acciones: cuenta quien actuó de verdad');
        $conSuplantada = array_merge($todos, ['suplantada']);
        $accionesRoot = $listado($acciones, $tokens['root']);
        $check($accionesRoot['status'] === 200 && $vistos($accionesRoot['body'], $conSuplantada, $marca) === $conSuplantada, 'c3 CANARIO: root ve las siete, también la suplantada', implode(',', $vistos($accionesRoot['body'], $conSuplantada, $marca)));
        $accionesGeneral = $listado($acciones, $tokens['general-1']);
        $check($vistos($accionesGeneral['body'], $conSuplantada, $marca) === ['general-1', 'org-1', 'org-2', 'usuario'], 'c4 el general: ni la del otro general, ni la de root, ni la que root hizo suplantando', implode(',', $vistos($accionesGeneral['body'], $conSuplantada, $marca)));
        $accionesOrg = $listado($acciones, $tokens['org-1']);
        $check($vistos($accionesOrg['body'], $conSuplantada, $marca) === ['org-1', 'usuario'], 'c5 el de organización: ni la del otro de su organización, ni la que root hizo en su nombre', implode(',', $vistos($accionesOrg['body'], $conSuplantada, $marca)));

        //─── d · El detalle por id ────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] El detalle, pidiéndolo por id');
        $detalle = fn(string $sufijo, string $quien): int => $pedir($sinPrefijo($ruta(ActionsLogController::routeName('detail', ['id' => $accionDe[$sufijo]], true))), $tokens[$quien])['status'];
        $check($detalle('org-1', 'general-1') === 200 && $detalle('general-1', 'general-1') === 200, 'd1 CANARIO: el general abre la del de organización y la suya');
        $check($detalle('root', 'general-1') === 404 && $detalle('general-2', 'general-1') === 404 && $detalle('suplantada', 'general-1') === 404, 'd2 la de root, la del otro general y la suplantada dan 404 al general');
        $check($detalle('org-2', 'org-1') === 404 && $detalle('suplantada', 'org-1') === 404 && $detalle('org-1', 'org-1') === 200, 'd3 al de organización: 404 la del otro de su organización y la suplantada; la suya, 200');
        $check($detalle('suplantada', 'root') === 200, 'd4 CANARIO: root abre la suplantada');

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $database->prepare("DELETE FROM `{$tablaAcciones}` WHERE textMessageVariables LIKE ?")->execute(["%{$marca}%"]);
            $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?")->execute(["{$marca}%"]);
            $placeholders = $usuarios !== [] ? implode(',', array_fill(0, count($usuarios), '?')) : null;
            if ($placeholders !== null) {
                //Lo que la aplicación anotara a nombre de los usuarios de la prueba.
                $database->prepare("DELETE FROM `{$tablaAcciones}` WHERE createdBy IN ({$placeholders})")->execute(array_values($usuarios));
                $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE userID IN ({$placeholders})")->execute(array_values($usuarios));
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaOrganizaciones, $organizacion > 0 ? [$organizacion] : []], [$tablaUsuarios, array_values($usuarios)]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //El usuario apunta a su organización y la organización a su creador: los usuarios, a la global; luego cae la
            //organización y al final los usuarios.
            $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE username LIKE ?")->execute([OrganizationMapper::INITIAL_ID_GLOBAL, "{$prefijo}%"]);
            if ($organizacion > 0) {
                $database->prepare("DELETE FROM `{$tablaOrganizaciones}` WHERE id = ?")->execute([$organizacion]);
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $cuenta = function (string $sql, array $valores) use ($database): int {
            $consulta = $database->prepare($sql);
            //RETORNO-IGNORADO: PDO va con ERRMODE_EXCEPTION; un fallo lanza y lo cuenta z0.
            $consulta->execute($valores);
            return (int) $consulta->fetchColumn();
        };
        $quedan = $cuenta("SELECT COUNT(*) FROM `{$tablaAcciones}` WHERE textMessageVariables LIKE ?", ["%{$marca}%"])
             + $cuenta("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?", ["{$marca}%"])
             + $cuenta("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE ?", ["{$prefijo}%"])
             + $cuenta("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$prefijo}%"]);
        $check($quedan === 0, 'z1 no queda ninguna acción, intento, organización ni usuario de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('La jerarquía de P133 por HTTP en Registros › Accesos (intentos, registro de ingreso y lo que cuentan cifras y exportaciones) y en el registro de acciones (listado y detalle por id): root ve todo; el administrador general, lo suyo y lo de rangos menores; el de organización, además, solo de la suya; una acción que root hizo suplantando no la ve ningún administrador. Crea y borra una organización, seis usuarios, sus intentos y siete acciones.')->setEffects([CliActions::EFFECT_DATABASE])->register();
