<?php

/**
 * MailjetConfig.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

use PiecesPHP\Core\BaseHashEncryption;

/**
 * MailjetConfig
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class MailjetConfig
{

    /**
     * @var string
     */
    protected $email;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $apiKey;
    /**
     * @var string
     */
    protected $secretKey;
    /**
     * @var string
     */
    protected $smtpHost;
    /**
     * @var int
     */
    protected $smtpPort;

    public function __construct()
    {
        $this->loadConfigutarion();

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function email(string $value = null)
    {
        $name = 'email';
        if ($value !== null) {
            $this->$name = $value;
            return $this;
        } else {
            return $this->$name;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function name(string $value = null)
    {
        $name = 'name';
        if ($value !== null) {
            $this->$name = $value;
            return $this;
        } else {
            return $this->$name;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function apiKey(string $value = null)
    {
        $name = 'apiKey';
        if ($value !== null) {
            $this->$name = $value;
            return $this;
        } else {
            return $this->$name;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function secretKey(string $value = null)
    {
        $name = 'secretKey';
        if ($value !== null) {
            $this->$name = $value;
            return $this;
        } else {
            return $this->$name;
        }

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function smtpHost(string $value = null)
    {
        $name = 'smtpHost';
        if ($value !== null) {
            $this->$name = $value;
            return $this;
        } else {
            return $this->$name;
        }

    }

    /**
     * @param int $value
     * @return int|static
     */
    public function smtpPort(int $value = null)
    {
        $name = 'smtpPort';
        if ($value !== null) {
            $this->$name = $value;
            return $this;
        } else {
            return $this->$name;
        }

    }

    /**
     * @return static
     */
    public function loadConfigutarion()
    {

        $mailConfig = get_config('mailjet');

        if (is_string($mailConfig)) {
            $decryptData = self::decrypt($mailConfig);
            $uncompressData = gzuncompress($decryptData);
            $jsonDecodedData = is_string($uncompressData) ? json_decode($uncompressData, true) : null;
            if (is_array($jsonDecodedData)) {
                $mailConfig = $jsonDecodedData;
            } else {
                $mailConfig = [];
            }
        } else {
            $mailConfig = [];
        }

        $defaultConfig = [
            'email' => 'correo@correo.com',
            'name' => 'Mailer',
            'apiKey' => 'API_KEY',
            'secretKey' => 'SECRET_KEY',
            'smtpHost' => 'in-v3.mailjet.com',
            'smtpPort' => 587,
        ];

        foreach ($defaultConfig as $nameConfig => $valueConfig) {

            if (!array_key_exists($nameConfig, $mailConfig)) {
                $mailConfig[$nameConfig] = $valueConfig;
            }

        }

        $this->email = $mailConfig['email'];
        $this->name = $mailConfig['name'];
        $this->apiKey = $mailConfig['apiKey'];
        $this->secretKey = $mailConfig['secretKey'];
        $this->smtpHost = $mailConfig['smtpHost'];
        $this->smtpPort = $mailConfig['smtpPort'];

        return $this;

    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];
        $data['email'] = $this->email;
        $data['name'] = $this->name;
        $data['apiKey'] = $this->apiKey;
        $data['secretKey'] = $this->secretKey;
        $data['smtpHost'] = $this->smtpHost;
        $data['smtpPort'] = $this->smtpPort;
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
        $mailConfig = new MailjetConfig;
        $data = [];
        $data['email'] = $mailConfig->email();
        $data['name'] = $mailConfig->name();
        $data['apiKey'] = $mailConfig->apiKey();
        $data['secretKey'] = $mailConfig->secretKey();
        $data['smtpHost'] = $mailConfig->smtpHost();
        $data['smtpPort'] = $mailConfig->smtpPort();
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
