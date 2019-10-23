<?php
/**
 * CategoryMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Category\Mappers;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * CategoryMapper.
 *
 * @package     PiecesPHP\BuiltIn\Article\Category\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 */
class CategoryMapper extends BaseEntityMapper
{
    const TABLE = 'pcsphp_articles_categories';

    /**
     * @var string $table
     */
    protected $table = self::TABLE;

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
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
     * getContentByLang
     *
     * @param string $lang
     * @param bool $asMapper
     *
     * @return CategoryContentMapper|\stdClass|null
     */
    public function getContentByLang(string $lang, bool $asMapper = true)
    {
        $result = null;

        if ($this->id !== null) {

            $model = CategoryContentMapper::model();

            $model->select()->where([
                'content_of' => $this->id,
                'lang' => $lang,
            ])->execute();

            $result = $model->result();
            $result = count($result) > 0 ? $result[0] : null;

            if ($result !== null && $asMapper) {
                $result = new CategoryContentMapper($result->id);
            }

        }

        return $result;
    }

    /**
     * getName
     *
     * @return string
     */
    public function getName()
    {

        $name = '';

        if ($this->id !== null) {

            $content = CategoryContentMapper::getByPreferedsIDsAndContenOf($this->id);
            $name = !is_null($content) ? $content->name : '';

        }

        return $name;

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
