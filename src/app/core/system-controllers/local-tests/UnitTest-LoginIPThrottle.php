<?php

//El freno del login con contraseña: por IP antes de bloquear una cuenta, y la cuenta solo con fallos desde varias IP. La
//lógica, con filas sintéticas; el login, en proceso, variando REMOTE_ADDR. Siembra zz- y lo retira.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/login-ip-throttle';
$cliTaskDescription = 'El login frena por IP antes de bloquear una cuenta, y la cuenta solo se bloquea con fallos desde varias IP';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:LoginIPThrottle] Iniciando suite...', true, "\r\n", '33');
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

    $config = OTPRateLimiter::config();
    $ahora = new \DateTime('2026-01-10 12:00:00');
    $fila = static function (string $usuario, string $ip, int $minutosAtras, array $extra = ['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD]) use ($ahora): array {
        return [
            'usernameAttempt' => $usuario,
            'ip' => $ip,
            'success' => LoginAttemptsModel::FAIL_ATTEMPT,
            'date' => (clone $ahora)->modify("-{$minutosAtras} minutes")->format('Y-m-d H:i:s'),
            'extraData' => json_encode($extra),
        ];
    };
    $fallos = static function (array $minutosAtras, string $usuario = 'ana', string $ip = '10.0.0.1', array $extra = ['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD]) use ($fila): array {
        return array_map(fn (int $minutos): array => $fila($usuario, $ip, $minutos, $extra), $minutosAtras);
    };
    $segundos = static function (array $filas, string $ip = '10.0.0.1') use ($ahora, $config): int {
        return OTPRateLimiter::loginSecondsToUnlockFromRows($filas, $ip, $ahora, $config);
    };
    $ips = static function (array $filas, string $usuario = 'ana') use ($ahora, $config): int {
        return OTPRateLimiter::loginDistinctIPsFromRows($filas, $usuario, $ahora, $config);
    };

    //──── 1. La configuración ──────────────────────────────────────────────────────────────────
    echoTerminal('[1/6] config(): las tres claves del login, y su vía fuera de las del OTP');

    $check($config['loginMaxFailuresPerIP'] === 5 && $config['loginAccountDistinctIPs'] === 3 && $config['loginAccountWindowMinutes'] === 1440,
        'otp_security de config.php: 5 fallos por IP, 3 IP distintas, ventana de la cuenta de 1440 minutos', json_encode($config, JSON_THROW_ON_ERROR));
    $check(!in_array(OTPRateLimiter::VIA_LOGIN_PASSWORD, OTPRateLimiter::VIAS, true), 'login-password NO está en VIAS: los topes del OTP no cuentan las contraseñas');
    echoTerminal(' ');

    //──── 2. El freno por IP ───────────────────────────────────────────────────────────────────
    echoTerminal('[2/6] El freno por IP: el tope dentro de windowMinutes, y dura lockMinutes desde el fallo que lo completa');

    $lock = $config['lockMinutes'] * 60;
    $check($segundos($fallos([4, 3, 2, 1])) === 0, 'debajo del tope: 4 fallos desde una IP no frenan');
    $check($segundos($fallos([4, 3, 2, 1, 0])) === $lock, "DISCRIMINANTE: el 5.º frena la IP {$config['lockMinutes']} minutos desde él");
    $check($segundos($fallos([10, 9, 8, 7, 6])) === $lock - 360, 'el 5.º fue hace 6 minutos: faltan lockMinutes menos 6');
    $check($segundos($fallos([40, 30, 20, 10, 1])) === 0, 'fuera de la ventana no cuentan: nunca 5 dentro de windowMinutes');
    $varios = [];
    foreach (['ana', 'beto', 'carla', 'dario', 'eva'] as $i => $usuario) {
        $varios[] = $fila($usuario, '10.0.0.1', $i);
    }
    $check($segundos($varios) === $lock, 'cuenta la IP, no el usuario: 5 usuarios distintos desde la misma IP la frenan');
    $check($segundos($fallos([4, 3, 2, 1, 0]), '10.0.0.2') === 0, 'otra IP no queda frenada');
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD, 'otpLocked' => true])) === 0,
        'DISCRIMINANTE: las respuestas frenadas (otpLocked) se registran, pero no cuentan: el freno no se alarga solo');
    $sinColumna = array_map(function (array $f): array {
        unset($f['extraData']);
        return $f;
    }, $fallos([4, 3, 2, 1, 0]));
    $check($segundos($sinColumna) === $lock, 'sin la columna extraData no se sabe la vía y cuenta: falla cerrado');
    $check($segundos([['basura' => true], 'no es una fila', $fila('ana', '10.0.0.1', 0)]) === 0, 'una fila ilegible se ignora, y un fallo solo no frena');
    echoTerminal(' ');

    //──── 3. Las vías no se mezclan ────────────────────────────────────────────────────────────
    echoTerminal('[3/6] Las vías no se mezclan: el login no cuenta el OTP y el OTP no cuenta el login');

    foreach (OTPRateLimiter::VIAS as $via) {
        $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['otpVia' => $via])) === 0, "los fallos de la vía {$via} no frenan el login");
    }
    $check($segundos($fallos([4, 3, 2, 1, 0], 'ana', '10.0.0.1', ['dimensions' => ['w' => null]])) === 0, 'los fallos sin vía (los de antes de este cambio) no frenan el login');
    $variasIP = [];
    for ($i = 0; $i < 25; $i++) {
        $variasIP[] = $fila('ana', '10.0.0.1', intdiv($i, 3));
    }
    $check(OTPRateLimiter::secondsToUnlockFromRows($variasIP, 'ana', '10.0.0.1', $ahora, $config) === 0,
        'DISCRIMINANTE: 25 fallos de login-password del mismo usuario e IP no tocan secondsToUnlock() del OTP');
    echoTerminal(' ');

    //──── 4. La cuenta: IP distintas ───────────────────────────────────────────────────────────
    echoTerminal('[4/6] La cuenta: cuántas IP distintas tienen fallos de contraseña de un usuario');

    $check($ips(array_merge($fallos([5, 4, 3, 2], 'ana', '10.0.0.1'), $fallos([1], 'ana', '10.0.0.2'))) === 2, '2 IP: no llegan a loginAccountDistinctIPs');
    $tresIP = array_merge($fallos([3], 'ana', '10.0.0.1'), $fallos([2], 'ana', '10.0.0.2'), $fallos([1], 'ana', '10.0.0.3'));
    $check($ips($tresIP) === 3, 'DISCRIMINANTE: 3 IP sí llegan');
    $check($ips($tresIP, 'ANA ') === 3, 'el usuario se compara sin mayúsculas ni espacios');
    $check($ips($tresIP, 'beto') === 0, 'los fallos de otro usuario no cuentan');
    $viejo = $config['loginAccountWindowMinutes'] + 1;
    $check($ips(array_merge($fallos([$viejo], 'ana', '10.0.0.1'), $fallos([$viejo], 'ana', '10.0.0.2'), $fallos([1], 'ana', '10.0.0.3'))) === 1,
        'fuera de loginAccountWindowMinutes no cuentan');
    $check($ips(array_merge($fallos([3], 'ana', '10.0.0.1'), $fallos([2], 'ana', '10.0.0.2', ['otpVia' => OTPRateLimiter::VIA_LOGIN_TOTP]), $fallos([1], 'ana', '10.0.0.3', ['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD, 'otpLocked' => true]))) === 1,
        'las filas del OTP y las frenadas no suman IP');
    $tresSinColumna = array_map(function (array $f): array {
        unset($f['extraData']);
        return $f;
    }, $tresIP);
    $check($ips($tresSinColumna) === 3, 'sin la columna extraData cuentan: falla cerrado');
    echoTerminal(' ');

    //──── 5. El informe de intentos ────────────────────────────────────────────────────────────
    echoTerminal('[5/6] El informe de intentos: login-password se lee como el formulario de acceso');

    $formulario = LoginAttemptsModel::viaLabel(null);
    $check(LoginAttemptsModel::viaLabel(json_encode(['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD])) === $formulario, 'viaLabel(login-password) da «' . $formulario . '», como un fallo sin vía');
    $bloqueada = LoginAttemptsModel::viaLabel(['otpVia' => OTPRateLimiter::VIA_LOGIN_PASSWORD, 'otpLocked' => true]);
    $check(str_starts_with($bloqueada, $formulario . ' (') && $bloqueada !== $formulario, 'y con otpLocked, «' . $bloqueada . '»');
    echoTerminal(' ');

    //──── 6. De punta a punta ──────────────────────────────────────────────────────────────────
    echoTerminal('[6/6] De punta a punta: UsersController::login() en proceso, con REMOTE_ADDR de cada IP');

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'e0 hay conexión a la base')) {
        return ['success' => false, 'message' => 'Sin conexión a la base.'];
    }
    //IP de documentación (RFC 5737) reservadas para esta suite: sus filas son suyas y se borran al empezar y al acabar.
    $ipsPropias = ['198.51.100.71', '198.51.100.72', '198.51.100.81', '198.51.100.82', '198.51.100.83', '198.51.100.91', '198.51.100.92'];
    $listaIPs = implode(', ', array_map(fn (string $ip): string => $database->quote($ip), $ipsPropias));
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $prefijo = 'zz-prueba-freno-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $direccionPrevia = $_SERVER['REMOTE_ADDR'] ?? null;
    $database->exec("DELETE FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs})");

    try {
        $crearUsuario = function (string $sufijo) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash('zz-Clave-Buena-1', \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Freno';
            $u->secondLastname = '';
            $u->type = UsersModel::TYPE_USER_GENERAL;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $entrar = function (string $ip, string $usuario, string $clave): array {
            $_SERVER['REMOTE_ADDR'] = $ip;
            $peticion = (new RequestRoute('POST', (new \Slim\Psr7\Factory\UriFactory())->createUri('http://localhost/zz-login'), new \Slim\Psr7\Headers(), [], [], (new \Slim\Psr7\Factory\StreamFactory())->createStream('')))
                ->withParsedBody(['username' => $usuario, 'password' => $clave, 'overwriteSession' => true]);
            $respuesta = (new UsersController())->login($peticion, new ResponseRoute());
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            return ['status' => $respuesta->getStatusCode(), 'retryAfter' => $respuesta->getHeaderLine('Retry-After'), 'json' => is_array($cuerpo) ? $cuerpo : []];
        };
        $estadoDe = fn (int $id): array => (array) $database->query("SELECT status, failedAttempts FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetch(\PDO::FETCH_ASSOC);

        //e1-e4: desde UNA IP, seis contraseñas malas: la IP queda frenada y la cuenta no.
        $idUna = $crearUsuario('una-ip');
        $errores = [];
        for ($i = 1; $i <= 5; $i++) {
            $errores[] = (string) ($entrar('198.51.100.71', "{$prefijo}-una-ip", 'zz-mala')['json']['error'] ?? '?');
        }
        $check($errores === array_fill(0, 5, UsersController::INCORRECT_PASSWORD), 'e1 CANARIO: los 5 primeros fallos dan INCORRECT_PASSWORD', implode(', ', $errores));
        $sexto = $entrar('198.51.100.71', "{$prefijo}-una-ip", 'zz-mala');
        $check($sexto['status'] === 429 && ($sexto['json']['error'] ?? null) === OTPRateLimiter::ERROR_TOO_MANY_ATTEMPTS && ($sexto['json']['auth'] ?? null) === false && (int) $sexto['retryAfter'] > 0,
            'e2 DISCRIMINANTE: el 6.º da 429 TOO_MANY_ATTEMPTS con auth false y Retry-After', json_encode($sexto, JSON_THROW_ON_ERROR));
        $estado = $estadoDe($idUna);
        $check((int) ($estado['status'] ?? -1) === UsersModel::STATUS_USER_ACTIVE, 'e3 DISCRIMINANTE: la cuenta NO queda bloqueada por fallos desde una sola IP', json_encode($estado, JSON_THROW_ON_ERROR));
        $buena = $entrar('198.51.100.71', "{$prefijo}-una-ip", 'zz-Clave-Buena-1');
        $check($buena['status'] === 429 && ($buena['json']['auth'] ?? null) === false, 'e4 con la IP frenada ni la contraseña buena entra: no se mira el usuario', (string) $buena['status']);
        $otraIP = $entrar('198.51.100.72', "{$prefijo}-una-ip", 'zz-mala');
        $check($otraIP['status'] === 200 && ($otraIP['json']['error'] ?? null) === UsersController::INCORRECT_PASSWORD, 'e5 desde otra IP el mismo usuario no está frenado', json_encode($otraIP['json'], JSON_THROW_ON_ERROR));
        $marcados = (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip = '198.51.100.71' AND usernameAttempt = " . $database->quote("{$prefijo}-una-ip") . " AND extraData LIKE '%otpLocked%'")->fetchColumn();
        $check($marcados === 2, 'e6 los dos intentos frenados quedan registrados y marcados', "hay {$marcados}");

        //e7: desde 2 IP, con failedAttempts al tope, la cuenta no se bloquea.
        $idDos = $crearUsuario('dos-ip');
        $ultimo = [];
        foreach (['198.51.100.91', '198.51.100.91', '198.51.100.91', '198.51.100.92'] as $ip) {
            $ultimo = $entrar($ip, "{$prefijo}-dos-ip", 'zz-mala');
        }
        $estado = $estadoDe($idDos);
        $check((int) ($estado['status'] ?? -1) === UsersModel::STATUS_USER_ACTIVE && (int) ($estado['failedAttempts'] ?? 0) >= UsersController::MAX_ATTEMPTS && ($ultimo['json']['error'] ?? null) === UsersController::INCORRECT_PASSWORD,
            'e7 2 IP y failedAttempts al tope: la cuenta NO se bloquea y responde INCORRECT_PASSWORD', json_encode($estado, JSON_THROW_ON_ERROR));

        //e8: desde 3 IP, con failedAttempts al tope, se bloquea; y responde como cualquier contraseña mala (P201).
        $idTres = $crearUsuario('tres-ip');
        foreach (['198.51.100.81', '198.51.100.81', '198.51.100.82', '198.51.100.83'] as $ip) {
            $ultimo = $entrar($ip, "{$prefijo}-tres-ip", 'zz-mala');
        }
        $estado = $estadoDe($idTres);
        $deNadie = $entrar('198.51.100.83', "{$prefijo}-nadie-e8", 'zz-mala');
        $check((int) ($estado['status'] ?? -1) === UsersModel::STATUS_USER_ATTEMPTS_BLOCK && ($ultimo['json']['error'] ?? null) === UsersController::INCORRECT_PASSWORD
            && ($ultimo['json']['message'] ?? 'a') === ($deNadie['json']['message'] ?? 'b'),
            'e8 DISCRIMINANTE: 3 IP y failedAttempts al tope: la cuenta queda bloqueada, y la respuesta es INCORRECT_PASSWORD con el mismo mensaje que un usuario que no existe',
            json_encode($estado + ['error' => $ultimo['json']['error'] ?? null, 'message' => $ultimo['json']['message'] ?? null], JSON_THROW_ON_ERROR));
        //Recién bloqueada, sus fallos siguen en la ventana: está bajo ataque y no dice su estado (P223 a).
        $buena = $entrar('198.51.100.82', "{$prefijo}-tres-ip", 'zz-Clave-Buena-1');
        $check($buena['status'] === 200 && ($buena['json']['error'] ?? null) === UsersController::INCORRECT_PASSWORD && ($buena['json']['auth'] ?? null) === false,
            'e8b con la contraseña buena, durante el ataque, el dueño ve INCORRECT_PASSWORD (P223 a)', (string) ($buena['json']['error'] ?? $buena['status']));
        //Solo las filas de esta cuenta de la prueba salen de la ventana: deja de estar bajo ataque y vuelve a decir su estado.
        $ventana = OTPRateLimiter::config()['loginAccountWindowMinutes'] + 60;
        $database->prepare("UPDATE `{$tablaIntentos}` SET date = DATE_SUB(date, INTERVAL {$ventana} MINUTE) WHERE usernameAttempt = ?")->execute(["{$prefijo}-tres-ip"]);
        $fuera = $entrar('198.51.100.82', "{$prefijo}-tres-ip", 'zz-Clave-Buena-1');
        $check($fuera['status'] === 200 && ($fuera['json']['error'] ?? null) === UsersController::BLOCKED_FOR_ATTEMPTS && ($fuera['json']['auth'] ?? null) === false,
            'e8c con sus fallos fuera de la ventana, con la buena, el dueño ve BLOCKED_FOR_ATTEMPTS', (string) ($fuera['json']['error'] ?? $fuera['status']));

        //e9: un usuario que no existe también cuenta para el freno de la IP.
        $nadie = [];
        for ($i = 0; $i < 4; $i++) {
            $nadie = $entrar('198.51.100.72', "{$prefijo}-nadie-{$i}", 'zz-mala');
        }
        $check(($nadie['json']['error'] ?? null) === UsersController::INCORRECT_PASSWORD && $entrar('198.51.100.72', "{$prefijo}-nadie", 'zz-mala')['status'] === 429,
            'e9 los fallos de usuarios que no existen también frenan la IP: con el de e5 son 5, y el siguiente da 429');
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
            foreach ($usuarios as $id) {
                $database->exec("DELETE FROM `{$tablaIntentos}` WHERE userID = {$id}");
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
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs}) OR usernameAttempt LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario ni intento de acceso de la prueba', "quedan {$quedan}");
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:LoginIPThrottle] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "El login frena por IP y bloquea la cuenta solo desde varias IP ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_DATABASE])->register();
