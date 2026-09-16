<?php

//Lote 7c B2a: los envíos que necesitan base, de punta a punta contra Mailpit en 127.0.0.1 (ADR 0015 y 0010).
//La configuración de correo se desvía solo en memoria; el token y el usuario zz se borran en los finally.

use App\Controller\GenericTokenController;
use App\Model\UsersModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/mail-senders-db', function ($args) {

    echoTerminal("\e[33m[TEST:MailSendersDatabase] El comentario de un token y el código OTP llegan a un sumidero SMTP local\e[39m");
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
    $balance = function () use (&$passed, &$failed): array {
        echoTerminal(' ');
        $total = $passed + $failed;
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];
    };

    $api = 'http://127.0.0.1:8025/api/v1';
    $http = function (string $method, string $path) use ($api): ?array {
        $context = stream_context_create(['http' => ['method' => $method, 'timeout' => 5, 'ignore_errors' => true]]);
        $body = @file_get_contents($api . $path, false, $context);
        if (!is_string($body)) {
            return null;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    };
    $mensajeUnico = function () use ($http): array {
        $lista = $http('GET', '/messages');
        $mensajes = is_array($lista) ? ($lista['messages'] ?? []) : [];
        if (count($mensajes) !== 1) {
            return [count($mensajes), []];
        }
        $detalle = $http('GET', '/message/' . rawurlencode((string) ($mensajes[0]['ID'] ?? '')));
        return [1, is_array($detalle) ? $detalle : []];
    };
    $direcciones = fn($lista): array => array_map(fn($d) => is_array($d) ? ($d['Address'] ?? null) : null, is_array($lista) ? $lista : []);
    $remitenteDe = fn(array $detalle) => is_array($detalle['From'] ?? null) ? ($detalle['From']['Address'] ?? null) : null;
    $json = fn($valor): string => (string) json_encode($valor, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PARTIAL_OUTPUT_ON_ERROR);
    $remitente = 'zz-prueba-remitente@localhost.test';

    //─── 1 · Comprobación previa: sin Mailpit local, o con Mailpit saliendo a la red, no se crea ni se envía nada ─
    echoTerminal('[1/3] Mailpit escucha en 127.0.0.1 y no comprueba versiones');
    $arranque = 'Arranca Mailpit con: ./mailpit --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 --disable-version-check';
    $socket = @fsockopen('127.0.0.1', 1025, $errno, $errstr, 1.0);
    $smtpEscucha = is_resource($socket);
    if ($smtpEscucha) {
        //RETORNO-IGNORADO: el socket solo sondeaba si el puerto escucha; que no cierre no cambia el veredicto.
        fclose($socket);
    }
    $info = $http('GET', '/info');
    $sinVersion = is_array($info) && in_array($info['LatestVersion'] ?? null, [null, '', 'disabled'], true);
    $check($smtpEscucha, 'hay un SMTP escuchando en 127.0.0.1:1025', $arranque);
    $check(is_array($info), 'la API de Mailpit responde en 127.0.0.1:8025', $arranque);
    $check($sinVersion, 'Mailpit no comprueba versiones (LatestVersion ausente, vacío o "disabled")', $arranque);
    if (!($smtpEscucha && is_array($info) && $sinVersion)) {
        echoTerminal('   No se crea ni se envía nada: la comprobación previa no se cumple.');
        return $balance();
    }
    echoTerminal(' ');

    $database = (new BaseModel())->getDatabase();
    $original = get_config('mail');
    $selector = null;
    $userID = null;

    try {
        $mail = new MailConfig;
        $mail->isSmtp(true);
        $mail->smtpDebug(0);
        $mail->host('127.0.0.1');
        $mail->port(1025);
        $mail->auth(false);
        $mail->protocol('');
        $mail->autoTls(false);
        $mail->user($remitente);
        $mail->password('');
        $mail->name('ZZ Prueba');
        set_config('mail', $mail->toSave());

        //─── 2 · El comentario de un token genérico ────────────────────────────────────────────────────
        echoTerminal('[2/3] GenericTokenController::commentary()');
        try {
            $http('DELETE', '/messages');
            $url = GenericTokenController::createTokenURL('commentary', ['zz' => true], 10);
            $selector = preg_match_all('/[0-9a-f]{32}/', $url, $coincidencias) === 1 ? $coincidencias[0][0] : null;
            $request = new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/prueba'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            $request = $request->withParsedBody([
                'email' => 'zz-prueba-comentario@localhost.test',
                'subject' => 'ZZ asunto comentario',
                'message' => '<b class="zz-c">c</b>',
                'token' => (string) $selector,
            ]);
            $respuesta = (new GenericTokenController())->commentary($request, new ResponseRoute());
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            echoTerminal('   forma de la respuesta: ' . $json($cuerpo));
            $check(is_array($cuerpo) && ($cuerpo['success'] ?? null) === true, 'c1. la respuesta dice éxito', $json($cuerpo));
            [$cuantos, $detalle] = $mensajeUnico();
            $check($cuantos === 1, 'c2. Mailpit tiene exactamente 1 mensaje', (string) $cuantos);
            $html = (string) ($detalle['HTML'] ?? '');
            $check(($detalle['Subject'] ?? null) === 'ZZ asunto comentario', 'c3. el asunto es «ZZ asunto comentario»', var_export($detalle['Subject'] ?? null, true));
            $check($direcciones($detalle['To'] ?? null) === [$remitente], "c4. el destinatario es {$remitente}", $json($direcciones($detalle['To'] ?? null)));
            $check($cuantos === 1 && !str_contains($html, '<b class="zz-c">'), 'c5. el HTML NO contiene la etiqueta viva');
            $check(str_contains($html, '&lt;b class=&quot;zz-c&quot;&gt;c&lt;/b&gt;'), 'c6. el HTML contiene la etiqueta escapada');
            $check($selector !== null && GenericTokenController::tokenBySelector($selector) === null, 'c7. el token se borró tras enviar', var_export($selector, true));
        } catch (\Throwable $exception) {
            $check(false, 'commentary', get_class($exception) . ': ' . $exception->getMessage());
        } finally {
            if ($selector !== null) {
                //RETORNO-IGNORADO: el resto de token se cuenta y se imprime al final.
                GenericTokenController::deleteOwnToken(GenericTokenController::tokenBySelector($selector));
            }
        }
        echoTerminal(' ');

        //─── 3 · El código OTP ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[3/3] OTPHandler::generateOTP()');
        $username = 'zz_otp_mail_' . bin2hex(random_bytes(4));
        $email = $username . '@localhost.test';
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

            $http('DELETE', '/messages');
            $lanzo = null;
            try {
                OTPHandler::generateOTP(new UserSystemFeaturesController(), $username);
            } catch (\Throwable $exception) {
                $lanzo = get_class($exception) . ': ' . $exception->getMessage();
            }
            $check($lanzo === null, 'o1. generateOTP no lanza', (string) $lanzo);
            [$cuantos, $detalle] = $mensajeUnico();
            $check($cuantos === 1, 'o2. Mailpit tiene exactamente 1 mensaje', (string) $cuantos);
            $asunto = __(OTPHandler::LANG_GROUP, 'Contraseña de un uso') . ' - ' . Config::app_title();
            $check(($detalle['Subject'] ?? null) === $asunto, "o3. el asunto es «{$asunto}»", var_export($detalle['Subject'] ?? null, true));
            $check($direcciones($detalle['To'] ?? null) === [$email], "o4. el destinatario es {$email}", $json($direcciones($detalle['To'] ?? null)));
            $check($remitenteDe($detalle) === $remitente, "o5. el remitente es {$remitente}", var_export($remitenteDe($detalle), true));
            $validez = vsprintf(__(OTPHandler::LANG_GROUP, 'Tiene una validez de %s minutos'), [OTPRateLimiter::config()['oneUseCodeMinutes']]);
            $check(str_contains((string) ($detalle['HTML'] ?? ''), $validez), "o6. el HTML contiene «{$validez}»");
            $filas = $database->prepare('SELECT COUNT(*) FROM ' . OTPSecretsUsersMapper::TABLE . ' WHERE user = ?');
            $filas->execute([$userID]);
            $cuantasFilas = (int) $filas->fetchColumn();
            $check($cuantasFilas >= 1, 'o7. hay al menos una fila en ' . OTPSecretsUsersMapper::TABLE . ' para el usuario zz', (string) $cuantasFilas);
        } catch (\Throwable $exception) {
            $check(false, 'generateOTP', get_class($exception) . ': ' . $exception->getMessage());
        } finally {
            if ($userID !== null) {
                $database->prepare('DELETE FROM ' . OTPSecretsUsersMapper::TABLE . ' WHERE user = ?')->execute([$userID]);
                $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$userID]);
                $database->prepare('DELETE FROM ' . UsersModel::TABLE . ' WHERE id = ?')->execute([$userID]);
            }
        }

        $http('DELETE', '/messages');

    } finally {
        set_config('mail', $original);
    }

    $restoToken = $selector !== null && GenericTokenController::tokenBySelector($selector) !== null ? 1 : 0;
    $restoUsuario = 0;
    $restoOTP = 0;
    if ($userID !== null) {
        $consulta = $database->prepare('SELECT COUNT(*) FROM ' . UsersModel::TABLE . ' WHERE id = ?');
        $consulta->execute([$userID]);
        $restoUsuario = (int) $consulta->fetchColumn();
        $consulta = $database->prepare('SELECT COUNT(*) FROM ' . OTPSecretsUsersMapper::TABLE . ' WHERE user = ?');
        $consulta->execute([$userID]);
        $restoOTP = (int) $consulta->fetchColumn();
    }
    echoTerminal(' ');
    echoTerminal("   restos tras la limpieza: token={$restoToken}, usuario={$restoUsuario}, filas OTP={$restoOTP}");

    return $balance();

})->setDescription('El comentario de un token genérico y el código OTP llegan a Mailpit en 127.0.0.1, con base local y la configuración desviada en memoria.')->setEffects([CliActions::EFFECT_EMAIL, CliActions::EFFECT_DATABASE])->register();
