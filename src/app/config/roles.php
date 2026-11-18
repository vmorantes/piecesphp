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

use PiecesPHP\UserSystem\UserTypes;

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

//Los tipos de usuario y su rol: UserTypes (ADR 0078), con el segmento de rutas de cada uno.
$config['roles']['types'] = UserTypes::roles($config['roles']['baseInitialSegmentedPermissions']);

$config['control_access_login'] = true;
$config['admin_url']['relative'] = true;
$config['admin_url']['url'] = '';

//La marca global vive en `SessionToken::minimumDateCreated()`: el valor por defecto es el que estaba escrito aquí, y
//la clave «session_minimum_date» lo sustituye. Moverla sigue echando fuera a todo el mundo.
