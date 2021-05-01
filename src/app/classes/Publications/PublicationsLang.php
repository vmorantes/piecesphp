<?php

/**
 * PublicationsLang.php
 */

namespace Publications;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * PublicationsLang.
 *
 * @package     Publications
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class PublicationsLang extends LangInjector
{

    const LANG_GROUP = 'publications-lang';

    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
