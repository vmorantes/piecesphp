<?php

/**
 * GoogleReCaptchaV3Routes.php
 */

namespace GoogleReCaptchaV3;

use GoogleReCaptchaV3\Controllers\GoogleReCaptchaV3Controller;
use PiecesPHP\Core\RouteGroup;

/**
 * GoogleReCaptchaV3Routes.
 *
 * @package     GoogleReCaptchaV3
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class GoogleReCaptchaV3Routes
{

    const ENABLE = true;

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        if (self::ENABLE) {

            $group = GoogleReCaptchaV3Controller::routes($group);

            GoogleReCaptchaV3Lang::injectLang();

        }

        return $group;
    }

}
