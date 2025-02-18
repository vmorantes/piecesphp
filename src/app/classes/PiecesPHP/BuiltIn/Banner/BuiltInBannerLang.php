<?php

/**
 * BuiltInBannerLang.php
 */

namespace PiecesPHP\BuiltIn\Banner;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * BuiltInBannerLang.
 *
 * @package     PiecesPHP\BuiltIn\Banner
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class BuiltInBannerLang extends LangInjector
{

    const LANG_GROUP = 'built-in-banner-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
