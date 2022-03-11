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
     * @return void
     */
    public static function addMessage(string $name, $value)
    {
        if (!self::sessionExists()) {
            self::initSession();
        }
        $_SESSION[self::NAME_SESSION][$name] = $value;
    }

    /**
     * @return array
     */
    public static function getMessages()
    {
        if (self::sessionExists() && !empty($_SESSION[self::NAME_SESSION])) {
            $messages = $_SESSION[self::NAME_SESSION];
            unset($_SESSION[self::NAME_SESSION]);
            return $messages;
        } else {
            return [];
        }
    }

    /**
     * @return bool
     */
    public static function hasMessages()
    {
        if (self::sessionExists() && !empty($_SESSION[self::NAME_SESSION])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return void
     */
    private static function initSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::NAME_SESSION] = [];
    }

    /**
     * @return bool
     */
    private static function sessionExists()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION[self::NAME_SESSION]) ? true : false;
    }
}
