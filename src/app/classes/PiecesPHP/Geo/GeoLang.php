<?php

/**
 * GeoLang.php
 */

namespace PiecesPHP\Geo;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * GeoLang.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GeoLang extends LangInjector
{

    const LANG_GROUP = 'geo-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
