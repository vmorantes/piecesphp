<?php

/**
 * OrganizationsLang.php
 */

namespace Organizations;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * OrganizationsLang.
 *
 * @package     Organizations
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class OrganizationsLang extends LangInjector
{

    const LANG_GROUP = 'organizations-lang';

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
