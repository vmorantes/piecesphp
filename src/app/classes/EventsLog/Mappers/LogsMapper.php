<?php

/**
 * LogsMapper.php
 */

namespace EventsLog\Mappers;

use App\Model\UsersModel;
use EventsLog\LogsLang;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;

/**
 * LogsMapper.
 *
 * @package     EventsLog\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @property int|null $id
 * @property string $textMessage
 * @property \stdClass|array|string $textMessageVariables
 * @property string|null $referenceColumn
 * @property string|null $referenceValue
 * @property string|null $referenceSource
 * @property int|UsersModel $createdBy
 * @property string|\DateTime $createdAt
 * @property string|null $ip
 * @property string|null $geolocationByIp
 * @property \stdClass|string|null $meta
 */
class LogsMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'textMessage' => [
            'type' => 'text',
        ],
        'textMessageVariables' => [
            'type' => 'json',
            'null' => false,
        ],
        'referenceColumn' => [
            'type' => 'text',
            'null' => true,
        ],
        'referenceValue' => [
            'type' => 'text',
            'null' => true,
        ],
        'referenceSource' => [
            'type' => 'text',
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
        'createdAt' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const TABLE = 'actions_log';
    const LANG_GROUP = LogsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`id` DESC',
        '`createdAt` DESC',
        '`createdBy` DESC',
    ];

    const MSG_GENERIC = 'GENERIC';
    const MSG_UPDATE_PROFILE = 'UPDATE_PROFILE';
    const MSG_UPDATE_PROFILE_IMAGE = 'UPDATE_PROFILE_IMAGE';
    const MSG_REQUEST_PASSWORD_RECOVERY = 'REQUEST_PASSWORD_RECOVERY';
    const MSG_PASSWORD_RECOVERY_BY_CODE = 'PASSWORD_RECOVERY_BY_CODE';

    const MESSAGES = [
        self::MSG_GENERIC => '%message%',
        self::MSG_UPDATE_PROFILE => 'El usuario %username% ha actualizado su perfil',
        self::MSG_UPDATE_PROFILE_IMAGE => 'El usuario %username% ha actualizado su imagen de perfil',
        self::MSG_REQUEST_PASSWORD_RECOVERY => 'El usuario %username% ha solicitado recuperar su contraseña',
        self::MSG_PASSWORD_RECOVERY_BY_CODE => 'El usuario %username% ha recuperado su contraseña mediante un código',
    ];

    const MODULE_NAMES_EQUIVALENCES_BY_SOURCE = [
        UsersModel::TABLE => 'Usuarios',
    ];

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * @param int $value
     * @param string $fieldCompare
     * @return static
     */
    public function __construct(int $value = null, string $fieldCompare = 'primary_key')
    {
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, null, true), 'ip');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, null, true), 'geolocationByIp');
        parent::__construct($value, $fieldCompare);
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        $textMessage = $this->textMessage;
        $textMessageVariables = $this->textMessageVariables;

        if (!is_object($textMessageVariables) && !is_array($textMessageVariables)) {
            $textMessageVariables = [];
        }

        return strReplaceTemplate(__(self::LANG_GROUP, $textMessage), (array) $textMessageVariables);
    }

    /**
     * @return string
     */
    public function createdByFullname()
    {
        $createdBy = $this->createdBy;

        if (!is_object($createdBy)) {
            $this->createdBy = new UsersModel($createdBy);
            $createdBy = $this->createdBy;
        }

        return $createdBy->getFullName();
    }

    /**
     * @param string $format
     * @return string
     */
    public function createdAtFormat(string $format = null)
    {

        $formatDefault = __(self::LANG_GROUP, "F d {1} Y");
        $createdAt = $this->createdAt instanceof \DateTime ? $this->createdAt : null;
        if ($format !== null) {
            $formated = localeDateFormat($format, $createdAt);
        } else {
            $formated = localeDateFormat($formatDefault, $createdAt);
            $formated = strReplaceTemplate($formated, [
                '{1}' => __(self::LANG_GROUP, 'de'),
            ]);
        }
        return $formated;
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $loggedUser = getLoggedFrameworkUser(true);
        $this->createdAt = new \DateTime();
        $this->createdBy = $loggedUser !== null ? $loggedUser->userMapper : 1;
        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            $this->id = $idInserted;
        }

        return $saveResult;

    }

    /**
     * @param bool $noDateUpdate
     * @inheritDoc
     */
    public function update(bool $noDateUpdate = false)
    {
        return false;
    }

    /**
     * @param string $messageType
     * @param array $variables
     * @param string $referenceColumn
     * @param string $referenceValue
     * @param string $referenceSource
     * @return LogsMapper
     */
    public static function addLog(string $messageType, array $variables = [], string $referenceColumn = null, string $referenceValue = null, string $referenceSource = null)
    {
        $mapper = new LogsMapper;
        $notExistsMessage = true;

        if (function_exists('geoip_record_by_name')) {
            try {
                $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
                $geoIP = function_exists('geoip_record_by_name') ? call_user_func('geoip_record_by_name', $ip) : null;
                $geoIP = is_array($geoIP) ? $geoIP : [
                    'country_name' => 'Sin especificar',
                ];
                $geolocationByIp = array_key_exists('country_name', $geoIP) ? $geoIP['country_name'] : 'Sin especificar';
                $mapper->geolocationByIp = $geolocationByIp;
                $mapper->ip = $ip;
            } catch (\Throwable $e) {}
        }

        foreach (self::MESSAGES as $type => $text) {

            if ($type == $messageType) {
                $notExistsMessage = false;
                $mapper->textMessage = $text;
                $mapper->textMessageVariables = $variables;

                if ($referenceColumn !== null || $referenceValue !== null || $referenceSource !== null) {
                    $mapper->referenceColumn = $referenceColumn;
                    $mapper->referenceValue = $referenceValue;
                    $mapper->referenceSource = $referenceSource;
                }

                $mapper->save();
                break;
            }

        }

        if ($notExistsMessage) {
            throw new \Exception(__(self::LANG_GROUP, "El tipo de mensaje '{$messageType}' no existe."));
        }
        return $mapper;
    }

    /**
     * Campos adicionales:
     * - idPadding
     * - createdByUser
     * - textMessageReplacement
     * - createdAtFormat
     * - ip
     * - geolocationByIp
     * - moduleNameFromSource
     * - moduleName
     * @return string[]
     */
    protected static function fieldsToSelect()
    {

        $mapper = (new LogsMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableUser = UsersModel::TABLE;

        $createdAtFormatReplacements = json_encode([
            '{1}' => __(self::LANG_GROUP, 'de'),
        ], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
        $moduleNameBySourceJSON = json_encode((object) self::MODULE_NAMES_EQUIVALENCES_BY_SOURCE, \JSON_UNESCAPED_UNICODE);

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "(SELECT {$tableUser}.username FROM {$tableUser} WHERE {$tableUser}.id = {$table}.createdBy) AS createdByUser",
            "strTemplateReplace({$table}.textMessage, {$table}.textMessageVariables) AS textMessageReplacement",
            "strTemplateReplace(DATE_FORMAT({$table}.createdAt, '%M %d {1} %Y %h:%i:%s %p'), '{$createdAtFormatReplacements}') AS createdAtFormat",
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.ip')) AS ip",
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.geolocationByIp')) AS geolocationByIp",
            "JSON_UNQUOTE(JSON_EXTRACT('{$moduleNameBySourceJSON}', CONCAT('$.', {$table}.referenceSource))) AS moduleNameFromSource",
            "(SELECT IF(moduleNameFromSource IS NULL, {$table}.referenceSource, moduleNameFromSource)) AS moduleName",
        ];

        $allFields = array_keys(self::getFields());

        foreach ($allFields as $field) {
            $fields[] = "{$table}.{$field}";
        }

        return $fields;

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
     * @return LogsMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new LogsMapper;
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass ? $value : @json_decode($value);

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

        $allFilled = count($fieldsFilleds) === count($fields);

        return $allFilled ? $mapper : null;

    }

    /**
     * @return bool
     */
    public static function jsonExtractExistsMySQL()
    {

        try {

            $json = [
                'ok' => true,
            ];
            $json = json_encode($json);
            $sql = "SELECT JSON_EXTRACT('{$json}'" . ', \'$.test\')';
            $prepared = self::model()->prepare($sql);
            $prepared->execute();
            return true;

        } catch (\PDOException $e) {

            if ($e->getCode() == 1305 || $e->getCode() == 42000) {
                return false;
            } else {
                throw $e;
            }

        }

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new LogsMapper)->getModel();
    }
}
