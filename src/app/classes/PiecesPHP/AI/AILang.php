<?php

/**
 * AILang.php
 */

namespace PiecesPHP\AI;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * AILang - El grupo de traducción del centro de mando de la IA.
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class AILang extends LangInjector
{
    const LANG_GROUP = 'ai';

    /**
     * @var bool
     */
    private static $injected = false;

    /**
     * Una vez por petición: el módulo no tiene rutas propias, así que lo pide la puerta cuando lo necesita.
     *
     * @return void
     */
    public static function injectLang()
    {
        if (self::$injected) {
            return;
        }
        self::$injected = true;
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }
}
