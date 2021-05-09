<?php

/**
 * constants.php
 */

/**
 * Constantes globales
 * En este este archivo se puede añadir cualquier constante adicional.
 * Puede hacerse uso de todas las funciones del sistema.
 */

//Errores
define('LOG_ERRORS_PATH', app_basepath('logs'));
define('LOG_ERRORS_BACKUP_PATH', app_basepath('logs/olds'));

//Configuraciones
define('APP_CONFIGURATION_MODULE', true);

//Zona administrativa
define('ADMIN_PATH_VIEWS', 'panel');

//Importadores
define('IMPORTS_MODULE_ENABLED', false);

//Mensajería
define('MESSAGES_ENABLED', false);
define('MESSAGES_PATH_VIEWS', 'messages');
define('MESSAGES_PATH_STATICS', 'statics/features/messages');
define('MESSAGES_PATH_JS', MESSAGES_PATH_STATICS . '/js');
define('REFRESH_MESSAGES_STATUS', false);

//Tablero de noticias
define('BLACKBOARD_NEWS_ENABLED', false);
define('BLACKBOARD_NEWS_PATH_VIEWS', 'blackboard-news');
define('BLACKBOARD_NEWS_PATH_STATICS', 'statics/features/blackboard-news');
define('BLACKBOARD_NEWS_PATH_JS', BLACKBOARD_NEWS_PATH_STATICS . '/js');

//Módulo de imágenes integrado
define('PIECES_PHP_DYNAMIC_IMAGES_ENABLE', true);

//Ubicaciones
define('LOCATIONS_ENABLED', false);
define('LOCATIONS_LANG_GROUP', 'locationBackend');
define('LOCATIONS_PATH_JS', 'statics/features/locations/js');

//Temporizadores
define('ACTIVE_TIMER', false);

//Gestor de archivos
define('FILE_MANAGER_MODULE', true);

//Otras
define('ADMIN_AREA_PATH_JS', 'statics/admin-area/js');

//Traducciones
define('ADMIN_MENU_LANG_GROUP', 'sidebarAdminZone');
define('SUPPORT_FORM_ADMIN_LANG_GROUP', 'supportFormAdminZone');
define('CROPPER_ADAPTER_LANG_GROUP', 'cropper');
define('LOGIN_REPORT_LANG_GROUP', 'loginReport');
define('MAIL_TEMPLATES_LANG_GROUP', 'mailTemplates');
define('USER_LOGIN_LANG_GROUP', 'userLogin');
define('LANG_GROUP', 'public');
