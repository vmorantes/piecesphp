<?php

/**
 * APILang.php
 */

namespace API;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * APILang.
 *
 * @package     API
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class APILang extends LangInjector
{

    const LANG_GROUP = 'api-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
