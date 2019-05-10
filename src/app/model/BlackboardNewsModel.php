<?php
/**
 * BlackboardNewsModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * BlackboardNewsModel.
 *
 * Modelo de tablón de noticias
 *
 * @package     App\Model
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class BlackboardNewsModel extends BaseEntityMapper
{
    const TABLE = 'pcsphp_blackboard_news_messages';

    protected static $where = [];

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'author' => [
            'type' => 'int',
            'reference_table' => 'pcsphp_users',
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'text' => [
            'type' => 'text',
        ],
        'type' => [
            'type' => 'int',
        ],
        'start_date' => [
            'type' => 'date',
            'null' => true,
        ],
        'end_date' => [
            'type' => 'date',
            'null' => true,
        ],
        'created_date' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
    ];

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
     * @param string $field_compare
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'primary_key')
    {
        parent::__construct($value, $field_compare);
    }

}
