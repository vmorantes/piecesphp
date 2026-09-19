<?php

/**
 * DocumentTypesLang.php
 */

namespace Forms\DocumentTypes;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * DocumentTypesLang.
 *
 * @package     Forms\DocumentTypes
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DocumentTypesLang extends LangInjector
{

    const LANG_GROUP = 'document-types-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
