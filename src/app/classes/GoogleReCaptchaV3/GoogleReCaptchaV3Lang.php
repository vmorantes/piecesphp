<?php

/**
 * GoogleReCaptchaV3Lang.php
 */

namespace GoogleReCaptchaV3;

use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * GoogleReCaptchaV3Lang.
 *
 * @package     GoogleReCaptchaV3
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class GoogleReCaptchaV3Lang extends LangInjector
{

    const LANG_GROUP = 'app-google-recaptcha-v3-lang';

    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }

}
