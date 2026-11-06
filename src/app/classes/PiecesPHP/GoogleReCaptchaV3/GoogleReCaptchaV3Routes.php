<?php

/**
 * GoogleReCaptchaV3Routes.php
 */

namespace PiecesPHP\GoogleReCaptchaV3;

use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\GoogleReCaptchaV3\Controllers\GoogleReCaptchaV3Controller;

/**
 * GoogleReCaptchaV3Routes.
 *
 * @package     PiecesPHP\GoogleReCaptchaV3
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
        //La opción que guarda (ADR 0055), con el nombre que le da GoogleReCaptchaV3Controller; fuera del if, porque la
        //fila se lee aunque el módulo esté apagado.
        ConfigOptions::declare(GoogleReCaptchaV3Controller::CONFIG_NAME, GoogleReCaptchaV3Controller::class, ConfigOptions::SHAPE_JSON);
        if (self::ENABLE) {

            $group = GoogleReCaptchaV3Controller::routes($group);

            GoogleReCaptchaV3Lang::injectLang();

        }

        return $group;
    }

}
