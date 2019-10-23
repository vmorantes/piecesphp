<?php
/**
 * ArticleMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Mappers;

use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryContentMapper;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
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
     * getBasicData
     *
     * @return \stdClass
     */
    public function getBasicData()
    {
        $data = (object) $this->humanReadable();
        $data->preferDate = $this->getPreferDate(self::TYPE_PREFER_DATE_DATETIME);
        $data->author = $this->author->getPublicData();
        $data->meta = $this->meta;
        $data->category = CategoryContentMapper::getByPreferedsIDsAndContenOf($this->category->id)->humanReadable();
        $data->created = $this->created;
        $data->start_date = $this->start_date;
        $data->end_date = $this->end_date;
        $data->link = $this->getSingleURL();
        return $data;
    }

    /**
     * getSingleURL
     *
     * @return string
     */
    public function getSingleURL()
    {
        return ArticleControllerPublic::routeName('single', ['friendly_name' => $this->friendly_url]);
    }

    /**
     * getPreferDate
     *
     * @param string $formatOutput
     * @return \DateTime|string|int
     */
    public function getPreferDate(string $formatOutput = 'DATETIME')
    {
        $date = !is_null($this->start_date) ? $this->start_date : $this->created;
        $value = $date;

        switch ($formatOutput) {
            case self::TYPE_PREFER_DATE_DAY_NUMBER:
                $value = $date->format('d');
                break;
            case self::TYPE_PREFER_DATE_DAY_NAME:
                $value = __('day', $date->format('w'));
                break;
            case self::TYPE_PREFER_DATE_MONTH_NUMBER:
                $value = $date->format('m');
                break;
            case self::TYPE_PREFER_DATE_MONTH_NAME:
                $value = __('month', (string) ($date->format('n') - 1));
                break;
            case self::TYPE_PREFER_DATE_YEAR:
                $value = $date->format('Y');
                break;
            case self::TYPE_PREFER_DATE_DATETIME:
            default:
                $value = $date;
                break;
        }

        return $value;
    }

    /**
     * formatPreferDate
     *
     * @param string $format
     * @return string
     */
    public function formatPreferDate(string $format = '{DAY_NAME}, {DAY_NUMBER} de {MONTH_NAME}, {YEAR}')
    {

        $pattern = [
            self::TYPE_PREFER_DATE_DAY_NUMBER,
            self::TYPE_PREFER_DATE_DAY_NAME,
            self::TYPE_PREFER_DATE_MONTH_NUMBER,
            self::TYPE_PREFER_DATE_MONTH_NAME,
            self::TYPE_PREFER_DATE_YEAR,
        ];
        $replace = [
            $this->getPreferDate(self::TYPE_PREFER_DATE_DAY_NUMBER),
            $this->getPreferDate(self::TYPE_PREFER_DATE_DAY_NAME),
            $this->getPreferDate(self::TYPE_PREFER_DATE_MONTH_NUMBER),
            $this->getPreferDate(self::TYPE_PREFER_DATE_MONTH_NAME),
            $this->getPreferDate(self::TYPE_PREFER_DATE_YEAR),
        ];

        $formated = str_replace($pattern, $replace, $format);

        return $formated;
    }

    /**
     * addVisit
     *
     * @return static
     */
    public function addVisit()
    {
        $meta = $this->meta;
        $meta->visits += 1;
        $this->meta = $meta;
        $this->update();
        return $this;
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
        $model->orderBy('id DESC');

        if ($onlyDateRange) {

            $now = date('Y-m-d H:i:s');

            $where = [
                "(start_date <= '{$now}' OR start_date IS NULL) AND",
                "(end_date > '{$now}' OR end_date IS NULL)",
            ];
            $where = implode(' ', $where);

            $model->where($where);

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
     * allByDateOrder
     *
     * @param bool $as_mapper
     * @param bool $onlyDateRange
     * @param int $page
     * @param int $perPage
     *
     * @return static[]|array
     */
    public static function allByDateOrder(bool $as_mapper = false, bool $onlyDateRange = true, int $page = null, $perPage = null)
    {
        $model = self::model();

        $model->select();
        $model->orderBy('start_date DESC, end_date DESC, created DESC');

        if ($onlyDateRange) {

            $now = date('Y-m-d H:i:s');

            $where = [
                "(start_date <= '{$now}' OR start_date IS NULL) AND",
                "(end_date > '{$now}' OR end_date IS NULL)",
            ];
            $where = implode(' ', $where);

            $model->where($where);

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
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '', bool $onlyDateRange = true)
    {

        $defaultLabel = is_string($defaultLabel) && strlen($defaultLabel) > 0 ? $defaultLabel : __('articlesBackend', 'Artículos');
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

            $where = [
                "category = '{$category}' AND",
                "(start_date <= '{$now}' OR start_date IS NULL) AND",
                "(end_date > '{$now}' OR end_date IS NULL)",
            ];
            $where = implode(' ', $where);

        }

        if (!is_null($excludeID)) {
            $where .= " AND id != {$excludeID}";
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

            $where = [
                "friendly_url = '{$friendly_url}' AND",
                "(start_date <= '{$now}' OR start_date IS NULL) AND",
                "(end_date > '{$now}' OR end_date IS NULL)",
            ];
            $where = implode(' ', $where);

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

            $value = is_string($value) ? "'{$value}'" : $value;

            $where = [
                "{$column} = {$value} AND",
                "(start_date <= '{$now}' OR start_date IS NULL) AND",
                "(end_date > '{$now}' OR end_date IS NULL)",
            ];

            $where = implode(' ', $where);

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
     * @param int $maxWords
     * @return string
     */
    public static function generateFriendlyURL(string $name, int $ignore_id, int $maxWords = null)
    {
        $baseFriendlyURL = friendly_url($name);
        $friendlyURL = $baseFriendlyURL;
        $countFriendlyURL = self::friendlyURLCount($baseFriendlyURL, $ignore_id);
        $num = 1;

        while ($countFriendlyURL > 0) {
            $friendlyURL = $baseFriendlyURL . '-' . $num;
            $countFriendlyURL = self::friendlyURLCount($friendlyURL, $ignore_id);
            $num++;
        }

        return $friendlyURL;
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
