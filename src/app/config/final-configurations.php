<?php

/**
 * final-configurations.php
 */
use App\Controller\AdminPanelController;
use App\Controller\PublicAreaController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
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
    ADMIN_MENU_LANG_GROUP => new LangInjector(basepath('app/lang/sidebarAdminZone'), $langsOptions),
    AdminPanelController::LANG_GROUP => new LangInjector(basepath('app/lang/adminZone'), $langsOptions),
];

foreach ($langInjectors as $group => $injector) {
    $injector->injectGroup($group);
}

//Configuraciones adicionales en archivos independientes. Se incluyen todos los archivos .php dentro de ./final-configurations-includes

$finalConfigurationsIncludesDirectory = new DirectoryObject(basepath('app/config/final-configurations-includes'));
$finalConfigurationsIncludesDirectory->process();
$finalConfigurationsIncludesFiles = $finalConfigurationsIncludesDirectory->getFiles();

if (!empty($finalConfigurationsIncludesFiles)) {
    foreach ($finalConfigurationsIncludesFiles as $finalConfigurationsIncludesFile) {
        if ($finalConfigurationsIncludesFile->getExists()) {
            if (mb_strtolower($finalConfigurationsIncludesFile->getExtension()) == 'php') {
                include_once $finalConfigurationsIncludesFile->getPath();
            }
        }
    }
}
