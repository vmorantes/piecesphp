<?php

/**
 * DocumentsLang.php
 */

namespace Documents;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * DocumentsLang.
 *
 * @package     Documents
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DocumentsLang extends LangInjector
{

    const LANG_GROUP = 'document-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
