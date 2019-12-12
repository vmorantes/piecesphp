<?php
/**
 * ArticleViewMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Mappers;

use App\Model\UsersModel;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryContentMapper;
use PiecesPHP\BuiltIn\Article\Category\Mappers\CategoryMapper;
use PiecesPHP\BuiltIn\Article\Controllers\ArticleControllerPublic;
use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Config;

/**
 * ArticleViewMapper.
 *
 * @package     PiecesPHP\BuiltIn\Article\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int $sub_id
 * @property int|UsersModel $author
 * @property int|CategoryMapper $category
 * @property string $lang
 * @property string $title
 * @property string $friendly_url
 * @property string $content
 * @property string $seo_description
 * @property string $folder
 * @property int $visits
 * @property array|object|null $images
 * @property array|object|null $meta
 * @property string|\DateTime|null $start_date
 * @property string|\DateTime|null $end_date
 * @property string|\DateTime $created
 * @property string|\DateTime $updated
 */
class ArticleViewMapper extends BaseEntityMapper
{
    const TABLE = 'pcsphp_articles_view';

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

    /**
     * $article
     *
     * @var ArticleMapper
     */
    protected $article = null;
    /**
     * $subArticle
     *
     * @var ArticleContentMapper
     */
    protected $subArticle = null;

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'sub_id' => [
            'type' => 'int',
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
        'lang' => [
            'type' => 'varchar',
            'length' => 255,
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
        'seo_description' => [
            'type' => 'text',
            'null' => true,
            'default' => '',
        ],
        'folder' => [
            'type' => 'text',
        ],
        'visits' => [
            'type' => 'int',
        ],
        'images' => [
            'type' => 'json',
            'null' => true,
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
     * @return static
     */
    public function __construct(int $value = null)
    {
        parent::__construct($value, 'sub_id');
        if ($this->id !== null) {
            $this->article = new ArticleMapper($this->id);
            $this->subArticle = new ArticleMapper($this->sub_id);
        }
    }

    /**
     * addVisit
     *
     * @return static
     */
    public function addVisit()
    {
        if ($this->id !== null) {
            $this->article->visits += 1;
            $this->article->update();
        }
        return $this;
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
        $data->images = $this->images;
        $data->category = (object) CategoryContentMapper::getByPreferedsIDsAndContenOf($this->category->id)->humanReadable();
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
     * save
     *
     * El método está desactivado puesto que es una vista
     *
     * @return void
     */
    public function save()
    {
    }

    /**
     * update
     *
     * El método está desactivado puesto que es una vista
     *
     * @return void
     */
    public function update()
    {
    }

    /**
     * getAlternatives
     *
     * @return \stdClass[]
     */
    public function getAlternatives()
    {

        $model = self::model();

        $model->select([
            'title',
            'lang',
            'friendly_url',
        ])->where([
            'id' => $this->id,
            'sub_id' => [
                '!=' => $this->sub_id,
            ],
        ])->orderBy("FIELD(lang, '" . implode("', '", Config::get_allowed_langs()) . "') ASC");

        $model->execute();

        return $model->result();

    }

    /**
     * getURLAlternatives
     *
     * @param bool $onlyURLs
     *
     * @return string[]|\stdClass[]
     */
    public function getURLAlternatives(bool $onlyURLs = true)
    {

        $alternatives = $this->getAlternatives();
        $currentLang = Config::get_lang();
        $allowedLangs = Config::get_allowed_langs();
        $urls = [];

        foreach ($alternatives as $alt) {

            if (in_array($alt->lang, $allowedLangs)) {

                $url = ArticleControllerPublic::routeName('single', ['friendly_name' => $alt->friendly_url]);
                $url = convert_lang_url($url, $currentLang, $alt->lang);

                if ($onlyURLs) {

                    $urls[$alt->lang] = $url;

                } else {

                    $obj = new \stdClass;
                    $obj->title = $alt->title;
                    $obj->url = $url;
                    $urls[$alt->lang] = $obj;

                }

            }

        }

        return $urls;

    }

    /**
     * getURLAlternativesAnchorTags
     *
     * @return string[]
     */
    public function getURLAlternativesAnchorTags()
    {

        $elements = $this->getURLAlternatives(false);
        $anchors = [];

        foreach ($elements as $lang => $data) {

            $anchors[] = "<a href='{$data->url}' alt='{$data->title}'>" . __('lang', $lang) . "</a>";

        }

        return $anchors;

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
            $result = new static($result->sub_id);
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
                $result = new static($result->sub_id);
            }
        } else {
            $result = null;
        }

        return $result;
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
                return new static($e->sub_id);
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
                return new static($e->sub_id);
            }, $result);
        }

        return $result;
    }

    /**
     * allByCategory
     *
     * @param int $category
     * @param string $lang
     * @param bool $as_mapper
     * @param int $excludeID
     * @param bool $onlyDateRange
     * @param int $page
     * @param int $perPage
     * @return object|static[]
     */
    public static function allByCategory(
        int $category,
        string $lang,
        bool $as_mapper = false,
        int $excludeID = null,
        bool $onlyDateRange = true,
        int $page = null,
        int $perPage = null
    ) {
        $model = self::model();

        $where = [
            "category" => $category,
            "lang" => $lang,
        ];

        if ($onlyDateRange) {

            $now = date('Y-m-d H:i:s');

            $where = [
                "category = '{$category}' AND",
                "lang = '{$lang}' AND",
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
                return new static($e->sub_id);
            }, $result);
        }

        return $result;
    }

    /**
     * getContentByLang
     *
     * @param int $id
     * @param string $lang
     * @param bool $asMapper
     *
     * @return ArticleContentMapper|\stdClass|null
     */
    public static function getContentByLang(int $id, string $lang, bool $asMapper = true)
    {
        $result = null;

        $model = ArticleContentMapper::model();

        $model->select()->where([
            'content_of' => $id,
            'lang' => $lang,
        ])->execute();

        $result = $model->result();
        $result = count($result) > 0 ? $result[0] : null;

        if ($result !== null && $asMapper) {
            $result = new ArticleContentMapper($result->id);
        }

        return $result;

    }

    /**
     * getByPreferedSubID
     *
     * @param int $id
     * @param bool $asMapper
     * @return ArticleContentMapper|null
     */
    public static function getByPreferedSubID(int $id, bool $asMapper = true)
    {
        $model = self::model();

        $where = "(sub_id = '" . implode("' OR sub_id = '", self::getPreferedsSubIDs()) . "') AND id = $id";

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return count($result) > 0 ? ($asMapper ? new ArticleContentMapper($result[0]->sub_id) : ArticleContentMapper::getBy('id', $result[0]->sub_id)) : null;
    }

    /**
     * getAllByPreferedsSubIDs
     *
     * @return static[]
     */
    public static function getAllByPreferedsSubIDs()
    {
        $model = self::model();

        $where = "sub_id = '" . implode("' OR sub_id = '", self::getPreferedsSubIDs()) . "'";

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return $result;
    }

    /**
     * getPreferedsSubIDs
     *
     * @return int[]
     */
    public static function getPreferedsSubIDs()
    {
        $model = self::model();

        $model->select();

        $model->execute();

        $result = $model->result();

        $allowedLangs = get_config('allowed_langs');
        $appLang = get_config('app_lang');
        $prefereds = [];

        if (count($result) > 0) {

            $resultGroupedByContentOf = [];

            foreach ($result as $i) {

                $resultGroupedByContentOf[$i->id] = isset($resultGroupedByContentOf[$i->id]) ? $resultGroupedByContentOf[$i->id] : [];

                $resultGroupedByContentOf[$i->id][] = $i;

            }

            foreach ($resultGroupedByContentOf as $i) {

                $added = false;
                $hasAppLang = false;
                $allowedLangsExists = [];

                foreach ($i as $j) {

                    $allowedLangsExists[] = $j->lang;

                    if ($j->lang == $appLang) {
                        $hasAppLang = true;
                    }

                }

                foreach ($i as $j) {

                    foreach ($allowedLangs as $lang) {

                        if (!$added) {
                            $prefereds[$j->id] = (int) $j->sub_id;
                        }

                        if ($hasAppLang) {

                            if ($j->lang == $appLang) {
                                $added = true;
                            }

                        } else {

                            if ($lang == $j->lang) {
                                $added = true;
                            }

                        }

                    }

                }

            }

            $result = $prefereds;

        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * generateFriendlyURL
     *
     * @param string $friendly_url
     * @param int $ignoreSubID
     * @param int $maxWords
     * @return string
     */
    public static function generateFriendlyURL(string $name, int $ignoreSubID, int $maxWords = null)
    {
        $baseFriendlyURL = friendly_url($name, $maxWords);
        $friendlyURL = $baseFriendlyURL;
        $countFriendlyURL = self::friendlyURLCount($baseFriendlyURL, $ignoreSubID);
        $num = 1;

        while ($countFriendlyURL > 0) {
            $friendlyURL = $baseFriendlyURL . '-' . $num;
            $countFriendlyURL = self::friendlyURLCount($friendlyURL, $ignoreSubID);
            $num++;
        }

        return $friendlyURL;
    }

    /**
     * friendlyURLCount
     *
     * @param string $friendly_url
     * @param int $ignoreSubID
     * @return bool
     */
    public static function friendlyURLCount(string $friendly_url, int $ignoreSubID)
    {

        $model = self::model();

        $where = [
            'friendly_url' => $friendly_url,
            'sub_id' => [
                '!=' => $ignoreSubID,
            ],
        ];

        $model->select('COUNT(id) AS total')->where($where)->execute();

        $result = $model->result();

        return count($result) > 0 ? (int) $result[0]->total : 0;
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
     * existsBySubID
     *
     * @param int $sub_id
     * @return bool
     */
    public static function existsBySubID(int $sub_id)
    {
        $model = self::model();

        $where = [
            "sub_id = $sub_id",
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
     * @param string $friendlyURL
     * @param int $category
     * @param int $id
     * @param int $subID
     * @return bool
     */
    public static function isDuplicate(string $title, string $friendlyURL, int $category, int $id, int $subID)
    {
        $model = self::model();

        $title = \stripslashes($title);
        $title = \addslashes($title);
        $friendlyURL = \stripslashes($friendlyURL);
        $friendlyURL = \addslashes($friendlyURL);

        $where = [
            "(
				(title = '$title' OR friendly_url = '$friendlyURL')
				AND id != $id AND sub_id != $subID
			)",
            'OR',
            "(
				friendly_url = '$friendlyURL' AND sub_id != $subID
			)",
            "AND id != $id",
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
