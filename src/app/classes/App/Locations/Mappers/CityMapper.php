<?php

/**
 * CityMapper.php
 */

namespace App\Locations\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * CityMapper.
 *
 * Mapper de estados
 *
 * @package     App\Locations\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int|null $id
 * @property string|null $code
 * @property int|StateMapper $state
 * @property string $name
 * @property int $active
 */
class CityMapper extends BaseEntityMapper
{
    const PREFIX_TABLE = 'locations_';
    const TABLE = 'cities';

    const ACTIVE = 1;
    const INACTIVE = 0;

    const STATUS = [
        self::ACTIVE => 'Activa',
        self::INACTIVE => 'Inactiva',
    ];

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'code' => [
            'type' => 'varchar',
            'null' => true,
        ],
        'state' => [
            'type' => 'int',
            'reference_table' => StateMapper::PREFIX_TABLE . StateMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'name',
            'mapper' => StateMapper::class,
        ],
        'name' => [
            'type' => 'varchar',
        ],
        'active' => [
            'type' => 'int',
            'default' => self::ACTIVE,
        ],
    ];

    /**
     * @var string
     */
    protected $table = self::PREFIX_TABLE . self::TABLE;

    /**
     * @param int $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'id')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * Campos extra:
     *  - idPadding
     *  - stateName
     *  - countryID
     *  - countryName
     * @return string[]
     */
    protected static function fieldsToSelect()
    {

        $mapper = new CityMapper();

        $table = self::PREFIX_TABLE . self::TABLE;
        $tableState = StateMapper::PREFIX_TABLE . StateMapper::TABLE;
        $tableCountry = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;

        $stateNameQuery = "(SELECT {$tableState}.name FROM {$tableState} WHERE {$tableState}.id = {$table}.state)";
        $countryIDQuery = "(SELECT {$tableState}.country FROM {$tableState} WHERE {$tableState}.id = state)";
        $countryNameQuery = "(SELECT {$tableCountry}.name FROM {$tableCountry} WHERE {$tableCountry}.id = countryID)";

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "{$stateNameQuery} AS stateName",
            "{$countryIDQuery} AS countryID",
            "{$countryNameQuery} AS countryName",
        ];

        $allFields = array_keys($mapper->getFields());

        foreach ($allFields as $field) {
            $fields[] = "{$table}.{$field}";
        }

        return $fields;

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
            $result = new CityMapper($result->id);
        }

        return $result;
    }

    /**
     * @param int $state_id
     * @param bool $as_mapper
     * @return array|static[]
     */
    public static function getByState(int $state_id, bool $as_mapper = false)
    {

        $query = self::model()->select();

        $query->where([
            'state' => $state_id,
        ]);

        $query->execute();

        $result = $query->result();
        $result = is_array($result) ? $result : [];

        if ($as_mapper) {
            $result = array_map(function ($i) {
                return new CityMapper($i->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @param string $name
     * @param bool $as_mapper
     * @param int $state_id
     * @return array|static[]
     */
    public static function getByName(string $name, bool $as_mapper = false, int $state_id = null)
    {

        $query = self::model()->select();

        $where = [];

        $where['name'] = trim($name);

        if ($state_id !== null) {
            $where['state'] = $state_id;
        }

        $query->where($where);

        $query->execute();

        $result = $query->result();
        $result = is_array($result) ? $result : [];

        if ($as_mapper) {
            $result = array_map(function ($i) {
                return new CityMapper($i->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @param bool $as_mapper
     * @param bool $onlyActives
     * @param int $stateID
     * @return static[]|array
     */
    public static function all(bool $as_mapper = false, bool $onlyActives = false, int $stateID = null)
    {

        $model = self::model();
        $table = self::PREFIX_TABLE . self::TABLE;

        $model->select(self::fieldsToSelect());

        $whereString = null;
        $where = [];
        $and = 'AND';

        if ($stateID != null) {
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$table}.state = {$stateID}";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if ($onlyActives != null) {
            $beforeOperator = !empty($where) ? $and : '';
            $activeValue = self::ACTIVE;
            $critery = "{$table}.active = {$activeValue}";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
            $model->where($whereString);
        }

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($as_mapper) {
            $result = array_map(function ($e) {
                return new CityMapper($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @param string $defaultLabel
     * @param string $defaultValue
     * @param int $stateID
     * @return array
     */
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '', int $stateID = null)
    {
        $defaultLabel = mb_strlen($defaultLabel) > 0 ? $defaultLabel : __(LOCATIONS_LANG_GROUP, 'Ciudades');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            $options[$e->id] = $e->name;
        }, self::all(false, true, $stateID));

        return $options;
    }

    /**
     * @param string $name
     * @param int $state_id
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicateName(string $name, int $state_id, int $ignore_id)
    {
        $model = self::model();
        $name = escapeString($name);

        $where = trim(implode(' ', [
            "name = '$name' AND ",
            "state = $state_id AND ",
            "id != $ignore_id",
        ]));

        $model->select()->where($where)->execute();

        $result = $model->result();

        return !empty($result);
    }

    /**
     * @param string $code
     * @param int $state_id
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicateCode(string $code = null, int $state_id, int $ignore_id)
    {

        if ($code !== null) {

            $model = self::model();
            $code = escapeString($code);

            $where = trim(implode(' ', [
                "code = '$code' AND ",
                "state = $state_id AND ",
                "id != $ignore_id",
            ]));

            $model->select()->where($where)->execute();

            $result = $model->result();

            return !empty($result);

        } else {

            return false;

        }

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new CityMapper)->getModel();
    }

}
