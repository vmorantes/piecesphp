<?php

/**
 * InterestResearchAreasLang.php
 */

namespace InterestResearchAreas;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * InterestResearchAreasLang.
 *
 * @package     InterestResearchAreas
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class InterestResearchAreasLang extends LangInjector
{

    const LANG_GROUP = 'interest-research-areas-lang';
    const LANG_GROUP_PUBLIC = 'interest-research-areas-lang-public';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
        $injector = new LangInjector(__DIR__ . '/lang/lang-public', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP_PUBLIC);
    }

}
