<?php

/**
 * MailConfig.php
 */
namespace PiecesPHP\Core\ConfigHelpers;

use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Settings\ORM\SettingsModel;

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
     * La opción donde se guarda, cifrada con Sealed::PURPOSE_MAIL_CONFIG (ADR 0061).
     */
    const CONFIG_NAME = 'mail';

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
    /**
     * @var string
     */
    protected $testHost;
    /**
     * @var int
     */
    protected $testPort;

    public function __construct()
    {
        $this->loadConfigutarion();
    }

    /**
     * @param string $value
     * @return string|static
     */
    public function testHost(?string $value = null)
    {

        if ($value !== null) {
            $this->testHost = $value;
            return $this;
        } else {
            return $this->testHost;
        }

    }

    /**
     * @param int $value
     * @return int|static
     */
    public function testPort(?int $value = null)
    {

        if ($value !== null) {
            $this->testPort = $value;
            return $this;
        } else {
            return $this->testPort;
        }

    }

    /**
     * Si el correo va al sumidero. **Lo decide `MailDelivery` y nadie más** (ADR 0043 §1): hasta el
     * 2026-10-03 esto leía `test_mode`, y la ausencia de `environment.php` acababa enviando de verdad.
     *
     * @return bool
     */
    public function testModeActive(): bool
    {
        return MailDelivery::goesToSink();
    }

    /**
     * @param int $value
     * @return int|static
     */
    public function smtpDebug(?int $value = null)
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
    public function isSmtp(?bool $value = null)
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
    public function auth(?bool $value = null)
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
    public function host(?string $value = null)
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
    public function user(?string $value = null)
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
    public function password(?string $value = null)
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
    public function name(?string $value = null)
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
    public function protocol(?string $value = null)
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
    public function port(?int $value = null)
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
    public function autoTls(?bool $value = null)
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
    public function smtpOptions(?array $value = null)
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
        } else {
            $mailConfig = [];
        }

        $defaultConfig = [
            'smtp_debug' => 0,
            'is_smtp' => true,
            'auth' => true,
            'host' => 'localhost',
            'user' => 'correo@correo.com',
            'password' => '',
            'name' => '',
            'protocol' => 'ssl',
            'port' => '465',
            'auto_tls' => true,
            'smtp_options' => [],
            'test_host' => '127.0.0.1',
            'test_port' => 1025,
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
        $this->testHost = (string) $mailConfig['test_host'];
        $this->testPort = (int) $mailConfig['test_port'];

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
        $data['test_host'] = $this->testHost;
        $data['test_port'] = $this->testPort;
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

        $data['test_host'] = $mailConfig->testHost();
        $data['testHost'] = $mailConfig->testHost();

        $data['test_port'] = $mailConfig->testPort();
        $data['testPort'] = $mailConfig->testPort();

        return array_key_exists($name, $data) ? $data[$name] : null;

    }

    /**
     * @param string $value
     * @return string
     */
    protected static function encrypt(string $value)
    {
        return Sealed::seal($value, Sealed::PURPOSE_MAIL_CONFIG);
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
            return Sealed::open($value, Sealed::PURPOSE_MAIL_CONFIG) ?? '';
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
