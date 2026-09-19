<?php

/**
 * ApplicationCallsLang.php
 */

namespace ApplicationCalls;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * ApplicationCallsLang.
 *
 * @package     ApplicationCalls
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class ApplicationCallsLang extends LangInjector
{

    const LANG_GROUP = 'application-calls-lang';
    const LANG_GROUP_PUBLIC = 'application-calls-lang-public';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
        $injector = new LangInjector(__DIR__ . '/lang/lang-public', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP_PUBLIC);
    }

}
