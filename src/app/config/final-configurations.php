<?php

/**
 * @pcsphp-config framework
 * Qué conviene editar aquí: nada: carga los idiomas del núcleo y las extensiones, las del framework (core/extensions/) y las tuyas (config/extensions/).
 */

/**
 * final-configurations.php
 */
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use App\Controller\PublicAreaController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;
use PiecesPHP\LangInjector;
use PiecesPHP\UserSystem\UserDataPackage;

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
    AdminPanelController::ADMIN_LANG_GROUP => new LangInjector(basepath('app/lang/adminZone'), $langsOptions),
    MAILING_GENERAL_LANG_GROUP => new LangInjector(basepath('app/lang/mailingGeneral'), $langsOptions),
    UserDataPackage::LANG_GROUP => new LangInjector(basepath('app/lang/usersModule'), $langsOptions),
    LOCATIONS_LANG_GROUP => new LangInjector(basepath('app/lang/locationBackend'), $langsOptions),
    FileValidator::LANG_GROUP => new LangInjector(basepath('app/lang/FileValidator'), $langsOptions),
    LOGIN_REPORT_LANG_GROUP => new LangInjector(basepath('app/lang/loginReport'), $langsOptions),
    'about-framework' => new LangInjector(basepath('app/lang/about-framework'), $langsOptions),
];

foreach ($langInjectors as $group => $injector) {
    $injector->injectGroup($group);
}

//Extensiones: las del framework (core/extensions) y después las del clon (config/extensions). La carpeta vieja
//final-configurations-includes se sigue cargando si existe, y su aviso del sistema pide moverla.
foreach ([basepath('app/core/extensions'), basepath('app/config/extensions'), basepath('app/config/final-configurations-includes')] as $extensionsPath) {
    if (!is_dir($extensionsPath)) {
        continue;
    }
    $extensionsDirectory = new DirectoryObject($extensionsPath);
    $extensionsDirectory->process();
    foreach ($extensionsDirectory->getFiles() as $extensionFile) {
        if ($extensionFile->getExists() && mb_strtolower($extensionFile->getExtension()) == 'php') {
            include_once $extensionFile->getPath();
        }
    }
}

//Indica si la aplicación está en local o en producción
add_to_front_configurations('isLH', is_local());

//El nombre de la sesión, para que el JavaScript NO lo lleve escrito: su literal es solo el último recurso.
add_to_front_configurations('sessionTokenName', \PiecesPHP\Core\SessionToken::tokenName());
