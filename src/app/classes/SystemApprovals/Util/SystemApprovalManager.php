<?php

/**
 * SystemApprovalManager.php
 */

namespace SystemApprovals\Util;

use PiecesPHP\Core\BaseEventDispatcher;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsLang;

/**
 * SystemApprovalManager.
 *
 * @package     SystemApprovals\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class SystemApprovalManager
{
    protected static ?SystemApprovalManager $instance = null;
    /**
     * @var class-string<ApprovalElementHandlerInterface>[]
     */
    protected array $configurations = [];
    protected array $langAlternatives = [];

    /**
     * Privado: no se instancia desde fuera. Carga la configuración del sistema de aprobaciones.
     */
    private function __construct()
    {
        $configurations = require_once realpath(__DIR__ . '/configurations.php');

        /**
         * @var ApprovalElementHandlerInterface[] $configurations
         */
        foreach ($configurations as $class) {

            if (is_subclass_of($class, ApprovalElementHandlerInterface::class)) {

                $enabled = $class::isEnabled();

                if ($enabled) {
                    //Escuchar eventos de cambios
                    BaseEventDispatcher::listen('updated', function ($payload) use ($class) {
                        $mapperName = $class::getMapperClass();
                        if ($payload::class == $mapperName) {
                            //¿Cambió algo que NO sea sello de auditoría? El evento dice que
                            //cambió una fila; los sellos los escribe el mapper solo. Ver T87.
                            if (!self::isRealEdition($payload, $class)) {
                                return;
                            }
                            $contentTypeName = $class::getContentType($payload);
                            $referenceTable = $class::getReferenceTable();
                            $referenceColumn = $class::getReferenceColumn();
                            $approvalElement = SystemApprovalsMapper::getByMultipleCriteries([
                                [
                                    'column' => 'referenceTable',
                                    'value' => $referenceTable,
                                ],
                                [
                                    'column' => 'referenceValue',
                                    'value' => $payload->$referenceColumn,
                                ],
                            ], [], false, true);
                            if ($approvalElement !== null && $approvalElement->status != SystemApprovalsMapper::STATUS_APPROVED) {
                                $approvalElement->referenceAlias = $contentTypeName;
                                //Si está rechazado pasa a pendiente al editar
                                if ($approvalElement->status == SystemApprovalsMapper::STATUS_REJECTED) {
                                    $approvalElement->status = SystemApprovalsMapper::STATUS_PENDING;
                                }
                                $approvalElement->update();
                            }

                            $class::onUpdatedRecord($payload, $approvalElement);
                        }
                    }, $class::getMapperClass());
                    $this->configurations[] = $class;
                }

            }

        }
    }

    /**
     * ¿El guardado fue una edición de verdad, o solo movió sellos de auditoría?
     *
     * `null` —«no lo sé»— cuenta como edición: anunciar de menos perdería la reapertura de
     * rechazos, que es intención declarada del PROPIETARIO. Ver T87.
     *
     * @param mixed $payload
     * @param mixed $class
     * @return bool
     */
    protected static function isRealEdition($payload, $class): bool
    {
        if (!is_string($class) || !is_subclass_of($class, ApprovalElementHandlerInterface::class)) {
            return true;
        }

        if (!is_object($payload) || !method_exists($payload, 'lastChangedFields')) {
            return true;
        }

        $changed = $payload->lastChangedFields();
        if (!is_array($changed)) {
            return true;
        }

        return count(array_diff($changed, $class::auditFields())) > 0;
    }

    /**
     * Actualiza el estado de un elemento en el sistema de aprobaciones y ejecuta las acciones correspondientes
     * según el nuevo estado (aprobado o rechazado).
     *
     * @param SystemApprovalsMapper $mapper
     * @return void
     */
    public function updateStatus(SystemApprovalsMapper $mapper)
    {
        $configurations = $this->configurations;
        foreach ($configurations as $class) {
            if ($mapper->referenceTable == $class::getReferenceTable()) {
                $elementMapper = new ($class::getMapperClass())($mapper->referenceValue);
                if ($mapper->status == SystemApprovalsMapper::STATUS_APPROVED) {
                    $class::onApproved($elementMapper);
                } elseif ($mapper->status == SystemApprovalsMapper::STATUS_REJECTED) {
                    $class::onRejected($elementMapper);
                }
                //Cualquier cambio, no solo aprobar o rechazar: volver a PENDING también quita la visibilidad.
                if (method_exists($class, 'onStatusChanged')) {
                    $class::onStatusChanged($elementMapper, (string) $mapper->status);
                }
                break;
            }
        }
    }

    /**
     * Obtiene el usuario asociado para notificaciones
     *
     * @param SystemApprovalsMapper $mapper
     * @return UsersModel|null
     */
    public function getContactUser(SystemApprovalsMapper $mapper)
    {
        $configurations = $this->configurations;
        foreach ($configurations as $class) {
            if ($mapper->referenceTable == $class::getReferenceTable()) {
                $elementMapper = new ($class::getMapperClass())($mapper->referenceValue);
                return $class::getContactUser($elementMapper);
                break;
            }
        }
        return null;
    }

    /**
     * Comprueba si un elemento está aprobado.
     *
     * @param string $mapperName El nombre del mapeador.
     * @param mixed $referenceValue El valor de referencia.
     * @return bool Devuelve true si la aprobación está aprobado, de lo contrario, false.
     */
    public function isApproved(string $mapperName, $referenceValue)
    {
        $result = false;
        $referenceValue = is_scalar($referenceValue) ? $referenceValue : -1;
        $configurations = $this->configurations;
        foreach ($configurations as $class) {
            $mapperNameElement = $class::getMapperClass();
            $referenceTable = $class::getReferenceTable();
            if ($mapperNameElement == $mapperName) {
                $approvalElement = SystemApprovalsMapper::getByMultipleCriteries([
                    [
                        'column' => 'referenceTable',
                        'value' => $referenceTable,
                    ],
                    [
                        'column' => 'referenceValue',
                        'value' => $referenceValue,
                    ],
                ]);
                $result = $approvalElement !== null ? $approvalElement->status == SystemApprovalsMapper::STATUS_APPROVED : false;
                break;
            }
        }
        return $result;
    }

    /**
     * Devuelve la instancia del mapeador según la tabla
     *
     * @param string $tableName La tabla del mapeador.
     * @param mixed $referenceValue El valor de referencia.
     * @return EntityMapper|null
     */
    public function getMapperInstance(string $tableName, $referenceValue)
    {
        $result = false;
        $referenceValue = is_scalar($referenceValue) ? $referenceValue : -1;
        $configurations = $this->configurations;
        foreach ($configurations as $class) {
            $mapperNameElement = $class::getMapperClass();
            $referenceTable = $class::getReferenceTable();
            if ($referenceTable == $tableName) {
                return new $mapperNameElement($referenceValue);
                break;
            }
        }
        return null;
    }

    /**
     * Devuelve el manejador de aprobaciones según la tabla
     *
     * @param string $tableName La tabla del mapeador.
     * @return class-string<ApprovalElementHandlerInterface>|null
     */
    public function getHandler(string $tableName)
    {
        $configurations = $this->configurations;
        foreach ($configurations as $class) {
            $referenceTable = $class::getReferenceTable();
            if ($referenceTable == $tableName) {
                return $class;
                break;
            }
        }
        return null;
    }

    /**
     * Genera los datos de captura a partir de una referencia para una tabla en SQL.
     *
     * @param string $column La columna específica para la que se generará el dato de captura.
     * @param bool $withTable Indica si se debe incluir el nombre de la tabla en la consulta.
     * @return string El dato de captura generado como una cadena de consulta SQL.
     */
    public function generateCaptureDataFromReferenceForTableOnSQL(string $column, bool $withTable = true)
    {
        $table = $withTable ? SystemApprovalsMapper::TABLE . '.' : '';
        $configurations = $this->configurations;
        $subQueries = [];

        foreach ($configurations as $class) {
            $className = (string) $class;
            $classProperties = get_class_vars($className);
            $tableElement = $class::getReferenceTable();
            $columnElement = $class::getReferenceColumn();
            $supportedFields = $class::getFields();
            $columnToExtract = $column;
            //Para usuarios en caso de usuario que lo creo se pone el mismo id del usuario
            if ($columnToExtract == 'createdBy' && $tableElement == UsersModel::TABLE) {
                $columnToExtract = 'id';
            }
            //Para verificar estado de activo o inactivo
            if ($columnToExtract == 'isActive') {

                if (property_exists($class, 'STATUS_ACTIVATION_COLUMN') && property_exists($class, 'STATUS_ACTIVATION_POSITIVES_VALUES')) {
                    $STATUS_ACTIVATION_COLUMN = $classProperties['STATUS_ACTIVATION_COLUMN'];
                    $STATUS_ACTIVATION_POSITIVES_VALUES = implode("','", $classProperties['STATUS_ACTIVATION_POSITIVES_VALUES']);
                    $subQueries[] = "(SELECT {$tableElement}.{$STATUS_ACTIVATION_COLUMN} IN ('{$STATUS_ACTIVATION_POSITIVES_VALUES}') FROM {$tableElement} WHERE {$table}referenceValue = {$tableElement}.{$columnElement} AND  {$table}referenceTable = '{$tableElement}')";
                }

            } else {
                if (in_array($columnToExtract, $supportedFields)) {
                    $subQueries[] = "(SELECT {$tableElement}.{$columnToExtract} FROM {$tableElement} WHERE {$table}referenceValue = {$tableElement}.{$columnElement} AND  {$table}referenceTable = '{$tableElement}')";
                }
            }
        }

        $subQueries = !empty($subQueries) ? "COALESCE(" . implode(",", $subQueries) . ")" : '(NULL)';
        return $subQueries;
    }

    /**
     * La union de los textos que pueden acabar en `referenceAlias`, segun los handlers
     * REGISTRADOS. Es la lista blanca del filtro, y sale del contrato, no de la base.
     *
     * @return string[]
     */
    public function getContentTypes(): array
    {
        $textos = [];
        foreach ($this->configurations as $class) {
            foreach ($class::getContentTypes() as $texto) {
                $textos[] = $texto;
            }
        }
        return array_values(array_unique($textos));
    }

    /**
     * Retorna una instancia única de SystemApprovalManager.
     *
     * Este método garantiza que solo haya una instancia de SystemApprovalManager en todo el sistema.
     * Si no hay una instancia activa, crea una nueva y la devuelve. Si ya hay una instancia activa,
     * devuelve la instancia existente.
     *
     * @return SystemApprovalManager La instancia única de SystemApprovalManager.
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new SystemApprovalManager();
        }
        return self::$instance;
    }

    /**
     * Inicia las aprobaciones: comprueba la configuración e inserta los registros pendientes o aprobados
     * automáticamente.
     */
    public static function init()
    {
        $instance = self::getInstance();
        $configurations = $instance->configurations;
        $table = SystemApprovalsMapper::TABLE;
        $approvedStatus = SystemApprovalsMapper::STATUS_APPROVED;
        $pendingStatus = SystemApprovalsMapper::STATUS_PENDING;

        //Agregar elementos a la tabla de aprobación
        foreach ($configurations as $class) {

            $sql = [];

            //Obtener elementos
            $mapperName = $class::getMapperClass();
            $referenceTable = $class::getReferenceTable();
            $referenceColumn = $class::getReferenceColumn();
            $creationDateColumn = $class::getCreationDateColumn();
            //El CAST no sobra: contra un entero, MariaDB compara como número y el índice `reference` solo sirve para referenceTable.
            $model = $mapperName::model();
            $model
                ->select()
                ->where("CAST({$referenceTable}.{$referenceColumn} AS CHAR) NOT IN (SELECT {$table}.referenceValue FROM {$table} WHERE {$table}.referenceTable = '{$referenceTable}')");
            $model->execute();
            $result = $model->result();

            //Configurar valores iniciales
            //`excludesElement()` es de BaseApprovalHandler, no de la interfaz: un handler que solo la implemente no la tiene.
            $excludes = method_exists($class, 'excludesElement');
            foreach ($result as $element) {
                if ($excludes && $class::excludesElement((int) $element->id)) {
                    continue;
                }
                $contentName = $class::getContentType($element->id);
                $approval = $class::isAutoApproval($element->id);
                $columnsAndValues = [
                    'referenceAlias' => "'{$contentName}'",
                    'referenceValue' => "'{$element->$referenceColumn}'",
                    'referenceTable' => "'{$referenceTable}'",
                    'referenceDate' => "'{$element->$creationDateColumn}'",
                    'createdBy' => UsersModel::SYSTEM_USER_ID,
                    'createdAt' => "'" . date('Y-m-d H:i:s') . "'",
                    'status' => "'{$pendingStatus}'",
                ];
                if ($approval) {
                    $columnsAndValues['status'] = "'{$approvedStatus}'";
                    $columnsAndValues['approvalAt'] = "'" . date('Y-m-d H:i:s') . "'";
                    $columnsAndValues['approvalBy'] = UsersModel::SYSTEM_USER_ID;
                }
                $columns = implode(', ', array_keys($columnsAndValues));
                $values = implode(', ', array_values($columnsAndValues));
                $sql[] = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";
            }

            //Ejecutar consultas
            $pdo = (new BaseModel())::getDb(Config::app_db('default')['db']);
            if ($pdo === null) {
                throw new \Exception(__(SystemApprovalsLang::LANG_GROUP, 'No pudo conectarse a la base de datos'));
            }
            try {

                $pdo->beginTransaction();

                foreach ($sql as $query) {
                    $preparedStatement = $pdo->prepare($query);
                    $preparedStatement->execute();
                }

                $pdo->commit();

            } catch (\Exception $e) {
                $pdo->rollBack();
                log_exception($e);
            }

        }

    }
}
