<?php

/**
 * CountryMapper.php
 */

namespace App\Locations\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseModel;

/**
 * CountryMapper.
 *
 * Mapper de países
 *
 * @package     App\Locations\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property string $name
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
        'name' => [
            'type' => 'varchar',
        ],
        'active' => [
            'type' => 'int',
            'default' => self::ACTIVE,
        ],
    ];

    /**
     * $table
     *
     * @var string
     */
    protected $table = self::PREFIX_TABLE . self::TABLE;

    /**
     * __construct
     *
     * @param int $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'id')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * isDuplicate
     *
     * @param string $name
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicate(string $name, int $ignore_id)
    {
        $model = self::model();
        $name = \stripslashes($name);
        $name = \addslashes($name);

        $where = trim(implode(' ', [
            "name = '$name' AND ",
            "id != $ignore_id",
        ]));

        $model->select()->where($where)->execute();

        $result = $model->result();

        return count($result) > 0;
    }

    /**
     * model
     *
     * @return BaseModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }

}
