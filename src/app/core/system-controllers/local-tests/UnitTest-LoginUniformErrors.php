<?php

//Sin la contraseña buena, el acceso y las recuperaciones no dicen si un usuario existe ni en qué estado está (P201). En
//proceso, con REMOTE_ADDR de documentación propias. Siembra zz- y lo retira.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UserProblemsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/login-uniform-errors';
$cliTaskDescription = 'Sin la contraseña buena, el acceso y las recuperaciones no dicen si el usuario existe ni en qué estado está';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:LoginUniformErrors] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'p0 hay conexión a la base')) {
        return ['success' => false, 'message' => 'Sin conexión a la base.'];
    }
    //IP de documentación (RFC 5737) de esta suite: .101-.104 para las comprobaciones y .150-.189 para medir el tiempo, una por
    //acceso, porque el freno por IP (core/login-ip-throttle) cortaría a partir del quinto fallo.
    $ipsPropias = array_merge(array_map(fn (int $n): string => "198.51.100.{$n}", range(101, 104)), array_map(fn (int $n): string => "198.51.100.{$n}", range(150, 189)));
    $listaIPs = implode(', ', array_map(fn (string $ip): string => $database->quote($ip), $ipsPropias));
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $tablaProblemas = (new UserProblemsModel())->getModel()->getTable();
    $prefijo = 'zz-prueba-uniforme-' . bin2hex(random_bytes(3));
    $clave = 'zz-Clave-Buena-1';
    $usuarios = [];
    $organizaciones = [];
    $direccionPrevia = $_SERVER['REMOTE_ADDR'] ?? null;
    $database->exec("DELETE FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs})");

    try {
        $crearUsuario = function (string $sufijo, int $estado, int $organizacion = OrganizationMapper::INITIAL_ID_GLOBAL) use ($prefijo, $clave, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Uniforme';
            $u->secondLastname = '';
            $u->type = UsersModel::TYPE_USER_GENERAL;
            $u->status = $estado;
            $u->failedAttempts = 0;
            $u->organization = $organizacion;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $peticion = fn (array $cuerpo): RequestRoute => (new RequestRoute('POST', (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-uniforme'), new \Slim\Psr7\Headers(), [], [], (new \Slim\Psr7\Factory\StreamFactory())->createStream('')))
            ->withParsedBody($cuerpo);
        $entrar = function (string $ip, string $usuario, string $contrasena) use ($peticion): array {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $respuesta = (new UsersController())->login($peticion(['username' => $usuario, 'password' => $contrasena, 'overwriteSession' => true]), new ResponseRoute());
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            return is_array($cuerpo) ? $cuerpo : [];
        };
        $intentosDe = fn (int $id): int => (int) $database->query("SELECT failedAttempts FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn();

        //──── 1. El acceso con la contraseña mala ──────────────────────────────────────────────
        echoTerminal('[1/4] Con la contraseña mala, todos responden lo mismo');

        $crearUsuario('activo', UsersModel::STATUS_USER_ACTIVE);
        $nadie = $entrar('198.51.100.101', "{$prefijo}-nadie", 'zz-mala');
        $mala = $entrar('198.51.100.101', "{$prefijo}-activo", 'zz-mala');
        $check(($mala['error'] ?? null) === UsersController::INCORRECT_PASSWORD, 'a1 CANARIO: el que existe, con la contraseña mala, da INCORRECT_PASSWORD', json_encode($mala, JSON_THROW_ON_ERROR));
        $check(($nadie['error'] ?? null) === UsersController::INCORRECT_PASSWORD, 'a2 DISCRIMINANTE: el que no existe da INCORRECT_PASSWORD, no USER_NO_EXISTS', json_encode($nadie, JSON_THROW_ON_ERROR));
        $check(($nadie['message'] ?? 'a') === ($mala['message'] ?? 'b') && ($nadie['auth'] ?? null) === false, 'a3 y el mismo mensaje que el que existe', ($nadie['message'] ?? '') . ' | ' . ($mala['message'] ?? ''));
        $check(!str_contains((string) ($nadie['message'] ?? ''), $prefijo), 'a4 el mensaje no lleva el nombre tecleado');
        $viaNadie = (string) $database->query("SELECT extraData FROM `{$tablaIntentos}` WHERE usernameAttempt = " . $database->quote("{$prefijo}-nadie") . ' ORDER BY id DESC LIMIT 1')->fetchColumn();
        $check(str_contains($viaNadie, 'login-password'), 'a5 el intento del que no existe se registra con la vía login-password: cuenta para el freno por IP', $viaNadie);

        $estados = [
            'bloqueado' => [UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::BLOCKED_FOR_ATTEMPTS],
            'inactivo' => [UsersModel::STATUS_USER_INACTIVE, UsersController::INACTIVE_USER],
        ];
        $check(!UsersModel::REQUIRE_APPROBATION_FOR_LOGIN, 'a6 esta instalación no exige aprobación: el pendiente entra y no tiene mensaje de estado que esconder');
        foreach ($estados as $sufijo => [$estado, $errorDeEstado]) {
            $id = $crearUsuario($sufijo, $estado);
            $r = $entrar('198.51.100.102', "{$prefijo}-{$sufijo}", 'zz-mala');
            $check(($r['error'] ?? null) === UsersController::INCORRECT_PASSWORD && ($r['message'] ?? null) === ($mala['message'] ?? ''),
                "a7 DISCRIMINANTE: {$sufijo} con la contraseña mala da INCORRECT_PASSWORD y el mismo mensaje, no su estado", (string) ($r['error'] ?? '?'));
            $check($intentosDe($id) === 0, "a8 {$sufijo}: el fallo se registra y no suma failedAttempts");
            $r = $entrar('198.51.100.103', "{$prefijo}-{$sufijo}", $clave);
            $check(($r['error'] ?? null) === $errorDeEstado && ($r['auth'] ?? null) === false, "a9 {$sufijo} con la contraseña BUENA sí da su estado: {$errorDeEstado}", (string) ($r['error'] ?? '?'));
        }
        $r = $entrar('198.51.100.103', "{$prefijo}-activo", $clave);
        $check(($r['auth'] ?? null) === true, 'a10 CANARIO: el activo con la contraseña buena entra');

        //a11: el fallo que COMPLETA el bloqueo (failedAttempts al tope y fallos desde 3 IP) responde como un usuario que no existe.
        $idBloquea = $crearUsuario('bloquea', UsersModel::STATUS_USER_ACTIVE);
        $database->exec("UPDATE `{$tablaUsuarios}` SET failedAttempts = " . (UsersController::MAX_ATTEMPTS - 1) . " WHERE id = {$idBloquea}");
        $entrar('198.51.100.150', "{$prefijo}-bloquea", 'zz-mala');
        $entrar('198.51.100.151', "{$prefijo}-bloquea", 'zz-mala');
        $alBloquear = $entrar('198.51.100.152', "{$prefijo}-bloquea", 'zz-mala');
        $nadieAlBloquear = $entrar('198.51.100.152', "{$prefijo}-nadie", 'zz-mala');
        $estadoBloquea = (int) $database->query("SELECT status FROM `{$tablaUsuarios}` WHERE id = {$idBloquea}")->fetchColumn();
        //`user` es el texto que tecleó quien pide: se compara todo lo demás.
        $sinUsuario = function (array $cuerpo): array {
            unset($cuerpo['user']);
            return $cuerpo;
        };
        $check($estadoBloquea === UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'a11 CANARIO: ese fallo bloquea la cuenta', "estado {$estadoBloquea}");
        $check($alBloquear !== [] && $sinUsuario($alBloquear) === $sinUsuario($nadieAlBloquear),
            'a12 DISCRIMINANTE: y responde EXACTAMENTE lo mismo que un usuario que no existe (salvo `user`, lo tecleado)', json_encode($alBloquear, JSON_THROW_ON_ERROR));

        //a13-a21: BAJO ATAQUE (4 fallos en la ventana desde 3 IP, la condición del bloqueo), una cuenta que no puede entrar
        //no dice su estado ni con la buena (P223 a). Con IP propias: cada una falla dos veces como mucho, lejos del freno por IP.
        $atacar = function (string $sufijo, array $ips) use ($entrar, $prefijo): void {
            foreach ($ips as $ip) {
                $entrar($ip, "{$prefijo}-{$sufijo}", 'zz-mala');
            }
        };
        $crearUsuario('ataque-bloqueado', UsersModel::STATUS_USER_ATTEMPTS_BLOCK);
        $r = $entrar('198.51.100.110', "{$prefijo}-ataque-bloqueado", $clave);
        $check(($r['error'] ?? null) === UsersController::BLOCKED_FOR_ATTEMPTS, 'a13 CANARIO: bloqueada SIN ataque, con la buena, sigue diciendo su estado (P201)', (string) ($r['error'] ?? '?'));
        $atacar('ataque-bloqueado', ['198.51.100.111', '198.51.100.112', '198.51.100.113', '198.51.100.111']);
        $r = $entrar('198.51.100.114', "{$prefijo}-ataque-bloqueado", $clave);
        $check(($r['error'] ?? null) === UsersController::INCORRECT_PASSWORD && ($r['message'] ?? null) === ($mala['message'] ?? '') && ($r['auth'] ?? null) === false,
            'a14 DISCRIMINANTE: la misma, con 4 fallos desde 3 IP, con la buena da INCORRECT_PASSWORD y el mismo mensaje', json_encode($r, JSON_THROW_ON_ERROR));
        $fila = $database->query("SELECT success, extraData FROM `{$tablaIntentos}` WHERE usernameAttempt = " . $database->quote("{$prefijo}-ataque-bloqueado") . ' ORDER BY id DESC LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        $extra = is_array($fila) ? json_decode((string) $fila['extraData'], true) : null;
        $check(is_array($fila) && (int) $fila['success'] === LoginAttemptsModel::FAIL_ATTEMPT && ($extra['otpVia'] ?? null) === 'login-password' && ($extra['statusWithheld'] ?? null) === true,
            'a15 y ese intento se registra como un fallo de contraseña, que cuenta, y marcado con statusWithheld', json_encode($fila, JSON_THROW_ON_ERROR));
        $crearUsuario('ataque-inactivo', UsersModel::STATUS_USER_INACTIVE);
        $atacar('ataque-inactivo', ['198.51.100.115', '198.51.100.116', '198.51.100.117', '198.51.100.115']);
        $r = $entrar('198.51.100.118', "{$prefijo}-ataque-inactivo", $clave);
        $check(($r['error'] ?? null) === UsersController::INCORRECT_PASSWORD, 'a16 inactiva bajo ataque, con la buena: INCORRECT_PASSWORD (la pendiente entra aquí: a6)', (string) ($r['error'] ?? '?'));
        $crearUsuario('dos-ips', UsersModel::STATUS_USER_ATTEMPTS_BLOCK);
        $atacar('dos-ips', ['198.51.100.119', '198.51.100.120', '198.51.100.119', '198.51.100.120']);
        $r = $entrar('198.51.100.121', "{$prefijo}-dos-ips", $clave);
        $check(($r['error'] ?? null) === UsersController::BLOCKED_FOR_ATTEMPTS, 'a17 con 4 fallos desde solo 2 IP no es ataque (P200 a): sigue diciendo su estado', (string) ($r['error'] ?? '?'));
        $ahora = date('Y-m-d H:i:s');
        $database->prepare('INSERT INTO `' . OrganizationMapper::TABLE . '` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-org", 'zz-' . bin2hex(random_bytes(4)), "{$prefijo}-org", $ahora, UsersModel::SYSTEM_USER_ID, OrganizationMapper::INACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass()])]);
        $organizaciones[] = (int) $database->lastInsertId();
        $crearUsuario('org-inactiva', UsersModel::STATUS_USER_ACTIVE, end($organizaciones));
        $r = $entrar('198.51.100.122', "{$prefijo}-org-inactiva", $clave);
        $check(($r['error'] ?? null) === UsersController::ORGANIZATION_IS_NOT_ACTIVE, 'a18 CANARIO: activa de una organización inactiva, sin ataque, con la buena dice ORGANIZATION_IS_NOT_ACTIVE', (string) ($r['error'] ?? '?'));
        $atacar('org-inactiva', ['198.51.100.123', '198.51.100.124', '198.51.100.125', '198.51.100.123']);
        $r = $entrar('198.51.100.124', "{$prefijo}-org-inactiva", $clave);
        $check(($r['error'] ?? null) === UsersController::INCORRECT_PASSWORD, 'a19 y bajo ataque, con la buena: INCORRECT_PASSWORD', (string) ($r['error'] ?? '?'));
        //La lógica sin base, en sus bordes.
        $config = ['loginAccountWindowMinutes' => 1440, 'loginAccountDistinctIPs' => 3];
        $momento = new \DateTimeImmutable('2026-01-02 12:00:00');
        $fallo = fn (string $ip, string $cuando = '2026-01-02 11:00:00'): array => ['usernameAttempt' => 'zz-u', 'ip' => $ip, 'success' => LoginAttemptsModel::FAIL_ATTEMPT, 'date' => $cuando];
        $bajoAtaque = fn (array $filas): bool => OTPRateLimiter::loginAccountUnderAttackFromRows($filas, 'zz-u', UsersController::MAX_ATTEMPTS, $momento, $config);
        $check($bajoAtaque([$fallo('a'), $fallo('b'), $fallo('c'), $fallo('a')]), 'a20 CANARIO sin base: 4 fallos desde 3 IP en la ventana es ataque');
        $check(!$bajoAtaque([$fallo('a'), $fallo('b'), $fallo('c')]) && !$bajoAtaque([$fallo('a'), $fallo('b'), $fallo('a'), $fallo('b')])
            && !$bajoAtaque([$fallo('a'), $fallo('b'), $fallo('c'), $fallo('a', '2025-12-31 11:00:00')]),
            'a21 sin base: 3 fallos desde 3 IP, 4 desde 2 IP, o el cuarto fuera de la ventana: no es ataque');
        echoTerminal(' ');

        //──── 2. Usuario olvidado y desbloqueo ─────────────────────────────────────────────────
        echoTerminal('[2/4] generateCode() responde como un envío a quien no existe, sin crear nada');

        $_SERVER['REMOTE_ADDR'] = '198.51.100.104';
        $antes = (int) $database->query("SELECT COUNT(*) FROM `{$tablaProblemas}`")->fetchColumn();
        foreach ([UserProblemsController::TYPE_USER_FORGET, UserProblemsController::TYPE_USER_BLOCKED] as $tipo) {
            $respuesta = (new UserProblemsController())->generateCode($peticion(['username' => "{$prefijo}-nadie@example.com", 'type' => $tipo]), new ResponseRoute());
            $cuerpo = (array) json_decode((string) $respuesta->getBody(), true);
            $check(($cuerpo['send_mail'] ?? null) === true && ($cuerpo['error'] ?? null) === UserProblemsController::NO_ERROR
                && ($cuerpo['message'] ?? null) === __(UserProblemsController::LANG_GROUP, 'Se ha enviado un mensaje al correo proporcionado.'),
                "b1 DISCRIMINANTE: {$tipo} con un correo que no existe: send_mail true y el mensaje de envío", (string) json_encode($cuerpo));
        }
        $despues = (int) $database->query("SELECT COUNT(*) FROM `{$tablaProblemas}`")->fetchColumn();
        $check($despues === $antes, 'b2 y no se crea ninguna fila de problemas', "antes {$antes}, después {$despues}");
        echoTerminal(' ');

        //──── 3. Un código cuyo usuario ya no existe ───────────────────────────────────────────
        echoTerminal('[3/4] resolveProblem() con un código vivo de un usuario que ya no existe');

        $codigo = (string) random_int(900000, 999999);
        $problema = new UserProblemsModel();
        $problema->created = new \DateTime();
        $problema->expired = (clone $problema->created)->modify('+1 hour');
        $problema->email = "{$prefijo}-ido@example.com";
        $problema->code = $codigo;
        $problema->type = UserProblemsController::TYPE_USER_FORGET;
        $problema->save();
        $respuesta = (new UserProblemsController())->resolveProblem($peticion(['code' => $codigo, 'type' => UserProblemsController::TYPE_USER_FORGET]), new ResponseRoute());
        $cuerpo = (array) json_decode((string) $respuesta->getBody(), true);
        $check(($cuerpo['error'] ?? null) === UserProblemsController::EXPIRED_OR_NOT_EXIST_CODE && ($cuerpo['success'] ?? null) === false,
            'c1 DISCRIMINANTE: EXPIRED_OR_NOT_EXIST_CODE, no USER_NO_EXISTS', (string) json_encode($cuerpo));
        echoTerminal(' ');

        //──── 4. El tiempo ─────────────────────────────────────────────────────────────────────
        echoTerminal('[4/4] El tiempo: 20 accesos de cada tipo, una IP por acceso (dato, no puerta)');

        $mediana = function (array $valores): float {
            sort($valores);
            $n = count($valores);
            return $n % 2 === 1 ? $valores[intdiv($n, 2)] : ($valores[$n / 2 - 1] + $valores[$n / 2]) / 2;
        };
        $tiempos = ['nadie' => [], 'existe' => []];
        $crearUsuario('tiempo', UsersModel::STATUS_USER_ACTIVE);
        for ($i = 0; $i < 20; $i++) {
            $inicio = hrtime(true);
            $entrar('198.51.100.' . (150 + $i), "{$prefijo}-nadie-t", 'zz-mala');
            $tiempos['nadie'][] = (hrtime(true) - $inicio) / 1e6;
            $inicio = hrtime(true);
            $entrar('198.51.100.' . (170 + $i), "{$prefijo}-tiempo", 'zz-mala');
            $tiempos['existe'][] = (hrtime(true) - $inicio) / 1e6;
        }
        echoTerminal(sprintf('   medido: mediana del que no existe %.1f ms; del que existe con la contraseña mala %.1f ms', $mediana($tiempos['nadie']), $mediana($tiempos['existe'])));
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($direccionPrevia === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $direccionPrevia;
        }
        try {
            $database->exec("DELETE FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs}) OR usernameAttempt LIKE " . $database->quote("{$prefijo}%"));
            $database->exec("DELETE FROM `{$tablaProblemas}` WHERE email LIKE " . $database->quote("{$prefijo}%"));
            foreach ($usuarios as $id) {
                $database->exec("DELETE FROM `{$tablaIntentos}` WHERE userID = {$id}");
                $database->exec('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID = {$id}");
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
            foreach ($organizaciones as $id) {
                $database->exec('DELETE FROM `' . OrganizationMapper::TABLE . "` WHERE id = {$id}");
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs}) OR usernameAttempt LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
             + ($usuarios === [] ? 0 : (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . '` WHERE userID IN (' . implode(', ', $usuarios) . ')')->fetchColumn());
        $check($quedan === 0, 'z1 no queda ningún usuario, intento de acceso, fila de problemas ni sesión de la prueba', "quedan {$quedan}");
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:LoginUniformErrors] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Sin la contraseña buena no se sabe si el usuario existe ni cómo está ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_DATABASE])->register();
