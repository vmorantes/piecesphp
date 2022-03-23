<?php

/**
 * LogsLang.php
 */

namespace EventsLog;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * LogsLang.
 *
 * @package     Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class LogsLang extends LangInjector
{

    const LANG_GROUP = 'logs-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
