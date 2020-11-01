<?php

/**
 * MailjetConfig.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

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
     * @param int $value
     * @return string|static
     */
    public function email(int $value = null)
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
     * @param int $value
     * @return string|static
     */
    public function name(int $value = null)
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
     * @param int $value
     * @return string|static
     */
    public function apiKey(int $value = null)
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
     * @param int $value
     * @return string|static
     */
    public function secretKey(int $value = null)
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
     * @param int $value
     * @return string|static
     */
    public function smtpHost(int $value = null)
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
        $mailConfig = $mailConfig instanceof \stdClass || is_array($mailConfig) ? (array) $mailConfig : [];

        $defaultConfig = [
            'email' => 'correo@correo.com',
            'name' => 'Mailer',
            'apiKey' => 'API_KEY',
            'secretKey' => 'SECRET_KEY',
            'smptHost' => 'in-v3.mailjet.com',
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
        $this->smptHost = $mailConfig['smptHost'];
        $this->smtpPort = $mailConfig['smtpPort'];

        return $this;

    }

}
