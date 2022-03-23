<?php

/**
 * PersonsLang.php
 */

namespace Persons;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * PersonsLang.
 *
 * @package     Persons
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class PersonsLang extends LangInjector
{

    const LANG_GROUP = 'persons-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
