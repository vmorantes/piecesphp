<?php
/**
 * ArticleMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use App\Model\UsersModel;

/**
 * ArticleMapper.
 *
 * @package     PiecesPHP\BuiltIn\Article\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|UsersModel $author
 * @property string $title
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
		'title' => [
			'type' => 'varchar',
			'length' => 255,
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
	 *
	 * @return static[]|array
	 */
	public static function all(bool $as_mapper = false)
	{
		$model = self::model();

		$model->select()->execute();

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
	public static function allForSelect(string $defaultLabel = 'Artículos', string $defaultValue = '')
	{
		$options = [];
		$options[$defaultValue] = $defaultLabel;

		array_map(function ($e) use (&$options) {
			$options[$e->id] = $e->name;
		}, self::all());

		return $options;
	}

	/**
	 * isDuplicate
	 *
	 * @param string $title
	 * @param int $ignore_id
	 * @return bool
	 */
	public static function isDuplicate(string $title, int $ignore_id)
	{
		$model = self::model();

		$where = trim(implode(' ', [
			"title = '$title' AND ",
			"id != $ignore_id",
		]));

		$model->select()->where($where)->execute();

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
		return (new static)->getModel();
	}
}
