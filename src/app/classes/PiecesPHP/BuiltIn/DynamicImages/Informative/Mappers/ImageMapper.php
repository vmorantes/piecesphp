<?php

/**
 * ImageMapper.php
 */

namespace PiecesPHP\BuiltIn\DynamicImages\Informative\Mappers;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;

/**
 * ImageMapper.
 *
 * @package     PiecesPHP\BuiltIn\DynamicImages\Informative\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $link
 * @property string $image
 * @property \stdClass|string|null $meta
 */
class ImageMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'title' => [
            'type' => 'text',
        ],
        'description' => [
            'type' => 'text',
            'null' => true,
            'dafault' => '',
        ],
        'link' => [
            'type' => 'text',
            'null' => true,
            'dafault' => '',
        ],
        'image' => [
            'type' => 'text',
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const TABLE = 'pcsphp_dynamic_images';
    const LANG_GROUP = 'bi-dynamic-images-hero';

    /**
     * $table
     *
     * @var string
     */
    protected $table = self::TABLE;

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
     * hasDescription
     *
     * @return bool
     */
    public function hasDescription()
    {
        return count(trim($this->description)) > 0;
    }

    /**
     * hasLink
     *
     * @return bool
     */
    public function hasLink()
    {
        return count(trim($this->link)) > 0;
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
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Imágenes');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {

            $value = $e->title;
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
     * model
     *
     * @return BaseModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }
}
