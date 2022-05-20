<?php

/**
 * MySpaceLang.php
 */

namespace MySpace;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * MySpaceLang.
 *
 * @package     MySpace
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class MySpaceLang extends LangInjector
{

    const LANG_GROUP = 'my-space-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
