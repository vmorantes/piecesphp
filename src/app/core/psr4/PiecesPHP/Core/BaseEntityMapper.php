<?php

/**
 * BaseEntityMapper.php
 */
namespace PiecesPHP\Core;

use App\Model\UsersModel;
use PiecesPHP\Core\Database\Database;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use ReflectionMethod;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;
use SystemApprovals\Util\SystemApprovalManager;

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
                $lcTimeNameList = array_key_exists($currentLang, $lcTimeNameOptions) ? $lcTimeNameOptions[$currentLang] : null;
                $lcTimeNameList = is_array($lcTimeNameList) ? $lcTimeNameList : [$lcTimeNameList];

                if (is_array($lcTimeNameList) && !empty($lcTimeNameList)) {
                    foreach ($lcTimeNameList as $lcTimeName) {
                        if (is_string($lcTimeName) && mb_strlen($lcTimeName) > 0) {
                            $databaseInstance = $this->getModel()->getDatabase();
                            if ($databaseInstance instanceof Database) {
                                try {
                                    $prepareStatement = $databaseInstance->prepare("SET lc_time_names = '{$lcTimeName}';");
                                    $prepareStatement->execute();
                                    $prepareStatement->closeCursor();
                                    break;
                                } catch (\Exception $e) {
                                    log_exception($e);
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
            self::$localeSetted = true;
        }
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        /**
         * @category GlobalMethodDispatch
         */
        BaseEventDispatcher::dispatch(get_class($this), 'saving', $this);
        $saved = parent::save();
        if ($saved) {
            BaseEventDispatcher::dispatch(get_class($this), 'saved', $this);
        }
        return $saved;
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        /**
         * @category GlobalMethodDispatch
         */
        BaseEventDispatcher::dispatch(get_class($this), 'updating', $this);
        $updated = parent::update();
        if ($updated) {
            BaseEventDispatcher::dispatch(get_class($this), 'updated', $this);
        }
        return $updated;
    }

    /**
     * Método mágico estático para interceptar llamadas a métodos específicos
     *
     * @param string $name Nombre del método llamado
     * @param array $arguments Argumentos pasados al método
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {

        $className = static::class;
        $reflection = new ReflectionMethod($className, $name);
        $reflection->setAccessible(true);
        $result = $reflection->invokeArgs(null, $arguments);

        if ($name === 'fieldsToSelect') {
            $approvedValue = SystemApprovalsMapper::STATUS_APPROVED;
            if (SystemApprovalsRoutes::ENABLE) {
                $tableApprovals = SystemApprovalsMapper::TABLE;
                $tableProfiles = UserProfileMapper::TABLE;
                $tableUsers = UsersModel::TABLE;
                $tableName = constant("{$className}::TABLE");
                $approvalHandler = SystemApprovalManager::getInstance()->getHandler($tableName);
                if ($approvalHandler !== null) {
                    $referenceColumn = $approvalHandler::getReferenceColumn();
                    $tableColumn = "{$tableName}.{$referenceColumn}";
                    $result[] = "(SELECT {$tableApprovals}.status FROM {$tableApprovals} WHERE {$tableApprovals}.referenceTable = '{$tableName}' AND {$tableApprovals}.referenceValue = {$tableColumn} LIMIT 1) AS systemApprovalStatus";
                } else {

                    if ($tableName == $tableProfiles) {
                        $result[] = "(SELECT {$tableApprovals}.status FROM {$tableApprovals} WHERE {$tableApprovals}.referenceTable = '{$tableUsers}' AND {$tableApprovals}.referenceValue = {$tableProfiles}.belongsTo LIMIT 1) AS systemApprovalStatus";
                    } else {
                        $result[] = "'{$approvedValue}' AS systemApprovalStatus"; //Para homologar todas
                    }

                }
            } else {
                $result[] = "'{$approvedValue}' AS systemApprovalStatus"; //Para homologar todas
            }
        }

        return $result;

    }

}
