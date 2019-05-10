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
     * addMessage
     *
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
     * getMessages
     *
     * @return array
     */
    public static function getMessages()
    {
        if (self::sessionExists() && count($_SESSION[self::NAME_SESSION]) > 0) {
            $messages = $_SESSION[self::NAME_SESSION];
            unset($_SESSION[self::NAME_SESSION]);
            return $messages;
        } else {
            return [];
        }
    }

    /**
     * hasMessages
     *
     * @return bool
     */
    public static function hasMessages()
    {
        if (self::sessionExists() && count($_SESSION[self::NAME_SESSION]) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * initSession
     *
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
     * sessionExists
     *
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
