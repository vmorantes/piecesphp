<?php

/**
 * ComponentProviderLang.php
 */

namespace Components;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * ComponentProviderLang.
 *
 * @package     Components
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class ComponentProviderLang extends LangInjector
{

    const LANG_GROUP = 'components-provider-lang';

    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
