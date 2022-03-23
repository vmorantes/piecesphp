<?php

/**
 * NewsletterLang.php
 */

namespace Newsletter;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * NewsletterLang.
 *
 * @package     Newsletter
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class NewsletterLang extends LangInjector
{

    const LANG_GROUP = 'newsletter-lang';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
