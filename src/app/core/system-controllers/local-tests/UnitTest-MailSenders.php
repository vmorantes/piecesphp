<?php

//Lote 7c: los envíos de correo del framework, de punta a punta contra Mailpit en 127.0.0.1 (ADR 0015).
//La configuración de correo se desvía SOLO en memoria (set_config): nada guardado cambia y nada sale de la máquina.

use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/mail-senders', function ($args) {

    echoTerminal("\e[33m[TEST:MailSenders] Los correos del framework llegan a un sumidero SMTP local\e[39m");
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
    $http = function (string $method, string $path, ?array $payload = null) use ($api): ?array {
        $opciones = ['method' => $method, 'timeout' => 5, 'ignore_errors' => true];
        if ($payload !== null) {
            $opciones['header'] = 'Content-Type: application/json';
            $opciones['content'] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        $context = stream_context_create(['http' => $opciones]);
        $body = @file_get_contents($api . $path, false, $context);
        if (!is_string($body)) {
            return null;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    };

    //La bandeja es DE LA MÁQUINA: vaciarla borró 62 ajenos (2026-10-02) y reclamar todo `@localhost.test`, los de mail-demo.
    //Lo propio lleva la marca de las pruebas, `zz-` o `zz_` delante, en `@localhost.test`: solo eso se cuenta y se borra.
    $mios = function () use ($http): array {
        $lista = $http('GET', '/messages?limit=200');
        $mensajes = is_array($lista) ? ($lista['messages'] ?? []) : [];
        $propios = [];
        foreach (is_array($mensajes) ? $mensajes : [] as $mensaje) {
            foreach ((array) ($mensaje['To'] ?? []) as $destino) {
                $direccion = is_array($destino) ? (string) ($destino['Address'] ?? '') : '';
                if (str_ends_with($direccion, '@localhost.test') && preg_match('/^zz[-_]/', $direccion) === 1) {
                    $propios[] = $mensaje;
                    continue 2;
                }
            }
        }
        return $propios;
    };
    $limpiarMios = function () use ($http, $mios): void {
        $ids = array_values(array_filter(array_map(fn ($m): string => (string) ($m['ID'] ?? ''), $mios())));
        if ($ids !== []) {
            //RETORNO-IGNORADO: si el borrado fallara, el conteo de los míos lo delataría en la comprobación siguiente.
            $http('DELETE', '/messages', ['IDs' => $ids]);
        }
    };

    //─── 1 · Comprobación previa: sin Mailpit local, o con Mailpit saliendo a la red, no se envía nada ─────
    echoTerminal('[1/4] Mailpit escucha en 127.0.0.1 y no comprueba versiones');
    $arranque = 'Arranca Mailpit con: ./mailpit --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 --disable-version-check';
    $socket = @fsockopen('127.0.0.1', 1025, $errno, $errstr, 1.0);
    $smtpEscucha = is_resource($socket);
    if ($smtpEscucha) {
        //RETORNO-IGNORADO: el socket solo sondeaba si el puerto escucha; que no cierre no cambia el veredicto.
        fclose($socket);
    }
    $info = $http('GET', '/info');
    //Con --disable-version-check vale "disabled"; cualquier versión en este campo es que preguntó fuera (#127).
    $sinVersion = is_array($info) && in_array($info['LatestVersion'] ?? null, [null, '', 'disabled'], true);
    $check($smtpEscucha, 'hay un SMTP escuchando en 127.0.0.1:1025', $arranque);
    $check(is_array($info), 'la API de Mailpit responde en 127.0.0.1:8025', $arranque);
    $check($sinVersion, 'Mailpit no comprueba versiones (LatestVersion ausente, vacío o "disabled")', $arranque);
    if (!($smtpEscucha && is_array($info) && $sinVersion)) {
        echoTerminal('   No se envía nada: la comprobación previa no se cumple.');
        return $balance();
    }
    echoTerminal(' ');

    //CANARIO: un correo a OTRO `@localhost.test`, sin la marca de las pruebas, tiene que seguir ahí al terminar.
    $canarioAjeno = 'demo-canario-' . bin2hex(random_bytes(3)) . '@localhost.test';
    $sembradoAjeno = $http('POST', '/send', ['From' => ['Email' => 'demo-remitente@localhost.test'], 'To' => [['Email' => $canarioAjeno]], 'Subject' => 'Ajeno a las suites de envío', 'Text' => 'canario']);

    //Cada envío deja su fila en el registro de correos: se retiran las DE ESTA CORRIDA (id posterior al de ahora) y con la
    //marca de las pruebas. El `_` va escapado: en LIKE es comodín, y «zz_» casaría con cualquier «zz» y un carácter.
    $registro = \PiecesPHP\SystemStatus\Mappers\MailLogMapper::model()->getDatabase();
    $tablaRegistro = \PiecesPHP\SystemStatus\Mappers\MailLogMapper::tableName();
    $desdeRegistro = $registro !== null ? (int) $registro->query("SELECT COALESCE(MAX(`id`), 0) FROM `{$tablaRegistro}`")->fetchColumn() : 0;
    $filtroRegistro = "`id` > ? AND (`recipients` LIKE 'zz-%' OR `recipients` LIKE 'zz\\_%')";
    $retiradasRegistro = 0;
    $retirarRegistro = function () use ($registro, $tablaRegistro, $desdeRegistro, $filtroRegistro, &$retiradasRegistro): void {
        if ($registro === null) {
            return;
        }
        $borrar = $registro->prepare("DELETE FROM `{$tablaRegistro}` WHERE {$filtroRegistro}");
        $borrar->execute([$desdeRegistro]);
        $retiradasRegistro += $borrar->rowCount();
    };

    //─── 2 · El código de recuperación de contraseña ────────────────────────────────────────────────────
    echoTerminal('[2/4] RecoveryPasswordController::mailRecoveryPasswordCode()');

    $original = get_config('mail');
    $originalesExtra = [];
    foreach (['osTicketAPI', 'osTicketAPIKey', 'other_problems_recipients'] as $claveExtra) {
        $originalesExtra[$claveExtra] = get_config($claveExtra);
    }

    try {

        $mail = new MailConfig;
        //isSmtp y smtpDebug los fija la suite: salen de la configuración guardada, y con isSmtp en false el envío iría por mail().
        $mail->isSmtp(true);
        $mail->smtpDebug(0);
        $mail->host('127.0.0.1');
        $mail->port(1025);
        $mail->auth(false);
        $mail->protocol('');
        $mail->autoTls(false);
        $mail->user('zz-prueba-remitente@localhost.test');
        $mail->password('');
        $mail->name('ZZ Prueba');
        set_config('mail', $mail->toSave());

        $usuario = (object) ['email' => 'zz-prueba-destino@localhost.test', 'username' => 'zz-prueba'];
        $asunto = __('revoveryPasswordModule', 'Recuperación de contraseña');
        $enlace = get_route('users-recovery-form') . '?code=ZZ7C0DE';
        $formaMostrada = false;

        foreach ([false, true] as $onlyCode) {

            $caso = $onlyCode ? 'solo código' : 'con enlace';
            $limpiarMios();

            $enviado = (new RecoveryPasswordController())->mailRecoveryPasswordCode('ZZ7C0DE', $usuario, $onlyCode);
            $check($enviado === true, "{$caso}: send() devuelve true", var_export($enviado, true));

            $mensajes = $mios();
            $check(count($mensajes) === 1, "{$caso}: hay exactamente 1 mensaje de los míos", (string) count($mensajes));
            if (count($mensajes) !== 1) {
                continue;
            }

            $detalle = $http('GET', '/message/' . rawurlencode((string) ($mensajes[0]['ID'] ?? '')));
            $detalle = is_array($detalle) ? $detalle : [];
            if (!$formaMostrada) {
                echoTerminal('   forma de To: ' . json_encode($detalle['To'] ?? null, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                echoTerminal('   forma de From: ' . json_encode($detalle['From'] ?? null, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                $formaMostrada = true;
            }

            $destinos = array_map(fn($d) => is_array($d) ? ($d['Address'] ?? null) : null, is_array($detalle['To'] ?? null) ? $detalle['To'] : []);
            $remitente = is_array($detalle['From'] ?? null) ? ($detalle['From']['Address'] ?? null) : null;
            $html = (string) ($detalle['HTML'] ?? '');

            $check(($detalle['Subject'] ?? null) === $asunto, "{$caso}: el asunto es «{$asunto}»", var_export($detalle['Subject'] ?? null, true));
            $check($destinos === ['zz-prueba-destino@localhost.test'], "{$caso}: el destinatario es zz-prueba-destino@localhost.test", json_encode($destinos, JSON_THROW_ON_ERROR));
            $check(str_contains($html, 'ZZ7C0DE'), "{$caso}: el HTML contiene el código");
            if (!$onlyCode) {
                $check(str_contains($html, $enlace), "{$caso}: el HTML contiene el enlace de get_route('users-recovery-form')", $enlace);
            }
            $check($remitente === 'zz-prueba-remitente@localhost.test', "{$caso}: el remitente es zz-prueba-remitente@localhost.test", var_export($remitente, true));
        }

        echoTerminal(' ');

        $mensajeUnico = function () use ($http, $mios): array {
            $mensajes = $mios();
            if (count($mensajes) !== 1) {
                return [count($mensajes), []];
            }
            $detalle = $http('GET', '/message/' . rawurlencode((string) ($mensajes[0]['ID'] ?? '')));
            return [1, is_array($detalle) ? $detalle : []];
        };
        $direcciones = fn($lista): array => array_map(fn($d) => is_array($d) ? ($d['Address'] ?? null) : null, is_array($lista) ? $lista : []);
        $remitenteDe = fn(array $detalle) => is_array($detalle['From'] ?? null) ? ($detalle['From']['Address'] ?? null) : null;
        $destino = 'zz-prueba-destino@localhost.test';
        $remitenteEsperado = 'zz-prueba-remitente@localhost.test';

        //─── 3 · Los códigos de usuario olvidado y bloqueado ────────────────────────────────────────────
        echoTerminal('[3/4] UserProblemsController::sendCode()');
        $asuntoCodigo = __(UserProblemsController::LANG_GROUP, 'Código de verificación');
        $tipos = [
            UserProblemsController::TYPE_USER_FORGET => get_route('users-forget-form') . '?code=ZZ7C0DE',
            UserProblemsController::TYPE_USER_BLOCKED => get_route('users-blocked-form') . '?code=ZZ7C0DE',
        ];
        foreach ($tipos as $tipo => $enlaceTipo) {
            $limpiarMios();
            $enviado = (new \ReflectionMethod(UserProblemsController::class, 'sendCode'))->invoke(new UserProblemsController(), 'ZZ7C0DE', $usuario, $tipo);
            $check($enviado === true, "{$tipo}: invoke devuelve true", var_export($enviado, true));
            [$cuantos, $detalle] = $mensajeUnico();
            $check($cuantos === 1, "{$tipo}: Mailpit tiene exactamente 1 mensaje", (string) $cuantos);
            if ($cuantos !== 1) {
                continue;
            }
            $html = (string) ($detalle['HTML'] ?? '');
            $check(($detalle['Subject'] ?? null) === $asuntoCodigo, "{$tipo}: el asunto es «{$asuntoCodigo}»", var_export($detalle['Subject'] ?? null, true));
            $check($direcciones($detalle['To'] ?? null) === [$destino], "{$tipo}: el destinatario es {$destino}", json_encode($direcciones($detalle['To'] ?? null), JSON_THROW_ON_ERROR));
            $check(str_contains($html, 'ZZ7C0DE'), "{$tipo}: el HTML contiene el código");
            $check(str_contains($html, $enlaceTipo), "{$tipo}: el HTML contiene su enlace", $enlaceTipo);
            $check($remitenteDe($detalle) === $remitenteEsperado, "{$tipo}: el remitente es {$remitenteEsperado}", var_export($remitenteDe($detalle), true));
        }
        echoTerminal(' ');

        //─── 4 · Otros problemas, con osTicket vacío en memoria ─────────────────────────────────────────
        echoTerminal('[4/4] UserProblemsController::sendMessageOtherProblems()');
        //Con osTicket configurado intentaría la red primero: se vacía solo en memoria.
        set_config('osTicketAPI', '');
        set_config('osTicketAPIKey', '');
        $otros = new \ReflectionMethod(UserProblemsController::class, 'sendMessageOtherProblems');
        $argumentos = ['zz-prueba-visitante@localhost.test', 'ZZ Visitante', 'ZZ mensaje 7c', ['subject' => 'ZZ asunto 7c']];

        set_config('other_problems_recipients', [$destino]);
        $limpiarMios();
        $resultado = $otros->invoke(new UserProblemsController(), ...$argumentos);
        $check(is_array($resultado) && ($resultado['success'] ?? null) === true, "a) con destinatario: success === true", var_export(is_array($resultado) ? ($resultado['success'] ?? null) : $resultado, true));
        [$cuantos, $detalle] = $mensajeUnico();
        $check($cuantos === 1, 'a) Mailpit tiene exactamente 1 mensaje', (string) $cuantos);
        if ($cuantos === 1) {
            if (!array_key_exists('ReplyTo', $detalle)) {
                $sinHtml = array_diff_key($detalle, ['HTML' => true, 'Text' => true]);
                echoTerminal('   PARADA: el detalle no trae ReplyTo. Forma del detalle, sin HTML: ' . json_encode($sinHtml, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            }
            echoTerminal('   forma de ReplyTo: ' . json_encode($detalle['ReplyTo'] ?? null, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $asuntoOtros = 'ZZ asunto 7c - ' . Config::app_title();
            $check(($detalle['Subject'] ?? null) === $asuntoOtros, "a) el asunto es «{$asuntoOtros}»", var_export($detalle['Subject'] ?? null, true));
            $check($direcciones($detalle['To'] ?? null) === [$destino], "a) el destinatario es {$destino}", json_encode($direcciones($detalle['To'] ?? null), JSON_THROW_ON_ERROR));
            $check($direcciones($detalle['ReplyTo'] ?? null) === ['zz-prueba-visitante@localhost.test'], 'a) responder a es zz-prueba-visitante@localhost.test', json_encode($direcciones($detalle['ReplyTo'] ?? null), JSON_THROW_ON_ERROR));
            $check(str_contains((string) ($detalle['HTML'] ?? ''), 'ZZ mensaje 7c'), 'a) el HTML contiene el mensaje');
            $check($remitenteDe($detalle) === $remitenteEsperado, "a) el remitente es {$remitenteEsperado}", var_export($remitenteDe($detalle), true));
        }

        set_config('other_problems_recipients', []);
        $limpiarMios();
        $resultado = $otros->invoke(new UserProblemsController(), ...$argumentos);
        $check(is_array($resultado) && ($resultado['success'] ?? null) === false, 'b) sin destinatarios: success === false', var_export(is_array($resultado) ? ($resultado['success'] ?? null) : $resultado, true));
        $cuantosB = count($mios());
        $check($cuantosB === 0, 'b) no hay ningún mensaje mío: no se envió nada', (string) $cuantosB);

        $limpiarMios();

    } finally {
        set_config('mail', $original);
        foreach ($originalesExtra as $claveExtra => $valorExtra) {
            set_config($claveExtra, $valorExtra === false ? null : $valorExtra);
        }
        $retirarRegistro();
    }

    $quedanRegistro = -1;
    if ($registro !== null) {
        $contarRegistro = $registro->prepare("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE {$filtroRegistro}");
        $contarRegistro->execute([$desdeRegistro]);
        $quedanRegistro = (int) $contarRegistro->fetchColumn();
    }
    $check($quedanRegistro === 0, "z la prueba retira sus filas del registro de correos ({$retiradasRegistro} retirada(s)): no queda ninguna suya", "quedan {$quedanRegistro}");

    $idsAjeno = [];
    foreach ((array) (($http('GET', '/messages?limit=200') ?? [])['messages'] ?? []) as $mensajeAjeno) {
        foreach ((array) (is_array($mensajeAjeno) ? ($mensajeAjeno['To'] ?? []) : []) as $destinoAjeno) {
            if (is_array($destinoAjeno) && (string) ($destinoAjeno['Address'] ?? '') === $canarioAjeno) {
                $idsAjeno[] = (string) ($mensajeAjeno['ID'] ?? '');
            }
        }
    }
    $check(is_array($sembradoAjeno) && count($idsAjeno) === 1, 'z CANARIO: un correo a otro @localhost.test, sin la marca de las pruebas, sigue ahí: solo se borra lo propio', count($idsAjeno) . ' ajeno(s)');
    if ($idsAjeno !== []) {
        //RETORNO-IGNORADO: el canario es de la prueba y se retira; si quedara, se vería en la bandeja.
        $http('DELETE', '/messages', ['IDs' => $idsAjeno]);
    }

    return $balance();

})->setDescription('Los correos del framework llegan a Mailpit en 127.0.0.1, con la configuración desviada solo en memoria.')->setEffects([CliActions::EFFECT_EMAIL])->register();
