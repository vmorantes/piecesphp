<?php

//Registros › Tokens y códigos: permisos, jerarquía, acciones solo de root y sin suplantar, historia. Crea y borra
//cinco usuarios zz-agente-fr-tokens-*; envía UN correo a zz-prueba-fr-tokens-*@mailinator.com (ADR 0011).
//@areas src/app/classes/EventsLog/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/SystemStatus/
//@areas src/app/classes/SystemApprovals/

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Authentication\TokenEvents;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\RecoveryPasswordModel;
use PiecesPHP\UserSystem\ORM\TicketsLogModel;
use PiecesPHP\UserSystem\ORM\UserProblemsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/tokens-tab', function ($args) {

    echoTerminal("\e[33m[TEST:TokensTab] Registros › Tokens y códigos: quién ve, quién toca, y lo que deja\e[39m");
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
    if (!$check(LogsRoutes::ENABLE, 'c2 el registro de acciones está encendido (la historia lo necesita)')) {
        return $balance();
    }

    $ruta = function (string $nombre, array $parametros = []): string {
        $url = get_route($nombre, $parametros, true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, string $jwt, ?array $post = null, array $query = []) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = ['X-Requested-With: XMLHttpRequest'];
        if ($jwt !== '') {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/') . (!empty($query) ? '?' . http_build_query($query) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $cabeceras,
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'json' => json_decode($body, true)];
    };
    $tabla = ['draw' => 1, 'start' => 0, 'length' => 5000];
    $filas = fn(array $r): array => is_array($r['json']['data'] ?? null) ? $r['json']['data'] : [];

    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-agente-fr-tokens-{$marca}";
    $buzon = "zz-prueba-fr-tokens-{$marca}@mailinator.com";
    $tablaUsuarios = UsersModel::TABLE;
    $tablaRegistro = LogsMapper::TABLE;
    $tablaOTP = OTPSecretsUsersMapper::TABLE;
    $tablaRecuperacion = (new RecoveryPasswordModel())->getModel()->getTable();
    $tablaProblemas = (new UserProblemsModel())->getModel()->getTable();
    $tablaTickets = (new TicketsLogModel())->getModel()->getTable();
    $maxRegistro = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaRegistro}`")->fetchColumn();
    $usuarios = [];
    $correos = [];

    $crear = function (string $sufijo, int $tipo, string $correo = '') use ($prefijo, &$usuarios, &$correos): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = $correo !== '' ? $correo : "{$prefijo}-{$sufijo}@example.com";
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        $correos[] = (string) $u->email;
        return (int) $u->id;
    };
    $sesion = function (int $userID): string {
        $sid = UserSessions::open($userID);
        return SessionToken::generateToken(['id' => $userID, 'sid' => $sid], null, SessionToken::DURATION_MAX, false);
    };
    //Los eventos de la prueba: los de después de empezar, por tipo y usuario de referencia.
    $eventos = function (string $tipo, int $userID) use ($database, $tablaRegistro, &$maxRegistro): array {
        $consulta = $database->prepare("SELECT textMessageVariables FROM `{$tablaRegistro}` WHERE id > ? AND textMessage = ? AND referenceValue = ?");
        //RETORNO-IGNORADO: lo que cuenta son las filas que siguen, y se comprueban.
        $consulta->execute([$maxRegistro, LogsMapper::MESSAGES[$tipo], (string) $userID]);
        return $consulta->fetchAll(\PDO::FETCH_COLUMN);
    };

    try {

        //─── a · Rutas y permisos ─────────────────────────────────────────────────────────────────
        echoTerminal('[a] Rutas y permisos');
        $nombres = ['user-tokens-datatables', 'user-tokens-history', 'user-tokens-reveal', 'user-tokens-invalidate', 'user-tokens-reset-two-factor'];
        $faltan = array_filter($nombres, fn($n) => $ruta($n, ['kind' => 'x', 'id' => 1]) === '');
        $check($faltan === [], 'a1 existen las cinco rutas', implode(', ', $faltan));
        $soloRoot = true;
        foreach (['user-tokens-reveal', 'user-tokens-invalidate', 'user-tokens-reset-two-factor'] as $n) {
            $soloRoot = $soloRoot && Roles::hasPermissions($n, UsersModel::TYPE_USER_ROOT, true) && !Roles::hasPermissions($n, UsersModel::TYPE_USER_ADMIN_GRAL, true) && !Roles::hasPermissions($n, UsersModel::TYPE_USER_ADMIN_ORG, true);
        }
        $check($soloRoot, 'a2 ver el valor, invalidar y restablecer: solo el rol root');
        $check(Roles::hasPermissions('user-tokens-datatables', UsersModel::TYPE_USER_ADMIN_GRAL, true) && !Roles::hasPermissions('user-tokens-datatables', UsersModel::TYPE_USER_GENERAL, true),
            'a3 el listado, los roles de las sesiones (un administrador sí, un usuario general no)');

        $root = $crear('root', UsersModel::TYPE_USER_ROOT);
        $root2 = $crear('root2', UsersModel::TYPE_USER_ROOT);
        $gral = $crear('gral', UsersModel::TYPE_USER_ADMIN_GRAL);
        $genA = $crear('general-a', UsersModel::TYPE_USER_GENERAL, $buzon);
        $genB = $crear('general-b', UsersModel::TYPE_USER_GENERAL);
        $jwtRoot = $sesion($root);
        $jwtGral = $sesion($gral);
        $jwtGenA = $sesion($genA);
        $check(count($usuarios) === 5, 'banco: cinco usuarios de la prueba');

        //Los códigos: uno de un solo uso para A, el doble factor de B y una recuperación de root2.
        $codigo = (string) random_int(100000, 999999);
        OTPSecretsUsersMapper::setOneUseCode($genA, $codigo, 20);
        OTPSecretsUsersMapper::createTOTPData($genB);
        OTPSecretsUsersMapper::toggle2FA($genB, true, bin2hex(random_bytes(8)));
        OTPSecretsUsersMapper::confirm2FA($genB);
        $correoRoot2 = "{$prefijo}-root2@example.com";
        $codigoRecuperacion = (string) random_int(100000, 999999);
        $database->prepare("INSERT INTO `{$tablaRecuperacion}` (email, code, created, expired) VALUES (?, ?, ?, ?)")
            ->execute([$correoRoot2, $codigoRecuperacion, date('Y-m-d H:i:s'), date('Y-m-d H:i:s', time() + 3600)]);
        $idRecuperacion = (int) $database->lastInsertId();
        $idOTP = (int) $database->query("SELECT id FROM `{$tablaOTP}` WHERE user = {$genA} AND method = '" . OTPSecretsUsersMapper::METHOD_ONE_USE_CODE . "'")->fetchColumn();
        $idTOTP = (int) $database->query("SELECT id FROM `{$tablaOTP}` WHERE user = {$genB} AND method = '" . OTPSecretsUsersMapper::METHOD_TOTP . "'")->fetchColumn();
        $check($idOTP > 0 && $idTOTP > 0 && $idRecuperacion > 0 && OTPSecretsUsersMapper::isEnabled2FA($genB) && OTPHandler::checkValidityOTP($codigo, "{$prefijo}-general-a"),
            'banco: el código de un solo uso de A vale (CANARIO), B tiene el doble factor y root2 una recuperación');

        //─── b · Quién ve qué (P133) ──────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Quién ve qué');
        $claves = function (array $r): array {
            $vistas = [];
            foreach ($r['json']['data'] ?? [] as $f) {
                $vistas[] = strip_tags((string) ($f[0] ?? '')) . '|' . strip_tags((string) ($f[1] ?? ''));
            }
            return $vistas;
        };
        $deRoot = $claves($pedir($ruta('user-tokens-datatables'), $jwtRoot, null, $tabla));
        $otp = TokenEvents::label(TokenEvents::KIND_ONE_USE_CODE) . "|{$prefijo}-general-a";
        $dobleFactor = TokenEvents::label(TokenEvents::KIND_TWO_FACTOR) . "|{$prefijo}-general-b";
        $recuperacion = TokenEvents::label(TokenEvents::KIND_RECOVERY) . "|{$prefijo}-root2";
        $check(in_array($otp, $deRoot, true) && in_array($dobleFactor, $deRoot, true) && in_array($recuperacion, $deRoot, true),
            'b1 root ve los tres: el código de A, el doble factor de B y la recuperación de root2', implode(' / ', array_slice($deRoot, 0, 8)));
        $deGral = $pedir($ruta('user-tokens-datatables'), $jwtGral, null, $tabla);
        $vistasGral = $claves($deGral);
        $check($deGral['status'] === 200 && in_array($otp, $vistasGral, true) && in_array($dobleFactor, $vistasGral, true), 'b2 el administrador general ve los de los usuarios generales', (string) $deGral['status']);
        $check(!in_array($recuperacion, $vistasGral, true), 'b3 pero no la de otro root');
        $accionesGral = implode('', array_map(fn($f) => (string) ($f[4] ?? ''), $filas($deGral)));
        $check(!str_contains($accionesGral, 'data-token-'), 'b4 y sin acciones: no son suyas');
        $check($pedir($ruta('user-tokens-datatables'), $jwtGenA, null, $tabla)['status'] !== 200, 'b5 un usuario general no ve la pestaña');
        $check(!str_contains($pedir($ruta('user-tokens-datatables'), $jwtRoot, null, $tabla)['body'], $codigo), 'b6 el listado no lleva ningún valor de código');

        //─── c · Solo root, nunca suplantando ─────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Solo root, y sin suplantar');
        $mostrar = $ruta('user-tokens-reveal', ['kind' => TokenEvents::KIND_ONE_USE_CODE, 'id' => $idOTP]);
        $deGralMostrar = $pedir($mostrar, $jwtGral, []);
        $check($deGralMostrar['status'] === 403 && !str_contains($deGralMostrar['body'], $codigo), 'c1 el administrador general no puede Mostrar: 403', (string) $deGralMostrar['status']);
        $check($pedir($ruta('user-tokens-invalidate', ['kind' => TokenEvents::KIND_ONE_USE_CODE, 'id' => $idOTP]), $jwtGral, [])['status'] === 403, 'c2 ni Invalidar: 403');
        $check($pedir($ruta('user-tokens-reset-two-factor', ['id' => $idTOTP]), $jwtGral, [])['status'] === 403, 'c3 ni Restablecer: 403');
        //Actuar como otro root ya no es posible (P204 a): lo que se mide es que empezarlo se rechaza sin soltar el valor.
        $suplantando = $pedir($ruta('user-impersonation-start'), $jwtRoot, ['userID' => $root2]);
        $check($suplantando['status'] === 400 && !str_contains($suplantando['body'], $codigo), 'c4 empezar a actuar como otro root: 400 y sin el valor', (string) $suplantando['status']);
        $check(OTPHandler::checkValidityOTP($codigo, "{$prefijo}-general-a") && OTPSecretsUsersMapper::isEnabled2FA($genB), 'c5 y nada cambió: el código vale y el doble factor sigue');

        //─── d · Mostrar, Invalidar, Restablecer ──────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] Lo que hace root');
        $visto = $pedir($mostrar, $jwtRoot, []);
        $check($visto['status'] === 200 && ($visto['json']['values']['value'] ?? null) === $codigo, 'd1 Mostrar devuelve el código', (string) $visto['status']);
        $vistos = $eventos(LogsMapper::MSG_TOKEN_VALUE_VIEWED, $genA);
        $check(count($vistos) === 1 && !str_contains((string) $vistos[0], $codigo), 'd2 y deja UN evento, sin el valor dentro', (string) count($vistos));
        $check($pedir($ruta('user-tokens-reveal', ['kind' => TokenEvents::KIND_TWO_FACTOR, 'id' => $idTOTP]), $jwtRoot, [])['status'] === 404, 'd3 el doble factor no se muestra: 404');
        $invalidar = $pedir($ruta('user-tokens-invalidate', ['kind' => TokenEvents::KIND_ONE_USE_CODE, 'id' => $idOTP]), $jwtRoot, []);
        $check($invalidar['status'] === 200 && !OTPHandler::checkValidityOTP($codigo, "{$prefijo}-general-a"), 'd4 Invalidar deja el código inservible para el login', (string) $invalidar['status']);
        $login = $pedir($ruta('users-login-request'), '', ['username' => "{$prefijo}-general-a", 'password' => $codigo]);
        $check(($login['json']['auth'] ?? null) === false, 'd5 y por HTTP, entrar con él no entra');
        $check(count($eventos(LogsMapper::MSG_TOKEN_INVALIDATED, $genA)) === 1, 'd6 con su evento');
        $check($pedir($ruta('user-tokens-invalidate', ['kind' => TokenEvents::KIND_RECOVERY, 'id' => $idRecuperacion]), $jwtRoot, [])['status'] === 200
            && RecoveryPasswordModel::findValid($correoRoot2, $codigoRecuperacion) === null, 'd7 invalidar la recuperación de root2 la deja sin valor');
        $verificar = $pedir($ruta('users-new-password-verify-code'), '', ['username' => "{$prefijo}-root2", 'code' => $codigoRecuperacion]);
        $check(($verificar['json']['success'] ?? null) === false, 'd8 y la recuperación con ese código falla');
        $restablecer = $pedir($ruta('user-tokens-reset-two-factor', ['id' => $idTOTP]), $jwtRoot, []);
        $check($restablecer['status'] === 200 && !OTPSecretsUsersMapper::isEnabled2FA($genB), 'd9 Restablecer apaga el doble factor de B', (string) $restablecer['status']);
        $check(count($eventos(LogsMapper::MSG_TWO_FACTOR_RESET, $genB)) === 1, 'd10 con su evento');
        OTPSecretsUsersMapper::createTOTPData($root);
        OTPSecretsUsersMapper::toggle2FA($root, true, bin2hex(random_bytes(8)));
        OTPSecretsUsersMapper::confirm2FA($root);
        $idTOTPRoot = (int) $database->query("SELECT id FROM `{$tablaOTP}` WHERE user = {$root} AND method = '" . OTPSecretsUsersMapper::METHOD_TOTP . "'")->fetchColumn();
        $propio = $pedir($ruta('user-tokens-reset-two-factor', ['id' => $idTOTPRoot]), $jwtRoot, []);
        $check($propio['status'] === 400 && OTPSecretsUsersMapper::isEnabled2FA($root), 'd11 el propio no: 400, y sigue encendido', (string) $propio['status']);

        //─── e · La emisión y los dos defectos de «usuario olvidado» ──────────────────────────────
        echoTerminal(' ');
        echoTerminal('[e] Pedir un código lo anota');
        $antes = time();
        $pedido = $pedir($ruta('users-forget-request-code'), '', ['username' => "{$prefijo}-general-a", 'type' => UserProblemsController::TYPE_USER_FORGET]);
        $check(count($eventos(LogsMapper::MSG_TOKEN_ISSUED, $genA)) === 1, 'e1 pedir «usuario olvidado» deja el evento de emisión', $pedido['body']);
        $problema = $database->prepare("SELECT created, expired FROM `{$tablaProblemas}` WHERE email = ? ORDER BY id DESC LIMIT 1");
        //RETORNO-IGNORADO: lo que cuenta es la fila que sigue, y se comprueba.
        $problema->execute([$buzon]);
        $fila = $problema->fetch(\PDO::FETCH_ASSOC);
        $creado = is_array($fila) ? (int) strtotime((string) $fila['created']) : 0;
        $caduca = is_array($fila) ? (int) strtotime((string) $fila['expired']) : 0;
        $check($creado >= $antes - 5 && $caduca - $creado === 86400, 'e2 la creación es la de verdad y caduca 24 h después (antes, las dos iguales)', json_encode($fila) ?: '');
        $ticket = $database->prepare("SELECT information FROM `{$tablaTickets}` WHERE email = ? ORDER BY id DESC LIMIT 1");
        //RETORNO-IGNORADO: lo que cuenta es la fila que sigue, y se comprueba.
        $ticket->execute([$buzon]);
        $informacion = json_decode((string) $ticket->fetchColumn(), true);
        $check(is_array($informacion) && !array_key_exists('code', $informacion), 'e3 el registro de tickets ya no guarda el código', json_encode(is_array($informacion) ? array_keys($informacion) : null) ?: '');

        //─── f · La historia ──────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[f] La historia');
        $textos = fn(array $r): string => implode("\n", array_map(fn($f) => strip_tags((string) ($f[1] ?? '')) . ' · ' . strip_tags((string) ($f[2] ?? '')), $filas($r)));
        $historiaRoot = $textos($pedir($ruta('user-tokens-history'), $jwtRoot, null, $tabla));
        $check(str_contains($historiaRoot, "{$prefijo}-general-a") && str_contains($historiaRoot, "{$prefijo}-general-b") && str_contains($historiaRoot, "{$prefijo}-root2"), 'f1 root ve la historia de los tres');
        $historiaGral = $textos($pedir($ruta('user-tokens-history'), $jwtGral, null, $tabla));
        $check(str_contains($historiaGral, "{$prefijo}-general-a") && !str_contains($historiaGral, "{$prefijo}-root2"), 'f2 el administrador general, la de los generales y no la de root2');
        $check(!str_contains($historiaRoot, $codigo) && !str_contains($historiaRoot, $codigoRecuperacion), 'f3 ninguna línea de la historia lleva un valor');

    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . $e->getMessage());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $database->prepare("DELETE FROM `{$tablaRegistro}` WHERE id > ? AND (textMessageVariables LIKE ? OR referenceValue IN ('" . implode("','", array_map('intval', $usuarios ?: [0])) . "'))")->execute([$maxRegistro, "%{$prefijo}%"]);
        $marcas = implode(',', array_map('intval', $usuarios ?: [0]));
        $database->exec("DELETE FROM `{$tablaRegistro}` WHERE createdBy IN ({$marcas})");
        $database->exec("DELETE FROM `{$tablaOTP}` WHERE user IN ({$marcas})");
        $database->exec('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})");
        $database->exec("DELETE FROM `login_attempts` WHERE userID IN ({$marcas})");
        $database->prepare("DELETE FROM `login_attempts` WHERE usernameAttempt LIKE ?")->execute(["{$prefijo}%"]);
        foreach ($correos as $correo) {
            foreach ([$tablaRecuperacion, $tablaProblemas, $tablaTickets] as $t) {
                $database->prepare("DELETE FROM `{$t}` WHERE email = ?")->execute([$correo]);
            }
        }
        foreach ($usuarios as $id) {
            $aprobaciones = SystemApprovalsMapper::model();
            $aprobaciones->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $aprobaciones->delete(new WhereSegment([
                new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaUsuarios),
                new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
            ]))->execute();
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => $id])->execute();
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$marcas})");
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE '{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE id > {$maxRegistro} AND textMessageVariables LIKE '%{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email LIKE '%{$marca}%'")->fetchColumn();
        $quedan += (int) $database->query("SELECT COUNT(*) FROM `{$tablaTickets}` WHERE email LIKE '%{$marca}%'")->fetchColumn();
        $quedan += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue IN ('" . implode("','", array_map('intval', $usuarios ?: [0])) . "')")->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, aprobación, código, ticket ni registro de la prueba', (string) $quedan);
        echoTerminal("   correo de la prueba: {$buzon}");
        //RETORNO-IGNORADO: la línea del registro de correos de la prueba; lo que quede lo cuenta gates como resto.
        \PiecesPHP\SystemStatus\Mappers\MailLogMapper::deleteByRecipientMark($buzon);
    }

    return $balance();

})->setDescription('Registros › Tokens y códigos: las cinco rutas y su permiso; root ve los vigentes de todos y el administrador general solo los de rangos menores, sin acciones; Mostrar, Invalidar y Restablecer son de root y nunca suplantando; Mostrar deja un evento sin el valor, lo invalidado ya no entra ni recupera, el doble factor se apaga (el propio no); pedir un código deja su emisión; «usuario olvidado» ya no mueve la creación ni guarda el código en los tickets; y la historia con su jerarquía. Por HTTP. Crea y borra cinco usuarios y sus códigos; envía UN correo a zz-prueba-fr-tokens-*@mailinator.com.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_EMAIL])->register();
