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

//Blog integrado
define('PIECES_PHP_BLOG_ENABLED', false);

//Tienda integrada
define('PIECES_PHP_SHOP_ENABLE', false);

//Módulo de imágenes integrado
define('PIECES_PHP_DYNAMIC_IMAGES_ENABLE', false);

//Ubicaciones
define('LOCATIONS_ENABLED', false);
define('LOCATIONS_PATH_JS', 'statics/features/locations/js');

//Temporizadores
define('ACTIVE_TIMER', false);

//Otras
define('ADMIN_AREA_PATH_JS', 'statics/admin-area/js');
