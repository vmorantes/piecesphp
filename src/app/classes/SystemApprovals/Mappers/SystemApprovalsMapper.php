<?php

/**
 * SystemApprovalsMapper.php
 */

namespace SystemApprovals\Mappers;

use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use SystemApprovals\SystemApprovalsLang;
use SystemApprovals\Util\SystemApprovalManager;

/**
 * SystemApprovalsMapper.
 *
 * @package     SystemApprovals\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 * @property int|null $id
 * @property string $referenceAlias
 * @property string $referenceValue
 * @property string $referenceTable
 * @property string|\DateTime $referenceDate
 * @property string|null $reason
 * @property string|\DateTime $createdAt
 * @property string|\DateTime|null $approvalAt
 * @property int|UsersModel $createdBy
 * @property int|UsersModel|null $approvalBy
 * @property int $status
 * @property \stdClass|string|null $meta
 */
class SystemApprovalsMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'referenceAlias' => [
            'type' => 'text',
        ],
        'referenceValue' => [
            'type' => 'text',
        ],
        'referenceTable' => [
            'type' => 'text',
        ],
        'referenceDate' => [
            'type' => 'text',
        ],
        'reason' => [
            'type' => 'test',
            'null' => true,
        ],
        'createdAt' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
        'approvalAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'createdBy' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'approvalBy' => [
            'type' => 'int',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
            'null' => true,
        ],
        'status' => [
            'type' => 'text',
            'default' => self::STATUS_PENDING,
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUSES = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_APPROVED => 'Aprobado',
        self::STATUS_REJECTED => 'Rechazado',
    ];

    const CAN_APPROVAL_ALL = [
        UsersModel::TYPE_USER_ROOT,
        UsersModel::TYPE_USER_ADMIN_GRAL,
        UsersModel::TYPE_USER_INSTITUCIONAL,
    ];

    const TABLE = 'system_approvals_elements';
    const LANG_GROUP = SystemApprovalsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`idPadding` DESC',
    ];

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * @var \stdClass
     */
    protected $extendedRecord = null;

    /**
     * @param int $value
     * @param string $fieldCompare
     * @return static
     */
    public function __construct(int $value = null, string $fieldCompare = 'primary_key')
    {
        parent::__construct($value, $fieldCompare);
        if ($this->id !== null) {
            $model = SystemApprovalsMapper::model();
            $model->select(SystemApprovalsMapper::fieldsToSelect())->where("id = {$this->id}");
            $row = $model->row();
            $this->extendedRecord = is_object($row) ? $row : null;
        }
    }

    /**
     * HTML tag para tiempo de diferencia
     *
     * @return string
     */
    public function getTimeTag()
    {
        $e = $this->extendedRecord;
        $timeDisplay = '';
        if ($e !== null) {
            $asMonths = $e->elapsedMonths > 1;
            $timeText = __(self::LANG_GROUP, 'días');
            $timeValue = $e->elapsedDays;
            $timeTagStatus = $timeValue < 8 ? 'green' : (
                $timeValue <= 15 ? 'orange' : 'red'
            );
            $timeIcon = $timeTagStatus == 'green' ? 'check' : (
                $timeTagStatus == 'orange' ? 'exclamation' :
                'times'
            );
            if ($asMonths) {
                $timeText = __(self::LANG_GROUP, 'meses');
                $timeValue = $e->elapsedMonths;
            }

            $timeIcon = "<span class='icon'> <i class='icon {$timeIcon}'></i> </span>";
            $timeText = "<span class='text'>{$timeValue} {$timeText}</span>";
            $timeDisplay = "<div class='time-tag {$timeTagStatus}'>{$timeIcon} {$timeText}</div>";
        }
        return $timeDisplay;
    }

    /**
     * Obtiene un objeto que representa la entidad con campos extendidos por fieldsToSelect
     *
     * @return \stdClass|null
     */
    public function getExtendedElement()
    {
        return $this->extendedRecord;
    }

    /**
     * Para el referenceAlias por la función __()
     * @return string
     */
    public function referenceAliasLangSensitive()
    {
        return __(self::LANG_GROUP, $this->referenceAlias);
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $this->createdAt = new \DateTime();
        $this->createdBy = getLoggedFrameworkUser()->id;
        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            if ($idInserted !== null) {
                $this->id = $idInserted;
            }
        }

        return $saveResult;

    }

    /**
     * @param bool $noDateUpdate
     * @inheritDoc
     */
    public function update(bool $noDateUpdate = false)
    {
        return parent::update();
    }

    /**
     * Campos extra:
     *  - idPadding
     *  - elapsedDays
     *  - elapsedWeeks
     *  - elapsedMonths
     *  - referenceDateFormat
     *  - approvalAtFormat
     *  - referenceCreatedBy
     *  - referenceOrganization
     *  - referenceOrganizationAdministrator
     *  - referenceOrtanizationApprovalValue
     *  - referenceUserFirstName
     *  - referenceUserSecondName
     *  - referenceUserFirstLastName
     *  - referenceUserSecondLastName
     *  - referenceUserNames
     *  - referenceUserLastNames
     *  - referenceUserFullName
     *  - statusText
     * @return string[]
     */
    protected static function fieldsToSelect(string $formatDate = null)
    {
        $formatDate = $formatDate ?? get_default_format_date(null, true);
        $mapper = (new SystemApprovalsMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();
        $tableUsers = UsersModel::TABLE;
        $tableOrganizations = OrganizationMapper::TABLE;
        $approvalManager = SystemApprovalManager::getInstance();
        $approvalManager->generateCaptureDataFromReferenceForTableOnSQL('createdBy');

        //Datos del contenido
        $referenceCreatedByUserID = $approvalManager->generateCaptureDataFromReferenceForTableOnSQL('createdBy');
        $referenceUserOrganizationID = "(SELECT {$tableUsers}.organization FROM {$tableUsers} WHERE {$tableUsers}.id = (SELECT referenceCreatedBy))";
        $referenceUserFirstNameSegment = "(SELECT {$tableUsers}.firstname FROM {$tableUsers} WHERE {$tableUsers}.id = (SELECT referenceCreatedBy))";
        $referenceUserFirstLastNameSegment = "(SELECT {$tableUsers}.first_lastname FROM {$tableUsers} WHERE {$tableUsers}.id = (SELECT referenceCreatedBy))";
        $referenceUserSecondNameSegment = "(SELECT IF({$tableUsers}.secondname IS NOT NULL, CONCAT(' ', {$tableUsers}.secondname), '') FROM {$tableUsers} WHERE {$tableUsers}.id = (SELECT referenceCreatedBy))";
        $referenceUserSecondLastNameSegment = "(SELECT IF({$tableUsers}.second_lastname IS NOT NULL, CONCAT(' ', {$tableUsers}.second_lastname), '') FROM {$tableUsers} WHERE {$tableUsers}.id = (SELECT referenceCreatedBy))";
        $referenceOrganizationAdministrator = "(SELECT JSON_UNQUOTE(JSON_EXTRACT({$tableOrganizations}.meta, '$.administrator')) FROM {$tableOrganizations} WHERE {$tableOrganizations}.id = (SELECT referenceOrganization))";
        $referenceOrtanizationApprovalValue = "(SELECT subMain.status FROM {$table} AS subMain WHERE subMain.referenceTable = '{$tableOrganizations}' AND subMain.referenceValue = (SELECT referenceOrganization))";

        $statusesJSON = json_encode((object) self::statuses(), \JSON_UNESCAPED_UNICODE);

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "TIMESTAMPDIFF(DAY, {$table}.referenceDate, NOW()) AS elapsedDays",
            "TIMESTAMPDIFF(WEEK, {$table}.referenceDate, NOW()) AS elapsedWeeks",
            "TIMESTAMPDIFF(MONTH, {$table}.referenceDate, NOW()) AS elapsedMonths",
            "DATE_FORMAT({$table}.referenceDate, '{$formatDate}') AS referenceDateFormat",
            "DATE_FORMAT({$table}.approvalAt, '{$formatDate}') AS approvalAtFormat",
            "TRIM({$referenceCreatedByUserID}) AS referenceCreatedBy",
            "TRIM({$referenceUserOrganizationID}) AS referenceOrganization",
            "TRIM({$referenceOrganizationAdministrator}) AS referenceOrganizationAdministrator",
            "TRIM({$referenceOrtanizationApprovalValue}) AS referenceOrtanizationApprovalValue",
            "TRIM({$referenceUserFirstNameSegment}) AS referenceUserFirstName",
            "TRIM({$referenceUserSecondNameSegment}) AS referenceUserSecondName",
            "TRIM({$referenceUserFirstLastNameSegment}) AS referenceUserFirstLastName",
            "TRIM({$referenceUserSecondLastNameSegment}) AS referenceUserSecondLastName",
            "TRIM(CONCAT((SELECT referenceUserFirstName), ' ', (SELECT referenceUserSecondName))) AS referenceUserNames",
            "TRIM(CONCAT((SELECT referenceUserFirstLastName), ' ', (SELECT referenceUserSecondLastName))) AS referenceUserLastNames",
            "TRIM(CONCAT((SELECT referenceUserNames), ' ', (SELECT referenceUserLastNames))) AS referenceUserFullName",
            "JSON_UNQUOTE(JSON_EXTRACT('{$statusesJSON}', CONCAT('$.', {$table}.status))) AS statusText",
        ];

        //Multi-idioma
        $fieldsElement = array_keys($mapper->getFields());
        foreach ($fieldsElement as $fieldName) {
            $fields[] = "{$table}.{$fieldName}";
        }
        return $fields;
    }

    /**
     * Obtiene los "nombres de contenido" desde la agrupación de referenceAlias en base de datos (los pasa por __())
     *
     * @return string[]
     */
    public static function getReferencesAliases()
    {
        $model = self::model();
        $model->select("referenceAlias")->groupBy('referenceAlias')->execute();
        $elements = array_map(fn($e) => __(self::LANG_GROUP, $e->referenceAlias), $model->result());
        return array_combine($elements, $elements);
    }

    /**
     * Obtiene los días transcurridos existentes
     *
     * @return string[]
     */
    public static function getElapsepDaysExistents()
    {
        $options = [
            '1' => '>= 1 ' . __(self::LANG_GROUP, 'día(s)'),
            '5' => '>= 5 ' . __(self::LANG_GROUP, 'día(s)'),
            '10' => '>= 10 ' . __(self::LANG_GROUP, 'día(s)'),
            '15' => '>= 15 ' . __(self::LANG_GROUP, 'día(s)'),
            '20' => '>= 20 ' . __(self::LANG_GROUP, 'día(s)'),
            '30' => '>= 30 ' . __(self::LANG_GROUP, 'día(s)'),
            '45' => '>= 45 ' . __(self::LANG_GROUP, 'día(s)'),
            '60' => '>= 60 ' . __(self::LANG_GROUP, 'día(s)'),
        ];
        return $options;
    }

    /**
     * @return array
     */
    public static function statuses()
    {
        $options = [];
        foreach (self::STATUSES as $value => $text) {
            $options[$value] = __(self::LANG_GROUP, $text);
        }
        return $options;
    }

    /**
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @param bool $withOptionAny
     * @return array
     */
    public static function getReferencesAliasesForSelect(string $defaultLabel = '', string $defaultValue = '', bool $withOptionAny = false)
    {
        $sourceOptions = self::getReferencesAliases();
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Tipo de contenido');
        $options = [];
        $options[$defaultValue] = $defaultLabel;
        if ($withOptionAny) {
            $options['-1'] = __(self::LANG_GROUP, 'Todos');
        }
        foreach ($sourceOptions as $value => $text) {
            $options[$value] = $text;
        }
        return $options;
    }

    /**
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @param bool $withOptionAny
     * @return array
     */
    public static function getElapsepDaysExistentsForSelect(string $defaultLabel = '', string $defaultValue = '', bool $withOptionAny = false)
    {
        $sourceOptions = self::getElapsepDaysExistents();
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Tiempo');
        $options = [];
        $options[$defaultValue] = $defaultLabel;
        if ($withOptionAny) {
            $options['-1'] = __(self::LANG_GROUP, 'En cualquier momento');
        }
        foreach ($sourceOptions as $value => $text) {
            $options[$value] = $text;
        }
        return $options;
    }

    /**
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function all(bool $asMapper = false)
    {
        $model = self::model();

        $selectFields = [];

        $model->select($selectFields);

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = self::objectToMapper($value);
            }
        }

        return $result;
    }

    /**
     * @param string $column
     * @param int $value
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function allBy(string $column, $value, bool $asMapper = false)
    {
        $model = self::model();

        $model->select()->where([
            $column => $value,
        ])->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = self::objectToMapper($value);
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param string $column
     * @param boolean $as_mapper
     * @return static|object|null
     */
    public static function getBy($value, string $column = 'id', bool $as_mapper = false)
    {
        $model = self::model();

        $where = [
            $column => $value,
        ];

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        $result = !empty($result) ? $result[0] : null;

        if (!is_null($result) && $as_mapper) {
            $result = self::objectToMapper($result);
        }

        return $result;
    }

    /**
     * @param array[] $criteries Cada elemento debe tener las claves value, column y opcionalmente beforeOperator
     * @param string[] $orderBy
     * @param bool $extendedFields
     * @param bool $asMapper
     * @return \stdClass|static|null
     */
    public static function getByMultipleCriteries(array $criteries = [], array $orderBy = [], bool $extendedFields = false, bool $asMapper = false)
    {

        $orderBy = array_map(fn($e) => is_string($e) && mb_strlen($e) > 1 ? $e : null, $orderBy);
        $orderBy = array_filter($orderBy, fn($e) => $e !== null);

        $model = self::model();
        $selectFields = $extendedFields ? self::fieldsToSelect() : '*';
        $model->select($selectFields);
        $where = [];
        $criteriesAdded = 0;

        if (!empty($criteries)) {
            foreach ($criteries as $critery) {
                $column = array_key_exists('column', $critery) ? $critery['column'] : null;
                $value = array_key_exists('value', $critery) ? $critery['value'] : null;
                $beforeOperatorBase = array_key_exists('beforeOperator', $critery) ? $critery['beforeOperator'] : 'AND';
                if ($column !== null && $value !== null) {
                    $isNumber = is_double($value) || is_int($value);
                    $criteryValue = $isNumber ? $value : "'" . escapeString($value) . "'";
                    $beforeOperator = !empty($where) ? $beforeOperatorBase : '';
                    $critery = "{$column}  = {$criteryValue}";
                    $where[] = "{$beforeOperator} ({$critery})";
                    $criteriesAdded++;
                }
            }
        }

        if ($criteriesAdded > 0) {

            if (!empty($where)) {
                $whereString = trim(implode(' ', $where));
                $model->where($whereString);
            }

            if (!empty($orderBy)) {
                $model->orderBy($orderBy);
            }

            $model->execute();

            $result = $model->result();
            $result = !empty($result) ? $result[0] : null;

            if ($asMapper && $result !== null) {
                $result = new SystemApprovalsMapper($result->id);
            }

            return $result;
        } else {
            return null;
        }
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function existsByID(int $id)
    {
        $model = self::model();

        $where = [
            "id = $id",
        ];
        $where = trim(implode(' ', $where));

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return !empty($result);
    }

    /**
     * Devuelve el mapeador desde un objeto
     *
     * @param \stdClass $element
     * @param bool $setExtended
     * @return SystemApprovalsMapper|null
     */
    public static function objectToMapper(\stdClass $element, bool $setExtended = true)
    {

        $origialElement = $element;
        $element = (array) $element;
        $mapper = new SystemApprovalsMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        if ($setExtended) {
            $mapper->extendedRecord = $origialElement;
        }

        $defaultPropertiesValues = [
        ];

        foreach ($defaultPropertiesValues as $defaultProperty => $defaultPropertyValue) {
            if (!array_key_exists($defaultProperty, $element)) {
                $element[$defaultProperty] = $defaultPropertyValue;
            }
        }

        $defaultMetaPropertiesValues = [];

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass  ? $value : @json_decode($value);

                    foreach ($defaultMetaPropertiesValues as $defaultMetaProperty => $defaultMetaPropertyValue) {
                        foreach ($defaultMetaPropertiesValues as $defaultMetaProperty => $defaultMetaPropertyValue) {
                            if (!property_exists($value, $defaultMetaProperty)) {
                                $value->$defaultMetaProperty = $defaultMetaPropertyValue;
                            }
                        }
                    }

                    if ($value instanceof \stdClass) {
                        foreach ($value as $metaPropertyName => $metaPropertyValue) {

                            if ($mapper->hasMetaProperty($metaPropertyName)) {
                                $mapper->$metaPropertyName = $metaPropertyValue;
                                $fieldsFilleds[] = $metaPropertyName;
                            }

                        }
                    }

                } else {
                    $mapper->$property = $value;
                }

                $fieldsFilleds[] = $property;

            }

        }

        $fieldsFilleds = array_unique($fieldsFilleds);
        $fields = array_unique($fields);
        sort($fieldsFilleds);
        sort($fields);
        $allFilled = count($fieldsFilleds) === count($fields);

        return $allFilled ? $mapper : null;

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new SystemApprovalsMapper)->getModel();
    }
}
