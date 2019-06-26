<?php

/**
 * SessionToken.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\BaseToken;

/**
 * SessionToken - Autenticación
 *
 * Manejar autenticaciones
 * @category    Autenticación
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class SessionToken
{
    const TOKEN_NAME = 'JWTAuth';

    const DEFAULT_MINIMUM_DATE_CREATED = '1990-01-01';

    /**
     * $minimumDateCreated
     *
     * @var \DateTime
     */
    private static $minimumDateCreated = null;

    /**
     * @param mixed $data Información que se almacenará en el token
     * @param string $key Llave
     * @param int $expire_time Duración en segundos
     * @return string El token generado
     */
    public static function generateToken($data, string $key = null, int $expire_time = null)
    {
        $time = time();

        if (is_null($expire_time)) {
            $expire_time = $time + 31 * 24 * 3600;
        } else {
            $expire_time += $time;
        }

        $token = BaseToken::setToken($data, $key, $time, $expire_time, true);

        return $token;
    }

    /**
     * isActiveSession
     *
     * @param string $token
     * @param string $key
     * @return bool
     */
    public static function isActiveSession(string $token, string $key = null)
    {
        $logged = BaseToken::check($token, $key);

        if (self::$minimumDateCreated === null) {
            self::$minimumDateCreated = new \DateTime(self::DEFAULT_MINIMUM_DATE_CREATED);
        }

        if ($logged !== true) {

            return false;

        } else if ($logged === true) {

            $dateCreatedToken = BaseToken::getCreated($token, $key);
            $dateCreatedToken = new \DateTime(date('Y-m-d H:i:s', $dateCreatedToken));

            if ($dateCreatedToken >= self::$minimumDateCreated) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    /**
     * getJWTReceived
     *
     * @return string
     */
    public static function getJWTReceived()
    {

        $JWT = '';

        $name_on_server = 'HTTP_' . strtoupper(self::TOKEN_NAME);

        if (isset($_SERVER[$name_on_server])) {

            $JWT = $_SERVER[$name_on_server];

        } elseif (isset($_COOKIE[self::TOKEN_NAME])) {

            $JWT = $_COOKIE[self::TOKEN_NAME];

        }

        return $JWT;
    }

    /**
     * setMinimumDateCreated
     *
     * @param \DateTime $minimumDateCreated
     * @return void
     */
    public static function setMinimumDateCreated(\DateTime $minimumDateCreated)
    {
        self::$minimumDateCreated = $minimumDateCreated;
    }

    //---- Preservación de compatibilidad
    public static function initSession($data, string $key = null, int $expire_time = null)
    {
        return self::generateToken($data, $key, $expire_time);
    }

}
