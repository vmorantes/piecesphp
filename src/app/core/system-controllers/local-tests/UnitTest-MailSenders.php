<?php

//Lote 7c: los envíos de correo del framework, de punta a punta contra Mailpit en 127.0.0.1 (ADR 0015).
//La configuración de correo se desvía SOLO en memoria (set_config): nada guardado cambia y nada sale de la máquina.

use App\Controller\RecoveryPasswordController;
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
    $http = function (string $method, string $path) use ($api): ?array {
        $context = stream_context_create(['http' => ['method' => $method, 'timeout' => 5, 'ignore_errors' => true]]);
        $body = @file_get_contents($api . $path, false, $context);
        if (!is_string($body)) {
            return null;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : [];
    };

    //─── 1 · Comprobación previa: sin Mailpit local, o con Mailpit saliendo a la red, no se envía nada ─────
    echoTerminal('[1/2] Mailpit escucha en 127.0.0.1 y no comprueba versiones');
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

    //─── 2 · El código de recuperación de contraseña ────────────────────────────────────────────────────
    echoTerminal('[2/2] RecoveryPasswordController::mailRecoveryPasswordCode()');

    $original = get_config('mail');

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
        $enlace = get_route('recovery-form') . '?code=ZZ7C0DE';
        $formaMostrada = false;

        foreach ([false, true] as $onlyCode) {

            $caso = $onlyCode ? 'solo código' : 'con enlace';
            $http('DELETE', '/messages');

            $enviado = (new RecoveryPasswordController())->mailRecoveryPasswordCode('ZZ7C0DE', $usuario, $onlyCode);
            $check($enviado === true, "{$caso}: send() devuelve true", var_export($enviado, true));

            $lista = $http('GET', '/messages');
            $mensajes = is_array($lista) ? ($lista['messages'] ?? []) : [];
            $check(count($mensajes) === 1, "{$caso}: Mailpit tiene exactamente 1 mensaje", (string) count($mensajes));
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
                $check(str_contains($html, $enlace), "{$caso}: el HTML contiene el enlace de get_route('recovery-form')", $enlace);
            }
            $check($remitente === 'zz-prueba-remitente@localhost.test', "{$caso}: el remitente es zz-prueba-remitente@localhost.test", var_export($remitente, true));
        }

        $http('DELETE', '/messages');

    } finally {
        set_config('mail', $original);
    }

    return $balance();

})->setDescription('Los correos del framework llegan a Mailpit en 127.0.0.1, con la configuración desviada solo en memoria.')->setEffects([CliActions::EFFECT_EMAIL])->register();
