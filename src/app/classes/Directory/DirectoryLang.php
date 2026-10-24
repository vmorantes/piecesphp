<?php

/**
 * DirectoryLang.php
 */

namespace Directory;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * DirectoryLang.
 *
 * @package     Directory
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DirectoryLang extends LangInjector
{

    const LANG_GROUP = 'directory-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
