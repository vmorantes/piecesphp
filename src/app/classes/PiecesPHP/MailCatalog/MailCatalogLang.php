<?php

/**
 * MailCatalogLang.php
 */

namespace PiecesPHP\MailCatalog;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * MailCatalogLang - Las traducciones del catálogo de correos.
 *
 * @package     PiecesPHP\MailCatalog
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailCatalogLang extends LangInjector
{
    const LANG_GROUP = 'mail-catalog';

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }
}
