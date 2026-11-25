<?php
/**
 * module-constants-defaults.php
 */

//Carga DESPUÉS de config/constants.php: un constants.php anterior a la 10.54.0 no trae las cuatro primeras (ni uno anterior
//a la 10.65.0 la última), y sin ellas su *Routes no se evalúa y cae el sitio entero. Lo que el proyecto definió no se toca.
if (!defined('DIRECTORY_MODULE')) {
    define('DIRECTORY_MODULE', true);
}
if (!defined('GEO_MODULE')) {
    define('GEO_MODULE', true);
}
if (!defined('NOTIFICATIONS_MODULE')) {
    define('NOTIFICATIONS_MODULE', true);
}
if (!defined('MCP_MODULE')) {
    define('MCP_MODULE', true);
}
if (!defined('DATA_IMPORT_EXPORT_USERS')) {
    define('DATA_IMPORT_EXPORT_USERS', true);
}
