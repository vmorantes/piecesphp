<?php

//Un usuario cuyo tipo ya no es un rol no entra, no se borra y root lo ve en un aviso (P290, fase 1). El acceso, en proceso
//con REMOTE_ADDR propias; el token y el aviso, por HTTP. Siembra zz- y lo retira.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\InvalidTypeUsersAlert;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/invalid-user-type', function ($args) {

    echoTerminal("\e[33m[TEST:InvalidUserType] Un usuario sin tipo válido: no entra, no se borra y root lo ve\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que core/user-sessions.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'p0 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $panelURL = get_route('admin', [], true);
    $panel = '/' . ltrim(is_string($panelURL) && str_contains($panelURL, '://') ? (string) parse_url($panelURL, \PHP_URL_PATH) : (string) $panelURL, '/');
    $cabeceraToken = SessionToken::tokenName();
    //Por XHR, una sesión rechazada es 401 y no una redirección al acceso.
    $pedir = function (string $path, string $jwt, bool $xhr = false) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $xhr ? ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'] : ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => $status, 'body' => is_string($body) ? $body : ''];
    };

    //Un código que no es un rol: se comprueba antes de usarlo.
    $tipoInexistente = 9999;
    $ips = ['198.51.100.210', '198.51.100.211', '198.51.100.212'];
    $listaIPs = implode(', ', array_map(fn (string $ip): string => $database->quote($ip), $ips));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $tablaSesiones = UserSessions::TABLE;
    $prefijo = 'zz-prueba-tipo-' . bin2hex(random_bytes(3));
    $clave = 'zz-Clave-Buena-1';
    $usuarios = [];
    $direccionPrevia = $_SERVER['REMOTE_ADDR'] ?? null;
    $maxLogPrevio = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();
    $database->exec("DELETE FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs})");

    try {
        $crear = function (string $sufijo, int $tipo, int $estado = UsersModel::STATUS_USER_ACTIVE) use ($prefijo, $clave, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Tipo';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = $estado;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $sesion = function (int $userID) use ($database, $tablaSesiones): array {
            $sid = UserSessions::open($userID);
            $jwt = SessionToken::generateToken(['id' => $userID, 'sid' => $sid], null, SessionToken::DURATION_MAX, false);
            $consulta = $database->prepare("SELECT id FROM `{$tablaSesiones}` WHERE sessionHash = ?");
            $consulta->execute([UserSessions::hash($sid)]);
            return ['jwt' => $jwt, 'id' => (int) $consulta->fetchColumn()];
        };
        $fila = function (string $tabla, int $id) use ($database): array {
            $consulta = $database->prepare("SELECT * FROM `{$tabla}` WHERE id = ?");
            $consulta->execute([$id]);
            $r = $consulta->fetch(\PDO::FETCH_ASSOC);
            return is_array($r) ? $r : [];
        };
        $peticion = fn (array $cuerpo): RequestRoute => (new RequestRoute('POST', (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-tipo'), new \Slim\Psr7\Headers(), [], [], (new \Slim\Psr7\Factory\StreamFactory())->createStream('')))
            ->withParsedBody($cuerpo);
        $entrar = function (string $ip, string $usuario, string $contrasena) use ($peticion): array {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $respuesta = (new UsersController())->login($peticion(['username' => $usuario, 'password' => $contrasena, 'overwriteSession' => true]), new ResponseRoute());
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            return is_array($cuerpo) ? $cuerpo : [];
        };

        $check(!Roles::roleExists($tipoInexistente) && Roles::roleExists(UsersModel::TYPE_USER_GENERAL), "p1 CANARIO: {$tipoInexistente} no es un rol y el tipo general sí");
        $antes = InvalidTypeUsersAlert::count(true);
        $persona = $crear('persona', UsersModel::TYPE_USER_GENERAL);
        $root = $crear('root', UsersModel::TYPE_USER_ROOT);

        //─── a · El acceso ────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] El acceso');
        $r = $entrar($ips[0], "{$prefijo}-persona", $clave);
        $check(($r['auth'] ?? null) === true, 'a1 CANARIO: con un tipo válido y la contraseña buena, entra', json_encode($r['error'] ?? null, JSON_THROW_ON_ERROR));
        //El token de ANTES del cambio de tipo, con su fila: lo que el navegador del usuario aún guarda.
        $previa = $sesion($persona);
        $check(in_array($pedir($panel, $previa['jwt'], true)['status'], [200, 302], true), 'a2 CANARIO: su token entra al panel mientras su tipo es válido');

        $database->prepare("UPDATE `{$tablaUsuarios}` SET type = ? WHERE id = ?")->execute([$tipoInexistente, $persona]);
        $r = $entrar($ips[1], "{$prefijo}-persona", 'zz-mala');
        $check(($r['error'] ?? null) === UsersController::INCORRECT_PASSWORD, 'a3 con la contraseña mala responde lo de siempre: no delata la cuenta', json_encode($r['error'] ?? null, JSON_THROW_ON_ERROR));
        $r = $entrar($ips[2], "{$prefijo}-persona", $clave);
        $check(($r['auth'] ?? null) === false && ($r['token'] ?? null) === '' && ($r['error'] ?? null) === UsersController::INVALID_USER_TYPE,
            'a4 con la contraseña buena, auth false, sin token y el código INVALID_USER_TYPE', json_encode([$r['auth'] ?? null, $r['error'] ?? null], JSON_THROW_ON_ERROR));
        $check(($r['message'] ?? null) === __('users', UsersController::INVALID_USER_TYPE) && str_contains((string) ($r['message'] ?? ''), 'tipo de usuario válido'),
            'a5 con su mensaje en español', (string) ($r['message'] ?? ''));

        //─── b · La petición con el token de antes ───────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] La petición siguiente, con el token de antes');
        $estado = $pedir($panel, $previa['jwt'], true)['status'];
        $check($estado === 401, 'b1 no tiene sesión', "HTTP {$estado}");
        $usuario = $fila($tablaUsuarios, $persona);
        $check((int) ($usuario['status'] ?? -1) === UsersModel::STATUS_USER_ACTIVE, 'b2 su estado NO cambia: no se le borra', 'status ' . ($usuario['status'] ?? 'sin fila'));
        $filaSesion = $fila($tablaSesiones, $previa['id']);
        $check(($filaSesion['closedAt'] ?? null) !== null && ($filaSesion['closeReason'] ?? null) === UserSessions::REASON_INVALID_TYPE,
            'b3 su fila de sesión queda cerrada, con su motivo', json_encode([$filaSesion['closedAt'] ?? null, $filaSesion['closeReason'] ?? null], JSON_THROW_ON_ERROR));

        //─── c · El aviso ────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] El aviso para root');
        $check(InvalidTypeUsersAlert::count(true) === $antes + 1, 'c1 cuenta al usuario sin tipo válido', "antes {$antes}, ahora " . InvalidTypeUsersAlert::count(true));
        $crear('borrado', $tipoInexistente, UsersModel::STATUS_USER_DELETED);
        $check(InvalidTypeUsersAlert::count(true) === $antes + 1, 'c2 no cuenta a uno ya borrado');
        $sistema = $fila($tablaUsuarios, UsersModel::SYSTEM_USER_ID);
        $todos = $database->prepare("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE type = ? AND status <> ?");
        $todos->execute([(int) ($sistema['type'] ?? 0), UsersModel::STATUS_USER_DELETED]);
        $deSuTipo = (int) $todos->fetchColumn();
        if ($sistema !== [] && !Roles::roleExists((int) $sistema['type'])) {
            //Con el usuario de sistema dentro, los de su tipo serían uno más de los que el aviso cuenta.
            $porTipo = $database->prepare("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE type = ? AND status <> ? AND id <> ?");
            $porTipo->execute([(int) $sistema['type'], UsersModel::STATUS_USER_DELETED, UsersModel::SYSTEM_USER_ID]);
            $check((int) $porTipo->fetchColumn() === $deSuTipo - 1 && InvalidTypeUsersAlert::count(true) === $antes + 1, 'c3 el usuario de sistema, sin rol, no se cuenta', "de su tipo {$deSuTipo}");
        } else {
            $check(false, 'c3 el usuario de sistema existe y no tiene rol', json_encode($sistema === [] ? null : $sistema['type'], JSON_THROW_ON_ERROR));
        }
        $deRoot = $sesion($root)['jwt'];
        $pagina = $pedir($panel, $deRoot);
        $texto = preg_match('/data-key="' . preg_quote(InvalidTypeUsersAlert::KEY, '/') . '"[^>]*>\s*<span class="title">([^<]*)<\/span>/', $pagina['body'], $aviso) === 1 ? html_entity_decode($aviso[1]) : null;
        $check($pagina['status'] === 200 && $texto === InvalidTypeUsersAlert::message($antes + 1), 'c4 root lo ve en el panel', "HTTP {$pagina['status']}, «" . ($texto ?? 'sin aviso') . '»');
        $check(str_contains($pagina['body'], (string) get_route('users-list', [], true)) || str_contains($pagina['body'], (string) parse_url((string) get_route('users-list', [], true), \PHP_URL_PATH)), 'c5 con el enlace al listado de usuarios');
        $general = $crear('general', UsersModel::TYPE_USER_ADMIN_GRAL);
        $paginaGeneral = $pedir($panel, $sesion($general)['jwt']);
        $check($paginaGeneral['status'] === 200 && !str_contains($paginaGeneral['body'], 'data-key="' . InvalidTypeUsersAlert::KEY . '"'), 'c6 un administrador general no lo ve: solo root', "HTTP {$paginaGeneral['status']}");

    } catch (\Throwable $e) {
        $check(false, 'z0 la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($direccionPrevia === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $direccionPrevia;
        }
        try {
            $database->exec("DELETE FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs})");
            $intentos = $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?");
            $intentos->execute(["{$prefijo}%"]);
            if ($usuarios !== []) {
                $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                $database->prepare("DELETE FROM `{$tablaSesiones}` WHERE userID IN ({$marcas})")->execute(array_values($usuarios));
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLogPrevio], array_values($usuarios)));
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
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $cuenta = function (string $sql, array $valores) use ($database): int {
            $consulta = $database->prepare($sql);
            $consulta->execute($valores);
            return (int) $consulta->fetchColumn();
        };
        $quedan = $cuenta("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$prefijo}%"])
         + $cuenta("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?", ["{$prefijo}%"])
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs})")->fetchColumn();
        if ($usuarios !== []) {
            $marcas = implode(',', array_fill(0, count($usuarios), '?'));
            $quedan += $cuenta("SELECT COUNT(*) FROM `{$tablaSesiones}` WHERE userID IN ({$marcas})", array_values($usuarios));
        }
        $check($quedan === 0, 'z1 no queda ningún usuario, intento de acceso ni sesión de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('Un usuario cuyo tipo ya no es un rol: el acceso lo rechaza con INVALID_USER_TYPE solo con la contraseña buena; con un token de antes no hay sesión, su estado no cambia y su fila se cierra; el aviso para root lo cuenta sin el usuario de sistema ni los borrados. Crea y borra usuarios zz-, sus sesiones y sus intentos de acceso.')->setEffects([CliActions::EFFECT_DATABASE])->register();
