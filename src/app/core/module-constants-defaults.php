<?php
/**
 * module-constants-defaults.php
 */

//Carga DESPUÉS de config/constants.php: un constants.php anterior a la 10.54.0 no trae estas cuatro, y sin ellas el
//`const ENABLE` de su *Routes no se evalúa y cae el sitio entero. Lo que el proyecto haya definido no se toca.
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
