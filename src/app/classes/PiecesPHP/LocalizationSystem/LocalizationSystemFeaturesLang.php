<?php

/**
 * LocalizationSystemFeaturesLang.php
 */

namespace PiecesPHP\LocalizationSystem;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * LocalizationSystemFeaturesLang.
 *
 * @package     PiecesPHP\LocalizationSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class LocalizationSystemFeaturesLang extends LangInjector
{

    const LANG_GROUP = 'localization-system-features-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $langs = Config::get_allowed_langs();
        $langs[] = 'default'; //Para añadir por defecto
        $injector = new LangInjector(__DIR__ . '/lang', $langs);
        $injector->injectGroup(self::LANG_GROUP);
    }

}
