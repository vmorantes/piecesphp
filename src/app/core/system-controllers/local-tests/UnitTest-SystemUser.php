<?php

//El usuario de sistema (B14): no entra, no está en los listados, no se toca, y lo hecho sin sesión es suyo.
//Crea un root zz-agente-fr-* con su sesión; borra lo que crea, con sus intentos, aprobaciones y registros.
//@areas src/app/classes/EventsLog/
//@areas src/app/classes/Organizations/
//@areas src/app/classes/SystemApprovals/

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UserProblemsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserHierarchy;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/system-user', function ($args) {

    echoTerminal("\e[33m[TEST:SystemUser] El usuario de sistema: inaccesible, fuera de los listados y autor de lo hecho sin sesión\e[39m");
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

    $tablaUsuarios = UsersModel::TABLE;
    $sistema = UsersModel::SYSTEM_USER_ID;
    $consulta = $database->prepare("SELECT id, username, password, type, status, organization FROM `{$tablaUsuarios}` WHERE id = ?");
    //RETORNO-IGNORADO: lo que cuenta es la fila que sigue, y se comprueba.
    $consulta->execute([$sistema]);
    $fila = $consulta->fetch(\PDO::FETCH_ASSOC);
    //Una prueba NO migra la instalación: sin la fila, lo dice y para.
    if (!$check(is_array($fila), 'c2 la fila del usuario de sistema existe', 'aplica databases/actualizaciones/2026-10-14-usuario-de-sistema.sql')) {
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
    //Los listados de DataTables: todas las filas de una vez.
    $tabla = ['draw' => 1, 'start' => 0, 'length' => 5000];
    $filas = fn(array $r): array => is_array($r['json']['data'] ?? null) ? $r['json']['data'] : [];

    $prefijo = 'zz-agente-fr-' . bin2hex(random_bytes(3));
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $tablaRegistro = LogsMapper::TABLE;
    $maxIntento = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaIntentos}`")->fetchColumn();
    $maxRegistro = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaRegistro}`")->fetchColumn();
    $usuarios = [];
    $usuarioActualPrevio = get_config('current_user');

    try {

        //─── a · La fila ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] La fila');
        $check((int) $fila['id'] === -10 && (int) $fila['type'] === UsersModel::TYPE_USER_SYSTEM && (int) $fila['status'] === UsersModel::STATUS_USER_INACTIVE && $fila['organization'] === null,
            'a1 id -10, tipo 99, estado 0 (inactivo), sin organización', json_encode([$fila['id'], $fila['type'], $fila['status']]) ?: '');
        $hash = (string) $fila['password'];
        $check(!password_verify('', $hash) && !password_verify('!', $hash) && !password_verify('system', $hash) && !password_verify($prefijo, $hash),
            'a2 password_verify() no acepta ninguna cadena (vacía, «!», el nombre, una al azar)');
        $check(!array_key_exists(UsersModel::TYPE_USER_SYSTEM, UsersModel::TYPES_USERS) && !\PiecesPHP\Core\Roles::roleExists(UsersModel::TYPE_USER_SYSTEM),
            'a3 su tipo no está en TYPES_USERS ni tiene rol');
        $check(UserHierarchy::rank(UsersModel::TYPE_USER_SYSTEM) === UserHierarchy::RANK_NOBODY
            && !UserHierarchy::canActOn(UsersModel::TYPE_USER_ADMIN_GRAL, null, UsersModel::TYPE_USER_SYSTEM, null),
            'a4 su rango es RANK_NOBODY: un administrador general no actúa sobre él');
        $check(UsersModel::isSystemUser(-10) && !UsersModel::isSystemUser(1) && !UsersModel::isSystemUser(null) && !UsersModel::isSystemUser(0),
            'a5 isSystemUser() solo es cierto para -10');

        //─── b · No entra ni recupera nada ────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] No entra ni recupera nada');
        $login = $ruta('users-login-request');
        $inexistente = "{$prefijo}-nadie";
        $deNadie = $pedir($login, '', ['username' => $inexistente, 'password' => '!']);
        $check(($deNadie['json']['error'] ?? null) === UsersController::INCORRECT_PASSWORD, 'b1 CANARIO: un usuario que no existe da INCORRECT_PASSWORD (P201)', $deNadie['body']);
        foreach (['system' => 'el nombre', 'system@localhost.invalid' => 'el correo'] as $quien => $como) {
            $r = $pedir($login, '', ['username' => $quien, 'password' => '!']);
            $check(($r['json']['auth'] ?? null) === false && ($r['json']['error'] ?? null) === UsersController::INCORRECT_PASSWORD && ($r['json']['message'] ?? 'a') === ($deNadie['json']['message'] ?? 'b'),
                "b2 por {$como}, el login lo rechaza como a uno que no existe (no «inactivo»): mismo error y mismo mensaje", (string) ($r['json']['error'] ?? $r['status']));
        }
        $check((new RecoveryPasswordController())->resolveUser('system') === null && (new RecoveryPasswordController())->resolveUser('system@localhost.invalid') === null,
            'b3 la recuperación de contraseña no lo encuentra, ni por nombre ni por correo');
        $check((new RecoveryPasswordController())->resolveUser('root') !== null || (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = 'root'")->fetchColumn() === 0,
            'b4 CANARIO: la recuperación sí encuentra a un usuario real');
        //Quien no existe recibe la respuesta de un envío (P201): que no se envíe nada se mide en la tabla de problemas.
        $tablaProblemas = (new UserProblemsModel())->getModel()->getTable();
        $codigosDeSistema = fn (): int => (int) $database->query("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email = 'system@localhost.invalid'")->fetchColumn();
        $codigosAntes = $codigosDeSistema();
        $olvido = $pedir($ruta('users-forget-request-code'), '', ['username' => 'system', 'type' => UserProblemsController::TYPE_USER_FORGET]);
        $olvidoNadie = $pedir($ruta('users-forget-request-code'), '', ['username' => $inexistente, 'type' => UserProblemsController::TYPE_USER_FORGET]);
        $check(is_array($olvido['json']) && $olvido['json'] === $olvidoNadie['json'] && $codigosDeSistema() === $codigosAntes,
            'b5 «usuario olvidado» lo trata como a uno que no existe y no crea código', $olvido['body']);
        $bloqueo = $pedir($ruta('users-blocked-request-code'), '', ['username' => 'system', 'type' => UserProblemsController::TYPE_USER_BLOCKED]);
        $bloqueoNadie = $pedir($ruta('users-blocked-request-code'), '', ['username' => $inexistente, 'type' => UserProblemsController::TYPE_USER_BLOCKED]);
        $check(is_array($bloqueo['json']) && $bloqueo['json'] === $bloqueoNadie['json'] && $codigosDeSistema() === $codigosAntes, 'b6 «usuario bloqueado», igual', $bloqueo['body']);

        //─── c · Fuera de los listados y recuentos ────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Fuera de los listados');
        $ids = fn(array $lista): array => array_map(fn($u) => (int) (is_object($u) && property_exists($u, 'id') ? $u->id : (is_array($u) ? ($u['id'] ?? 0) : 0)), $lista);
        $todos = UsersModel::all();
        $check(count($todos) > 0 && !in_array($sistema, $ids($todos), true), 'c3 UsersModel::all() no lo trae (y trae a alguien)', (string) count($todos));
        $check(UsersModel::getUsersByType(UsersModel::TYPE_USER_SYSTEM) === [] && UsersModel::getUsersByTypes([UsersModel::TYPE_USER_SYSTEM]) === [],
            'c4 getUsersByType/getUsersByTypes con su tipo dan vacío');
        $porCriterio = UsersModel::allByMultipleCriteries([['column' => 'username', 'value' => 'system'], ['column' => 'email', 'value' => 'system@localhost.invalid', 'beforeOperator' => 'OR']]);
        $check(is_array($porCriterio) && $porCriterio === [], 'c5 allByMultipleCriteries no lo trae aunque se le pida por nombre O por correo');
        $paginado = UsersController::_all(1, 5000, UsersModel::TYPE_USER_SYSTEM);
        $check($paginado->totalElements() === 0, 'c6 UsersController::_all con su tipo cuenta 0', (string) $paginado->totalElements());

        $root = new UsersModel();
        $root->username = "{$prefijo}-root";
        $root->email = "{$prefijo}-root@example.com";
        $root->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $root->firstname = 'Zz';
        $root->secondname = '';
        $root->firstLastname = 'Agente';
        $root->secondLastname = '';
        $root->type = UsersModel::TYPE_USER_ROOT;
        $root->status = UsersModel::STATUS_USER_ACTIVE;
        $root->failedAttempts = 0;
        $root->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $root->createdAt = new \DateTime();
        $root->modifiedAt = $root->createdAt;
        $root->save();
        $usuarios[] = (int) $root->id;
        $sid = UserSessions::open((int) $root->id);
        $jwt = SessionToken::generateToken(['id' => (int) $root->id, 'sid' => $sid], null, SessionToken::DURATION_MAX, false);

        $listado = $pedir($ruta('users-datatables'), $jwt, null, $tabla);
        $nombres = array_map(fn($f) => (string) ($f[4] ?? ''), $filas($listado));
        $check($listado['status'] === 200 && count($nombres) > 0 && !in_array('system', $nombres, true), 'c7 el listado de usuarios (como root) no lo trae', (string) $listado['status']);
        //RETORNO-IGNORADO: lo que cuenta es la fila que sigue, y se comprueba.
        $consulta->execute([$sistema]);
        $despues = $consulta->fetch(\PDO::FETCH_ASSOC);
        $check(is_array($despues) && (int) $despues['status'] === UsersModel::STATUS_USER_INACTIVE, 'c8 y pintar el listado no lo marca borrado');
        $buscador = $pedir($ruta('users-search-dropdown'), $jwt, null, ['search' => 'system']);
        $encontrados = array_map(fn($r) => (string) ($r['value'] ?? $r['id'] ?? ''), is_array($buscador['json']['results'] ?? null) ? $buscador['json']['results'] : []);
        $check($buscador['status'] === 200 && !in_array((string) $sistema, $encontrados, true), 'c9 el buscador de usuarios no lo ofrece', $buscador['body']);
        $sinIngresos = $pedir($ruta('login-attempts-reports-ajax', ['type' => 'not-logged']), $jwt, null, $tabla);
        $nombresSinIngresos = array_map(fn($f) => (string) ($f[1] ?? ''), $filas($sinIngresos));
        $check($sinIngresos['status'] === 200 && count($nombresSinIngresos) > 0 && !in_array('system', $nombresSinIngresos, true), 'c10 «usuarios sin ingresos» no lo trae', (string) $sinIngresos['status']);

        //─── d · No se edita, no se suplanta, no se le cierran sesiones ───────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] No se toca');
        $editar = $pedir($ruta('users-edit-request'), $jwt, ['id' => $sistema, 'username' => 'system', 'email' => 'system@localhost.invalid', 'firstname' => 'Sistema', 'first_lastname' => 'X', 'status' => UsersModel::STATUS_USER_ACTIVE]);
        $check($editar['status'] === 404, 'd1 editarlo o cambiarle el estado, como root: 404', (string) $editar['status']);
        //RETORNO-IGNORADO: lo que cuenta es la fila que sigue, y se comprueba.
        $consulta->execute([$sistema]);
        $tras = $consulta->fetch(\PDO::FETCH_ASSOC);
        $check(is_array($tras) && (int) $tras['status'] === UsersModel::STATUS_USER_INACTIVE && (string) $tras['username'] === 'system', 'd2 y la fila sigue igual');
        $formulario = $pedir($ruta('users-form-edit', ['id' => $sistema]), $jwt);
        $check($formulario['status'] === 404, 'd3 el formulario de edición: 404', (string) $formulario['status']);
        $check(!UsersController::canManageUser(new UsersModel((int) $root->id), new UsersModel($sistema)), 'd4 canManageUser() lo niega a root (el avatar y el formulario)');
        $cerrar = $pedir($ruta('users-revoke-sessions-request'), $jwt, ['id' => $sistema]);
        $check($cerrar['status'] === 400, 'd5 cerrarle las sesiones: 400 (id < 1)', (string) $cerrar['status']);
        $suplantar = $pedir($ruta('user-impersonation-start'), $jwt, ['userID' => $sistema]);
        $huella = $database->prepare("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE id > ? AND referenceValue = ? AND textMessage = ?");
        //RETORNO-IGNORADO: lo que cuenta es el COUNT que sigue, y se comprueba.
        $huella->execute([$maxRegistro, (string) $sistema, LogsMapper::MESSAGES[LogsMapper::MSG_IMPERSONATION_START]]);
        $check($suplantar['status'] === 400 && (int) $huella->fetchColumn() === 0, 'd6 suplantarlo desde el panel: 400 y sin huella de inicio', (string) $suplantar['status']);

        //─── e · Lo hecho sin sesión es suyo ──────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[e] Lo hecho sin sesión');
        set_config('current_user', null);
        getLoggedFrameworkUser(true);
        LogsMapper::addLog(LogsMapper::MSG_IMPERSONATION_END, ['username' => $prefijo]);
        set_config('current_user', $usuarioActualPrevio);
        getLoggedFrameworkUser(true);
        $leido = $database->prepare("SELECT createdBy, meta FROM `{$tablaRegistro}` WHERE id > ? AND textMessageVariables LIKE ? ORDER BY id DESC LIMIT 1");
        //RETORNO-IGNORADO: lo que cuenta es la fila que sigue, y se comprueba.
        $leido->execute([$maxRegistro, "%{$prefijo}%"]);
        $registro = $leido->fetch(\PDO::FETCH_ASSOC);
        $meta = is_array($registro) ? json_decode((string) $registro['meta'], true) : null;
        $check(is_array($registro) && (int) $registro['createdBy'] === $sistema, 'e1 un addLog sin sesión deja createdBy = -10', json_encode($registro) ?: '');
        $check(($meta[LogsMapper::META_ACTOR]['kind'] ?? null) === LogsMapper::ACTOR_SYSTEM, 'e2 y su meta dice que actuó el sistema');

        //─── f · Sin aprobación ───────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[f] Sin aprobación');
        \SystemApprovals\Util\SystemApprovalManager::init();
        $aprobaciones = $database->prepare('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = ? AND referenceValue = ?");
        //RETORNO-IGNORADO: lo que cuenta es el COUNT que sigue, y se comprueba.
        $aprobaciones->execute([$tablaUsuarios, (string) $sistema]);
        $delSistema = (int) $aprobaciones->fetchColumn();
        $check($delSistema === 0, 'f1 tras init(), el usuario de sistema no tiene ninguna aprobación', "{$delSistema} fila(s)");
        //RETORNO-IGNORADO: lo que cuenta es el COUNT que sigue, y se comprueba.
        $aprobaciones->execute([$tablaUsuarios, (string) $root->id]);
        $check((int) $aprobaciones->fetchColumn() === 1, 'f2 CANARIO: un usuario nuevo sí recibe la suya');

    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . $e->getMessage());
    } finally {
        set_config('current_user', $usuarioActualPrevio);
        getLoggedFrameworkUser(true);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $database->prepare("DELETE FROM `{$tablaRegistro}` WHERE id > ? AND (textMessageVariables LIKE ? OR referenceValue = ? OR createdBy IN (" . (empty($usuarios) ? '0' : implode(',', $usuarios)) . '))')
            ->execute([$maxRegistro, "%{$prefijo}%", (string) $sistema]);
        $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND (usernameAttempt IN ('system', 'system@localhost.invalid') OR usernameAttempt LIKE ?)")
            ->execute([$maxIntento, "{$prefijo}%"]);
        if (!empty($usuarios)) {
            $marcas = implode(',', array_fill(0, count($usuarios), '?'));
            $database->prepare('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})")->execute($usuarios);
            $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE userID IN ({$marcas})")->execute($usuarios);
            $database->prepare("DELETE FROM `{$tablaRegistro}` WHERE createdBy IN ({$marcas})")->execute($usuarios);
            //Las aprobaciones no tienen clave foránea: sin esto quedaban huérfanas sin que nada fallara.
            $database->prepare("DELETE FROM `" . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue IN ({$marcas})")->execute(array_map('strval', $usuarios));
            $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$marcas})")->execute($usuarios);
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE '{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE id > {$maxRegistro} AND textMessageVariables LIKE '%{$prefijo}%'")->fetchColumn();
        if (!empty($usuarios)) {
            $quedan += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = '{$tablaUsuarios}' AND referenceValue IN ('" . implode("','", $usuarios) . "')")->fetchColumn();
        }
        $check($quedan === 0, 'z1 no queda ningún usuario, aprobación ni registro de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('El usuario de sistema (B14): la fila (-10, tipo 99, inactivo, sin contraseña válida, rango de nadie); no entra, no recupera contraseña ni usuario; no aparece en los listados de usuarios, el buscador, «sin ingresos» ni los recuentos por tipo; editarlo da 404, no se suplanta ni se le cierran sesiones; y un registro sin sesión queda a su nombre. Por HTTP y en proceso. Crea y borra un root de prueba.')->setEffects([CliActions::EFFECT_DATABASE])->register();
