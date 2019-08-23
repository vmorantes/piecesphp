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
 * @property string $title
 * @property string $friendly_url
 * @property string $content
 * @property array|object|null $meta
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
            'human_readable_reference_field' => 'name',
            'mapper' => CategoryMapper::class,
        ],
        'title' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'friendly_url' => [
            'type' => 'text',
        ],
        'content' => [
            'type' => 'text',
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
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
     * all
     *
     * @param bool $as_mapper
     * @param bool $onlyDateRange
     * @param int $page
     * @param int $perPage
     *
     * @return static[]|array
     */
    public static function all(bool $as_mapper = false, bool $onlyDateRange = true, int $page = null, $perPage = null)
    {
        $model = self::model();

        $model->select();

        if ($onlyDateRange) {

            $now = date('Y-m-d H:i:s');
            $model->where([
                'start_date' => [
                    '<=' => $now,
                ],
                'end_date' => [
                    '>' => $now,
                ],
            ]);

        }

        $model->execute(false, $page, $perPage);

        $result = $model->result();

        if ($as_mapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * allForSelect
     *
     * @param string $defaultLabel
     * @param string $defaultValue
     * @param bool $onlyDateRange
     * @return array
     */
    public static function allForSelect(string $defaultLabel = 'Artículos', string $defaultValue = '', bool $onlyDateRange = true)
    {
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            $options[$e->id] = $e->title;
        }, self::all(false, $onlyDateRange));

        return $options;
    }

    /**
     * allByCategory
     *
     * @param int $friendly_url
     * @param bool $as_mapper
     * @param int $excludeID
     * @param bool $onlyDateRange
     * @param int $page
     * @param int $perPage
     * @return object|static[]
     */
    public static function allByCategory(int $category, bool $as_mapper = false, int $excludeID = null, bool $onlyDateRange = true, int $page = null, $perPage = null)
    {
        $model = self::model();

        $where = [
            "category" => $category,
        ];

        if ($onlyDateRange) {

            $now = date('Y-m-d H:i:s');
            $where['start_date'] = [
                '<=' => $now,
            ];
            $where['end_date'] = [
                '>' => $now,
            ];

        }

        if (!is_null($excludeID)) {
            $where['id'] = [
                '!=' => $excludeID,
            ];
        }

        $model->select()->where($where);

        $model->execute(false, $page, $perPage);

        $result = $model->result();

        if ($as_mapper) {
            $result = array_map(function ($e) {
                return new static($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * getByFriendlyURL
     *
     * @param string $friendly_url
     * @param bool $as_mapper
     * @param bool $onlyDateRange
     * @return object|static|null
     */
    public static function getByFriendlyURL(string $friendly_url, bool $as_mapper = false, bool $onlyDateRange = true)
    {
        $model = self::model();

        $where = [
            "friendly_url" => $friendly_url,
        ];

        if ($onlyDateRange) {

            $now = date('Y-m-d H:i:s');
            $where['start_date'] = [
                '<=' => $now,
            ];
            $where['end_date'] = [
                '>' => $now,
            ];

        }

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        if (count($result) > 0) {
            $result = $result[0];
            if ($as_mapper) {
                $result = new static($result->id);
            }
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * getBy
     *
     * @param mixed $value
     * @param string $column
     * @param boolean $as_mapper
     * @param bool $onlyDateRange
     * @return static|object|null
     */
    public static function getBy($value, string $column = 'id', bool $as_mapper = false, bool $onlyDateRange = true)
    {
        $model = self::model();

        $where = [
            $column => $value,
        ];

        if ($onlyDateRange) {

            $now = date('Y-m-d H:i:s');
            $where['start_date'] = [
                '<=' => $now,
            ];
            $where['end_date'] = [
                '>' => $now,
            ];

        }

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
     * existsByFriendlyURL
     *
     * @param string $friendly_url
     * @return bool
     */
    public static function existsByFriendlyURL(string $friendly_url)
    {
        $model = self::model();

        $where = [
            "friendly_url = '$friendly_url'",
        ];
        $where = trim(implode(' ', $where));

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return count($result) > 0;
    }

    /**
     * isDuplicate
     *
     * @param string $title
     * @param string $friendly_url
     * @param int $category
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicate(string $title, string $friendly_url, int $category, int $ignore_id)
    {
        $model = self::model();

        $where = [
            "(title = '$title'",
            "OR friendly_url = '$friendly_url')",
            "AND id != $ignore_id",
            "AND category = $category",
        ];
        $where = trim(implode(' ', $where));

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return count($result) > 0;
    }

    /**
     * friendlyURLCount
     *
     * @param string $friendly_url
     * @param int $ignore_id
     * @return bool
     */
    public static function friendlyURLCount(string $friendly_url, int $ignore_id)
    {

        $model = self::model();

        $where = [
            'friendly_url' => $friendly_url,
            'id' => [
                '!=' => $ignore_id,
            ],
        ];

        $model->select('COUNT(id) AS total')->where($where)->execute();

        $result = $model->result();

        return count($result) > 0 ? (int) $result[0]->total : 0;
    }

    /**
     * generateFriendlyURL
     *
     * @param string $friendly_url
     * @param int $ignore_id
     * @return string
     */
    public static function generateFriendlyURL(string $name, int $ignore_id)
    {
        $friendly_url = friendly_url($name);
        $count_friendly_url = self::friendlyURLCount($friendly_url, $ignore_id);

        if ($count_friendly_url > 0) {
            $friendly_url = $friendly_url . '-' . $count_friendly_url;
        }

        return $friendly_url;
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
