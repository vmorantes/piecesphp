<?php
/**
 * TimeOnPlatformModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * TimeOnPlatformModel.
 *
 * Modelo de contador de tiempo en la plataforma
 *
 * @package     App\Model
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */

class TimeOnPlatformModel extends BaseEntityMapper
{
    const SUCCESS_ATTEMPT = 1;
    const FAIL_ATTEMPT = 0;

    const TABLE = 'time_on_platform';
    protected $table = self::TABLE;

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'user_id' => [
            'type' => 'int',
            'reference_table' => 'pcsphp_users',
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
            'null' => true,
        ],
        'minutes' => [
            'type' => 'double',
            'default' => 0.0,
        ],
    ];

    /**
     * __construct
     *
     * @param mixed int
     * @param mixed string
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'primary_key')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * addTime
     *
     * @param int $user_id
     * @param float $minutes
     * @return bool
     */
    public static function addTime(int $user_id, float $minutes)
    {
        if (self::existsUser($user_id)) {
            $mapper = self::getRecordByUser($user_id);
            $mapper->minutes += $minutes;
            $mapper->minutes = round($mapper->minutes, 3);
            return $mapper->update();

        } else {

            $mapper = new static();
            $mapper->user_id = $user_id;
            $mapper->minutes += $minutes;
            $mapper->minutes = round($mapper->minutes, 3);

            return $mapper->save();
        }
    }

    /**
     * existsUser
     *
     * @param int $user_id
     * @return bool
     */
    public static function existsUser(int $user_id)
    {
        $model = (new static())->getModel();
        $row = $model->select()->where([
            'user_id' => $user_id,
        ])->row();
        return $row !== false && $row !== -1;
    }
    /**
     * getRecordByUser
     *
     * @param int $user_id
     * @return static|null
     */
    public static function getRecordByUser(int $user_id)
    {
        $model = (new static())->getModel();
        $row = $model->select()->where([
            'user_id' => $user_id,
        ])->row();
        return $row !== false && $row !== -1 ? new static($row->id) : null;
    }
}
