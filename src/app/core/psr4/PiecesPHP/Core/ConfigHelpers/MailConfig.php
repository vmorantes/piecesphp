<?php

/**
 * MailConfig.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

/**
 * MailConfig
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class MailConfig
{

    /**
     * @var int
     */
    protected $smtpDebug;
    /**
     * @var bool
     */
    protected $isSmtp;
    /**
     * @var bool
     */
    protected $auth;
    /**
     * @var string
     */
    protected $host;
    /**
     * @var string
     */
    protected $user;
    /**
     * @var string
     */
    protected $password;
    /**
     * @var string
     */
    protected $protocol;
    /**
     * @var int
     */
    protected $port;
    /**
     * @var bool
     */
    protected $autoTls;
    /**
     * @var array
     */
    protected $smtpOptions;

    public function __construct()
    {
        $this->loadConfigutarion();
    }

    /**
     * @param int $value
     * @return int|static
     */
    public function smtpDebug(int $value = null)
    {

        if ($value !== null) {
            $this->smtpDebug = $value;
            return $this;
        } else {
            return $this->smtpDebug;
        }

    }

    /**
     * @param bool $value
     * @return bool|static
     */
    public function isSmtp(bool $value = null)
    {

        if ($value !== null) {
            $this->isSmtp = $value;
            return $this;
        } else {
            return $this->isSmtp;
        }

    }

    /**
     * @param bool $value
     * @return bool|static
     */
    public function auth(bool $value = null)
    {

        if ($value !== null) {
            $this->auth = $value;
            return $this;
        } else {
            return $this->auth;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function host(string $value = null)
    {

        if ($value !== null) {
            $this->host = $value;
            return $this;
        } else {
            return $this->host;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function user(string $value = null)
    {

        if ($value !== null) {
            $this->user = $value;
            return $this;
        } else {
            return $this->user;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function password(string $value = null)
    {

        if ($value !== null) {
            $this->password = $value;
            return $this;
        } else {
            return $this->password;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function protocol(string $value = null)
    {

        if ($value !== null) {
            $this->protocol = $value;
            return $this;
        } else {
            return $this->protocol;
        }

    }

    /**
     * @param int $value
     * @return int|static
     */
    public function port(int $value = null)
    {

        if ($value !== null) {
            $this->port = $value;
            return $this;
        } else {
            return $this->port;
        }

    }

    /**
     * @param bool $value
     * @return bool|static
     */
    public function autoTls(bool $value = null)
    {

        if ($value !== null) {
            $this->autoTls = $value;
            return $this;
        } else {
            return $this->autoTls;
        }

    }

    /**
     * @param array $value
     * @return array|static
     */
    public function smtpOptions(array $value = null)
    {

        if ($value !== null) {
            $this->smtpOptions = $value;
            return $this;
        } else {
            return $this->smtpOptions;
        }

    }

    /**
     * @return static
     */
    public function loadConfigutarion()
    {

        $mailConfig = get_config('mail');
        $mailConfig = $mailConfig instanceof \stdClass || is_array($mailConfig) ? (array) $mailConfig : [];

        $defaultConfig = [
            'smtp_debug' => 0,
            'is_smtp' => true,
            'auth' => true,
            'host' => 'localhost',
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

        $this->smtpDebug = $mailConfig['smtp_debug'];
        $this->isSmtp = $mailConfig['is_smtp'];
        $this->auth = $mailConfig['auth'];
        $this->host = $mailConfig['host'];
        $this->user = $mailConfig['user'];
        $this->password = $mailConfig['password'];
        $this->protocol = $mailConfig['protocol'];
        $this->port = $mailConfig['port'];
        $this->autoTls = $mailConfig['auto_tls'];
        $this->smtpOptions = $mailConfig['smtp_options'];

        return $this;

    }

}
