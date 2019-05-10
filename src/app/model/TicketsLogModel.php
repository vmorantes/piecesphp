<?php
/**
 * TicketsLogModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * TicketsLogModel.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $message
 * @property string $type
 * @property string|object|array $information
 * @property string|\DateTime $created
 */
class TicketsLogModel extends BaseEntityMapper
{
	protected $fields = [
		'id' => [
			'type' => 'int',
			'primary_key' => true,
		],
		'name' => [
			'type' => 'varchar',
			'null' => true,
		],
		'email' => [
			'type' => 'varchar',
			'null' => true,
		],
		'message' => [
			'type' => 'varchar',
			'null' => true,
		],
		'type' => [
			'type' => 'varchar',
			'null' => true,
		],
		'information' => [
			'type' => 'json',
			'null' => true,
		],
		'created' => [
			'type' => 'datetime',
		],
	];
	protected $table = 'pcsphp_tickets_log';

	/**
	 * __construct
	 *
	 * @param integer $id
	 * @return static
	 */
	public function __construct(int $id = null)
	{
		parent::__construct($id);
	}
}
