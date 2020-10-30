<?php

/**
 * Mailer.php
 */
namespace PiecesPHP\Core;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Mailer - Enviar mails.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @extends <a target='blank' href='https://github.com/PHPMailer/PHPMailer'>\PHPMailer\PHPMailer\PHPMailer</a>
 */
class Mailer extends PHPMailer
{

    /**
     * Log
     *
     * @var array
     */
    protected $log = [];

    /**
     * __construct
     *
     * Constructor
     *
     * @param bool $exceptions Establece si lanzará excepciones
     * @return void
     */
    public function __construct(bool $exceptions = true)
    {
        parent::__construct($exceptions);

        $mailConfig = get_config('mail');
        $mailConfig = $mailConfig instanceof \stdClass || is_array($mailConfig) ? (array) $mailConfig : [];

        $defaultConfig = [
            'smtp_debug' => SMTP::DEBUG_OFF,
            'is_smtp' => true,
            'host' => 'smtp.host.com',
            'user' => 'correo@correo.com',
            'password' => '123456',
            'protocol' => 'ssl',
            'port' => '465',
            'auto_tls' => true,
            'smtp_options' => [],
        ];

        foreach ($defaultConfig as $nameConfig => $valueConfig) {

            if (!array_key_exists($nameConfig, $mailConfig)) {
                $mailConfig[$nameConfig] = $valueConfig;
            }

        }

        $this->SMTPDebug = $mailConfig['smtp_debug'];
        $this->Host = $mailConfig['host'];
        $this->Port = $mailConfig['port'];
        $this->SMTPAutoTLS = $mailConfig['auto_tls'];

        if ($mailConfig['protocol'] !== false) {
            $this->SMTPSecure = $mailConfig['protocol'];
        }

        if (is_array($mailConfig['smtp_options']) && count($mailConfig['smtp_options']) > 0) {
            $this->SMTPOptions = $mailConfig['smtp_options'];
        }

        if ($mailConfig['auth'] === true) {
            $this->SMTPAuth = $mailConfig['auth'];
            $this->Username = $mailConfig['user'];
            $this->Password = $mailConfig['password'];
        }

        if ($mailConfig['is_smtp'] === true) {

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
     * setMessageInformation
     *
     * @param string $remitentMail
     * @param string $remitentName
     * @param string $recipientMail
     * @param string $recipientName
     * @param string $subject
     * @param string $body
     * @param string $altBody
     * @param bool $utf_decode
     * @return void
     */
    public function setMessageInformation(string $remitentMail, string $remitentName, string $recipientMail, string $recipientName, string $subject, string $body, string $altBody = '', bool $utf_decode = true)
    {
        if ($utf_decode) {
            $remitentName = utf8_decode($remitentName);
            $recipientName = utf8_decode($recipientName);
            $subject = utf8_decode($subject);
            $body = utf8_decode($body);
            $altBody = utf8_decode($altBody);
        }

        $this->setFrom($remitentMail, $remitentName);
        $this->addAddress($recipientMail, $recipientName);
        $this->isHTML(true);
        $this->Subject = $subject;
        $this->Body = $body;
        $this->AltBody = $altBody;
    }
}
