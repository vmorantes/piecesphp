<?php

/**
 * CountryMapper.php
 */

namespace App\Locations\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;

/**
 * CountryMapper.
 *
 * Mapper de países
 *
 * @package     App\Locations\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int|null $id
 * @property string|null $code
 * @property string $name
 * @property string $region
 * @property int $active
 */
class CountryMapper extends BaseEntityMapper
{
    const PREFIX_TABLE = 'locations_';
    const TABLE = 'countries';

    const ACTIVE = 1;
    const INACTIVE = 0;

    const STATUS = [
        self::ACTIVE => 'Activo',
        self::INACTIVE => 'Inactivo',
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
        'name' => [
            'type' => 'varchar',
        ],
        'region' => [
            'type' => 'text',
            'null' => true,
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
     * @return string[]
     */
    protected static function fieldsToSelect()
    {

        $mapper = new CountryMapper();

        $table = self::PREFIX_TABLE . self::TABLE;

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
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
            $result = new CountryMapper($result->id);
        }

        return $result;
    }

    /**
     * @param bool $as_mapper
     * @param bool $onlyActives
     * @return static[]|array
     */
    public static function all(bool $as_mapper = false, bool $onlyActives = false)
    {
        $model = self::model();

        $model->select(self::fieldsToSelect());

        if ($onlyActives) {
            $model->where([
                'active' => self::ACTIVE,
            ]);
        }

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($as_mapper) {
            $result = array_map(function ($e) {
                return new CountryMapper($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @param string $column
     * @param string $critery
     * @param bool $asMapper
     * @param bool $onlyActives
     * @return static[]|array
     */
    public static function allBy(string $column, string $critery, bool $asMapper = false, bool $onlyActives = false)
    {
        $model = self::model();

        $model->select(self::fieldsToSelect());
        $where = [
            $column => $critery,
        ];

        if ($onlyActives) {
            $where['active'] = self::ACTIVE;
        }

        $model->where($where);
        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new CountryMapper($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @param string[] $regions
     * @param bool $asMapper
     * @param bool $onlyActives
     * @return static[]|array
     */
    public static function allByRegions(array $regions = [], bool $asMapper = false, bool $onlyActives = false)
    {
        $model = self::model();

        $model->select(self::fieldsToSelect());
        $regions[] = 'NONE';
        $regionsForFindInSet = implode(',', $regions);
        $whereSegment = new WhereSegment([
            WhereItem::findInSet($regionsForFindInSet, 'region', true),
        ]);

        if ($onlyActives) {
            $whereSegment->addCritery(new WhereItem('active', WhereItem::EQUAL_OPERATOR, self::ACTIVE, WhereItem::AND_OPERATOR));
        }

        $model->where($whereSegment);
        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new CountryMapper($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @return \stdClass[]
     */
    public static function allRegions()
    {
        $model = self::model();
        $prepared = $model->prepare("SELECT region AS name FROM locations_countries GROUP BY region ORDER BY region ASC");
        $prepared->execute();
        return $prepared->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = mb_strlen($defaultLabel) > 0 ? $defaultLabel : __(LOCATIONS_LANG_GROUP, 'Países');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            $options[$e->id] = $e->name;
        }, self::all(false, true));

        return $options;
    }

    /**
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allRegionsForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = mb_strlen($defaultLabel) > 0 ? $defaultLabel : __(LOCATIONS_LANG_GROUP, 'Regiones');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            $options[$e->name] = $e->name;
        }, self::allRegions(false, true));

        return $options;
    }

    /**
     * @param string $name
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicateName(string $name, int $ignore_id)
    {
        $model = self::model();
        $name = escapeString($name);

        $where = trim(implode(' ', [
            "name = '$name' AND ",
            "id != $ignore_id",
        ]));

        $model->select()->where($where)->execute();

        $result = $model->result();

        return !empty($result);
    }

    /**
     * @param string $code
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicateCode(string $code = null, int $ignore_id)
    {

        if ($code !== null) {

            $model = self::model();
            $code = escapeString($code);

            $where = trim(implode(' ', [
                "code = '$code' AND ",
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
        return (new CountryMapper)->getModel();
    }

}
