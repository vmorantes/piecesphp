<?php

/**
 * final-configurations.php
 */

use App\Controller\BlackboardNewsController;
use PiecesPHP\Core\Config;
use PiecesPHP\LangInjector;

/**
 * Configuraciones adicionales.
 * Este script se ejecuta justo antes de comenzar el manejo de las rutas, es decir; en el punto final antes de iniciar la aplicación.
 */

//Idiomas
$langInjectors = [
    LANG_GROUP => new LangInjector(basepath('app/lang/public'), Config::get_allowed_langs()),
    BlackboardNewsController::LANG_GROUP => new LangInjector(basepath('app/lang/blackboard-news'), Config::get_allowed_langs()),
];

foreach ($langInjectors as $group => $injector) {
    $injector->injectGroup($group);
}
