<?php

/**
 * MailTester.php
 */

namespace PiecesPHP\Settings\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Settings\Controllers\SettingsController;

/**
 * Las dos pruebas de Configuración › Sistema › Correo: probar la conexión y enviar un correo de prueba.
 *
 * **El destino se lee de un `Mailer` recién creado, nunca de `MailConfig`**: con el correo retenido, el constructor del
 * `Mailer` se desvía al servidor de pruebas, y decir el host configurado midiendo el del sumidero es el defecto que
 * documenta el ADR 0046. Por eso las dos respuestas dicen el configurado y el usado.
 *
 * @package     PiecesPHP\Settings\Mail
 */
class MailTester
{

    const LANG_GROUP = SettingsController::LANG_GROUP;

    /**
     * Segundos que se espera a cada paso de la conversación SMTP.
     */
    const TIMEOUT = 15;

    /**
     * Conecta, saluda, cifra si toca, se autentica si toca y cuelga, sin enviar nada. `Mailer::probeSMTP()` no sirve:
     * solo abre el puerto y no se autentica. La contraseña no sale de aquí: ni a la respuesta ni al registro.
     *
     * @return array{success:bool,message:string,configured:string,used:string,retained:bool,route:string}
     */
    public static function probe(): array
    {
        $mailer = new Mailer();
        $route = self::route($mailer);

        $smtp = new SMTP();
        $smtp->do_debug = SMTP::DEBUG_OFF;
        $smtp->Timeout = self::TIMEOUT;
        $steps = [];
        $failure = null;

        try {
            $prefix = $mailer->SMTPSecure === PHPMailer::ENCRYPTION_SMTPS ? 'ssl://' : '';
            $options = is_array($mailer->SMTPOptions) ? $mailer->SMTPOptions : [];
            if (!$smtp->connect($prefix . $mailer->Host, (int) $mailer->Port, self::TIMEOUT, $options)) {
                $failure = [__(self::LANG_GROUP, 'conectar'), self::serverError($smtp)];
            } else {
                $steps[] = __(self::LANG_GROUP, 'conecta');
                $helo = is_string($mailer->Helo) && $mailer->Helo !== '' ? $mailer->Helo : (gethostname() ?: 'localhost.localdomain');
                if (!$smtp->hello($helo)) {
                    $failure = [__(self::LANG_GROUP, 'saludar'), self::serverError($smtp)];
                } else {
                    $steps[] = __(self::LANG_GROUP, 'saluda');
                    //Como PHPMailer::smtpConnect(): STARTTLS si se pide, o si Auto TLS está puesto y el servidor lo ofrece.
                    $tls = $mailer->SMTPSecure === PHPMailer::ENCRYPTION_STARTTLS
                        || ($mailer->SMTPAutoTLS && $mailer->Host !== 'localhost' && $prefix === '' && (bool) $smtp->getServerExt('STARTTLS'));
                    if ($tls && (!$smtp->startTLS() || !$smtp->hello($helo))) {
                        $failure = [__(self::LANG_GROUP, 'cifrar la conexión'), self::serverError($smtp)];
                    } else {
                        if ($tls || $prefix !== '') {
                            $steps[] = __(self::LANG_GROUP, 'cifra la conexión');
                        }
                        if ($mailer->SMTPAuth) {
                            $authType = is_string($mailer->AuthType) && $mailer->AuthType !== '' ? $mailer->AuthType : null;
                            if (!$smtp->authenticate($mailer->Username, $mailer->Password, $authType)) {
                                $failure = [__(self::LANG_GROUP, 'autenticarse'), self::serverError($smtp)];
                            } else {
                                $steps[] = __(self::LANG_GROUP, 'se autentica');
                            }
                        } else {
                            $steps[] = __(self::LANG_GROUP, 'sin autenticarse, porque no se pide');
                        }
                    }
                }
                if ($failure === null) {
                    //RETORNO-IGNORADO: colgar es cortesía; la prueba ya terminó.
                    $smtp->quit();
                }
            }
        } catch (\Throwable $e) {
            $failure = [__(self::LANG_GROUP, 'conectar'), $e->getMessage()];
        } finally {
            $smtp->close();
        }

        $message = $failure === null
            ? sprintf(__(self::LANG_GROUP, 'La conexión funciona: %s. No se envió nada.'), implode(', ', $steps))
            : sprintf(__(self::LANG_GROUP, 'Falló al %s: %s'), $failure[0], $failure[1]);

        return ['success' => $failure === null, 'message' => $message] + $route;
    }

    /**
     * Envía un correo de prueba por el `Mailer` de verdad, así que queda en el registro de correos.
     *
     * @param string $to
     * @return array{success:bool,message:string,configured:string,used:string,retained:bool,route:string}
     */
    public static function send(string $to): array
    {
        $mailer = new Mailer();
        $route = self::route($mailer);
        $mailConfig = new MailConfig;
        //Los getters de MailConfig devuelven el valor o el propio objeto: se estrecha por tipo, no con un cast.
        $from = $mailConfig->user();
        $fromName = $mailConfig->name();
        $app = (string) get_config('title_app');

        try {
            $mailer->setFrom(is_string($from) ? $from : '', is_string($fromName) ? $fromName : '');
            $mailer->addAddress($to);
            $mailer->isHTML(true);
            $mailer->Subject = sprintf(__(self::LANG_GROUP, 'Correo de prueba de %s'), $app);
            $mailer->Body = '<p>' . escape_html(sprintf(__(self::LANG_GROUP, 'Si lees esto, el correo sale de %s. Lo envió la prueba de Configuración › Sistema › Correo el %s.'), $app, date('Y-m-d H:i:s'))) . '</p>';
            $sent = $mailer->send();
            $error = $sent ? null : $mailer->ErrorInfo;
        } catch (\Throwable $e) {
            $sent = false;
            $error = $e->getMessage();
        }

        if ($sent) {
            $message = $route['retained']
                ? sprintf(__(self::LANG_GROUP, 'Enviado a %s. El correo está retenido: fue al servidor de pruebas, o al buzón en disco si no respondía; el registro de correos dice cuál.'), $to)
                : sprintf(__(self::LANG_GROUP, 'Enviado a %s. Queda en el registro de correos.'), $to);
        } else {
            $message = sprintf(__(self::LANG_GROUP, 'No se envió: %s'), (string) $error);
        }

        return ['success' => $sent, 'message' => $message] + $route;
    }

    /**
     * A dónde iría el correo: el SMTP configurado y el que usa de verdad el `Mailer`.
     *
     * @param Mailer $mailer
     * @return array{configured:string,used:string,retained:bool,route:string}
     */
    private static function route(Mailer $mailer): array
    {
        $mailConfig = new MailConfig;
        $host = $mailConfig->host();
        $port = $mailConfig->port();
        $configured = (is_string($host) ? $host : '') . ':' . (is_int($port) ? $port : '');
        $used = $mailer->Host . ':' . $mailer->Port;
        return [
            'configured' => $configured,
            'used' => $used,
            'retained' => MailDelivery::goesToSink(),
            'route' => sprintf(__(self::LANG_GROUP, 'Configurado: %s · usado: %s'), $configured, $used),
        ];
    }

    /**
     * El error tal cual lo dijo el servidor (o el socket), sin el eco de lo que se envió.
     *
     * @param SMTP $smtp
     * @return string
     */
    private static function serverError(SMTP $smtp): string
    {
        $error = $smtp->getError();
        $parts = array_filter([
            is_string($error['error'] ?? null) ? $error['error'] : '',
            is_string($error['detail'] ?? null) ? trim($error['detail']) : '',
        ], fn (string $part): bool => $part !== '');
        return $parts !== [] ? implode(': ', $parts) : __(self::LANG_GROUP, 'sin respuesta del servidor');
    }

}
