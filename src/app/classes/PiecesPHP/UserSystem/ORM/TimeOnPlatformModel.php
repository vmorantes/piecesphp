<?php
/**
 * TimeOnPlatformModel.php
 */
namespace PiecesPHP\UserSystem\ORM;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\UserSystem\UserHierarchy;

/**
 * TimeOnPlatformModel.
 *
 * Modelo de contador de tiempo en la plataforma
 *
 * @package     App\Model
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int $id
 * @property int|UsersModel|null $userID
 * @property float $minutes
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
        'userID' => [
            'type' => 'bigint',
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
     * @param int|null $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(?int $value = null, string $field_compare = 'primary_key')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * @param int $userId
     * @param float $minutes
     * @return bool
     */
    public static function addTime(int $userId, float $minutes)
    {
        $result = false;
        if (self::existsUser($userId)) {
            $mapper = self::getRecordByUser($userId);

            if ($mapper !== null) {
                $mapper->minutes += $minutes;
                $mapper->minutes = round($mapper->minutes, 3);
                $result = $mapper->update();
            }

        } else {

            $mapper = new TimeOnPlatformModel();
            $mapper->userID = $userId;
            $mapper->minutes += $minutes;
            $mapper->minutes = round($mapper->minutes, 3);

            $result = $mapper->save();
        }

        return $result;
    }

    /**
     * @param int $userId
     * @return bool
     */
    public static function existsUser(int $userId)
    {
        $model = (new TimeOnPlatformModel())->getModel();
        $row = $model->select()->where([
            'userID' => $userId,
        ])->row();
        return $row !== false && $row !== -1;
    }

    /**
     * @param int $userId
     * @return TimeOnPlatformModel|null
     */
    public static function getRecordByUser(int $userId)
    {
        $model = (new TimeOnPlatformModel())->getModel();
        $row = $model->select()->where([
            'userID' => $userId,
        ])->row();
        return is_object($row) ? new TimeOnPlatformModel($row->id) : null;
    }

    /**
     * @return float
     */
    public static function getAllHoursOnPlatform()
    {
        $currentUser = getLoggedFrameworkUser();

        $model = (new TimeOnPlatformModel())->getModel();
        $tableUsers = UsersModel::TABLE;
        $table = self::TABLE;

        $model->select([
            "{$table}.*",
            "(SELECT {$tableUsers}.organization FROM {$tableUsers} WHERE {$tableUsers}.id = {$table}.userID) AS organizationID",
            "(SELECT {$tableUsers}.type FROM {$tableUsers} WHERE {$tableUsers}.id = {$table}.userID) AS userType",
        ]);

        $model->execute();
        $rows = $model->result();
        $rows = is_array($rows) ? $rows : [];
        //La cifra suma solo a quien quien mira puede ver (P133), como el listado.
        $rows = array_filter($rows, fn($row) => UserHierarchy::canSee(
            $currentUser,
            is_numeric($row->userID ?? null) ? (int) $row->userID : null,
            is_numeric($row->userType ?? null) ? (int) $row->userType : null,
            is_numeric($row->organizationID ?? null) ? (int) $row->organizationID : null
        ));
        $minutes = array_sum(array_column($rows, 'minutes'));
        $hours = $minutes / 60;
        return number_format($hours, 1);
    }
}
