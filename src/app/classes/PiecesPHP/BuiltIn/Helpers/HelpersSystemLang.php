<?php

/**
 * HelpersSystemLang.php
 */

namespace PiecesPHP\BuiltIn\Helpers;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * HelpersSystemLang.
 *
 * @package     PiecesPHP\BuiltIn\Helpers;
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class HelpersSystemLang extends LangInjector
{

    const LANG_GROUP = 'helpers-system-lang';

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
