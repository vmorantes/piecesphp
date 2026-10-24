<?php
/**
 * LoginAttemptsModel.php
 */
namespace PiecesPHP\UserSystem\ORM;

use PiecesPHP\UserSystem\UserHierarchy;
use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\Enums\CodeStringExceptionsEnum;
use PiecesPHP\Core\Database\Exceptions\DatabaseClassesExceptions;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use \PiecesPHP\Core\Routing\RequestRoute as Request;

/**
 * LoginAttemptsModel.
 *
 * Modelo de registro de inicio de sesión.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @property int $id
 * @property int|UsersModel|null $userID
 * @property string $usernameAttempt
 * @property int $success
 * @property string $ip
 * @property string|null $message
 * @property string|\DateTime $date
 * @property array|null $extraData
 */

class LoginAttemptsModel extends BaseEntityMapper
{
    const SUCCESS_ATTEMPT = 1;
    const FAIL_ATTEMPT = 0;

    const TABLE = 'login_attempts';
    protected $table = self::TABLE;

    /**
     * El tope de filas del registro por omisión: al pasarlo se borran las más viejas (trim()). Un clon lo cambia con
     * setMaxRows(), como el del registro de correos (MailLogMapper).
     *
     * @var int
     */
    const MAX_ROWS = 200000;

    /**
     * El suelo del tope: por debajo, el registro dejaría de servir para mirar hacia atrás.
     *
     * @var int
     */
    const MIN_ROWS = 1000;

    /**
     * @var int
     */
    protected static $maxRows = self::MAX_ROWS;

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
        'usernameAttempt' => [
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
     * @param int|null $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(?int $value = null, string $field_compare = 'id')
    {
        $model = new BaseModel();
        $model->setTable($this->table);
        $pStmt = $model->prepare("SHOW COLUMNS FROM {$this->table} WHERE Field LIKE 'extraData'");
        $pStmt->execute();
        $result = $pStmt->fetchAll();
        $exist_field = !empty($result);
        if ($exist_field) {
            $this->fields['extraData'] = [
                'type' => 'json',
                'default' => [],
                'null' => true,
            ];
        }
        parent::__construct($value, $field_compare);
        if ($exist_field) {
            if ($this->extraData === '') {
                $this->extraData = [];
            }
        }
    }

    /**
     * Por debajo de MIN_ROWS se queda en MIN_ROWS, sin lanzar. Con null vuelve a MAX_ROWS.
     *
     * @param int|null $rows
     * @return void
     */
    public static function setMaxRows(?int $rows): void
    {
        self::$maxRows = $rows === null ? self::MAX_ROWS : max(self::MIN_ROWS, $rows);
    }

    /**
     * @return int
     */
    public static function maxRows(): int
    {
        return self::$maxRows;
    }

    /**
     * Deja las maxRows() más recientes, por id (creciente). Nunca lanza: un registro que no se recorta no tumba la
     * acción que se está anotando.
     *
     * @return int Cuántas filas se retiraron.
     */
    public static function trim(): int
    {
        try {
            $database = (new BaseModel())->getDatabase();
            if ($database === null) {
                return 0;
            }
            $table = self::TABLE;
            $limit = $database->query("SELECT `id` FROM `{$table}` ORDER BY `id` DESC LIMIT 1 OFFSET " . (self::maxRows() - 1));
            $cut = $limit !== false ? $limit->fetchColumn() : false;
            if ($cut === false || $cut === null) {
                return 0;
            }
            $statement = $database->prepare("DELETE FROM `{$table}` WHERE `id` < ?");
            $statement->execute([(int) $cut]);
            return $statement->rowCount();
        } catch (\Throwable $throwable) {
            return 0;
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
    public static function addLogin(?int $user_id = null, string $username = '', bool $success = false, string $message = '', array $extraData = []): void
    {
        $mapper = new LoginAttemptsModel();
        $mapper->userID = $user_id;
        $mapper->usernameAttempt = $username;
        $mapper->success = $success ? self::SUCCESS_ATTEMPT : self::FAIL_ATTEMPT;
        $mapper->message = $message;
        $mapper->ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        try {
            $mapper->extraData = $extraData;
        } catch (DatabaseClassesExceptions $e) {
            if ($e->getCodeString() != CodeStringExceptionsEnum::UndefinedProperty) {
                throw $e;
            }
        }
        $mapper->save();
        self::trim();
    }

    /**
     * @param int $user_id
     * @return null|\DateTime
     */
    public static function lastLogin(int $user_id)
    {
        $model = (new LoginAttemptsModel())->getModel();
        $model->select()->where([
            'userID' => $user_id,
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
        $currentUser = getLoggedFrameworkUserOrFail();

        $whereString = null;
        $havingSegment = null;

        $where = [];

        //P133: lo propio y lo de rangos menores; un intento con un usuario que no existe lo ven los administradores.
        $visibility = UserHierarchy::visibilityGroup($currentUser, 'visibleUserID', 'visibleRank', 'organizationID', true);
        if ($visibility !== null) {
            $havingSegment = new HavingSegment();
            $havingSegment->addGroup($visibility);
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = LoginAttemptsModel::fieldsToSelect('%d-%m-%Y %h:%i:%s %p');
        //La columna de la vía no está en toda instalación (el constructor la detecta): sin ella, la columna va vacía.
        if (!array_key_exists('extraData', (new LoginAttemptsModel())->getFields())) {
            $selectFields[] = 'NULL AS extraData';
        }

        $columnsOrder = [
            'usernameAttempt',
            'success',
            'message',
            'extraData',
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
            'having_segment' => $havingSegment,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new LoginAttemptsModel(),
            'request' => $request,
            'on_set_data' => function ($e) use ($success) {
                $columns = [];
                $columns[] = $e->success == $success ? '<i class="check circle icon" style="visibility: visible;"></i>' : '<i class="times circle icon"></i>';
                //El usuario lo teclea cualquiera sin sesión en el login; el mensaje lo lleva dentro al bloquear.
                $columns[] = escape_html($e->usernameAttempt);
                $columns[] = escape_html($e->message);
                $columns[] = escape_html(self::viaLabel($e->extraData ?? null));
                $columns[] = escape_html($e->ip);
                $columns[] = $e->dateFormat;
                return $columns;
            },

        ]);

        return $result;
    }

    /**
     * Por qué vía llegó el intento, legible: la deja OTPRateLimiter en extraData; sin ella es el formulario de acceso.
     *
     * @param mixed $extraData La columna tal como sale de la consulta (JSON) o ya decodificada
     * @return string
     */
    public static function viaLabel($extraData): string
    {
        if (is_string($extraData)) {
            $extraData = json_decode($extraData, true);
        } elseif (is_object($extraData)) {
            $extraData = json_decode((string) json_encode($extraData), true);
        }
        $extraData = is_array($extraData) ? $extraData : [];
        $via = $extraData[OTPRateLimiter::EXTRA_DATA_VIA] ?? null;
        $labels = [
            OTPRateLimiter::VIA_GENERATE_OTP => __(LOGIN_REPORT_LANG_GROUP, 'Pedir código de un solo uso'),
            OTPRateLimiter::VIA_CHECK_TOTP => __(LOGIN_REPORT_LANG_GROUP, 'Comprobar código de un solo uso'),
            OTPRateLimiter::VIA_TWO_FACTOR_STATUS => __(LOGIN_REPORT_LANG_GROUP, 'Consultar el doble factor'),
            OTPRateLimiter::VIA_LOGIN_TOTP => __(LOGIN_REPORT_LANG_GROUP, 'Contraseña y doble factor'),
            OTPRateLimiter::VIA_RECOVERY_CODE => __(LOGIN_REPORT_LANG_GROUP, 'Código de recuperación'),
        ];
        //Una vía que no está en la lista se enseña tal cual: es un dato del registro, no se esconde.
        $label = is_string($via) ? ($labels[$via] ?? $via) : __(LOGIN_REPORT_LANG_GROUP, 'Formulario de acceso');
        if (($extraData[OTPRateLimiter::EXTRA_DATA_LOCKED] ?? false) === true) {
            $label .= ' (' . __(LOGIN_REPORT_LANG_GROUP, 'bloqueado') . ')';
        }
        return $label;
    }

    /**
     * @param Request $request
     * @return ResultOperations
     */
    public static function getLoggedUsers(Request $request)
    {

        $currentUser = getLoggedFrameworkUserOrFail();

        $whereString = null;
        $havingSegment = null;
        $table = self::TABLE;
        $tableUsers = UsersModel::TABLE;
        $tableTimeOnPlatform = TimeOnPlatformModel::TABLE;
        $successLogin = self::SUCCESS_ATTEMPT;

        $where = [
            UsersModel::excludeSystemUserSQL("{$tableUsers}.id"),
        ];
        $havingSegment = new HavingSegment();
        $havingSegment->addGroup(new HavingItemGroup([
            new HavingItem('wasLogged', HavingItem::EQUAL_OPERATOR, 1, HavingItem::AND_OPERATOR),
        ]));

        //P133: lo propio y lo de rangos menores (el administrador de organización, de la suya).
        $visibility = UserHierarchy::visibilityGroup($currentUser, 'visibleUserID', 'visibleRank', 'visibleOrganization');
        if ($visibility !== null) {
            $havingSegment->addGroup($visibility);
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $formatDate = '%d-%m-%Y %h:%i:%s %p';
        $selectFields = array_merge(UsersModel::fieldsToSelect(), self::visibilityFieldsForUsers());
        $selectFields[] = "(SELECT COUNT({$table}.userID) > 0 FROM {$table} WHERE {$table}.userID IS NOT NULL AND {$table}.userID = {$tableUsers}.id AND {$table}.success = {$successLogin}) AS wasLogged";
        $selectFields[] = "(SELECT DATE_FORMAT(MAX({$table}.date), '{$formatDate}') FROM {$table} WHERE {$table}.userID IS NOT NULL AND {$table}.userID = {$tableUsers}.id AND {$table}.success = {$successLogin}) AS lastLoginDate";
        $selectFields[] = "(SELECT SUM({$tableTimeOnPlatform}.minutes) FROM {$tableTimeOnPlatform} WHERE {$tableTimeOnPlatform}.userID = {$tableUsers}.id) AS timeOnPlatformMinutes";

        $columnsOrder = [
            "{$tableUsers}.id",
            'username',
            'fullname',
            'lastLoginDate',
            'timeOnPlatformMinutes',
        ];

        $customOrder = [
            'username' => 'ASC',
            'fullname' => 'ASC',
            "{$tableUsers}.id" => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'having_segment' => $havingSegment,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new UsersModel(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $timeOnPlatformMinutes = $e->timeOnPlatformMinutes;

                $columns = [];
                $columns[] = $e->idPadding;
                $columns[] = escape_html($e->username);
                $columns[] = escape_html($e->fullname);
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
        $currentUser = getLoggedFrameworkUserOrFail();

        $whereString = null;
        $havingSegment = null;
        $table = self::TABLE;
        $tableUsers = UsersModel::TABLE;
        $successLogin = self::SUCCESS_ATTEMPT;

        //Un usuario Eliminado deja de existir en la práctica: no es una cuenta sin ingreso. Quien sí ingresó sigue en el
        //historial (getLoggedUsers), que es actividad.
        $where = [
            UsersModel::excludeSystemUserSQL("{$tableUsers}.id"),
            " AND {$tableUsers}.status != " . UsersModel::STATUS_USER_DELETED,
        ];
        $havingSegment = new HavingSegment();
        $havingSegment->addGroup(new HavingItemGroup([
            new HavingItem('wasLogged', HavingItem::EQUAL_OPERATOR, 0, HavingItem::AND_OPERATOR),
        ]));

        //P133: lo propio y lo de rangos menores (el administrador de organización, de la suya).
        $visibility = UserHierarchy::visibilityGroup($currentUser, 'visibleUserID', 'visibleRank', 'visibleOrganization');
        if ($visibility !== null) {
            $havingSegment->addGroup($visibility);
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        $selectFields = array_merge(UsersModel::fieldsToSelect(), self::visibilityFieldsForUsers());
        $selectFields[] = "(SELECT COUNT({$table}.userID) > 0 FROM {$table} WHERE {$table}.userID IS NOT NULL AND {$table}.userID = {$tableUsers}.id AND {$table}.success = {$successLogin}) AS wasLogged";

        $columnsOrder = [
            "{$tableUsers}.id",
            'username',
            'fullname',
        ];

        $customOrder = [
            'username' => 'ASC',
            'fullname' => 'ASC',
            "{$tableUsers}.id" => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'having_segment' => $havingSegment,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new UsersModel(),
            'request' => $request,
            'on_set_data' => function ($e) {
                $columns = [];
                $columns[] = $e->idPadding;
                $columns[] = escape_html($e->username);
                $columns[] = escape_html($e->fullname);
                return $columns;
            },
        ]);

        return $result;
    }

    /**
     * Campos extra:
     *  - idPadding
     *  - organizationID
     *  - visibleUserID, userType, visibleRank (P133)
     *  - dateFormat
     * @return string[]
     */
    protected static function fieldsToSelect(?string $formatDate = null)
    {

        $formatDate ??= get_default_format_date(null, true);

        $mapper = (new LoginAttemptsModel);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableUser = UsersModel::TABLE;

        $organizationID = "SELECT {$tableUser}.organization FROM {$tableUser} WHERE {$tableUser}.id = {$table}.userID";
        $userType = "SELECT {$tableUser}.type FROM {$tableUser} WHERE {$tableUser}.id = {$table}.userID";

        $fields = [
            "LPAD({$table}.id, GREATEST(5, CHAR_LENGTH({$table}.id)), '0') AS idPadding",
            "({$organizationID}) AS organizationID",
            //Para la jerarquía (P133): quién es el usuario del intento y su rango.
            "{$table}.userID AS visibleUserID",
            "({$userType}) AS userType",
            UserHierarchy::rankSQL($userType) . ' AS visibleRank',
            "DATE_FORMAT({$table}.date, '{$formatDate}') AS dateFormat",
        ];

        $allFields = array_keys($mapper->getFields());
        foreach ($allFields as $field) {
            $fields[] = "{$table}.{$field}";
        }

        return $fields;

    }

    /**
     * Los alias de la jerarquía (P133) en una consulta sobre la tabla de usuarios.
     *
     * @return string[]
     */
    protected static function visibilityFieldsForUsers(): array
    {
        $tableUsers = UsersModel::TABLE;
        return [
            "{$tableUsers}.id AS visibleUserID",
            "{$tableUsers}.organization AS visibleOrganization",
            UserHierarchy::rankSQL("{$tableUsers}.type") . ' AS visibleRank',
        ];
    }

    /**
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function all(bool $asMapper = false)
    {

        $currentUser = getLoggedFrameworkUser();

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];
        //La misma regla que el listado (P133): contar o exportar no puede enseñar más que mirar.
        $result = array_values(array_filter($result, fn($row) => UserHierarchy::canSee(
            $currentUser,
            is_numeric($row->userID ?? null) ? (int) $row->userID : null,
            is_numeric($row->userType ?? null) ? (int) $row->userType : null,
            is_numeric($row->organizationID ?? null) ? (int) $row->organizationID : null,
            true
        )));

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
