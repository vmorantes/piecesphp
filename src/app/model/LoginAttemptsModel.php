<?php
/**
 * LoginAttemptsModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\Enums\CodeStringExceptionsEnum;
use PiecesPHP\Core\Database\Exceptions\DatabaseClassesExceptions;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use \Slim\Http\Request as Request;

/**
 * LoginAttemptsModel.
 *
 * Modelo de registro de inicio de sesión.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */

class LoginAttemptsModel extends BaseEntityMapper
{
    const SUCCESS_ATTEMPT = 1;
    const FAIL_ATTEMPT = 0;

    const TABLE = 'login_attempts';
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
        'username_attempt' => [
            'type' => 'varchar',
        ],
        'success' => [
            'type' => 'int',
        ],
        'ip' => [
            'type' => 'varchar',
        ],
        'message' => [
            'type' => 'text',
            'null' => true,
        ],
        'date' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
    ];

    /**
     * __construct
     *
     * @param mixed int
     * @param mixed string
     * @return static
     */
    public function __construct(int $value = null, string $field_compare = 'id')
    {
        $model = new BaseModel();
        $model->setTable($this->table);
        $pStmt = $model->prepare("SHOW COLUMNS FROM {$this->table} WHERE Field LIKE 'extra_data'");
        $pStmt->execute();
        $result = $pStmt->fetchAll();
        $exist_field = count($result) > 0;
        if ($exist_field) {
            $this->fields['extra_data'] = [
                'type' => 'json',
                'default' => [],
                'null' => true,
            ];
        }
        parent::__construct($value, $field_compare);
        if ($exist_field) {
            if ($this->extra_data === '') {
                $this->extra_data = [];
            }
        }
    }

    /**
     * addLogin
     *
     * @param int $user_id
     * @param string $username
     * @param bool $success
     * @param string $message
     * @param array $extraData
     * @return void
     */
    public static function addLogin(int $user_id = null, string $username, bool $success, string $message = '', array $extraData = [])
    {
        $mapper = new static();
        $mapper->user_id = $user_id;
        $mapper->username_attempt = $username;
        $mapper->success = $success ? self::SUCCESS_ATTEMPT : self::FAIL_ATTEMPT;
        $mapper->message = $message;
        $mapper->ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        try {
            $mapper->extra_data = $extraData;
        } catch (DatabaseClassesExceptions $e) {
            if ($e->getCodeString() != CodeStringExceptionsEnum::UndefinedProperty) {
                throw $e;
            }
        }
        $mapper->save();
    }

    /**
     * lastLogin
     *
     * @param int $user_id
     * @return null|\DateTime
     */
    public static function lastLogin(int $user_id)
    {
        $model = (new static())->getModel();
        $model->select()->where([
            'user_id' => $user_id,
            'success' => self::SUCCESS_ATTEMPT,
        ]);
        $model->orderBy('date DESC');
        $model->execute();
        $result = $model->result();
        return count($result) > 0 ? new \DateTime($result[0]->date) : null;
    }

    /**
     * getAttempts
     *
     * @param Request $request
     * @return PiecesPHP\Core\Utilities\ReturnTypes\ResultOperation
     */
    public static function getAttempts(Request $request)
    {
        $success = self::SUCCESS_ATTEMPT;
        $on_set_data = function ($element) use ($success) {
            $attempt = new static($element->id);
            $data = [];
            $data[] = $attempt->username_attempt;
            $data[] = $attempt->success == $success ? 'Sí' : 'No';
            $data[] = $attempt->message;
            $data[] = $attempt->ip;
            $data[] = $attempt->date->format('d-m-Y H:i:s');

            return $data;
        };

        $columns = [
            'username_attempt',
            'success',
            'message',
            'ip',
            'date',
        ];

        $options = [
            'request' => $request,
            'mapper' => new static(),
            'columns_order' => $columns,
            'custom_order' => [
                'date' => 'DESC',
            ],
            'on_set_data' => $on_set_data,
        ];

        return DataTablesHelper::process($options);
    }

    /**
     * getLoggedUsers
     *
     * @param Request $request
     * @return PiecesPHP\Core\Utilities\ReturnTypes\ResultOperation
     */
    public static function getLoggedUsers(Request $request)
    {
        $logins_table = self::TABLE;
        $users_table = 'pcsphp_users';

        $success = self::SUCCESS_ATTEMPT;
        $type = null;

        $where = null;
        if (!is_null($type)) {
            $where = "type = $type";
        }
        $on = "$logins_table.user_id = $users_table.id AND $logins_table.success = $success";

        $on_set_model = function ($model) use ($users_table, $logins_table, $on) {
            return $model->innerJoin($logins_table, $on);
        };

        $on_set_data = function ($element) {

            $user = new UsersModel($element->id);

            $data = [];
            $data[] = $user->id;
            $data[] = trim("$user->firstname $user->secondname $user->first_lastname $user->second_lastname");

            $data[] = self::lastLogin($user->id)->format('d-m-Y H:i:s');
            $time_on_platform = TimeOnPlatformModel::getRecordByUser($user->id);
            $data[] = !is_null($time_on_platform) ? round($time_on_platform->minutes, 0) . ' minuto(s)' : 'Sin registro';

            return $data;
        };

        $columns = [
            'id',
            ['firstname', 'secondname', 'first_lastname', 'second_lastname'],
        ];

        $options = [
            'request' => $request,
            'mapper' => new UsersModel(),
            'columns_order' => $columns,
            'on_set_model' => $on_set_model,
            'on_set_data' => $on_set_data,
            'where_string' => $where,
            'group_string' => "$users_table.id",
        ];

        $result = DataTablesHelper::process($options);

        return $result;
    }

    /**
     * getNotLoggedUsers
     *
     * @param Request $request
     * @return PiecesPHP\Core\Utilities\ReturnTypes\ResultOperation
     */
    public static function getNotLoggedUsers(Request $request)
    {
        $logins_table = self::TABLE;
        $users_table = 'pcsphp_users';

        $success = self::SUCCESS_ATTEMPT;
        $type = null;

        $on = "$logins_table.user_id = $users_table.id AND $logins_table.success = $success";
        $where = '';
        if (!is_null($type)) {
            $where .= "type = $type AND ";
        }
        $where .= "$users_table.id NOT IN ";

        if (!is_null($type)) {
            $where .= "(SELECT $users_table.id FROM $users_table INNER JOIN $logins_table ON $on WHERE type = $type GROUP BY $users_table.id)";
        } else {
            $where .= "(SELECT $users_table.id FROM $users_table INNER JOIN $logins_table ON $on GROUP BY $users_table.id)";
        }

        $on_set_data = function ($element) {

            $user = new UsersModel($element->id);

            $data = [];
            $data[] = $user->id;
            $data[] = trim("$user->firstname $user->secondname $user->first_lastname $user->second_lastname");

            return $data;
        };

        $columns = [
            'id',
            ['firstname', 'secondname', 'first_lastname', 'second_lastname'],
        ];

        $options = [
            'request' => $request,
            'mapper' => new UsersModel(),
            'columns_order' => $columns,
            'on_set_data' => $on_set_data,
            'where_string' => $where,
        ];

        return DataTablesHelper::process($options);
    }
}
