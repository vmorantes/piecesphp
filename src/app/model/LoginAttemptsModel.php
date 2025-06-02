<?php
/**
 * LoginAttemptsModel.php
 */
namespace App\Model;

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\Enums\CodeStringExceptionsEnum;
use PiecesPHP\Core\Database\Exceptions\DatabaseClassesExceptions;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use \PiecesPHP\Core\Routing\RequestRoute as Request;

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
        $exist_field = !empty($result);
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
     * @param int $user_id
     * @param string $username
     * @param bool $success
     * @param string $message
     * @param array $extraData
     * @return void
     */
    public static function addLogin(int $user_id = null, string $username, bool $success, string $message = '', array $extraData = [])
    {
        $mapper = new LoginAttemptsModel();
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
     * @param int $user_id
     * @return null|\DateTime
     */
    public static function lastLogin(int $user_id)
    {
        $model = (new LoginAttemptsModel())->getModel();
        $model->select()->where([
            'user_id' => $user_id,
            'success' => self::SUCCESS_ATTEMPT,
        ]);
        $model->orderBy('date DESC');
        $model->execute();
        $result = $model->result();
        return !empty($result) ? new \DateTime($result[0]->date) : null;
    }

    /**
     * @param Request $request
     * @return ResultOperations
     */
    public static function getAttempts(Request $request)
    {
        $currentUser = getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization !== null ? $currentUser->organization : -1;

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $table = self::TABLE;

        $where = [];
        $having = [];

        $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
        if (!$canModifyOrganizations) {
            $criteryValue = $currentOrganizationID;
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "({$table}.user_id IS NULL AND organizationID IS NULL) OR organizationID = {$criteryValue}";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = LoginAttemptsModel::fieldsToSelect('%d-%m-%Y %h:%i:%s %p');

        $columnsOrder = [
            'username_attempt',
            'success',
            'message',
            'ip',
            'dateFormat',
        ];

        $customOrder = [
            'date' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);
        $success = self::SUCCESS_ATTEMPT;

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new LoginAttemptsModel(),
            'request' => $request,
            'on_set_data' => function ($e) use ($success) {
                $columns = [];
                $columns[] = $e->success == $success ? '<i class="check circle icon" style="visibility: visible;"></i>' : '<i class="times circle icon"></i>';
                $columns[] = $e->username_attempt;
                $columns[] = stripslashes($e->message);
                $columns[] = $e->ip;
                $columns[] = $e->dateFormat;
                return $columns;
            },

        ]);

        return $result;
    }

    /**
     * @param Request $request
     * @return ResultOperations
     */
    public static function getLoggedUsers(Request $request)
    {

        $currentUser = getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization !== null ? $currentUser->organization : -1;

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $table = self::TABLE;
        $tableUsers = UsersModel::TABLE;
        $tableTimeOnPlatform = TimeOnPlatformModel::TABLE;
        $successLogin = self::SUCCESS_ATTEMPT;

        $where = [];
        $having = [
            'wasLogged = 1',
        ];

        $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
        if (!$canModifyOrganizations) {
            $criteryValue = $currentOrganizationID;
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$tableUsers}.organization = {$criteryValue}";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $formatDate = '%d-%m-%Y %h:%i:%s %p';
        $selectFields = UsersModel::fieldsToSelect();
        $selectFields[] = "(SELECT COUNT({$table}.user_id) > 0 FROM {$table} WHERE {$table}.user_id IS NOT NULL AND {$table}.user_id = {$tableUsers}.id AND {$table}.success = {$successLogin}) AS wasLogged";
        $selectFields[] = "(SELECT DATE_FORMAT(MAX({$table}.date), '{$formatDate}') FROM {$table} WHERE {$table}.user_id IS NOT NULL AND {$table}.user_id = {$tableUsers}.id AND {$table}.success = {$successLogin}) AS lastLoginDate";
        $selectFields[] = "(SELECT SUM({$tableTimeOnPlatform}.minutes) FROM {$tableTimeOnPlatform} WHERE {$tableTimeOnPlatform}.user_id = {$tableUsers}.id) AS timeOnPlatformMinutes";

        $columnsOrder = [
            'idPadding',
            'username',
            'fullname',
            'lastLoginDate',
            'timeOnPlatformMinutes',
        ];

        $customOrder = [
            'username' => 'ASC',
            'fullname' => 'ASC',
            'idPadding' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new UsersModel(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $timeOnPlatformMinutes = $e->timeOnPlatformMinutes;

                $columns = [];
                $columns[] = $e->idPadding;
                $columns[] = $e->username;
                $columns[] = $e->fullname;
                $columns[] = $e->lastLoginDate;
                $columns[] = !is_null($timeOnPlatformMinutes) ? round($timeOnPlatformMinutes, 0) . ' ' . __(LOGIN_REPORT_LANG_GROUP, 'minuto(s)') : __(LOGIN_REPORT_LANG_GROUP, 'Sin registro');
                return $columns;
            },
        ]);

        return $result;
    }

    /**
     * @param Request $request
     * @return ResultOperations
     */
    public static function getNotLoggedUsers(Request $request)
    {
        $currentUser = getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization !== null ? $currentUser->organization : -1;

        $whereString = null;
        $havingString = null;
        $and = 'AND';
        $table = self::TABLE;
        $tableUsers = UsersModel::TABLE;
        $successLogin = self::SUCCESS_ATTEMPT;

        $where = [];
        $having = [
            'wasLogged = 0',
        ];

        $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
        if (!$canModifyOrganizations) {
            $criteryValue = $currentOrganizationID;
            $beforeOperator = !empty($where) ? $and : '';
            $critery = "{$tableUsers}.organization = {$criteryValue}";
            $where[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = UsersModel::fieldsToSelect();
        $selectFields[] = "(SELECT COUNT({$table}.user_id) > 0 FROM {$table} WHERE {$table}.user_id IS NOT NULL AND {$table}.user_id = {$tableUsers}.id AND {$table}.success = {$successLogin}) AS wasLogged";

        $columnsOrder = [
            'idPadding',
            'username',
            'fullname',
        ];

        $customOrder = [
            'username' => 'ASC',
            'fullname' => 'ASC',
            'idPadding' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new UsersModel(),
            'request' => $request,
            'on_set_data' => function ($e) {
                $columns = [];
                $columns[] = $e->idPadding;
                $columns[] = $e->username;
                $columns[] = $e->fullname;
                return $columns;
            },
        ]);

        return $result;
    }

    /**
     * Campos extra:
     *  - idPadding
     *  - organizationID
     *  - dateFormat
     * @return string[]
     */
    protected static function fieldsToSelect(string $formatDate = null)
    {

        $formatDate = $formatDate ?? get_default_format_date(null, true);

        $mapper = (new LoginAttemptsModel);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableUser = UsersModel::TABLE;

        $organizationID = "SELECT {$tableUser}.organization FROM {$tableUser} WHERE {$tableUser}.id = {$table}.user_id";

        $fields = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "({$organizationID}) AS organizationID",
            "DATE_FORMAT({$table}.date, '{$formatDate}') AS dateFormat",
        ];

        $allFields = array_keys($mapper->getFields());
        foreach ($allFields as $field) {
            $fields[] = "{$table}.{$field}";
        }

        return $fields;

    }

    /**
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function all(bool $asMapper = false)
    {

        $currentUser = getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization !== null ? $currentUser->organization : -1;

        $model = self::model();
        $table = self::TABLE;
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);

        $having = [];

        if ($currentUser !== null) {
            $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
            if (!$canModifyOrganizations) {
                $criteryValue = $currentOrganizationID;
                $beforeOperator = !empty($having) ? 'AND' : '';
                $critery = "({$table}.user_id IS NULL AND organizationID IS NULL) OR organizationID = {$criteryValue}";
                $having[] = "{$beforeOperator} ({$critery})";
            }
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
            $model->having($havingString);
        }

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = new LoginAttemptsModel($value->id);
            }
        }

        return $result;
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new LoginAttemptsModel())->getModel();
    }
}
