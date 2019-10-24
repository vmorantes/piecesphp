<?php
/**
 * ArticleMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Mappers;

use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper;
use PiecesPHP\Core\BaseEntityMapper;

/**
 * ArticleMapper.
 *
 * @package     PiecesPHP\BuiltIn\Article\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|UsersModel $author
 * @property int|CategoryMapper $category
 * @property array|object|null $images
 * @property string $folder
 * @property int $visits
 * @property string|\DateTime|null $start_date
 * @property string|\DateTime|null $end_date
 * @property string|\DateTime $created
 * @property string|\DateTime $updated
 */
class ArticleMapper extends BaseEntityMapper
{
    const TABLE = 'pcsphp_articles';

    /**
     * @var string $table
     */
    protected $table = self::TABLE;

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
        'category' => [
            'type' => 'int',
            'reference_table' => CategoryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'id',
            'mapper' => CategoryMapper::class,
        ],
        'images' => [
            'type' => 'json',
            'null' => true,
        ],
        'folder' => [
            'type' => 'text',
        ],
        'visits' => [
            'type' => 'int',
        ],
        'start_date' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'end_date' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'created' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
        'updated' => [
            'type' => 'datetime',
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
     * @inheritDoc
     */
    public function save()
    {
        $current_user = get_config('current_user');
        if ($current_user !== false) {
            $this->author = $current_user->id;
        }
        return parent::save();
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        $this->updated = new \DateTime();
        return parent::update();
    }

    /**
     * isDuplicate
     *
     * @param int $category
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicate(int $category, int $ignore_id)
    {
        $model = self::model();

        $where = [
            "id != $ignore_id",
            "AND category = $category",
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
     * @return \PiecesPHP\Core\BaseModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }
}
