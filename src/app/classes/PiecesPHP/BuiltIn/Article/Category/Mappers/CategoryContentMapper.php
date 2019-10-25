<?php
/**
 * CategoryContentMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Category\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * CategoryContentMapper.
 *
 * @package     PiecesPHP\BuiltIn\Article\Category\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|CategoryMapper $content_of
 * @property string $lang
 * @property string $name
 * @property string $description
 * @property string $friendly_url
 */
class CategoryContentMapper extends BaseEntityMapper
{
    const TABLE = 'pcsphp_articles_categories_content';

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
            'reference_table' => CategoryMapper::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'mapper' => CategoryMapper::class,
        ],
        'lang' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'name' => [
            'type' => 'varchar',
            'length' => 255,
        ],
        'description' => [
            'type' => 'text',
            'null' => true,
        ],
        'friendly_url' => [
            'type' => 'text',
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
     * all
     *
     * @param bool $as_mapper
     * @param string $lang
     *
     * @return static[]|array
     */
    public static function all(bool $as_mapper = false, string $lang = null)
    {
        $model = self::model();

        $model->select()->execute();

        if ($lang !== null) {
            $model->where(['lang' => $lang]);
        }

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
     * @return array
     */
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = is_string($defaultLabel) && strlen($defaultLabel) > 0 ? $defaultLabel : __('articlesBackend', 'Categorías');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            $options[$e->content_of] = $e->name;
        }, self::getByPreferedsIDs(false));

        return $options;
    }

    /**
     * getByPreferedsIDs
     *
     * @return static[]
     */
    public static function getByPreferedsIDs()
    {
        $model = self::model();

        $where = "id = '" . implode("' OR id = '", self::getPreferedsIDs()) . "'";

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return $result;
    }

    /**
     * getByPreferedsIDsAndContenOf
     *
     * @param int $content_of
     * @return static|null
     */
    public static function getByPreferedsIDsAndContenOf(int $content_of)
    {
        $model = self::model();

        $where = "(id = '" . implode("' OR id = '", self::getPreferedsIDs()) . "') AND content_of = $content_of";

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        return count($result) > 0 ? new static($result[0]->id) : null;
    }

    /**
     * getPreferedsIDS
     *
     * @return int[]
     */
    public static function getPreferedsIDs()
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

                $resultGroupedByContentOf[$i->content_of] = isset($resultGroupedByContentOf[$i->content_of]) ? $resultGroupedByContentOf[$i->content_of] : [];

                $resultGroupedByContentOf[$i->content_of][] = $i;

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
                            $prefereds[$j->content_of] = (int) $j->id;
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
     * existsByName
     *
     * @param string $name
     * @return bool
     */
    public static function existsByName(string $name)
    {
        $model = self::model();

        $where = [
            "name = '$name'",
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
     * @param string $name
     * @param string $friendly_url
     * @param int $content_of
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicate(string $name, string $friendly_url, int $content_of, int $ignore_id)
    {
        $model = self::model();
		$name = \stripslashes($name);
		$name = \addslashes($name);
		$friendly_url = \stripslashes($friendly_url);
		$friendly_url = \addslashes($friendly_url);

        $where = [
            "(
				(name = '$name' OR friendly_url = '$friendly_url')
				AND content_of != $content_of AND id != $ignore_id
			)",
            'OR',
            "(
				friendly_url = '$friendly_url' AND id != $ignore_id
			)",
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

        $model->select('COUNT(id) AS total')->where($where);
        $model->execute();

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
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }
}
