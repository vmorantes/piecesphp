<?php

//Escribe en la base local: crea dos usuarios zz y en el finally borra sus filas de pcsphp_recovery_password,
//login_attempts (por username_attempt) y los dos usuarios. Ver ADR 0018.

use App\Controller\RecoveryPasswordController;
use App\Model\LoginAttemptsModel;
use App\Model\RecoveryPasswordModel;
use App\Model\UsersModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/password-recovery-guards', function ($args) {

    echoTerminal("\e[33m[TEST:PasswordRecoveryGuards] La recuperación de contraseña no se adivina, no entrega el usuario y su enlace no escribe\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $username = 'zz_recovery_' . bin2hex(random_bytes(5));
    $email = $username . '@example.invalid';
    $usernameB = 'zz_recovery_b_' . bin2hex(random_bytes(5));
    $emailB = $usernameB . '@example.invalid';
    $userID = null;
    $userIDB = null;

    $peticion = function (string $metodo, array $cuerpo = []): RequestRoute {
        $request = new RequestRoute(
            $metodo,
            (new UriFactory())->createUri('http://localhost/prueba'),
            new Headers(),
            [],
            [],
            (new StreamFactory())->createStream('')
        );
        $conCuerpo = $request->withParsedBody($cuerpo);
        return $conCuerpo instanceof RequestRoute ? $conCuerpo : $request;
    };

    //Devuelve [estado, cuerpo decodificado, respuesta]; una excepción deja el detalle en el cuerpo y estado 0.
    $llamar = function (string $metodoControlador, RequestRoute $request, array $argumentos = []): array {
        try {
            $controlador = new RecoveryPasswordController();
            $respuesta = $controlador->$metodoControlador($request, new ResponseRoute(), $argumentos);
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            return [$respuesta->getStatusCode(), is_array($cuerpo) ? $cuerpo : [], $respuesta];
        } catch (\Throwable $exception) {
            return [0, ['excepcion' => get_class($exception) . ': ' . $exception->getMessage()], null];
        }
    };

    $detalle = fn(int $estado, array $cuerpo): string => "estado {$estado}, cuerpo " . json_encode($cuerpo, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PARTIAL_OUTPUT_ON_ERROR);

    $filaCodigo = function (string $code, string $modificadorExpiracion) use ($email): void {
        $fila = new RecoveryPasswordModel();
        $fila->email = $email;
        $fila->code = $code;
        $fila->created = new \DateTime();
        $fila->expired = (new \DateTime())->modify($modificadorExpiracion);
        $fila->save();
    };

    $hashActual = function () use ($database, &$userID): string {
        $statement = $database->prepare('SELECT password FROM ' . UsersModel::TABLE . ' WHERE id = ?');
        $statement->execute([$userID]);
        return (string) $statement->fetchColumn();
    };

    $contiene = function ($valor, array $buscados) use (&$contiene): bool {
        if (is_array($valor)) {
            foreach ($valor as $item) {
                if ($contiene($item, $buscados)) {
                    return true;
                }
            }
            return false;
        }
        return is_string($valor) && in_array($valor, $buscados, true);
    };

    $hubo429 = false;

    try {
        $statement = $database->prepare(
            'INSERT INTO ' . UsersModel::TABLE . ' (username, password, firstname, first_lastname, email, type, status)'
            . ' VALUES (?, ?, ?, ?, ?, ?, 1)'
        );
        $statement->execute([
            $username,
            password_hash('zz-' . bin2hex(random_bytes(6)), \PASSWORD_DEFAULT),
            'ZZ', 'Prueba', $email,
            UsersModel::TYPE_USER_GENERAL,
        ]);
        $userID = (int) $database->lastInsertId();
        $statement->execute([
            $usernameB,
            password_hash('zz-' . bin2hex(random_bytes(6)), \PASSWORD_DEFAULT),
            'ZZ', 'Prueba B', $emailB,
            UsersModel::TYPE_USER_GENERAL,
        ]);
        $userIDB = (int) $database->lastInsertId();
        echoTerminal("   usuarios de prueba creados: id={$userID} y, sin código, id={$userIDB}");
        echoTerminal(' ');

        //─── 1/6 · Ligadura al usuario ─────────────────────────────────────────────────────
        echoTerminal('[1/6] El código solo vale para su usuario');
        $filaCodigo('111111', '+1 hour');

        [$estado, $cuerpo] = $llamar('verifyCode', $peticion('POST', ['code' => '111111', 'username' => $emailB]));
        $hubo429 = $estado === 429;
        $check($estado !== 0 && ($cuerpo['success'] ?? null) === false, 'g1. el código de un usuario no vale para otro que existe → success false', $detalle($estado, $cuerpo));

        [$estado, $cuerpo] = $llamar('verifyCode', $peticion('POST', ['code' => '111111', 'username' => $email]));
        $hubo429 = $hubo429 || $estado === 429;
        $check(($cuerpo['success'] ?? null) === true, 'g2. verifyCode con el código y su usuario → success true', $detalle($estado, $cuerpo));
        $check($estado !== 0 && !array_key_exists('userName', $cuerpo), 'g3. la respuesta de g2 no tiene userName', $detalle($estado, $cuerpo));
        echoTerminal(' ');

        //─── 2/6 · El cambio no entrega el usuario ─────────────────────────────────────────
        echoTerminal('[2/6] Cambiar la contraseña no entrega el usuario ni su hash');
        $nueva = 'zz-Nueva-' . bin2hex(random_bytes(4));
        $hashAntes = $hashActual();
        [$estado, $cuerpo] = $llamar('newPasswordCreateCode', $peticion('POST', [
            'username' => $email,
            'code' => '111111',
            'password' => $nueva,
            'repassword' => $nueva,
        ]));
        $hubo429 = $hubo429 || $estado === 429;
        $hashDespues = $hashActual();
        $check(($cuerpo['success'] ?? null) === true, 'g4. newPasswordCreateCode → success true', $detalle($estado, $cuerpo));
        $check(
            $estado !== 0 && !array_key_exists('user', $cuerpo) && !$contiene($cuerpo, [$hashAntes, $hashDespues]),
            'g5. la respuesta no tiene user ni contiene el hash anterior o el nuevo',
            $detalle($estado, $cuerpo)
        );
        $check(password_verify($nueva, $hashDespues), 'g6. el hash guardado corresponde a la contraseña nueva');
        $statement = $database->prepare('SELECT COUNT(*) FROM pcsphp_recovery_password WHERE email = ?');
        $statement->execute([$email]);
        $filasRestantes = (int) $statement->fetchColumn();
        $check($filasRestantes === 0, 'g7. no quedan códigos pendientes para ese correo', "quedan {$filasRestantes}");
        echoTerminal(' ');

        //─── 3/6 · Caducado ────────────────────────────────────────────────────────────────
        echoTerminal('[3/6] Un código caducado no vale');
        $filaCodigo('222222', '-1 hour');
        [$estado, $cuerpo] = $llamar('verifyCode', $peticion('POST', ['code' => '222222', 'username' => $email]));
        $hubo429 = $hubo429 || $estado === 429;
        $check($estado !== 0 && ($cuerpo['success'] ?? null) === false, 'g8. verifyCode con un código caducado → success false', $detalle($estado, $cuerpo));
        echoTerminal(' ');

        if ($hubo429) {
            $statement = $database->prepare('SELECT COUNT(*) FROM ' . LoginAttemptsModel::TABLE . ' WHERE success = ? AND ip = ? AND date >= ?');
            $statement->execute([LoginAttemptsModel::FAIL_ATTEMPT, '0.0.0.0', date('Y-m-d H:i:s', time() - 30 * 60)]);
            echoTerminal("   \e[31mAVISO: hubo un 429 antes de [4/6]; fallos con IP 0.0.0.0 en los últimos 30 minutos: " . (int) $statement->fetchColumn() . "\e[39m");
            echoTerminal(' ');
        }

        //─── 4/6 · Límite de intentos ──────────────────────────────────────────────────────
        echoTerminal('[4/6] Tras cinco fallos, el código correcto recibe 429');
        $database->prepare('DELETE FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt = ?')->execute([$email]);
        $filaCodigo('333333', '+1 hour');
        $cincoFallos = true;
        $detalleFallos = [];
        foreach (['000001', '000002', '000003', '000004', '000005'] as $codigoErroneo) {
            [$estado, $cuerpo] = $llamar('verifyCode', $peticion('POST', ['code' => $codigoErroneo, 'username' => $email]));
            if ($estado !== 200 || ($cuerpo['success'] ?? null) !== false) {
                $cincoFallos = false;
                $detalleFallos[] = "{$codigoErroneo}: " . $detalle($estado, $cuerpo);
            }
        }
        $check($cincoFallos, 'g9. cinco códigos erróneos → 200 y success false', implode(' | ', $detalleFallos));

        [$estado, $cuerpo, $respuesta] = $llamar('verifyCode', $peticion('POST', ['code' => '333333', 'username' => $email]));
        $check(
            $estado === 429 && $respuesta !== null && $respuesta->hasHeader('Retry-After'),
            'g10. el sexto, con el código correcto → 429 con Retry-After',
            $detalle($estado, $cuerpo)
        );

        $statement = $database->prepare('SELECT extra_data FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt = ? AND success = ?');
        $statement->execute([$email, LoginAttemptsModel::FAIL_ATTEMPT]);
        $fallosRegistrados = 0;
        foreach ($statement->fetchAll(\PDO::FETCH_COLUMN) as $extraData) {
            $datos = is_string($extraData) ? json_decode($extraData, true) : null;
            if (is_array($datos) && ($datos[OTPRateLimiter::EXTRA_DATA_VIA] ?? null) === 'recovery-code') {
                $fallosRegistrados++;
            }
        }
        $check($fallosRegistrados >= 5, 'g11. login_attempts guarda al menos 5 fallos con otpVia recovery-code', "hay {$fallosRegistrados}");
        echoTerminal(' ');

        //─── 5/6 · La petición no delata ───────────────────────────────────────────────────
        echoTerminal('[5/6] Pedir un código responde igual exista o no el usuario');
        $inexistente = 'zz-no-existe-' . bin2hex(random_bytes(4)) . '@example.invalid';
        $esperado = [
            'send_mail' => true,
            'error' => 'NO_ERROR',
            'message' => OTPRateLimiter::uniformOTPMessage(),
        ];
        [$estado, $cuerpo] = $llamar('recoveryPasswordRequestCode', $peticion('POST', ['username' => $inexistente]));
        $check(
            $estado === 200 && ($cuerpo['send_mail'] ?? null) === true && ($cuerpo['error'] ?? null) === 'NO_ERROR' && ($cuerpo['message'] ?? null) === $esperado['message'],
            'g12. recoveryPasswordRequestCode con usuario inexistente → 200 y respuesta uniforme',
            $detalle($estado, $cuerpo)
        );
        [$estado, $cuerpo] = $llamar('recoveryPasswordRequest', $peticion('POST', ['username' => $inexistente]));
        $check($estado === 200 && $cuerpo === $esperado, 'g13. recoveryPasswordRequest con el mismo cuerpo → 200 y la misma respuesta uniforme', $detalle($estado, $cuerpo));
        $statement = $database->prepare('SELECT COUNT(*) FROM pcsphp_recovery_password WHERE email = ?');
        $statement->execute([$inexistente]);
        $filasInexistente = (int) $statement->fetchColumn();
        $check($filasInexistente === 0, 'g14. no se crea ningún código para el usuario inexistente', "hay {$filasInexistente}");
        echoTerminal(' ');

        //─── 6/6 · El enlace no escribe ────────────────────────────────────────────────────
        echoTerminal('[6/6] El GET del enlace redirige al formulario y no cambia la contraseña');
        $hashAntes = $hashActual();
        [$estado, $cuerpo, $respuesta] = $llamar('newPasswordCreate', $peticion('GET'), ['url_token' => 'zz-token-inexistente']);
        $rutaFormulario = (string) parse_url((string) get_route('recovery-form'), \PHP_URL_PATH);
        $location = $respuesta !== null ? $respuesta->getHeaderLine('Location') : '';
        $rutaLocation = (string) parse_url($location, \PHP_URL_PATH);
        $check(
            $estado === 302 && $rutaFormulario !== '' && str_ends_with($rutaLocation, $rutaFormulario),
            'g15. estado 302 hacia el formulario de recuperación',
            "estado {$estado}, Location '{$location}', esperada la ruta '{$rutaFormulario}'" . (isset($cuerpo['excepcion']) ? ', ' . $cuerpo['excepcion'] : '')
        );
        $check($hashActual() === $hashAntes, 'g16. el hash de la contraseña no cambió');

    } finally {
        $database->prepare('DELETE FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt IN (?, ?)')->execute([$email, $emailB]);
        $database->prepare('DELETE FROM pcsphp_recovery_password WHERE email = ?')->execute([$email]);
        if ($userID !== null) {
            $database->prepare('DELETE FROM ' . UsersModel::TABLE . ' WHERE id = ?')->execute([$userID]);
        }
        if ($userIDB !== null) {
            $database->prepare('DELETE FROM ' . UsersModel::TABLE . ' WHERE id = ?')->execute([$userIDB]);
        }
    }

    $contar = function (string $sql, array $valores) use ($database): int {
        $statement = $database->prepare($sql);
        $statement->execute($valores);
        return (int) $statement->fetchColumn();
    };
    echoTerminal(' ');
    echoTerminal('   restos tras la limpieza:'
        . ' usuarios=' . $contar('SELECT COUNT(*) FROM ' . UsersModel::TABLE . ' WHERE username IN (?, ?)', [$username, $usernameB])
        . ', códigos=' . $contar('SELECT COUNT(*) FROM pcsphp_recovery_password WHERE email = ?', [$email])
        . ', intentos=' . $contar('SELECT COUNT(*) FROM ' . LoginAttemptsModel::TABLE . ' WHERE username_attempt IN (?, ?)', [$email, $emailB]));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La recuperación de contraseña no se adivina, no entrega el usuario y su enlace no escribe.')->setEffects([CliActions::EFFECT_DATABASE])->register();
