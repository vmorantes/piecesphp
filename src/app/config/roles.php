<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: los nombres de ruta de tus módulos, al final de cada lista de permisos (sección del clon). Los tipos de usuario y lo demás son del framework.
 */
//========================================================================================
/*                                                                                      *
 *                     CONFIGURACIONES DE ROLES Y NIVELES DE ACCESO                     *
 *                                                                                      */
//========================================================================================
/**
 * Roles, permisos por nombre de ruta y control de acceso por login. Formato y ejemplos:
 * source-docs/project/docs/piecesphp/content/configuration.md, «roles.php».
 */

use PiecesPHP\UserSystem\ORM\UsersModel;

//── Del framework: no lo edites ──
//──── Roles y usuarios ──────────────────────────────────────────────────────────────────
$config['roles']['active'] = true;

$permisosGenerales = [
    //Generales
    'admin', //Vista principal de la zona administrativa
    //Usuarios
    'users-form-profile',
    //Avatar
    'push-avatars', //Crear avatar
    //── Del clon ──
];

//── Del framework: no lo edites ──
$permisosAdministrativos = array_unique(array_merge($permisosGenerales, [
    //Usuarios
    "users-list", //Listado de los usuarios
    "users-selection-create", //Selección de tipo de usuario para creación
    "users-form-create", //Formulario de creación de usuarios
    "users-form-edit", //Formulario de edición de usuarios
    //── Del clon ──
]));

//── Del framework: no lo edites ──
$permisosSuperiores = array_unique(array_merge($permisosGenerales, $permisosAdministrativos, [
    //── Del clon ──
]));

//── Del framework: no lo edites ──
$config['roles']['baseInitialSegmentedPermissions'] = [
    'generals' => $permisosGenerales,
    'administratives' => $permisosAdministrativos,
    'superiors' => $permisosSuperiores,
];

$config['roles']['types'] = [
    [
        'code' => UsersModel::TYPE_USER_ROOT,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_ROOT] ?? null,
        'all' => false,
        'allowed_routes' => $permisosSuperiores,
    ],
    [
        'code' => UsersModel::TYPE_USER_ADMIN_GRAL,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_ADMIN_GRAL] ?? null,
        'all' => false,
        'allowed_routes' => $permisosAdministrativos,
    ],
    [
        'code' => UsersModel::TYPE_USER_ADMIN_ORG,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_ADMIN_ORG] ?? null,
        'all' => false,
        'allowed_routes' => $permisosGenerales,
    ],
    [
        'code' => UsersModel::TYPE_USER_GENERAL,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_GENERAL] ?? null,
        'all' => false,
        'allowed_routes' => $permisosGenerales,
    ],
    [
        'code' => UsersModel::TYPE_USER_INSTITUCIONAL,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_INSTITUCIONAL] ?? null,
        'all' => false,
        'allowed_routes' => $permisosGenerales,
    ],
    [
        'code' => UsersModel::TYPE_USER_COMUNICACIONES,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_COMUNICACIONES] ?? null,
        'all' => false,
        'allowed_routes' => $permisosGenerales,
    ],
    [
        'code' => UsersModel::TYPE_USER_GOOGLE_PLAY,
        'name' => UsersModel::TYPES_USERS[UsersModel::TYPE_USER_GOOGLE_PLAY] ?? null,
        'all' => false,
        'allowed_routes' => $permisosGenerales,
    ],
];

//Depurar los que no tengan "name" (por exclusión desde UsersModel.php)
$config['roles']['types'] = array_filter($config['roles']['types'], function ($type) {
    return $type['name'] != null;
});

//Si no se está usando el módulo de organizaciones, se elimina el tipo de usuario administrador de organización
if (!CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']) {
    $config['roles']['types'] = array_filter($config['roles']['types'], function ($type) {
        return $type['code'] != UsersModel::TYPE_USER_ADMIN_ORG && $type['name'] != null;
    });
}

$config['control_access_login'] = true;
$config['admin_url']['relative'] = true;
$config['admin_url']['url'] = '';

//La marca global vive en `SessionToken::minimumDateCreated()`: el valor por defecto es el que estaba escrito aquí, y
//la clave «session_minimum_date» lo sustituye. Moverla sigue echando fuera a todo el mundo.
