<?php

/**
 * GeoJsonManagerLang.php
 */

namespace GeoJSONManager;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * GeoJsonManagerLang.
 *
 * @package     GeoJSONManager
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class GeoJsonManagerLang extends LangInjector
{

    const LANG_GROUP = 'geojson-manager-lang';
    const LANG_GROUP_PUBLIC = 'geojson-manager-lang-public';

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
