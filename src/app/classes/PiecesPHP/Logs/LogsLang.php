<?php

/**
 * LogsLang.php
 */

namespace PiecesPHP\Logs;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * LogsLang.
 *
 * @package     PiecesPHP\Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class LogsLang extends LangInjector
{

    /**
     * No es 'logs-lang': ese es el del registro de acciones (EventsLog).
     */
    const LANG_GROUP = 'logs-viewer';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
