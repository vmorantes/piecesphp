<?php

/**
 * MailjetConfig.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Settings\ORM\SettingsModel;

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
     * La opción donde se guarda, cifrada con Sealed::PURPOSE_MAILJET_CONFIG (ADR 0061).
     */
    const CONFIG_NAME = 'mailjet';

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
    public function email(?string $value = null)
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
    public function name(?string $value = null)
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
    public function apiKey(?string $value = null)
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
    public function secretKey(?string $value = null)
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
    public function smtpHost(?string $value = null)
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
    public function smtpPort(?int $value = null)
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

        $mailConfig = get_config(self::CONFIG_NAME);

        if (is_string($mailConfig)) {
            $stored = $mailConfig;
            $decryptData = self::decrypt($stored);
            $uncompressData = $decryptData !== '' ? gzuncompress($decryptData) : false;
            $jsonDecodedData = is_string($uncompressData) ? json_decode($uncompressData, true) : null;
            if (is_array($jsonDecodedData)) {
                $mailConfig = $jsonDecodedData;
                self::sealLegacy($stored, $decryptData);
            } else {
                $mailConfig = [];
            }
        } elseif (!is_array($mailConfig)) {
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
        $mailConfig = new static;
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
        return Sealed::seal($value, Sealed::PURPOSE_MAILJET_CONFIG);
    }

    /**
     * Lo guardado en claro; '' si no se puede leer. Lo de antes de la 9 (ofuscado con la clase como clave, que es
     * pública) se sigue leyendo, y loadConfigutarion() lo vuelve a guardar cifrado.
     *
     * @param string $value
     * @return string
     */
    protected static function decrypt(string $value)
    {
        if (Sealed::isSealed($value)) {
            return Sealed::open($value, Sealed::PURPOSE_MAILJET_CONFIG) ?? '';
        }
        return BaseHashEncryption::deobfuscate($value, self::class);
    }

    /**
     * Lo de antes de la 9 se guarda cifrado una vez (ADR 0061 §11). Solo si lo leído es lo que hay en la base: una
     * prueba que fija la opción en memoria no escribe en ella.
     *
     * @param string $stored
     * @param string $compressed
     * @return void
     */
    private static function sealLegacy(string $stored, string $compressed): void
    {
        if (Sealed::isSealed($stored)) {
            return;
        }
        try {
            if (SettingsModel::getConfigValue(self::CONFIG_NAME) !== $stored) {
                return;
            }
            $sealed = self::encrypt($compressed);
            if (SettingsModel::setConfigValue(self::CONFIG_NAME, $sealed)) {
                set_config(self::CONFIG_NAME, $sealed);
            }
        } catch (\Throwable $exception) {
            log_soft_error('No se pudo volver a guardar cifrada la opción ' . self::CONFIG_NAME, ['error' => get_class($exception)]);
        }
    }

}
