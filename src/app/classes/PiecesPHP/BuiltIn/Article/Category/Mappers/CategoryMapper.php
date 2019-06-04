<?php
/**
 * CategoryMapper.php
 */

namespace PiecesPHP\BuiltIn\Article\Category\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use App\Model\UsersModel;

/**
 * CategoryMapper.
 *
 * @package     PiecesPHP\BuiltIn\Article\Category\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $friendly_url
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
	public static function allForSelect(string $defaultLabel = 'Categorías', string $defaultValue = '')
	{
		$options = [];
		$options[$defaultValue] = $defaultLabel;

		array_map(function ($e) use (&$options) {
			$options[$e->id] = $e->name;
		}, self::all());

		return $options;
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
	 * @param int $ignore_id
	 * @return bool
	 */
	public static function isDuplicate(string $name, string $friendly_url, int $ignore_id)
	{
		$model = self::model();

		$where = [
			"(name = '$name'",
			"OR friendly_url = '$friendly_url')",
			"AND id != $ignore_id",
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

		return count($result) > 0 ? (int)$result[0]->total : 0;
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
	 * @return BaseModel
	 */
	public static function model()
	{
		return (new static)->getModel();
	}
}
