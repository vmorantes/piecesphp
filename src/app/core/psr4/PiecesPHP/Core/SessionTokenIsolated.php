<?php

/**
 * SessionTokenIsolated.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\BaseToken;

/**
 * SessionTokenIsolated - Autenticación
 *
 * Para manjear sesiones utilitariamente como tokenización de vistas
 *
 * @category    Autenticación
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class SessionTokenIsolated
{
    const DEFAULT_MINIMUM_DATE_CREATED = '2024-12-13';
    /**
     * @var string
     */
    protected $tokenName = '';
    /**
     * @var string
     */
    protected $key = '';
    /**
     * @var \DateTime
     */
    private $minimumDateCreated = null;

    /**
     * @param string $tokenName
     * @param string|null $key
     */
    public function __construct(string $tokenName, string $key = null)
    {
        $this->tokenName = $tokenName;
        $this->key = $key !== null ? $key : Config::app_key();
    }

    /**
     * @param mixed $data Información que se almacenará en el token
     * @param int $duration Duración en minutos
     * @param bool $aud Validar token con ip
     * @return string El token generado
     */
    public function generateToken($data, int $duration = 60, bool $aud = false)
    {
        $key = $this->key;
        $time = time();
        $duration = $duration * 60 + $time;
        $token = BaseToken::setToken($data, $key, $time, $duration, $aud);
        return $token;
    }

    /**
     * @return bool
     */
    public function isActiveSession()
    {

        $token = $this->getJWTReceived();
        $key = $this->key;

        $logged = BaseToken::check($token, $key);

        if ($this->minimumDateCreated === null) {
            $this->minimumDateCreated = new \DateTime(self::DEFAULT_MINIMUM_DATE_CREATED);
        }

        if ($logged !== true) {

            return false;

        } else if ($logged === true) {

            $dateCreatedToken = BaseToken::getCreated($token, $key);
            $dateCreatedToken = new \DateTime(date('Y-m-d H:i:s', $dateCreatedToken));

            if ($dateCreatedToken >= $this->minimumDateCreated) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * @return mixed
     */
    public function getJWTData()
    {
        $token = $this->getJWTReceived();
        $key = $this->key;
        return BaseToken::getData($token, $key, null, false);
    }

    /**
     * @return string
     */
    public function getJWTReceived()
    {

        $JWT = '';
        $tokenName = $this->tokenName;

        $name_on_server = 'HTTP_' . strtoupper($tokenName);

        if (isset($_SERVER[$name_on_server])) {

            $JWT = $_SERVER[$name_on_server];

        } elseif (isset($_COOKIE[$tokenName])) {

            $JWT = $_COOKIE[$tokenName];

        }

        return $JWT;
    }

    /**
     * @param \DateTime $minimumDateCreated
     * @return void
     */
    public function setMinimumDateCreated(\DateTime $minimumDateCreated)
    {
        $this->minimumDateCreated = $minimumDateCreated;
    }

}
