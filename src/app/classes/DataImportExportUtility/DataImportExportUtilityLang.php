<?php

/**
 * DataImportExportUtilityLang.php
 */

namespace DataImportExportUtility;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * DataImportExportUtilityLang.
 *
 * @package     DataImportExportUtility
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class DataImportExportUtilityLang extends LangInjector
{

    const LANG_GROUP = 'DataImportExportUtility-lang';

    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
