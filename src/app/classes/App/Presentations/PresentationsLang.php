<?php

/**
 * PresentationsLang.php
 */

namespace App\Presentations;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * PresentationsLang.
 *
 * @package     App\Presentations
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class PresentationsLang extends LangInjector
{

    const LANG_GROUP = 'app-presentations-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
