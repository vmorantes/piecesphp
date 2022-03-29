<?php

/**
 * MailConfig.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

use PiecesPHP\Core\BaseHashEncryption;

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
    protected $name;
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
    public function name(string $value = null)
    {

        if ($value !== null) {
            $this->name = $value;
            return $this;
        } else {
            return $this->name;
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

        if (is_string($mailConfig)) {
            $decryptData = self::decrypt($mailConfig);
            $uncompressData = gzuncompress($decryptData);
            $jsonDecodedData = is_string($uncompressData) ? json_decode($uncompressData) : null;
            if (is_array($jsonDecodedData)) {
                $mailConfig = $jsonDecodedData;
            } else {
                $mailConfig = [];
            }
        } else {
            $mailConfig = [];
        }

        $defaultConfig = [
            'smtp_debug' => 0,
            'is_smtp' => true,
            'auth' => true,
            'host' => 'localhost',
            'user' => 'correo@correo.com',
            'password' => '123456',
            'name' => '',
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
        $this->name = mb_strlen($mailConfig['name']) > 0 ? $mailConfig['name'] : $mailConfig['user'];
        $this->protocol = $mailConfig['protocol'];
        $this->port = $mailConfig['port'];
        $this->autoTls = $mailConfig['auto_tls'];
        $this->smtpOptions = $mailConfig['smtp_options'];

        return $this;

    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];
        $data['smtp_debug'] = $this->smtpDebug;
        $data['is_smtp'] = $this->isSmtp;
        $data['auth'] = $this->auth;
        $data['host'] = $this->host;
        $data['user'] = $this->user;
        $data['password'] = $this->password;
        $data['name'] = $this->name;
        $data['protocol'] = $this->protocol;
        $data['port'] = $this->port;
        $data['auto_tls'] = $this->autoTls;
        $data['smtp_options'] = $this->smtpOptions;
        return $data;
    }

    /**
     * @return string
     * @throws \Exception Si la información falla al intentar encriptarse
     */
    public function toSave()
    {
        $jsonEncodedData = json_encode($this->toArray());
        $compressData = is_string($jsonEncodedData) ? gzcompress($jsonEncodedData) : null;
        if (!is_string($compressData)) {
            throw new \Exception('La información no pude ser encriptada.');
        }
        return self::encrypt($compressData);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public static function getValue(string $name)
    {

        $mailConfig = new MailConfig;

        $data = [];

        $data['smtp_debug'] = $mailConfig->smtpDebug();
        $data['smtpDebug'] = $mailConfig->smtpDebug();

        $data['is_smtp'] = $mailConfig->isSmtp();
        $data['isSmtp'] = $mailConfig->isSmtp();

        $data['auth'] = $mailConfig->auth();

        $data['host'] = $mailConfig->host();

        $data['user'] = $mailConfig->user();

        $data['password'] = $mailConfig->password();

        $data['name'] = $mailConfig->name();

        $data['protocol'] = $mailConfig->protocol();

        $data['port'] = $mailConfig->port();

        $data['auto_tls'] = $mailConfig->autoTls();
        $data['autoTls'] = $mailConfig->autoTls();

        $data['smtp_options'] = $mailConfig->smtpOptions();
        $data['smtpOptions'] = $mailConfig->smtpOptions();

        return array_key_exists($name, $data) ? $data[$name] : null;

    }

    /**
     * @param string $value
     * @return string
     */
    protected static function encrypt(string $value)
    {
        return BaseHashEncryption::encrypt($value, self::class);
    }

    /**
     * @param string $value
     * @return string
     */
    protected static function decrypt(string $value)
    {
        return BaseHashEncryption::decrypt($value, self::class);
    }

}
