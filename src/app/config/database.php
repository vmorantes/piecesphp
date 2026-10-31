<?php

/**
 * @pcsphp-config clon
 * Qué conviene editar aquí: la conexión a la base de datos de tu instalación.
 */
//========================================================================================
/*                                                                                      *
 *                         CONFIGURACIONES DE LAS BASE DE DATOS                         *
 *                                                                                      */
//========================================================================================
/**
 * Conexiones a base de datos, por grupo ($config['database'][GRUPO][CLAVE]); los mappers usan 'default'.
 * Formato y trampas: source-docs/project/docs/piecesphp/content/configuration.md, «database.php».
 */

//──── Bases de datos ────────────────────────────────────────────────────────────────────
if (is_local()) {
    $config['database']['default']['driver'] = 'mysql';
    $config['database']['default']['db'] = 'piecesphp';
    $config['database']['default']['user'] = 'admin';
    $config['database']['default']['password'] = '';
    $config['database']['default']['host'] = 'localhost';
    $config['database']['default']['charset'] = 'utf8mb4';
} else {
    $config['database']['default']['driver'] = 'mysql';
    $config['database']['default']['db'] = 'admin_pieces';
    $config['database']['default']['user'] = 'root';
    $config['database']['default']['password'] = 'PASSWORD';
    $config['database']['default']['host'] = 'localhost';
    $config['database']['default']['charset'] = 'utf8mb4';
}
