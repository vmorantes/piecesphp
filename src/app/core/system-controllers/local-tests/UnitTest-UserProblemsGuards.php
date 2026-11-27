<?php

//Las guardas de los problemas de acceso, en proceso con REMOTE_ADDR de TEST-NET (RFC 5737). Siembra
//zz-agente-jpd- y al empezar y al acabar borra lo de todas las pasadas.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/user-problems-guards';
$cliTaskDescription = 'Los problemas de acceso: el tipo se recorta una vez y se compara estricto, resolver un código frena por IP y el formulario de otros problemas tiene un tope de envíos por IP. Escribe usuarios y códigos zz-agente-jpd- y los borra';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:UserProblemsGuards] Iniciando suite...', true, "\r\n", '33');
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

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'e0 hay conexión a la base')) {
        return ['success' => false, 'message' => 'Sin conexión a la base.'];
    }

    $tablaUsuarios = UsersModel::TABLE;
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $tablaProblemas = 'pcsphp_user_problems';
    $tablaTickets = 'pcsphp_tickets_log';
    $tablaRegistro = LogsMapper::TABLE;
    $tablaCorreos = MailLogMapper::TABLE;
    $marcaComun = 'zz-agente-jpd-';
    $prefijo = $marcaComun . bin2hex(random_bytes(3));
    //IP de documentación (RFC 5737) reservadas para esta suite: sus filas son suyas y se borran al empezar y al acabar.
    $ipsPropias = ['198.51.100.130', '198.51.100.131', '198.51.100.132', '198.51.100.133', '198.51.100.134', '198.51.100.135'];
    $listaIPs = implode(', ', array_map(fn (string $ip): string => (string) $database->quote($ip), $ipsPropias));
    $direccionPrevia = $_SERVER['REMOTE_ADDR'] ?? null;

    $contar = function (string $sql, array $valores = []) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute($valores);
        return (int) $statement->fetchColumn();
    };
    $ejecutar = function (string $sql, array $valores = []) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute($valores);
        return $statement->rowCount();
    };
    $metaDeIPs = '(' . implode(' OR ', array_map(fn (string $ip): string => 'meta LIKE ' . $database->quote('%"' . $ip . '"%'), $ipsPropias)) . ')';

    //Lo que la suite puede dejar, por tabla: de todas las pasadas (la marca común), no solo de esta.
    $propio = function () use ($contar, $tablaUsuarios, $tablaIntentos, $tablaProblemas, $tablaTickets, $tablaRegistro, $tablaCorreos, $marcaComun, $listaIPs, $metaDeIPs): array {
        $patron = "{$marcaComun}%";
        return [
            'usuarios' => $contar("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", [$patron]),
            'problemas' => $contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email LIKE ?", [$patron]),
            'tickets' => $contar("SELECT COUNT(*) FROM `{$tablaTickets}` WHERE email LIKE ?", [$patron]),
            'intentos' => $contar("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs}) OR usernameAttempt LIKE ? OR userID IN (SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE ?)", [$patron, $patron]),
            'registro' => $contar("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE {$metaDeIPs} OR (referenceSource = ? AND referenceValue IN (SELECT CAST(id AS CHAR) FROM `{$tablaUsuarios}` WHERE username LIKE ?))", [$tablaUsuarios, $patron]),
            'correos' => $contar("SELECT COUNT(*) FROM `{$tablaCorreos}` WHERE recipients LIKE ?", ["%{$marcaComun}%"]),
        ];
    };
    $limpiar = function () use ($database, $ejecutar, $tablaUsuarios, $tablaIntentos, $tablaProblemas, $tablaTickets, $tablaRegistro, $tablaCorreos, $marcaComun, $listaIPs, $metaDeIPs): array {
        $patron = "{$marcaComun}%";
        $borradas = [];
        $ids = $database->prepare("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE ?");
        $ids->execute([$patron]);
        $ids = array_map('intval', $ids->fetchAll(\PDO::FETCH_COLUMN));
        $borradas['intentos'] = $ejecutar("DELETE FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs}) OR usernameAttempt LIKE ?", [$patron]);
        $borradas['problemas'] = $ejecutar("DELETE FROM `{$tablaProblemas}` WHERE email LIKE ?", [$patron]);
        $borradas['tickets'] = $ejecutar("DELETE FROM `{$tablaTickets}` WHERE email LIKE ?", [$patron]);
        $borradas['registro'] = $ejecutar("DELETE FROM `{$tablaRegistro}` WHERE {$metaDeIPs}");
        $borradas['correos'] = $ejecutar("DELETE FROM `{$tablaCorreos}` WHERE recipients LIKE ?", ["%{$marcaComun}%"]);
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '{$tablaUsuarios}' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            $borradas['intentos'] += $ejecutar("DELETE FROM `{$tablaIntentos}` WHERE userID = ?", [$id]);
            $borradas['registro'] += $ejecutar("DELETE FROM `{$tablaRegistro}` WHERE createdBy = ? OR (referenceSource = ? AND referenceValue = ?)", [$id, $tablaUsuarios, (string) $id]);
            $borradas['aprobaciones'] = ($borradas['aprobaciones'] ?? 0) + $ejecutar('DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?', [$tablaUsuarios, (string) $id]);
            $borradas['perfiles'] = ($borradas['perfiles'] ?? 0) + $ejecutar('DELETE FROM `user_system_profile` WHERE belongsTo = ?', [$id]);
            foreach ($referencias as [$tabla, $columna]) {
                $ejecutar("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?", [$id]);
            }
            $borradas['usuarios'] = ($borradas['usuarios'] ?? 0) + $ejecutar("DELETE FROM `{$tablaUsuarios}` WHERE id = ?", [$id]);
        }
        return array_filter($borradas);
    };

    $restosPrevios = $limpiar();
    if ($restosPrevios !== []) {
        echoTerminal('[0] Restos de una pasada anterior, borrados al empezar: ' . json_encode($restosPrevios));
    }
    $inicial = $propio();
    $check(array_sum($inicial) === 0, 'e1 al empezar no hay nada de la suite en la base', json_encode($inicial) ?: '');

    $peticion = function (array $cuerpo): RequestRoute {
        $request = new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-problemas'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $request = $request->withParsedBody($cuerpo);
        return $request instanceof RequestRoute ? $request : throw new \LogicException('withParsedBody no devolvió un RequestRoute');
    };
    //Devuelve [estado, Retry-After, cuerpo]; una excepción deja el detalle en el cuerpo y estado 0.
    $llamar = function (string $ip, string $metodo, array $cuerpo) use ($peticion): array {
        $_SERVER['REMOTE_ADDR'] = $ip;
        try {
            $respuesta = (new UserProblemsController())->$metodo($peticion($cuerpo), new ResponseRoute());
            $json = json_decode((string) $respuesta->getBody(), true);
            return [$respuesta->getStatusCode(), $respuesta->getHeaderLine('Retry-After'), is_array($json) ? $json : []];
        } catch (\Throwable $exception) {
            return [0, '', ['excepcion' => get_class($exception) . ': ' . $exception->getMessage()]];
        }
    };
    $describir = fn (array $valor): string => json_encode($valor, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PARTIAL_OUTPUT_ON_ERROR) ?: '';
    $crearUsuario = function (string $sufijo, int $estado = UsersModel::STATUS_USER_ACTIVE, int $intentos = 0) use ($database, $prefijo, $tablaUsuarios): int {
        $statement = $database->prepare("INSERT INTO `{$tablaUsuarios}` (username, password, firstname, firstLastname, email, type, status, failedAttempts) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $statement->execute(["{$prefijo}-{$sufijo}", password_hash('zz-' . bin2hex(random_bytes(6)), \PASSWORD_DEFAULT), 'Zz', 'Problemas', "{$prefijo}-{$sufijo}@example.invalid", UsersModel::TYPE_USER_GENERAL, $estado, $intentos]);
        return (int) $database->lastInsertId();
    };
    $codigoLibre = function () use ($contar, $tablaProblemas): string {
        do {
            $codigo = (string) random_int(100000, 999999);
        } while ($contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE code = ?", [$codigo]) > 0);
        return $codigo;
    };
    $plantarCodigo = function (string $correo, string $tipo, string $vigencia = '+24 hour', string $creacion = 'now') use ($database, $tablaProblemas, $codigoLibre): string {
        $codigo = $codigoLibre();
        $creado = new \DateTime($creacion);
        $database->prepare("INSERT INTO `{$tablaProblemas}` (email, code, created, expired, type) VALUES (?, ?, ?, ?, ?)")
            ->execute([$correo, $codigo, $creado->format('Y-m-d H:i:s'), (clone $creado)->modify($vigencia)->format('Y-m-d H:i:s'), $tipo]);
        return $codigo;
    };
    //Intentos plantados con la hora de la base, la misma con la que el freno los lee.
    $plantarIntentos = function (string $ip, int $cuantos, string $via, int $exito = LoginAttemptsModel::FAIL_ATTEMPT, bool $bloqueado = false, ?string $nombre = null) use ($database, $tablaIntentos, $prefijo): void {
        $extra = [OTPRateLimiter::EXTRA_DATA_VIA => $via];
        if ($bloqueado) {
            $extra[OTPRateLimiter::EXTRA_DATA_LOCKED] = true;
        }
        $fecha = (string) $database->query('SELECT NOW()')->fetchColumn();
        $insertar = $database->prepare("INSERT INTO `{$tablaIntentos}` (userID, usernameAttempt, success, ip, ipGroup, message, date, extraData) VALUES (NULL, ?, ?, ?, ?, 'zz', ?, ?)");
        for ($i = 0; $i < $cuantos; $i++) {
            $insertar->execute([$nombre ?? "{$prefijo}-plantado", $exito, $ip, OTPRateLimiter::ipGroup($ip), $fecha, json_encode($extra, JSON_THROW_ON_ERROR)]);
        }
    };
    $limpiarIPs = fn (): int => $ejecutar("DELETE FROM `{$tablaIntentos}` WHERE ip IN ({$listaIPs})");

    try {
        //──── 1. El tipo de un problema ───────────────────────────────────────────────────────────
        echoTerminal('[1/4] generateCode(): el tipo se recorta una vez, se compara estricto y se guarda recortado');
        if ($check(MailDelivery::goesToSink(), 'p1 PRE: el correo va al sumidero local: el código que se pide no sale de la máquina')) {
            $crearUsuario('olvido');
            $correo = "{$prefijo}-olvido@example.invalid";
            [$estado, , $cuerpo] = $llamar($ipsPropias[0], 'generateCode', ['username' => $correo, 'type' => ' ' . UserProblemsController::TYPE_USER_FORGET . ' ']);
            $fila = $database->prepare("SELECT type, code FROM `{$tablaProblemas}` WHERE email = ?");
            $fila->execute([$correo]);
            $filas = $fila->fetchAll(\PDO::FETCH_ASSOC);
            $tipo = (string) ($filas[0]['type'] ?? '');
            $resuelto = count($filas) === 1
                ? $llamar($ipsPropias[0], 'resolveProblem', ['code' => (string) $filas[0]['code'], 'type' => UserProblemsController::TYPE_USER_FORGET])
                : [0, '', []];
            $check($estado === 200 && ($cuerpo['send_mail'] ?? null) === true && count($filas) === 1 && $tipo === UserProblemsController::TYPE_USER_FORGET
                && ($resuelto[2]['success'] ?? null) === true && ($resuelto[2]['username'] ?? null) === "{$prefijo}-olvido",
                'm1 DISCRIMINANTE: con el tipo « TYPE_USER_FORGET » la fila guarda TYPE_USER_FORGET exacto y su código devuelve el usuario',
                'tipo «' . $tipo . '», filas ' . count($filas) . ', resolver ' . $describir($resuelto[2]));

            $antes = $contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email = ?", [$correo]);
            [$estado, , $cuerpo] = $llamar($ipsPropias[0], 'generateCode', ['username' => $correo, 'type' => 'TYPE_USER_OTRO']);
            $despues = $contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE email = ?", [$correo]);
            $check(($cuerpo['error'] ?? null) === UserProblemsController::UNEXPECTED_ACTION && ($cuerpo['send_mail'] ?? null) === false && $despues === $antes,
                'm2 un tipo que no está en la lista da UNEXPECTED_ACTION y no crea fila', $describir($cuerpo) . ", filas {$antes} → {$despues}");
        }
        echoTerminal(' ');

        //──── 2. Resolver un código frena por IP ──────────────────────────────────────────────────
        echoTerminal('[2/4] resolveProblem(): el freno por IP va antes de mirar el código, y comparte el presupuesto del OTP y la recuperación');
        $limpiarIPs();
        $tope = OTPRateLimiter::config()['maxFailuresPerIP'];
        $olvido = UserProblemsController::TYPE_USER_FORGET;
        $bloqueo = UserProblemsController::TYPE_USER_BLOCKED;
        $esFreno = fn (array $r): bool => $r[0] === 429 && ($r[2]['error'] ?? null) === OTPRateLimiter::ERROR_TOO_MANY_ATTEMPTS && (int) $r[1] > 0;

        //r1: tope-1 fallos plantados; el código malo cuenta como el último, y el siguiente ya está frenado.
        [$ip1, $ip2, $ip3, $ip4, $ip5, $ip6] = $ipsPropias;
        $plantarIntentos($ip1, $tope - 1, OTPRateLimiter::VIA_USER_PROBLEM_CODE);
        $malo = $llamar($ip1, 'resolveProblem', ['code' => $codigoLibre(), 'type' => $olvido]);
        $frenado = $llamar($ip1, 'resolveProblem', ['code' => $codigoLibre(), 'type' => $olvido]);
        $check($malo[0] === 200 && ($malo[2]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE && $esFreno($frenado),
            "r1 CANARIO: con {$tope}-1 fallos de la vía, un código malo da EXPIRED_OR_NOT_EXIST_CODE (200) y el siguiente 429 TOO_MANY_ATTEMPTS con Retry-After",
            $describir([$malo[0], $malo[2]['error'] ?? null, $frenado[0], $frenado[1], $frenado[2]['error'] ?? null]));

        //r2: con la IP frenada, ni el código BUENO de un bloqueado lo desbloquea, ni se gasta.
        $idBloqueado = $crearUsuario('bloqueado', UsersModel::STATUS_USER_ATTEMPTS_BLOCK, UsersController::MAX_ATTEMPTS);
        $correoBloqueado = "{$prefijo}-bloqueado@example.invalid";
        $codigoBueno = $plantarCodigo($correoBloqueado, $bloqueo);
        $plantarIntentos($ip2, $tope, OTPRateLimiter::VIA_USER_PROBLEM_CODE);
        $conBueno = $llamar($ip2, 'resolveProblem', ['code' => $codigoBueno, 'type' => $bloqueo]);
        $estado = (int) $contar("SELECT status FROM `{$tablaUsuarios}` WHERE id = ?", [$idBloqueado]);
        $sigue = $contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE code = ?", [$codigoBueno]);
        $check($esFreno($conBueno) && $estado === UsersModel::STATUS_USER_ATTEMPTS_BLOCK && $sigue === 1,
            'r2 DISCRIMINANTE: con la IP frenada, el código bueno de desbloqueo da 429, el usuario sigue bloqueado y el código sigue existiendo',
            $describir([$conBueno[0], $conBueno[2]['error'] ?? null, 'estado' => $estado, 'codigo' => $sigue]));

        //r3: los fallos de la recuperación de esa IP también frenan esta vía.
        $plantarIntentos($ip3, $tope, OTPRateLimiter::VIA_RECOVERY_CODE);
        $compartido = $llamar($ip3, 'resolveProblem', ['code' => $codigoLibre(), 'type' => $olvido]);
        $check($esFreno($compartido), "r3 {$tope} fallos de recovery-code de esa IP también frenan resolver un código: el presupuesto es compartido", $describir([$compartido[0], $compartido[2]['error'] ?? null]));

        //r4: la respuesta frenada queda registrada y marcada, y una marcada no cuenta para el tope.
        $marcadas = $contar("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip = ? AND success = ? AND extraData LIKE ? AND extraData LIKE '%otpLocked%'", [$ip1, LoginAttemptsModel::FAIL_ATTEMPT, '%' . OTPRateLimiter::VIA_USER_PROBLEM_CODE . '%']);
        $plantarIntentos($ip4, $tope - 1, OTPRateLimiter::VIA_USER_PROBLEM_CODE);
        $plantarIntentos($ip4, 3, OTPRateLimiter::VIA_USER_PROBLEM_CODE, LoginAttemptsModel::FAIL_ATTEMPT, true);
        $noCuenta = $llamar($ip4, 'resolveProblem', ['code' => $codigoLibre(), 'type' => $olvido]);
        $check($marcadas === 1 && $noCuenta[0] === 200 && ($noCuenta[2]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE,
            "r4 la respuesta frenada de r1 queda registrada y marcada (otpLocked), y con {$tope}-1 fallos y 3 marcadas la IP no está frenada",
            $describir(['marcadas' => $marcadas, $noCuenta[0], $noCuenta[2]['error'] ?? null]));

        //r5: el freno es por IP: desde otra, el mismo código bueno desbloquea.
        $desdeOtra = $llamar($ip5, 'resolveProblem', ['code' => $codigoBueno, 'type' => $bloqueo]);
        $estado = (int) $contar("SELECT status FROM `{$tablaUsuarios}` WHERE id = ?", [$idBloqueado]);
        $sigue = $contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE code = ?", [$codigoBueno]);
        $check(($desdeOtra[2]['success'] ?? null) === true && $estado !== UsersModel::STATUS_USER_ATTEMPTS_BLOCK && $sigue === 0,
            'r5 desde otra IP de la lista el mismo código bueno desbloquea: el freno es por IP, no global',
            $describir([$desdeOtra[0], $desdeOtra[2], 'estado' => $estado, 'codigo' => $sigue]));
        echoTerminal(' ');

        //──── 3. Lo que dejó el auditor de JPD ──────────────────────────────────────────────────────
        echoTerminal('[3/4] El freno no amplifica ni carga el fallo a su dueño, el tipo cambiado es un fallo, y solo vale el último código');
        $limpiarIPs();
        $ahoraBD = new \DateTime((string) $database->query('SELECT NOW()')->fetchColumn());

        //r6: sin usuario, windowRows() lee solo el grupo de la IP: un fallo sin nombre desde otra IP no aparece.
        $plantarIntentos($ip2, 1, OTPRateLimiter::VIA_USER_PROBLEM_CODE, LoginAttemptsModel::FAIL_ATTEMPT, false, '');
        $leidas = OTPRateLimiter::windowRows('', $ip1, $ahoraBD);
        $deOtra = array_filter($leidas, fn (array $f): bool => ($f['ip'] ?? null) === $ip2);
        $check(count($deOtra) === 0, 'r6 H2: un fallo con usuario vacío desde otra IP de la lista no aparece en windowRows(\'\', ip)', 'leídas ' . count($leidas) . ', de la otra IP ' . count($deOtra));
        $leidasBlanco = OTPRateLimiter::windowRows(' ', $ip1, $ahoraBD);
        $deOtraBlanco = array_filter($leidasBlanco, fn (array $f): bool => ($f['ip'] ?? null) === $ip2);
        $check(count($deOtraBlanco) === 0, 'r6b con un usuario en blanco (\' \'), tampoco: en MySQL \' \' casa con \'\'', 'leídas ' . count($leidasBlanco) . ', de la otra IP ' . count($deOtraBlanco));
        //Los fallos de la vía de una IP: con su userID y el nombre vacío (H7).
        $fallosDe = function (string $ip) use ($database, $tablaIntentos): array {
            $statement = $database->prepare("SELECT userID, usernameAttempt FROM `{$tablaIntentos}` WHERE ip = ? AND success = ? AND extraData LIKE ? ORDER BY id");
            $statement->execute([$ip, LoginAttemptsModel::FAIL_ATTEMPT, '%' . OTPRateLimiter::VIA_USER_PROBLEM_CODE . '%']);
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        };
        $sinNombreDe = fn (array $filas, int $id): bool => count($filas) > 0 && count(array_filter($filas, fn (array $f): bool => (int) $f['userID'] === $id && (string) $f['usernameAttempt'] === '')) === count($filas);

        //r7: el fallo de un código caducado lleva el userID del dueño y el nombre vacío.
        $idCaducado = $crearUsuario('caducado');
        $codigoCaducado = $plantarCodigo("{$prefijo}-caducado@example.invalid", $olvido, '+1 hour', '-2 hour');
        $caducado = $llamar($ip3, 'resolveProblem', ['code' => $codigoCaducado, 'type' => $olvido]);
        $filaFallo = $database->prepare("SELECT userID, usernameAttempt, success FROM `{$tablaIntentos}` WHERE ip = ? AND extraData LIKE ?");
        $filaFallo->execute([$ip3, '%' . OTPRateLimiter::VIA_USER_PROBLEM_CODE . '%']);
        $filasFallo = $filaFallo->fetchAll(\PDO::FETCH_ASSOC);
        $check(($caducado[2]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE && count($filasFallo) === 1
            && (int) $filasFallo[0]['userID'] === $idCaducado && (string) $filasFallo[0]['usernameAttempt'] === '' && (int) $filasFallo[0]['success'] === LoginAttemptsModel::FAIL_ATTEMPT,
            'r7 H7: el fallo con un código caducado deja la fila con su userID y usernameAttempt vacío: no gasta el presupuesto del dueño', $describir([$caducado[2]['error'] ?? null, $filasFallo]));

        //r8: un código de olvido mandado como desbloqueo es un fallo y no se gasta.
        $idCambiado = $crearUsuario('cambiado');
        $codigoCambiado = $plantarCodigo("{$prefijo}-cambiado@example.invalid", $olvido);
        $cambiado = $llamar($ip4, 'resolveProblem', ['code' => $codigoCambiado, 'type' => $bloqueo]);
        $fallosIP4 = $contar("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip = ? AND success = ? AND extraData LIKE ?", [$ip4, LoginAttemptsModel::FAIL_ATTEMPT, '%' . OTPRateLimiter::VIA_USER_PROBLEM_CODE . '%']);
        $sigueCambiado = $contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE code = ?", [$codigoCambiado]);
        $check(($cambiado[2]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE && $fallosIP4 === 1 && $sigueCambiado === 1 && $sinNombreDe($fallosDe($ip4), $idCambiado),
            'r8 un código de olvido mandado como desbloqueo da EXPIRED_OR_NOT_EXIST_CODE, cuenta como fallo (con su userID y sin nombre) y no se gasta', $describir([$cambiado[2]['error'] ?? null, 'fallos' => $fallosDe($ip4), 'codigo' => $sigueCambiado]));

        //r9: dos códigos del mismo tipo dejan dos filas; solo vale el último, y uno de otro tipo sigue valiendo.
        if ($check(MailDelivery::goesToSink(), 'p3 PRE: el correo va al sumidero local: los dos códigos pedidos no salen de la máquina')) {
            $idDos = $crearUsuario('dos-codigos');
            $correoDos = "{$prefijo}-dos-codigos@example.invalid";
            $codigoOtroTipo = $plantarCodigo($correoDos, $bloqueo, '+24 hour', '-30 minutes');
            $llamar($ip5, 'generateCode', ['username' => $correoDos, 'type' => $olvido]);
            //La primera, más vieja que el minuto del tope por correo: el tope no se toca.
            $database->prepare("UPDATE `{$tablaProblemas}` SET created = DATE_SUB(created, INTERVAL 5 MINUTE) WHERE email = ? AND type = ?")->execute([$correoDos, $olvido]);
            $llamar($ip5, 'generateCode', ['username' => $correoDos, 'type' => $olvido]);
            $codigos = $database->prepare("SELECT code FROM `{$tablaProblemas}` WHERE email = ? AND type = ? ORDER BY created ASC, id ASC");
            $codigos->execute([$correoDos, $olvido]);
            $codigos = array_map('strval', $codigos->fetchAll(\PDO::FETCH_COLUMN));
            $primero = count($codigos) === 2 ? $llamar($ip5, 'resolveProblem', ['code' => $codigos[0], 'type' => $olvido]) : [0, '', []];
            $otroTipo = $llamar($ip5, 'resolveProblem', ['code' => $codigoOtroTipo, 'type' => $bloqueo]);
            $segundo = count($codigos) === 2 ? $llamar($ip5, 'resolveProblem', ['code' => $codigos[1], 'type' => $olvido]) : [0, '', []];
            $check(count($codigos) === 2 && ($primero[2]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE
                && ($otroTipo[2]['error'] ?? null) !== UsersController::EXPIRED_OR_NOT_EXIST_CODE
                && ($segundo[2]['success'] ?? null) === true && ($segundo[2]['username'] ?? null) === "{$prefijo}-dos-codigos"
                && $sinNombreDe($fallosDe($ip5), $idDos),
                'r9 H1: dos códigos del mismo tipo dejan dos filas; el primero da EXPIRED_OR_NOT_EXIST_CODE (fallo con su userID y sin nombre), el segundo resuelve, y uno de desbloqueo del mismo correo sigue valiendo',
                $describir(['filas' => count($codigos), 'primero' => $primero[2]['error'] ?? null, 'otroTipo' => $otroTipo[2], 'segundo' => $segundo[2], 'fallos' => $fallosDe($ip5)]));

            //r9b: consumido el último, el anterior no revive: quedó caducado al pedir el nuevo.
            $deNuevo = count($codigos) === 2 ? $llamar($ip5, 'resolveProblem', ['code' => $codigos[0], 'type' => $olvido]) : [0, '', []];
            $caducoPrimero = count($codigos) === 2 ? $contar("SELECT COUNT(*) FROM `{$tablaProblemas}` WHERE code = ? AND expired <= NOW()", [$codigos[0]]) : 0;
            $check(($deNuevo[2]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE && $caducoPrimero === 1,
                'r9b consumido el segundo código, el primero sigue sin valer y su fila tiene expired ≤ ahora', $describir([$deNuevo[2]['error'] ?? null, 'caducado' => $caducoPrimero]));
        }

        //r9c: vale el de id mayor, con el mismo created y con un created que retrocedió.
        $idMismo = $crearUsuario('mismo-momento');
        $correoMismo = "{$prefijo}-mismo-momento@example.invalid";
        $momento = (new \DateTime())->format('Y-m-d H:i:s');
        $antiguoMismo = $plantarCodigo($correoMismo, $olvido, '+24 hour', $momento);
        $nuevoMismo = $plantarCodigo($correoMismo, $olvido, '+24 hour', $momento);
        $crearUsuario('retroceso');
        $correoRetroceso = "{$prefijo}-retroceso@example.invalid";
        $antiguoRetroceso = $plantarCodigo($correoRetroceso, $olvido);
        $nuevoRetroceso = $plantarCodigo($correoRetroceso, $olvido, '+24 hour', '-1 minute');
        $rechazos = fn (): int => $contar("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE referenceSource = ? AND referenceValue = ? AND textMessage = ?", [$tablaUsuarios, (string) $idMismo, LogsMapper::messages()[LogsMapper::MSG_TOKEN_REJECTED] ?? '']);
        $rechazosAntes = $rechazos();
        $res = [];
        foreach ([$antiguoMismo, $nuevoMismo, $antiguoRetroceso, $nuevoRetroceso] as $codigo) {
            $res[] = $llamar($ip6, 'resolveProblem', ['code' => $codigo, 'type' => $olvido])[2];
        }
        $check(($res[0]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE && ($res[1]['success'] ?? null) === true
            && ($res[2]['error'] ?? null) === UsersController::EXPIRED_OR_NOT_EXIST_CODE && ($res[3]['success'] ?? null) === true,
            'r9c con dos filas del mismo created vale la de id mayor, y también si el created de la nueva retrocedió', $describir($res));
        $rechazosDespues = $rechazos();
        $check($rechazosDespues === $rechazosAntes + 1, 'r9d el código sustituido deja su rechazo en Tokens y códigos (TOKEN_REJECTED del usuario), como el caducado', "{$rechazosAntes} → {$rechazosDespues}");
        echoTerminal(' ');

        //──── 4. El tope de envíos de otros problemas ─────────────────────────────────────────────
        echoTerminal('[4/4] sendMailOtherProblems(): un tope de intentos de envío por IP y hora, contado antes de enviar');
        $via = OTPRateLimiter::VIA_OTHER_PROBLEMS_SEND;
        $maximo = UserProblemsController::OTHER_PROBLEMS_MAX_PER_HOUR;
        $ahora = new \DateTime('2026-01-10 12:00:00');
        $exito = static function (int $minutosAtras, string $ip = '10.0.0.1', ?string $viaFila = null) use ($ahora, $via): array {
            return [
                'ip' => $ip,
                'success' => LoginAttemptsModel::SUCCESS_ATTEMPT,
                'date' => (clone $ahora)->modify("-{$minutosAtras} minutes")->format('Y-m-d H:i:s'),
                'extraData' => json_encode([OTPRateLimiter::EXTRA_DATA_VIA => $viaFila ?? $via], JSON_THROW_ON_ERROR),
            ];
        };
        $tope = fn (array $filas, string $ip = '10.0.0.1'): bool => OTPRateLimiter::ipSendLimitReachedFromRows($filas, $via, $ip, $maximo, $ahora);
        $check(!in_array($via, OTPRateLimiter::VIAS, true), 'o0 other-problems-send está FUERA de VIAS: no gasta el presupuesto del OTP');
        $cuatro = array_map(fn (int $m): array => $exito($m), [50, 40, 30, 20]);
        $cinco = array_map(fn (int $m): array => $exito($m), [50, 40, 30, 20, 10]);
        $sinColumna = array_map(function (array $f): array {
            unset($f['extraData']);
            return $f;
        }, $cinco);
        $fallos = array_map(fn (array $f): array => ['success' => LoginAttemptsModel::FAIL_ATTEMPT] + $f, $cinco);
        $frenadas = array_map(fn (int $m): array => ['extraData' => json_encode([OTPRateLimiter::EXTRA_DATA_VIA => $via, OTPRateLimiter::EXTRA_DATA_LOCKED => true], JSON_THROW_ON_ERROR)] + $exito($m), [50, 40, 30, 20, 10]);
        $check(!$tope($cuatro) && $tope($cinco)
            && !$tope(array_map(fn (int $m): array => $exito($m, '10.0.0.1', OTPRateLimiter::VIA_RECOVERY_CODE), [50, 40, 30, 20, 10]))
            && !$tope(array_map(fn (int $m): array => $exito($m, '10.0.0.2'), [50, 40, 30, 20, 10]))
            && !$tope(array_merge($cuatro, [$exito(61)]))
            && $tope(array_merge($cuatro, [$exito(-30)]))
            && $tope($sinColumna)
            && $tope($fallos)
            && !$tope($frenadas),
            "o1 la decisión pura: 4 intentos en la hora no topan y 5 sí; 5 de otra vía o de otro grupo de IP, no; uno de hace 61 min no cuenta; uno del futuro cuenta; sin extraData cuentan; cuentan con cualquier resultado; las frenadas (otpLocked) no");

        $osTicket = get_config('osTicketAPI');
        $osTicketClave = get_config('osTicketAPIKey');
        $conOsTicket = is_string($osTicket) && trim($osTicket) !== '' && is_string($osTicketClave) && trim($osTicketClave) !== '';
        $destinatarios = UserProblemsController::otherProblemsRecipients();
        echoTerminal('      - adónde enviaría en local: osTicket ' . ($conOsTicket ? 'CONFIGURADO' : 'sin configurar') . ', ' . count($destinatarios) . ' destinatario(s) de correo, correo ' . (MailDelivery::goesToSink() ? 'al sumidero local' : 'REAL'));
        if ($check(!$conOsTicket && MailDelivery::goesToSink(), 'p2 PRE: sin osTicket y con el correo al sumidero local: si el tope fallara, nada saldría de la máquina')) {
            $ip6 = $ipsPropias[5];
            //o3: cuenta el intento, aunque el envío falle (en local, sin canal, siempre falla).
            $correoIntento = "{$prefijo}-otros-intento@example.invalid";
            $intento = $llamar($ip6, 'sendMailOtherProblems', ['name' => 'Zz', 'email' => $correoIntento, 'message' => 'zz-agente-jpd: prueba del intento']);
            $intentos = $contar("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE ip = ? AND success = ? AND extraData LIKE ?", [$ip6, LoginAttemptsModel::FAIL_ATTEMPT, '%' . $via . '%']);
            $check($intento[0] === 200 && ($intento[2]['send_mail'] ?? null) === false && $intentos === 1,
                'o3 H3+H5: una petición sin el tope alcanzado deja UNA fila de other-problems-send (success 0) aunque el envío falle', $describir([$intento[0], $intento[2]['send_mail'] ?? null, 'filas' => $intentos]));
            $limpiarIPs();

            //o4: los intentos cuentan para su tope, no como accesos, y no frenan ni el OTP ni el login.
            $plantarIntentos($ip6, $maximo, $via);
            $check(OTPRateLimiter::ipSendLimitReached($via, $ip6, $maximo) && OTPRateLimiter::secondsToUnlock('', $ip6) === 0 && OTPRateLimiter::loginSecondsToUnlock($ip6) === 0,
                "o4 {$maximo} intentos fallidos de other-problems-send topan su vía, y secondsToUnlock('', ip) y loginSecondsToUnlock(ip) siguen en 0",
                $describir(['tope' => OTPRateLimiter::ipSendLimitReached($via, $ip6, $maximo), 'otp' => OTPRateLimiter::secondsToUnlock('', $ip6), 'login' => OTPRateLimiter::loginSecondsToUnlock($ip6)]));
            $limpiarIPs();

            $correoOtros = "{$prefijo}-otros@example.invalid";
            $plantarIntentos($ip6, $maximo, $via);
            $topado = $llamar($ip6, 'sendMailOtherProblems', ['name' => 'Zz', 'email' => $correoOtros, 'message' => 'zz-agente-jpd: prueba del tope']);
            $tickets = $contar("SELECT COUNT(*) FROM `{$tablaTickets}` WHERE email = ?", [$correoOtros]);
            $check($topado[0] === 429 && ($topado[2]['error'] ?? null) === OTPRateLimiter::ERROR_TOO_MANY_ATTEMPTS && (int) $topado[1] > 0 && $tickets === 0,
                "o2 DISCRIMINANTE: con {$maximo} intentos de la IP en la hora, el siguiente da 429 y no crea fila en el registro de solicitudes ni envía",
                $describir([$topado[0], $topado[1], $topado[2]['error'] ?? null, 'tickets' => $tickets]));
        }
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($direccionPrevia === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $direccionPrevia;
        }
        try {
            $borradas = $limpiar();
            echoTerminal('   - limpieza: ' . ($borradas === [] ? 'nada' : json_encode($borradas)));
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
    }
    $final = $propio();
    $check($final === $inicial, 'z1 al acabar, lo de la suite en la base es lo del principio (nada): usuarios, códigos, tickets, intentos, registro y correos', 'inicial ' . json_encode($inicial) . ', final ' . json_encode($final));

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . ' PASADAS ');
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:UserProblemsGuards] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Las guardas de los problemas de acceso se cumplen ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_DATABASE])->register();
