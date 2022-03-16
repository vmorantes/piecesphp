<?php

/**
 * BaseEntityMapper.php
 */
namespace PiecesPHP\Core;

use PiecesPHP\Core\Database\Database;
use PiecesPHP\Core\Database\EntityMapper;

/**
 * BaseEntityMapper - Implementación básica de un ORM
 *
 *
 * Constituye una abstracción de una entidad/tabla.
 *
 * @todo        Crear tablas automáticamente
 * @todo        Agregar diferentes relaciones
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BaseEntityMapper extends EntityMapper
{
    /**
     * @var bool
     */
    protected static $localeSetted = false;

    /**
     * @param mixed $value_compare (Debe ser de tipo escalar)
     * @param string $field_compare
     * @param array $options Las opciones de configuración
     * Opciones aceptadas:
     * - driver (string) El controlador PDO.  Opcional. Por defecto mysql
     * - database (string) Nombre de la base de datos. Obligatorio
     * - host (string) Servidor. Opcional. Por defecto localhost
     * - user (string) Usuario. Opcional. Por defecto root
     * - password (string) Contraseña. Opcional. Por defecto una cadena vacía
     * - charset (string) El juego de caracteres. Opcional. Por defecto utf8
     * @param bool $auto_config
     * @param string $db_group El grupo de configuraciones de la base de datos
     * Nota: esto sobreescribe a las opciones pasadas por la función estática setOptions
     * @return static
     */
    public function __construct($value_compare = null, string $field_compare = 'primary_key', $options = null, bool $auto_config = true, string $db_group = 'default')
    {
        if ($auto_config === true) {

            $driver = Config::app_db($db_group)['driver'];
            $database = Config::app_db($db_group)['db'];
            $host = Config::app_db($db_group)['host'];
            $user = Config::app_db($db_group)['user'];
            $password = Config::app_db($db_group)['password'];
            $charset = Config::app_db($db_group)['charset'];

            parent::__construct($value_compare, $field_compare, [
                'driver' => $driver,
                'database' => $database,
                'host' => $host,
                'user' => $user,
                'password' => $password,
                'charset' => $charset,
            ]);

        } else {
            parent::__construct($value_compare, $field_compare, $options);
        }

        if (!self::$localeSetted) {
            $lcTimeNameOptions = get_config('lc_time_names_mysql');
            if (is_array($lcTimeNameOptions) && !empty($lcTimeNameOptions)) {
                $currentLang = Config::get_lang();
                $lcTimeName = array_key_exists($currentLang, $lcTimeNameOptions) ? $lcTimeNameOptions[$currentLang] : null;
                if (is_string($lcTimeName) && mb_strlen($lcTimeName) > 0) {
                    $databaseInstance = $this->getModel()->getDatabase();
                    if ($databaseInstance instanceof Database) {
                        $prepareStatement = $databaseInstance->prepare("SET lc_time_names = '{$lcTimeName}';");
                        $prepareStatement->execute();
                        $prepareStatement->closeCursor();
                    }
                }
            }
            self::$localeSetted = true;
        }
    }
}
