<?php

/**
 * final-configurations.php
 */

use App\Controller\PublicAreaController;
use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * Configuraciones adicionales.
 * Este script se ejecuta justo antes de comenzar el manejo de las rutas, es decir; en el punto final antes de iniciar la aplicación.
 */

//Idiomas
$langsOptions = array_merge(Config::get_allowed_langs(), ['default']);
$langInjectors = [
    LANG_GROUP => new LangInjector(basepath('app/lang/public'), $langsOptions),
    PublicAreaController::LANG_REPLACE_GENERIC_TITLES => new LangInjector(basepath('app/lang/replace-generic-titles'), $langsOptions),
];

foreach ($langInjectors as $group => $injector) {
    $injector->injectGroup($group);
}
