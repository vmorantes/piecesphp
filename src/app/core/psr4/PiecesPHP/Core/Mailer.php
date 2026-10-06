<?php

/**
 * Mailer.php
 */
namespace PiecesPHP\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PHPMailer\PHPMailer\SMTP;
use PiecesPHP\Core\ConfigHelpers\MailConfig;

/**
 * Mailer - Enviar mails.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @see <a target='blank' href='https://github.com/PHPMailer/PHPMailer'>\PHPMailer\PHPMailer\PHPMailer</a>
 */
class Mailer extends PHPMailer
{

    /**
     * Por qué el mensaje acabó en el buzón, si acabó ahí.
     *
     * @var string
     */
    protected $outboxReason = '';


    /**
     * Log
     *
     * @var array
     */
    protected $log = [];

    /**
     * @param bool $exceptions Establece si lanzará excepciones
     * @return void
     */
    public function __construct(bool $exceptions = true)
    {
        parent::__construct($exceptions);

        $this->CharSet = 'UTF-8';

        $mailConfig = new MailConfig;

        /**
         * @var array<string,int|string|bool|mixed[]>
         */
        $PHPMailerProperties = [
            'SMTPDebug' => $mailConfig->smtpDebug(),
            'Host' => $mailConfig->host(),
            'Port' => $mailConfig->port(),
            'SMTPAutoTLS' => $mailConfig->autoTls(),
            'SMTPSecure' => $mailConfig->protocol(),
            'SMTPOptions' => $mailConfig->smtpOptions(),
            'SMTPAuth' => $mailConfig->auth(),
            'Username' => $mailConfig->user(),
            'Password' => $mailConfig->password(),
        ];

        foreach ($PHPMailerProperties as $PHPMailerProperty => $PHPMailerPropertyValue) {
            $this->$PHPMailerProperty = $PHPMailerPropertyValue;
        }

        //EL DESVÍO VA AQUÍ, NO EN MailConfig: la vista de configuración enseña y guarda los valores REALES, y si
        //MailConfig devolviera los del servidor de pruebas, el primer guardado los machacaría (P52).
        if ($mailConfig->testModeActive()) {
            $testHost = $mailConfig->testHost();
            $testPort = $mailConfig->testPort();
            $this->Host = is_string($testHost) ? $testHost : '127.0.0.1';
            $this->Port = is_int($testPort) ? $testPort : 1025;
            $this->SMTPAuth = false;
            $this->Username = '';
            $this->Password = '';
            $this->SMTPSecure = '';
            $this->SMTPAutoTLS = false;
            $this->isSMTP();
        } elseif ($mailConfig->isSmtp()) {
            $this->isSMTP();
        }

        $this->Debugoutput = function ($str, $level) {

            if (!isset($this->log[$level])) {
                $this->log[$level] = [];
            }

            $this->log[$level][] = $str;

        };

    }

    /**
     * Devuelve el log
     *
     * @return array
     */
    public function log()
    {
        return $this->log;
    }

    /**
     * @inheritDoc
     */
    public function setFrom($address, $name = '', $uselessParam = true)
    {

        $domainsNotAllowedOtherFrom = [
            'yandex.com',
            'yandex.ru',
            'zoho.com',
        ];

        foreach ($domainsNotAllowedOtherFrom as $domain) {
            if (strpos($this->Host, $domain) !== false) {
                $address = $this->Username;
                $name = explode('@', $address)[0];
                break;
            }
        }

        return parent::setFrom($address, $name, $uselessParam);
    }

    /**
     * Envía, y con el correo RETENIDO nunca se rinde a la entrega por el sistema: si el sumidero no
     * responde, el mensaje se guarda en el buzón y el envío NO cuenta como fallo (ADR 0045 §1).
     *
     * @return bool
     * @throws \Throwable Si la entrega es real y PHPMailer lanza, se propaga como siempre.
     */
    public function send(): bool
    {
        $this->tagWithInstallation();
        $delivery = MailDelivery::resolved();

        if (!MailDelivery::goesToSink()) {
            try {
                $sent = (bool) parent::send();
            } catch (\Throwable $throwable) {
                $this->record($delivery, MailLogMapper::RESULT_FAILED, get_class($throwable) . ': ' . $throwable->getMessage());
                throw $throwable;
            }
            $this->record($delivery, $sent ? MailLogMapper::RESULT_DELIVERED : MailLogMapper::RESULT_FAILED, $sent ? null : 'send() devolvió false sin lanzar');
            return $sent;
        }

        try {
            if (parent::send()) {
                $this->record($delivery, MailLogMapper::RESULT_DELIVERED, null);
                return true;
            }
            $this->outboxReason = 'el sumidero no aceptó el mensaje';
        } catch (\Throwable $throwable) {
            $this->outboxReason = get_class($throwable) . ': ' . $throwable->getMessage();
        }

        $alBuzon = $this->saveToOutbox();
        $this->record(
            $delivery,
            $alBuzon ? MailLogMapper::RESULT_OUTBOX : MailLogMapper::RESULT_FAILED,
            $alBuzon ? $this->outboxReason : 'no se pudo escribir en el buzón: ' . $this->outboxReason
        );
        return $alBuzon;
    }

    /**
     * Anota el correo y su resultado, **con el cuerpo cifrado y sin los adjuntos** (ADR 0048), y sin
     * romper el envío: si el registro falla, el correo ya salió y eso es lo que importa.
     *
     * @param string $delivery
     * @param string $result
     * @param string|null $reason
     * @return void
     */
    protected function record(string $delivery, string $result, ?string $reason): void
    {
        $destinos = [];
        foreach (array_keys($this->getAllRecipientAddresses()) as $address) {
            $destinos[] = (string) $address;
        }
        //`Body` y no `AltBody`: en este punto es el HTML que asignó quien envía, sin tocar (medido el 2026-10-05).
        //RETORNO-IGNORADO: `record()` ya devuelve false en vez de lanzar; quien lo mire es la pantalla.
        MailLogMapper::record($destinos, (string) $this->Subject, $this->originOfSend(), $delivery, $result, $reason, is_string($this->Body) ? $this->Body : null);
    }

    /**
     * De dónde salió el envío, para que el registro diga QUÉ lo originó sin tocar los diez sitios que
     * envían: se saca de la traza, el primer marco que no es del correo.
     *
     * **Si ese marco es un *closure*, se nombra su archivo y su línea** en vez de su clase. PHP
     * atribuye un *closure* a la clase en cuyo ámbito se creó, y eso daba orígenes como
     * `PiecesPHP\Terminal\LoadFailures::{closure:...}`, que nombra al cargador y no a quien envía
     * (medido el 2026-10-03). **Y va solo el nombre del archivo, no su ruta**: esto se pinta en una
     * pantalla del panel y la disposición del servidor no tiene por qué salir ahí.
     *
     * @return string|null
     */
    protected function originOfSend(): ?string
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12) as $frame) {
            $class = is_array($frame) && isset($frame['class']) && is_string($frame['class']) ? $frame['class'] : '';
            if ($class === '' || $class === self::class || str_starts_with($class, 'PHPMailer')) {
                continue;
            }
            $method = is_array($frame) && is_string($frame['function'] ?? null) ? (string) $frame['function'] : '';
            //`{closure:/ruta/al/archivo.php:12}` es como PHP 8.4 nombra un closure: ahí está el
            //sitio exacto del envío, que es más preciso que cualquier nombre de clase.
            if (preg_match('/^\{closure:(.+):(\d+)\}$/', $method, $matches) === 1) {
                return self::cleanOrigin(basename($matches[1]) . ':' . $matches[2]);
            }
            return self::cleanOrigin($class . ($method !== '' ? "::{$method}" : ''));
        }
        return null;
    }

    /**
     * Deja el origen en algo que se pueda pintar. **El nombre de una clase anónima lleva un byte
     * NUL** (medido el 2026-10-03), y este valor va a una pantalla del panel: un carácter de
     * control no se pinta, y según dónde acabe puede partir el texto.
     *
     * @param string $origin
     * @return string
     */
    protected static function cleanOrigin(string $origin): string
    {
        $limpio = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $origin);
        if ($limpio === '') {
            //Si el patrón no pudo con el texto, se cae del lado seguro: fuera todo lo que no sea legible.
            $limpio = (string) preg_replace('/[^\P{C}]+/u', ' ', $origin);
        }
        return trim($limpio);
    }

    /**
     * Marca el mensaje con el nombre de la instalación. **Mailpit agrupa por `X-Tags`**: medido el
     * 2026-10-03 mandando un mensaje con `X-Tags` y otra cabecera propia, y solo `X-Tags` apareció
     * entre sus etiquetas. Sirve para que una Mailpit compartida entre clones siga siendo legible
     * (ADR 0043 §4).
     *
     * @return void
     */
    protected function tagWithInstallation(): void
    {
        $etiqueta = MailDelivery::installationTag();
        if ($etiqueta === '') {
            return;
        }
        try {
            $this->addCustomHeader('X-Tags', $etiqueta);
        } catch (\Throwable $throwable) {
            //Una etiqueta que no se puede poner no impide enviar el correo: se sigue sin ella.
            unset($throwable);
        }
    }

    /**
     * Guarda el mensaje como `.eml` en el buzón. Devuelve si pudo guardarlo: un buzón que no se
     * puede escribir SÍ es un fallo de envío, porque entonces el mensaje no está en ningún sitio.
     *
     * @return bool
     */
    protected function saveToOutbox(): bool
    {
        $directory = MailDelivery::outboxDirectory();
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return false;
        }

        try {
            //`preSend()` construye el MIME sin entregarlo: es lo que permite guardar un mensaje que
            //nunca salió. Si ya se construyó en el intento anterior, `getSentMIMEMessage()` lo tiene.
            $mime = $this->getSentMIMEMessage();
        } catch (\Throwable $throwable) {
            try {
                $mime = $this->preSend() ? $this->getSentMIMEMessage() : '';
            } catch (\Throwable $second) {
                $mime = '';
            }
        }

        if ($mime === '') {
            return false;
        }

        $name = date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(4)) . '.eml';
        $written = @file_put_contents(rtrim($directory, '/\\') . '/' . $name, $mime);
        return is_int($written) && $written > 0;
    }

    /**
     * @param bool $sendmail
     * @return static
     */
    public function asGoDaddy(bool $sendmail = false)
    {
        //Con el correo RETENIDO esta reserva no se aplica: que el sumidero no responda no habilita la
        //entrega por el sistema, y la reserva pasa a ser el buzón en disco (ADR 0045 §1 y §2).
        if (MailDelivery::goesToSink()) {
            return $this;
        }
        if ($sendmail) {
            $this->isSendmail();
        } else {
            $this->isMail();
        }
        $this->SMTPAuth = false;
        $this->SMTPAutoTLS = false;
        $this->Host = 'localhost';
        $this->Port = 25;
        return $this;
    }

    /**
     * @return bool
     */
    public function checkSettedSMTP()
    {
        $connect = true;
        try {
            $connect = $this->smtpConnect();
        } catch (\Throwable $th) {
            $connect = false;
        }
        return $connect;
    }

    /**
     * Si el SMTP de ESE host y ESE puerto contesta. Se le pasan explícitos a propósito.
     *
     * `checkSettedSMTP()` **no sirve para diagnosticar**: mira la configuración ya cargada, y el
     * constructor la desvía al sumidero cuando el correo está retenido. Medido el 2026-10-03 con
     * el SMTP configurado en `127.0.0.1:9` (cerrado) y el sumidero en `127.0.0.1:1025` (vivo):
     * `checkSettedSMTP()` devolvía `true`, porque había conectado al sumidero. Para los ocho
     * sitios que preguntan antes de enviar ese comportamiento es el correcto —comprueban el mismo
     * mailer con el que van a enviar—, y por eso ese método se queda como está.
     *
     * Comprueba que CONTESTA, no que las credenciales valgan: no autentica y no envía.
     *
     * @param string $host
     * @param int $port
     * @return bool
     */
    public static function probeSMTP(string $host, int $port): bool
    {
        if (trim($host) === '' || $port <= 0) {
            return false;
        }
        $smtp = new SMTP();
        try {
            $connected = (bool) $smtp->connect($host, $port);
        } catch (\Throwable $throwable) {
            return false;
        }
        if ($connected) {
            try {
                //Se despide y cuelga: una sonda que deja la conexión abierta consume un hueco del
                //servidor hasta que su tiempo de espera la tire.
                $smtp->quit();
            } catch (\Throwable $throwable) {
                unset($throwable);
            }
            $smtp->close();
        }
        return $connected;
    }

    /**
     * @param string $host
     * @param integer $port
     * @return bool
     */
    public static function checkSMTP(string $host, int $port)
    {
        $smtp = new SMTP();
        return $smtp->connect($host, $port);
    }
}
