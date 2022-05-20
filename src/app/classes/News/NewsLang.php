<?php

/**
 * NewsLang.php
 */

namespace News;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * NewsLang.
 *
 * @package     News
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class NewsLang extends LangInjector
{

    const LANG_GROUP = 'news-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
