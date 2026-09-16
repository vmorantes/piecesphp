<?php

//Lote 7c B2a: los envíos que necesitan base, de punta a punta contra Mailpit en 127.0.0.1 (ADR 0015 y 0010).
//La configuración de correo se desvía solo en memoria; el token y el usuario zz se borran en los finally.

use App\Controller\ContactFormsController;
use App\Controller\GenericTokenController;
use App\Model\AppConfigModel;
use App\Model\UsersModel;
use API\Controllers\APIController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use Newsletter\Mappers\NewsletterSuscriberMapper;
use Newsletter\NewsletterRoutes;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use Slim\Psr7\Factory\StreamFactory;
use SystemApprovals\Controllers\SystemApprovalsController;
use SystemApprovals\Mappers\SystemApprovalsMapper;
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
    echoTerminal('[1/8] Mailpit escucha en 127.0.0.1 y no comprueba versiones');
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
    $nombreCaptcha = 'GoogleReCaptchaV3Controller';
    $valorCrudoCaptcha = function () use ($database, $nombreCaptcha): ?string {
        $consulta = $database->prepare('SELECT value FROM pcsphp_app_config WHERE name = ?');
        $consulta->execute([$nombreCaptcha]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    //Lo que había antes de tocar nada: null si la fila no existía.
    $captchaAntes = $valorCrudoCaptcha();
    $destinatariosOriginales = get_config('contact_form_recipients');
    $visitantes = [];
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $aprobID = null;
    $emailAPI = null;

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
        echoTerminal('[2/8] GenericTokenController::commentary()');
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
        echoTerminal('[3/8] OTPHandler::generateOTP()');
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

        //─── 4-6 · El formulario de contacto ───────────────────────────────────────────────────────────
        $tokenCaptcha = function () use ($nombreCaptcha): string {
            $modelo = new AppConfigModel($nombreCaptcha);
            if ($modelo->id === null) {
                $modelo->name = $nombreCaptcha;
                $modelo->value = [];
                $modelo->save();
                $modelo = new AppConfigModel($nombreCaptcha);
            }
            $token = 'zz-captcha-' . bin2hex(random_bytes(8));
            $tokens = (array) $modelo->value;
            $tokens[] = ['date' => date('Y-m-d H:i:s'), 'token' => $token];
            $modelo->value = $tokens;
            $modelo->update();
            return $token;
        };
        $tokenGuardado = function (string $token) use ($nombreCaptcha): bool {
            foreach ((array) (new AppConfigModel($nombreCaptcha))->value as $elemento) {
                if ((((array) $elemento)['token'] ?? null) === $token) {
                    return true;
                }
            }
            return false;
        };
        $formaMostrada = false;
        $contactar = function (string $captcha, array $destinatarios, ?string $emailPropio = null) use (&$visitantes, &$formaMostrada, $http, $json): array {
            set_config('contact_form_recipients', $destinatarios);
            $email = $emailPropio ?? 'zz-prueba-contacto-' . bin2hex(random_bytes(4)) . '@localhost.test';
            $visitantes[] = $email;
            $http('DELETE', '/messages');
            $request = new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/prueba'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            $request = $request->withParsedBody([
                'from' => 'zz-prueba',
                'name' => 'ZZ Contacto',
                'email' => $email,
                'subject' => 'ZZ asunto contacto',
                'message' => '<b class="zz-k">k</b>',
                'updates' => 'yes',
                'tokenCaptcha' => $captcha,
            ]);
            $respuesta = (new ContactFormsController())->contactMessage($request, new ResponseRoute());
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            $cuerpo = is_array($cuerpo) ? $cuerpo : [];
            if (!$formaMostrada) {
                echoTerminal('   forma de la respuesta: ' . $json($cuerpo));
                $formaMostrada = true;
            }
            return [$cuerpo, $email];
        };

        echoTerminal('[4/8] ContactFormsController::contactMessage() con CAPTCHA válido y destinatario');
        try {
            $token = $tokenCaptcha();
            [$cuerpo, $email] = $contactar($token, ['zz-prueba-destino@localhost.test']);
            $check(($cuerpo['success'] ?? null) === true, 'k1. la respuesta dice éxito', $json($cuerpo));
            [$cuantos, $detalle] = $mensajeUnico();
            $check($cuantos === 1, 'k2. Mailpit tiene exactamente 1 mensaje', (string) $cuantos);
            $prefijo = __(LANG_GROUP, 'Contacto') . ': ZZ asunto contacto';
            echoTerminal("   literal de 'Contacto': " . __(LANG_GROUP, 'Contacto'));
            $check(str_starts_with((string) ($detalle['Subject'] ?? ''), $prefijo), "k3. el asunto empieza por «{$prefijo}»", var_export($detalle['Subject'] ?? null, true));
            $check($direcciones($detalle['To'] ?? null) === ['zz-prueba-destino@localhost.test'], 'k4. el destinatario es zz-prueba-destino@localhost.test', $json($direcciones($detalle['To'] ?? null)));
            $check($direcciones($detalle['ReplyTo'] ?? null) === [$email], "k5. responder a es {$email}", $json($direcciones($detalle['ReplyTo'] ?? null)));
            $html = (string) ($detalle['HTML'] ?? '');
            $check($cuantos === 1 && !str_contains($html, '<b class="zz-k">') && str_contains($html, '&lt;b class=&quot;zz-k&quot;&gt;k&lt;/b&gt;'), 'k6. el HTML escapa el mensaje del visitante');
            $check(!$tokenGuardado($token), 'k7. el token se consumió');
        } catch (\Throwable $exception) {
            $check(false, 'contacto con CAPTCHA válido', get_class($exception) . ': ' . $exception->getMessage());
        }
        echoTerminal(' ');

        echoTerminal('[5/8] Sin destinatarios no se envía');
        try {
            [$cuerpo] = $contactar($tokenCaptcha(), []);
            $check(($cuerpo['success'] ?? null) !== true, 's1. la respuesta NO dice éxito', $json($cuerpo));
            $lista = $http('GET', '/messages');
            $cuantos = is_array($lista) ? count($lista['messages'] ?? []) : -1;
            $check($cuantos === 0, 's2. Mailpit tiene 0 mensajes', (string) $cuantos);
            //Un correo con UTF-8 inválido hace fallar el alta en el boletín ANTES de crear el Mailer (sin destinatarios no se crea).
            $emailRoto = 'zz-prueba-contacto-' . bin2hex(random_bytes(4)) . "-\xC3\x28@localhost.test";
            $respuestaRota = null;
            $lanzo = null;
            try {
                [$respuestaRota] = $contactar($tokenCaptcha(), [], $emailRoto);
            } catch (\Throwable $e) {
                $lanzo = get_class($e) . ': ' . $e->getMessage();
            }
            $check($lanzo === null && ($respuestaRota['success'] ?? null) !== true, 's3. una excepción antes de crear el correo no rompe la respuesta', (string) ($lanzo ?? $json($respuestaRota)));
        } catch (\Throwable $exception) {
            $check(false, 'contacto sin destinatarios', get_class($exception) . ': ' . $exception->getMessage());
        }
        echoTerminal(' ');

        echoTerminal('[6/8] Con un CAPTCHA falso no se envía');
        try {
            [$cuerpo, $emailFalso] = $contactar('zz-captcha-inexistente', ['zz-prueba-destino@localhost.test']);
            $check(($cuerpo['success'] ?? null) !== true && ($cuerpo['message'] ?? null) === __(LANG_GROUP, 'CAPTCHA_FAIL'), 'f1. la respuesta NO dice éxito y su mensaje es CAPTCHA_FAIL', $json($cuerpo));
            $lista = $http('GET', '/messages');
            $cuantos = is_array($lista) ? count($lista['messages'] ?? []) : -1;
            $check($cuantos === 0, 'f2. Mailpit tiene 0 mensajes', (string) $cuantos);
            $boletin = $database->prepare('SELECT COUNT(*) FROM ' . NewsletterSuscriberMapper::TABLE . ' WHERE email = ?');
            $boletin->execute([$emailFalso]);
            $suscritos = (int) $boletin->fetchColumn();
            $check(!NewsletterRoutes::ENABLE || $suscritos === 0, 'f3. con el CAPTCHA falso no se suscribe al boletín', "filas en el boletín: {$suscritos}");
        } catch (\Throwable $exception) {
            $check(false, 'contacto con CAPTCHA falso', get_class($exception) . ': ' . $exception->getMessage());
        }

        $http('DELETE', '/messages');

        //─── 7 · El correo de aprobación ───────────────────────────────────────────────────────────────
        echoTerminal('[7/8] SystemApprovalsController::approvalAction() aprueba un usuario');
        //set_title() de los dos controladores cambia la configuración 'title' del proceso: se devuelve al salir.
        $tituloPrevio = get_config('title');
        $consultaRoot = $database->prepare('SELECT type FROM ' . UsersModel::TABLE . ' WHERE id = 1');
        $consultaRoot->execute();
        $tipoUno = $consultaRoot->fetchColumn();
        echoTerminal('   usuario id 1: ' . ($tipoUno === false ? 'no existe' : 'type ' . $tipoUno . ((int) $tipoUno === UsersModel::TYPE_USER_ROOT ? ' (root)' : ' (NO es root)')));
        try {
            $usernameAprob = 'zz_aprob_' . bin2hex(random_bytes(4));
            $emailAprob = $usernameAprob . '@localhost.test';
            $alta = $database->prepare(
                'INSERT INTO ' . UsersModel::TABLE . ' (username, password, firstname, first_lastname, email, type, status)'
                . ' VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $alta->execute([
                $usernameAprob,
                password_hash('zz-' . bin2hex(random_bytes(6)), \PASSWORD_DEFAULT),
                '<b>zza</b>', 'Zz', $emailAprob,
                UsersModel::TYPE_USER_GENERAL,
                UsersModel::STATUS_USER_APPROVED_PENDING,
            ]);
            $aprobID = (int) $database->lastInsertId();

            set_config('current_user', (object) ['id' => 1]);
            getLoggedFrameworkUser(true);

            $fila = new SystemApprovalsMapper();
            $fila->referenceTable = UsersModel::TABLE;
            $fila->referenceValue = (string) $aprobID;
            $fila->referenceAlias = 'Usuario';
            $fila->referenceDate = date('Y-m-d H:i:s');
            $fila->createdBy = $aprobID;
            $fila->status = SystemApprovalsMapper::STATUS_PENDING;
            $fila->save();
            $filaID = (int) $fila->getLastInsertID();

            $aprobar = function () use ($filaID): array {
                $request = new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/prueba'), new Headers(), [], [], (new StreamFactory())->createStream(''));
                $request = $request->withAttribute('id', (string) $filaID)->withParsedBody([
                    'approvalStatus' => SystemApprovalsMapper::STATUS_APPROVED,
                    'reason' => '<i>zzm</i>',
                ]);
                $respuesta = (new SystemApprovalsController())->approvalAction($request, new ResponseRoute());
                $cuerpo = json_decode((string) $respuesta->getBody(), true);
                return is_array($cuerpo) ? $cuerpo : [];
            };

            $http('DELETE', '/messages');
            $cuerpo = $aprobar();
            echoTerminal('   forma de la respuesta: ' . $json($cuerpo));
            $check(($cuerpo['success'] ?? null) === true, 'a1. la respuesta dice éxito', $json($cuerpo));
            [$cuantos, $detalle] = $mensajeUnico();
            $check($cuantos === 1, 'a2. Mailpit tiene exactamente 1 mensaje', (string) $cuantos);
            $check($direcciones($detalle['To'] ?? null) === [$emailAprob], "a3. el destinatario es {$emailAprob}", $json($direcciones($detalle['To'] ?? null)));
            $finalAprob = ' - ' . __(SystemApprovalsController::LANG_GROUP, 'Aprobaciones');
            $check(str_ends_with((string) ($detalle['Subject'] ?? ''), $finalAprob), "a4. el asunto termina en «{$finalAprob}»", var_export($detalle['Subject'] ?? null, true));
            $html = (string) ($detalle['HTML'] ?? '');
            $check($cuantos === 1 && !str_contains($html, '<b>zza</b>') && str_contains($html, '&lt;b&gt;zza&lt;/b&gt;'), 'a5. el HTML escapa el nombre');
            $check($cuantos === 1 && !str_contains($html, '<i>zzm</i>') && str_contains($html, '&lt;i&gt;zzm&lt;/i&gt;'), 'a6. el HTML escapa el motivo');
            $estado = $database->prepare('SELECT status FROM ' . SystemApprovalsMapper::TABLE . ' WHERE id = ?');
            $estado->execute([$filaID]);
            $estadoFila = $estado->fetchColumn();
            $estado = $database->prepare('SELECT status FROM ' . UsersModel::TABLE . ' WHERE id = ?');
            $estado->execute([$aprobID]);
            $estadoUsuario = $estado->fetchColumn();
            $check($estadoFila === SystemApprovalsMapper::STATUS_APPROVED && (int) $estadoUsuario === UsersModel::STATUS_USER_ACTIVE, 'a7. la fila queda APPROVED y el usuario activo', "fila {$estadoFila}, usuario {$estadoUsuario}");
            $http('DELETE', '/messages');
            $aprobar();
            $lista = $http('GET', '/messages');
            $cuantos = is_array($lista) ? count($lista['messages'] ?? []) : -1;
            $check($cuantos === 0, 'a8. el mismo POST otra vez no reenvía', (string) $cuantos);
        } catch (\Throwable $exception) {
            $check(false, 'aprobación', get_class($exception) . ': ' . $exception->getMessage() . ' en ' . basename($exception->getFile()) . ':' . $exception->getLine());
        } finally {
            set_config('title', $tituloPrevio);
            set_config('current_user', $previoUsuario);
            set_config('pcsphp_current_user_stored', $previoGuardado);
            if ($aprobID !== null) {
                $database->prepare('DELETE FROM ' . SystemApprovalsMapper::TABLE . ' WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $aprobID]);
                $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$aprobID]);
                $database->prepare('DELETE FROM ' . UsersModel::TABLE . ' WHERE id = ?')->execute([$aprobID]);
            }
        }
        echoTerminal(' ');

        //─── 8 · El alta por la API ────────────────────────────────────────────────────────────────────
        echoTerminal('[8/8] APIController::usersActions() register');
        $emailAPI = 'zz-prueba-api-' . bin2hex(random_bytes(4)) . '@localhost.test';
        try {
            $clave = 'zz-' . bin2hex(random_bytes(8));
            $http('DELETE', '/messages');
            $request = new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/prueba'), new Headers(), [], [], (new StreamFactory())->createStream(''));
            $request = $request->withAttribute('actionType', 'register')->withParsedBody([
                'email' => $emailAPI,
                'password' => $clave,
                'passwordConfirm' => $clave,
                'firstName' => '<b>zzr</b>',
                'firstLastName' => 'Zz',
            ]);
            $respuesta = (new APIController())->usersActions($request, new ResponseRoute());
            $cuerpo = json_decode((string) $respuesta->getBody(), true);
            $cuerpo = is_array($cuerpo) ? $cuerpo : [];
            echoTerminal('   forma de la respuesta: ' . $json($cuerpo));
            $check(($cuerpo['success'] ?? null) === true, 'r1. la respuesta dice éxito', $json($cuerpo));
            [$cuantos, $detalle] = $mensajeUnico();
            $check($cuantos === 1, 'r2. Mailpit tiene exactamente 1 mensaje', (string) $cuantos);
            $check($direcciones($detalle['To'] ?? null) === [$emailAPI], "r3. el destinatario es {$emailAPI}", $json($direcciones($detalle['To'] ?? null)));
            $guardado = $database->prepare('SELECT firstname, type, status FROM ' . UsersModel::TABLE . ' WHERE email = ?');
            $guardado->execute([$emailAPI]);
            $filaAPI = $guardado->fetch(\PDO::FETCH_ASSOC);
            echoTerminal('   nombre guardado: ' . var_export(is_array($filaAPI) ? $filaAPI['firstname'] : null, true));
            $html = (string) ($detalle['HTML'] ?? '');
            $check($cuantos === 1 && !str_contains($html, '<b>zzr</b>') && str_contains($html, '&lt;b&gt;zzr&lt;/b&gt;'), 'r4. el HTML escapa el nombre');
            $check(is_array($filaAPI) && (int) $filaAPI['type'] === UsersModel::TYPE_USER_GENERAL && (int) $filaAPI['status'] === UsersModel::STATUS_USER_APPROVED_PENDING, 'r5. el usuario existe con type 2 y status 3', $json($filaAPI));
        } catch (\Throwable $exception) {
            $check(false, 'alta por la API', get_class($exception) . ': ' . $exception->getMessage());
        } finally {
            set_config('title', $tituloPrevio);
            $ids = $database->prepare('SELECT id FROM ' . UsersModel::TABLE . ' WHERE email = ?');
            $ids->execute([$emailAPI]);
            foreach ($ids->fetchAll(\PDO::FETCH_COLUMN) as $idAPI) {
                $database->prepare('DELETE FROM ' . SystemApprovalsMapper::TABLE . ' WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $idAPI]);
                $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$idAPI]);
                $database->prepare('DELETE FROM ' . UsersModel::TABLE . ' WHERE id = ?')->execute([$idAPI]);
            }
        }

        $http('DELETE', '/messages');

    } finally {
        set_config('mail', $original);
        set_config('contact_form_recipients', $destinatariosOriginales === false ? null : $destinatariosOriginales);
        if (count($visitantes) > 0) {
            $database->prepare('DELETE FROM ' . NewsletterSuscriberMapper::TABLE . ' WHERE email IN (' . implode(', ', array_fill(0, count($visitantes), '?')) . ')')->execute($visitantes);
        }
        if ($captchaAntes === null) {
            $database->prepare('DELETE FROM pcsphp_app_config WHERE name = ?')->execute([$nombreCaptcha]);
        } else {
            $database->prepare('UPDATE pcsphp_app_config SET value = ? WHERE name = ?')->execute([$captchaAntes, $nombreCaptcha]);
        }
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
    $captchaDespues = $valorCrudoCaptcha();
    $tokensZz = substr_count((string) $captchaDespues, 'zz-captcha-');
    $boletinZz = 0;
    if (count($visitantes) > 0) {
        $consulta = $database->prepare('SELECT COUNT(*) FROM ' . NewsletterSuscriberMapper::TABLE . ' WHERE email IN (' . implode(', ', array_fill(0, count($visitantes), '?')) . ')');
        $consulta->execute($visitantes);
        $boletinZz = (int) $consulta->fetchColumn();
    }
    $contarRestos = function (string $sql, array $valores) use ($database): int {
        $consulta = $database->prepare($sql);
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    };
    $restoAprobUsuario = $aprobID !== null ? $contarRestos('SELECT COUNT(*) FROM ' . UsersModel::TABLE . ' WHERE id = ?', [$aprobID]) : 0;
    $restoAprobFilas = $aprobID !== null ? $contarRestos('SELECT COUNT(*) FROM ' . SystemApprovalsMapper::TABLE . ' WHERE referenceTable = ? AND referenceValue = ?', [UsersModel::TABLE, (string) $aprobID]) : 0;
    $restoAprobPerfil = $aprobID !== null ? $contarRestos('SELECT COUNT(*) FROM user_system_profile WHERE belongsTo = ?', [$aprobID]) : 0;
    $restoAPI = $emailAPI !== null ? $contarRestos('SELECT COUNT(*) FROM ' . UsersModel::TABLE . ' WHERE email = ?', [$emailAPI]) : 0;
    echoTerminal("   restos de aprobación y alta: usuario aprobación={$restoAprobUsuario}, filas de aprobación={$restoAprobFilas}, perfil={$restoAprobPerfil}, usuario API={$restoAPI}");
    echoTerminal("   restos del contacto: tokens zz en el CAPTCHA={$tokensZz}, filas del boletín zz={$boletinZz}, fila GoogleReCaptchaV3Controller igual que antes=" . ($captchaDespues === $captchaAntes ? 'sí' : 'no'));

    return $balance();

})->setDescription('El comentario de un token genérico, el código OTP y el formulario de contacto, la aprobación y el alta por la API llegan a Mailpit en 127.0.0.1, con base local y la configuración desviada en memoria.')->setEffects([CliActions::EFFECT_EMAIL, CliActions::EFFECT_DATABASE])->register();
