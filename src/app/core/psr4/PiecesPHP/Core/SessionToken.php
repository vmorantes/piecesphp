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
 * @category	Autenticación
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class SessionToken
{
    const SESSION_NAME = 'pcs_php_auth_token';

    /**
     * Inicia la cookie self::SESSION_NAME con un token.
     * @param string $data Información que se almacenará en el token
     * @param string $key Llave
     * @param int $expire_time Duración en segundos
     * @return string
     * Devuelve el token generado
     * @use self::setToken
     */
    public static function initSession($data, $key = null, $expire_time = null)
    {
        if (is_null($expire_time)) {
            $time = time();
            $expire_time = $time + 31 * 24 * 3600;
            $token = BaseToken::setToken($data, $key, $time, $expire_time);
        } else {
            $time = time();
            $expire_time += $time;
            $token = BaseToken::setToken($data, $key, $time, $expire_time);
        }
        setcookie(self::SESSION_NAME, $token, 0, '/');
        return $token;
    }

    /**
     * Finaliza la cookie self::SESSION_NAME.
     * @return void
     */
    public static function finishSession()
    {
        if (isset($_COOKIE[self::SESSION_NAME])) {
            unset($_COOKIE[self::SESSION_NAME]);
            setcookie(self::SESSION_NAME, '', time() - 1, '/');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Verifica si la cookie self::SESSION_NAME está activa.
     * @return boolean true si esta activa, false si no
     * @use PiecesPHP\Core\BaseToken::check
     * @use self::finishSession
     */
    public static function isActiveSession(string $key = null)
    {
        if (isset($_COOKIE[self::SESSION_NAME])) {
            $token = $_COOKIE[self::SESSION_NAME];
            $logged = BaseToken::check($token, $key);
            if ($logged !== true) {
                self::finishSession();
                return false;
            } else if ($logged === true) {
                return true;
            }
        } else {
            return false;
        }
    }

    /**
     * Obtiene la cookie self::SESSION_NAME.
     *
     *
     * @param string $key La llave
     * @return mixed|boolean Devuelve la cookie o false si no hay sesión activa
     * @use self::isActiveSession
     */
    public static function getSession(string $key = null)
    {
        if (self::isActiveSession($key)) {
            return $_COOKIE[self::SESSION_NAME];
        } else {
            return false;
        }
    }
}
