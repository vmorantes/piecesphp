<?php

/**
 * ImagesRepositoryLang.php
 */

namespace ImagesRepository;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * ImagesRepositoryLang.
 *
 * @package     ImagesRepository
 * @author      Tejido Digital S.A.S.
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class ImagesRepositoryLang extends LangInjector
{

    const LANG_GROUP = 'images-repository-lang';

    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
