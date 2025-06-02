<?php

/**
 * LocationsLang.php
 */

namespace App\Locations;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * LocationsLang.
 *
 * @package     App\Locations
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class LocationsLang extends LangInjector
{

    const LANG_GROUP = LOCATIONS_LANG_GROUP;
    const LANG_GROUP_NAMES = LOCATIONS_LANG_GROUP . '-names';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $langs = Config::get_allowed_langs();
        $langs[] = 'default';
        $injector = new LangInjector(__DIR__ . '/lang', $langs);
        $injector->injectGroup(self::LANG_GROUP);
        $injector = new LangInjector(__DIR__ . '/lang/names', $langs);
        $injector->injectGroup(self::LANG_GROUP_NAMES);
    }

}
