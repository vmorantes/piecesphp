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
 * @version     v.1
 * @copyright   Copyright (c) 2018
 * @extends <a target='blank' href='https://github.com/PHPMailer/PHPMailer'>\PHPMailer\PHPMailer\PHPMailer</a>
 * @use \PHPMailer\PHPMailer\Exception
 * @info No funciona como módulo independiente, exclusivo de una aplicación PiecesPHP completa
 */
class Mailer extends PHPMailer
{

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

        $mail_config = get_config('mail');

        if (!isset($mail_config['smtp_debug'])) {
            $mail_config['smtp_debug'] = SMTP::DEBUG_OFF;
        }
        if (!isset($mail_config['is_smtp'])) {
            $mail_config['is_smtp'] = true;
        }
        if (!isset($mail_config['host'])) {
            $mail_config['host'] = 'smtp.host.com';
        }
        if (!isset($mail_config['auth'])) {
            $mail_config['auth'] = true;
        }
        if (!isset($mail_config['user'])) {
            $mail_config['user'] = 'correo@correo.com';
        }
        if (!isset($mail_config['password'])) {
            $mail_config['password'] = '123456';
        }
        if (!isset($mail_config['protocol'])) {
            $mail_config['protocol'] = 'ssl';
        }
        if (!isset($mail_config['port'])) {
            $mail_config['port'] = '465';
        }

        $this->SMTPDebug = $mail_config['smtp_debug'];

        if ($mail_config['is_smtp'] === true) {
            $this->isSMTP();
            $this->Host = $mail_config['host'];
            if ($mail_config['auth'] === true) {
                $this->SMTPAuth = $mail_config['auth'];
                $this->Username = $mail_config['user'];
                $this->Password = $mail_config['password'];
            }
            $this->SMTPSecure = $mail_config['protocol'];
            $this->Port = $mail_config['port'];
        }
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
