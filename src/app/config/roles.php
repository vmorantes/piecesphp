<?php
//========================================================================================
/*                                                                                      *
 *                     CONFIGURACIONES DE ROLES Y NIVELES DE ACCESO                     *
 *                                                                                      */
//========================================================================================
/**
 *
 * $config['roles']: Configuración para el sistema de permisos de la aplicación (PiecesPHP\Core\Roles).
 * Se usa la forma $config['roles']['CONFIGURACIÓN'].
 * Las configuraciones aceptadas son [active,types].
 *
 * Ejemplo:
 * $config['roles']['active'] = true; //Si está en false el framework ignorará el uso automático del sistema
 * $config['roles']['types'] = []; //Es un array con los tipos de roles
 * $config['roles']['types'][] = [
 *     'code' => 0, //Código del rol. Debe ser un int. Por defecto time(). (opcional).
 *     'name' => 'ADMIN', //Nombre del rol. Debe ser un string. (obligatorio).
 *     'all' => true, //Si es true tiene total acceso a todas las rutas.  Debe ser un bool. Por defecto false. (opcional).
 *     'allowed_routes' => [ //Array de rutas permitidas (esto es: los nombres de rutas establecidos en Slim). Debe ser un string. (opcional)
 *     ]
 * ];
 *
 * $config['control_access_login']: Controla el acceso de a las rutas según el valor 'require_login' que posean.
 * Si es true se hará el control. Si no desea que la aplicación haga estas validaciones automáticamente o desea
 * implementar otro sistema de usuario establézcala en false.
 *
 * $config['admin_url']: Array con las opciones [relative, url].
 * Si está usando el sistema de usuarios de PiecesPHP está opción será usada para configurar la url a donde
 * será redirigido automáticamente al iniciar sesión. Debe ser una URL relativa a la base.
 * Nota: Depende de que $config['control_access_login'] sea true
 *
 * $config['admin_url']['relative'] Debe ser true/false. Si es true la url será interpretada como relativa a $config['base_url'] de
 * lo contrario será tomada tal cual para la redirección.
 * $config['admin_url']['url'] La url
 */

use App\Model\UsersModel;
use PiecesPHP\Core\SessionToken;

//──── Roles y usuarios ──────────────────────────────────────────────────────────────────
$config['roles']['active'] = true;

$permisosGenerales = [
    //Generales
    'admin', //Vista principal de la zona administrativa
    //Usuarios
    'users-form-profile',
    //Avatar
    'avatars', //Traer todos los elementos de los avatares
    'push-avatars', //Crear avatar
];

$permisosAdministrativos = array_unique(array_merge($permisosGenerales, [
    //Usuarios
    "users-list", //Listado de los usuarios
    "users-selection-create", //Selección de tipo de usuario para creación
    "users-form-create", //Formulario de creación de usuarios
    "users-form-edit", //Formulario de edición de usuarios
]));

$permisosSuperiores = array_unique(array_merge($permisosGenerales, $permisosAdministrativos, [
    //Gestión de errores
    "admin-error-log",
]));

$config['roles']['types'] = [
    [
        'code' => UsersModel::TYPE_USER_ROOT,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_ROOT],
        'all' => false,
        'allowed_routes' => $permisosSuperiores,
    ],
    [
        'code' => UsersModel::TYPE_USER_ADMIN,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_ADMIN],
        'all' => false,
        'allowed_routes' => $permisosAdministrativos,
    ],
    [
        'code' => UsersModel::TYPE_USER_GENERAL,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_GENERAL],
        'all' => false,
        'allowed_routes' => $permisosGenerales,
    ],
];

$config['control_access_login'] = true;
$config['admin_url']['relative'] = true;
$config['admin_url']['url'] = 'admin';

//Definir fecha mínima del token de inicio de sesión
SessionToken::setMinimumDateCreated(\DateTime::createFromFormat('d-m-Y h:i:s A', '26-06-1999 05:43:59 PM'));
