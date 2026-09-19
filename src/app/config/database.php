<?php
//========================================================================================
/*                                                                                      *
 *                         CONFIGURACIONES DE LAS BASE DE DATOS                         *
 *                                                                                      */
//========================================================================================
/**
 * $config['database']: Configuraciones de las bases de datos es un array multidimensional para brindar
 * la posibilidad de configurar múltiples bases de datos siguiento la lógia $config['database']['GRUPO']['CONFIGURACIÓN'].
 * Las confiraciones posibles son [driver,db,user,password,host,charset]
 *
 * Por ejemplo:
 * $config['database']['default']['driver'] = 'mysql'; //Driver que se usará en PDO
 * $config['database']['default']['db'] = 'piecesphp'; //Nombre de la base de datos
 * $config['database']['default']['user'] = 'root'; //Usuario
 * $config['database']['default']['password'] = ''; //Contraseña
 * $config['database']['default']['host'] = 'localhost'; //Host
 * $config['database']['default']['charset'] = 'utf8'; //Juego de caracteres
 *
 * Nota: el ejemplo anterior muestra los valores que la aplicación asume por defecto.
 *
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
