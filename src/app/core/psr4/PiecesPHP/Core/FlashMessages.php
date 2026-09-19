<?php
/**
 * FlashMessages.php
 */
namespace PiecesPHP\Core;

/**
 * FlashMessages - Mensajes entre sesiones.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class FlashMessages
{
    const NAME_SESSION = 'pcs_php_flash_messages';
    const MESSAGE_NOT_EXISTS = 'MESSAGE_NOT_EXISTS';

    /**
     * @param string $name Nombre del mensaje
     * @param mixed $value Valor del mensaje
     * @param ?string $context Categoría de sesión
     * @return void
     */
    public static function addMessage(string $name, $value, ?string $context = null)
    {
        $context = $context !== null ? $context : self::NAME_SESSION;
        if (!self::sessionExists($context)) {
            self::initSession($context);
        }
        $_SESSION[$context][$name] = $value;
    }

    /**
     * @param ?string $context Categoría de sesión
     * @return array
     */
    public static function getMessages(?string $context = null)
    {
        $context = $context !== null ? $context : self::NAME_SESSION;
        if (self::sessionExists($context) && !empty($_SESSION[$context])) {
            $messages = $_SESSION[$context];
            unset($_SESSION[$context]);
            return $messages;
        } else {
            return [];
        }
    }

    /**
     * @param ?string $context Categoría de sesión
     * @return bool
     */
    public static function hasMessages(?string $context = null)
    {
        $context = $context !== null ? $context : self::NAME_SESSION;
        if (self::sessionExists($context) && !empty($_SESSION[$context])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param ?string $context Categoría de sesión
     * @return void
     */
    public static function initSession(?string $context = null)
    {
        $context = $context !== null ? $context : self::NAME_SESSION;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$context] = [];
    }

    /**
     * @param ?string $context Categoría de sesión
     * @return bool
     */
    public static function sessionExists(?string $context = null)
    {
        $context = $context !== null ? $context : self::NAME_SESSION;
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION[$context]) ? true : false;
    }
}
