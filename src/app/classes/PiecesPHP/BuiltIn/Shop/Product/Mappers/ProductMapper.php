<?php

/**
 * ProductMapper.php
 */

namespace PiecesPHP\BuiltIn\Shop\Product\Mappers;

use PiecesPHP\BuiltIn\Shop\Brand\Mappers\BrandMapper;
use PiecesPHP\BuiltIn\Shop\Category\Mappers\CategoryMapper;
use PiecesPHP\BuiltIn\Shop\SubCategory\Mappers\SubCategoryMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;

/**
 * ProductMapper.
 *
 * @package     PiecesPHP\BuiltIn\Shop\Product\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 * @property int $id
 * @property string $name
 * @property string $reference_code
 * @property double $price
 * @property int|BrandMapper $brand
 * @property int|CategoryMapper $category
 * @property int|SubCategoryMapper|null $subcategory
 * @property int $warranty_duration
 * @property int $warranty_measure
 * @property string $description
 * @property string $main_image
 * @property string[] $images
 * @property \stdClass|string|null $meta
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class ProductMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'name' => [
            'type' => 'text',
        ],
        'reference_code' => [
            'type' => 'text',
        ],
        'price' => [
            'type' => 'double',
        ],
        'brand' => [
            'type' => 'int',
            'reference_table' => BrandMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => BrandMapper::class,
        ],
        'category' => [
            'type' => 'int',
            'reference_table' => CategoryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => CategoryMapper::class,
        ],
        'subcategory' => [
            'type' => 'int',
            'reference_table' => SubCategoryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => SubCategoryMapper::class,
            'null' => true,
        ],
        'warranty_duration' => [
            'type' => 'int',
        ],
        'warranty_measure' => [
            'type' => 'int',
        ],
        'description' => [
            'type' => 'text',
            'null' => true,
            'dafault' => '',
        ],
        'main_image' => [
            'type' => 'text',
        ],
        'images' => [
            'type' => 'json',
            'dafault' => [],
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
        'created_at' => [
            'type' => 'datetime',
        ],
        'updated_at' => [
            'type' => 'datetime',
        ],
    ];

    const TABLE = 'pcsphp_shop_products';
    const LANG_GROUP = 'bi-shop-products';

    const WARRANTY_ON_DAY = 1;
    const WARRANTY_ON_WEEK = 2;
    const WARRANTY_ON_MONTH = 3;
    const WARRANTY_ON_YEAR = 4;
    const WARRANTY_NO = 5;

    const WARRANTY_MEASURES = [
        self::WARRANTY_ON_DAY => 'Día(s)',
        self::WARRANTY_ON_WEEK => 'Semana(s)',
        self::WARRANTY_ON_MONTH => 'Mes(es)',
        self::WARRANTY_ON_YEAR => 'Año(s)',
    ];

    /**
     * $table
     *
     * @var string
     */
    protected $table = self::TABLE;
    /**
     * $categories
     *
     * @var CategoryMapper[]
     */
    protected $categories = [];
    /**
     * $subcategories
     *
     * @var SubCategoryMapper[]
     */
    protected $subcategories = [];

    /**
     * __construct
     *
     * @param int $value
     * @param string $fieldCompare
     * @return static
     */
    public function __construct(int $value = null, string $fieldCompare = 'primary_key')
    {

        parent::__construct($value, $fieldCompare);

    }

    /**
     * warrantyMeasures
     *
     * @return array
     */
    public static function warrantyMeasures()
    {

        $langValues = [];

        foreach (self::WARRANTY_MEASURES as $key => $text) {

            $langValues[$key] = __(self::LANG_GROUP, $text);

        }

        return $langValues;

    }

    /**
     * all
     *
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function all(bool $asMapper = false)
    {
        $model = self::model();

        $model->select()->execute();

        $result = $model->result();

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * allByCategory
     *
     * @param int $categoryID
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function allByCategory(int $categoryID, bool $asMapper = false)
    {
        $table = self::TABLE;

        $model = self::model();

        $selectSegment = [
            "{$table}.*",
        ];

        $selectSegment = implode(' ', $selectSegment);

        $whereSegment = [
            "{$table}.category = {$categoryID}",
        ];

        $whereSegment = implode(' ', $whereSegment);

        $sql = "SELECT {$selectSegment} FROM {$table} WHERE {$whereSegment}";

        $preparedStatement = $model->prepare($sql);

        $preparedStatement->execute();

        $result = $preparedStatement->fetchAll(\PDO::FETCH_OBJ);

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * allBySubcategory
     *
     * @param int $subcategoryID
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function allBySubcategory(int $subcategoryID, bool $asMapper = false)
    {
        $table = self::TABLE;

        $model = self::model();

        $selectSegment = [
            "{$table}.*",
        ];

        $selectSegment = implode(' ', $selectSegment);

        $whereSegment = [
            "{$table}.subcategory = {$subcategoryID}",
        ];

        $whereSegment = implode(' ', $whereSegment);

        $sql = "SELECT {$selectSegment} FROM {$table} WHERE {$whereSegment}";

        $preparedStatement = $model->prepare($sql);

        $preparedStatement->execute();

        $result = $preparedStatement->fetchAll(\PDO::FETCH_OBJ);

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * allByBrand
     *
     * @param int $brandID
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function allByBrand(int $brandID, bool $asMapper = false)
    {

        $model = self::model();

        $model->select()->where([
            'brand' => $brandID,
        ])->execute();

        $result = $model->result();

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * allBy
     *
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

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * getBy
     *
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

        $result = count($result) > 0 ? $result[0] : null;

        if (!is_null($result) && $as_mapper) {
            $result = new static($result->id);
        }

        return $result;
    }

    /**
     * allForSelect
     *
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Productos');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {

            $value = $e->name . ' - ' . $e->reference_code;
            $options[$e->id] = $value;

        }, self::all());

        return $options;
    }

    /**
     * existsByID
     *
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

        return count($result) > 0;
    }

    /**
     * existsByReferenceCode
     *
     * @param string $referenceCode
     * @param int $ignoreID
     * @return bool
     */
    public static function existsByReferenceCode(string $referenceCode, int $ignoreID = -1)
    {
        $model = self::model();

        $referenceCode = \addslashes(\stripslashes($referenceCode));

        $where = [
            "reference_code = '$referenceCode'",
            "AND id != $ignoreID",
        ];
        $where = trim(implode(' ', $where));

        $model->select()->where($where);

        $model->execute();

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
