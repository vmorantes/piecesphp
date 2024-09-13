<?php

/**
 * UserSystemFeaturesLang.php
 */

namespace PiecesPHP\UserSystem;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * UserSystemFeaturesLang.
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class UserSystemFeaturesLang extends LangInjector
{

    const LANG_GROUP = 'user-system-features-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
