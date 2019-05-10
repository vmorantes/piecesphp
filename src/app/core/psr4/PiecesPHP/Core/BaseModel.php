<?php

/**
 * BaseModel.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * BaseModel - Implementación básica de modelo ActiveRecord.
 *
 * Constituye una abstracción de la interacción con base de datos.
 *
 * Los modelos que heredan de este deben tener el nombre NombreModel.
 * @todo        Cambiar parametrización del constructor por un array de opciones
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BaseModel extends ActiveRecordModel
{
    /**
     * Configuración del modelo.
     *
     * Usa PDO para la conexión.
     *
     * @param string $driver Controlador de PDO
     * @param string $database Nombre de la base de datos
     * @param string $host Servidor
     * @param string $user Usuario
     * @param string $password Contraseña
     * @param boolean $set_database En true define la propiedad BaseModel->db en false la deja en null
     * @param string $charset El juego de caracteres
     * @param string $database_group El grupo de configuraciones por defecto. Nota: Esto si se está usando con las
     * configuraciones automáticas en PiecesPHP
     * @return BaseModel
     */
    public function __construct(
        string $driver = null,
        string $database = null,
        string $host = null,
        string $user = null,
        string $password = null,
        bool $set_database = true,
        string $charset = null,
        string $database_group = 'default'
    ) {
        if ($set_database === true) {

            $driver = !is_null($driver) ? $driver : Config::app_db($database_group)['driver'];
            $database = !is_null($database) ? $database : Config::app_db($database_group)['db'];
            $host = !is_null($host) ? $host : Config::app_db($database_group)['host'];
            $user = !is_null($user) ? $user : Config::app_db($database_group)['user'];
            $password = !is_null($password) ? $password : Config::app_db($database_group)['password'];
            $charset = !is_null($charset) ? $charset : Config::app_db($database_group)['charset'];

            parent::__construct([
                'driver' => $driver,
                'database' => $database,
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'charset' => $charset,
            ]);

        } else {
            if (is_string($this->prefix_table)) {
                $this->table = trim($this->prefix_table) . trim($this->table);
            }
            if (is_null($this->fields) && is_string($this->table)) {
                $this->configFields();
            }
        }
    }
}
