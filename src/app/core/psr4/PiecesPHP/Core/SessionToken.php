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
     * @param mixed string
     * @return bool
     */
    public static function isActiveSession(string $token, string $key = null)
    {
        $logged = BaseToken::check($token, $key);

        if ($logged !== true) {
            return false;
        } else if ($logged === true) {
            return true;
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

    //---- Preservación de compatibilidad
    public static function initSession($data, string $key = null, int $expire_time = null)
    {
        return self::generateToken($data, $key, $expire_time);
    }

}
