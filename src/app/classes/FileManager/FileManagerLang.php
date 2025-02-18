<?php

/**
 * FileManagerLang.php
 */

namespace FileManager;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * FileManagerLang.
 *
 * @package     FileManager
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class FileManagerLang extends LangInjector
{

    const LANG_GROUP = 'filemanager-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $allowedLangs = Config::get_allowed_langs();
        $allowedLangs[] = 'default';
        $injector = new LangInjector(__DIR__ . '/lang', $allowedLangs);
        $injector->injectGroup(self::LANG_GROUP);
    }

}
