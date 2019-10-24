<?php
/**
 * ArticleContentMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Mappers;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * ArticleContentMapper.
 *
 * @package     PiecesPHP\BuiltIn\Article\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|ArticleMapper $content_of
 * @property string $lang
 * @property string $title
 * @property string $friendly_url
 * @property string $content
 * @property string $seo_description
 * @property array|object|null $meta
 */
class ArticleContentMapper extends BaseEntityMapper
{
    const TABLE = 'pcsphp_articles_content';

    const TYPE_PREFER_DATE_DATETIME = '{DATETIME}';
    const TYPE_PREFER_DATE_DAY_NUMBER = '{DAY_NUMBER}';
    const TYPE_PREFER_DATE_DAY_NAME = '{DAY_NAME}';
    const TYPE_PREFER_DATE_MONTH_NUMBER = '{MONTH_NUMBER}';
    const TYPE_PREFER_DATE_MONTH_NAME = '{MONTH_NAME}';
    const TYPE_PREFER_DATE_YEAR = '{YEAR}';

    /**
     * @var string $table
     */
    protected $table = self::TABLE;

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'content_of' => [
            'type' => 'int',
            'reference_table' => ArticleMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => ArticleMapper::class,
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'lang' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'friendly_url' => [
            'type' => 'text',
        ],
        'content' => [
            'type' => 'text',
        ],
        'seo_description' => [
            'type' => 'text',
            'null' => true,
            'default' => '',
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
        ],
    ];
    /**
     * __construct
     *
     * @param int $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'primary_key')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * model
     *
     * @return \PiecesPHP\Core\BaseModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }
}
