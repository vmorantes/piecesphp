<?php
/**
 * LoginAttemptsModel.php
 */
namespace PiecesPHP\UserSystem\ORM;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\Enums\CodeStringExceptionsEnum;
use PiecesPHP\Core\Database\Exceptions\DatabaseClassesExceptions;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\UserHierarchy;
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
 * @property string|null $ipGroup
 * @property string|null $message
 * @property string|\DateTime $date
 * @property array|null $extraData
 */

class LoginAttemptsModel extends BaseEntityMapper
{
    const SUCCESS_ATTEMPT = 1;

    /**
     * Los periodos de Accesos › Intentos, en días que acaban hoy (incluido); cualquier otro valor es DEFAULT_PERIOD.
     */
    const PERIODS = [7, 14, 30, 90];

    /**
     * @var int
     */
    const DEFAULT_PERIOD = 14;
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
        //Nula: las filas de antes de la columna no la tienen. El freno de intentos busca por ella (OTPRateLimiter::ipGroup()).
        'ipGroup' => [
            'type' => 'varchar',
            'null' => true,
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
     * @param int $userId
     * @param string $username
     * @param bool $success
     * @param string $message
     * @param array $extraData
     * @return void
     */
    public static function addLogin(?int $userId = null, string $username = '', bool $success = false, string $message = '', array $extraData = []): void
    {
        $mapper = new LoginAttemptsModel();
        $mapper->userID = $userId;
        $mapper->usernameAttempt = $username;
        $mapper->success = $success ? self::SUCCESS_ATTEMPT : self::FAIL_ATTEMPT;
        $mapper->message = $message;
        $mapper->ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $mapper->ipGroup = OTPRateLimiter::ipGroup($mapper->ip);
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
     * @param int $userId
     * @return null|\DateTime
     */
    public static function lastLogin(int $userId)
    {
        $model = (new LoginAttemptsModel())->getModel();
        $model->select()->where([
            'userID' => $userId,
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

        //El periodo y el resultado de la pestaña (`days`, `result`), por marcador.
        [, $from, $to] = self::periodFromRequest($request);
        $whereItems = [
            new WhereItem('date', WhereItem::GREATER_OR_EQUAL_OPERATOR, $from->format('Y-m-d H:i:s'), WhereItem::AND_OPERATOR),
            new WhereItem('date', WhereItem::LESS_THAN_OPERATOR, $to->format('Y-m-d H:i:s'), WhereItem::AND_OPERATOR),
        ];
        $resultFilter = $request->getQueryParam('result', '');
        if ($resultFilter === 'success' || $resultFilter === 'error') {
            $whereItems[] = new WhereItem('success', WhereItem::EQUAL_OPERATOR, $resultFilter === 'success' ? self::SUCCESS_ATTEMPT : 0, WhereItem::AND_OPERATOR);
        }
        $whereSegment = new WhereSegment();
        $whereSegment->addGroup(new WhereItemGroup($whereItems));

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
            'date',
        ];

        $customOrder = [
            'date' => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);
        $success = self::SUCCESS_ATTEMPT;

        $result = DataTablesHelper::process([
            'where_string' => $whereString,
            'where_segment' => $whereSegment,
            'having_segment' => $havingSegment,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new LoginAttemptsModel(),
            'request' => $request,
            'on_set_data' => function ($e) use ($success) {
                $columns = [];
                $isSuccess = $e->success == $success;
                //El marcador del resultado lo lee access-reports.js para teñir la fila de un fallido.
                $columns[] = '<span data-attempt-result="' . ($isSuccess ? 'success' : 'error') . '">' . ($isSuccess
                    ? \PiecesPHP\Core\Utilities\Helpers\StatusLabel::html(__(LOGIN_REPORT_LANG_GROUP, 'Exitoso'), \PiecesPHP\Core\Utilities\Helpers\StatusLabel::POSITIVE)
                    : \PiecesPHP\Core\Utilities\Helpers\StatusLabel::html(__(LOGIN_REPORT_LANG_GROUP, 'Fallido'), \PiecesPHP\Core\Utilities\Helpers\StatusLabel::NEGATIVE)) . '</span>';
                //El usuario lo teclea cualquiera sin sesión en el login; el mensaje lo lleva dentro al bloquear.
                $columns[] = escape_html($e->usernameAttempt);
                //Un ingreso correcto se anota sin mensaje: se dice qué fue.
                $message = (string) ($e->message ?? '');
                $columns[] = escape_html($isSuccess && trim($message) === '' ? __(LOGIN_REPORT_LANG_GROUP, 'Ingreso exitoso') : $message);
                $columns[] = escape_html(self::viaLabel($e->extraData ?? null));
                $columns[] = escape_html($e->ip);
                $columns[] = self::relativeDateHTML((string) $e->date, new \DateTime());
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
            OTPRateLimiter::VIA_LOGIN_PASSWORD => __(LOGIN_REPORT_LANG_GROUP, 'Formulario de acceso'),
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

        $table = self::TABLE;
        $tableUsers = UsersModel::TABLE;
        $tableTimeOnPlatform = TimeOnPlatformModel::TABLE;
        $successLogin = self::SUCCESS_ATTEMPT;

        //Con ingreso EN EL PERIODO (`days`): su último ingreso correcto cae dentro; el periodo acaba hoy. Por marcador.
        //Quien entró sigue aquí aunque después lo hayan Eliminado: es actividad (va con su etiqueta, sin contar en las cifras).
        [, $from] = self::periodFromRequest($request);
        $lastLogin = self::lastSuccessfulLoginSQL();
        $whereSegment = new WhereSegment();
        $whereSegment->addGroup(new WhereItemGroup([
            new WhereItem("{$tableUsers}.id", WhereItem::NOT_EQUAL_OPERATOR, UsersModel::SYSTEM_USER_ID, WhereItem::AND_OPERATOR),
            new WhereItem($lastLogin, WhereItem::GREATER_OR_EQUAL_OPERATOR, $from->format('Y-m-d H:i:s'), WhereItem::AND_OPERATOR),
        ]));

        //P133: lo propio y lo de rangos menores (el administrador de organización, de la suya).
        $havingSegment = null;
        $visibility = UserHierarchy::visibilityGroup($currentUser, 'visibleUserID', 'visibleRank', 'visibleOrganization');
        if ($visibility !== null) {
            $havingSegment = new HavingSegment();
            $havingSegment->addGroup($visibility);
        }

        $selectFields = array_merge(UsersModel::fieldsToSelect(), self::visibilityFieldsForUsers());
        $selectFields[] = "{$lastLogin} AS lastLogin";
        $selectFields[] = "(SELECT SUM({$tableTimeOnPlatform}.minutes) FROM {$tableTimeOnPlatform} WHERE {$tableTimeOnPlatform}.userID = {$tableUsers}.id) AS timeOnPlatformMinutes";

        //Sin tiempo registrado (NULL) ordena como el menor.
        $columnsOrder = [
            "{$tableUsers}.id",
            'username',
            'fullname',
            'lastLogin',
            'timeOnPlatformMinutes',
        ];

        $customOrder = [
            'lastLogin' => 'DESC',
            "{$tableUsers}.id" => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_segment' => $whereSegment,
            'having_segment' => $havingSegment,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'custom_order' => $customOrder,
            'mapper' => new UsersModel(),
            'request' => $request,
            'on_set_data' => function ($e) {

                $timeOnPlatformMinutes = $e->timeOnPlatformMinutes;
                $lastLogin = is_string($e->lastLogin ?? null) ? strtotime($e->lastLogin) : false;

                $columns = [];
                $columns[] = $e->idPadding;
                $columns[] = escape_html($e->username);
                $columns[] = escape_html($e->fullname) . ((int) ($e->status ?? 0) === UsersModel::STATUS_USER_DELETED ? ' ' . \PiecesPHP\Core\Utilities\Helpers\StatusLabel::neutral(__(LOGIN_REPORT_LANG_GROUP, 'Eliminado')) : '');
                $columns[] = $lastLogin !== false ? escape_html(date('d-m-Y H:i', $lastLogin)) : '';
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

        $tableUsers = UsersModel::TABLE;

        //Sin ingreso EN EL PERIODO (`days`): nunca, o el último correcto antes de su principio. Por marcador. Un usuario
        //Eliminado deja de existir en la práctica: no es una cuenta sin ingreso.
        [, $from] = self::periodFromRequest($request);
        $lastLogin = self::lastSuccessfulLoginSQL();
        $whereSegment = new WhereSegment();
        $whereSegment->addGroup(new WhereItemGroup([
            new WhereItem("{$tableUsers}.id", WhereItem::NOT_EQUAL_OPERATOR, UsersModel::SYSTEM_USER_ID, WhereItem::AND_OPERATOR),
            new WhereItem("{$tableUsers}.status", WhereItem::NOT_EQUAL_OPERATOR, UsersModel::STATUS_USER_DELETED, WhereItem::AND_OPERATOR),
        ]));
        $whereSegment->addGroup(new WhereItemGroup([
            new WhereItem($lastLogin, WhereItem::IS_NULL_OPERATOR, '', WhereItem::OR_OPERATOR),
            new WhereItem($lastLogin, WhereItem::LESS_THAN_OPERATOR, $from->format('Y-m-d H:i:s'), WhereItem::AND_OPERATOR),
        ]));

        //P133: lo propio y lo de rangos menores (el administrador de organización, de la suya).
        $havingSegment = null;
        $visibility = UserHierarchy::visibilityGroup($currentUser, 'visibleUserID', 'visibleRank', 'visibleOrganization');
        if ($visibility !== null) {
            $havingSegment = new HavingSegment();
            $havingSegment->addGroup($visibility);
        }

        $selectFields = array_merge(UsersModel::fieldsToSelect(), self::visibilityFieldsForUsers());

        $columnsOrder = [
            "{$tableUsers}.id",
            'username',
            'fullname',
        ];

        $customOrder = [
            'username' => 'ASC',
            "{$tableUsers}.id" => 'DESC',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([
            'where_segment' => $whereSegment,
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
     * El último ingreso correcto de cada usuario de la consulta sobre la tabla de usuarios (sin valores: solo nombres).
     *
     * @return string
     */
    protected static function lastSuccessfulLoginSQL(): string
    {
        $table = self::TABLE;
        $tableUsers = UsersModel::TABLE;
        return "(SELECT MAX({$table}.date) FROM {$table} WHERE {$table}.userID = {$tableUsers}.id AND {$table}.success = " . self::SUCCESS_ATTEMPT . ')';
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

        [$organizationID, $userType] = self::userSubqueries();

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
     * La organización y el tipo del usuario de cada intento, como subconsultas: las comparten el listado y el conteo.
     *
     * @return array{0:string,1:string} [organización, tipo]
     */
    protected static function userSubqueries(): array
    {
        $table = (new LoginAttemptsModel)->getModel()->getTable();
        $tableUser = UsersModel::TABLE;
        return [
            "SELECT {$tableUser}.organization FROM {$tableUser} WHERE {$tableUser}.id = {$table}.userID",
            "SELECT {$tableUser}.type FROM {$tableUser} WHERE {$tableUser}.id = {$table}.userID",
        ];
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
     * @param object|null $actor Quién mira, para la jerarquía (P133); null, el usuario de la sesión
     *
     * @return static[]|array
     */
    public static function all(bool $asMapper = false, ?object $actor = null)
    {

        $currentUser = $actor ?? getLoggedFrameworkUser();

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
     * El periodo de la petición: `days` (uno de PERIODS; otro valor, DEFAULT_PERIOD) y su rango [desde, hasta), los días
     * que acaban hoy, incluido.
     *
     * @param Request $request
     * @return array{0:int,1:\DateTimeImmutable,2:\DateTimeImmutable}
     */
    public static function periodFromRequest(Request $request): array
    {
        $requested = $request->getQueryParam('days', null);
        $days = is_string($requested) && ctype_digit($requested) && in_array((int) $requested, self::PERIODS, true) ? (int) $requested : self::DEFAULT_PERIOD;
        [$from, $to] = self::periodRange($days, new \DateTimeImmutable());
        return [$days, $from, $to];
    }

    /**
     * [desde, hasta) de los `$days` días que acaban el día de `$now`, incluido.
     *
     * @param int $days
     * @param \DateTimeImmutable $now
     * @return array{0:\DateTimeImmutable,1:\DateTimeImmutable}
     */
    public static function periodRange(int $days, \DateTimeImmutable $now): array
    {
        $to = $now->setTime(0, 0)->modify('+1 day');
        return [$to->modify('-' . $days . ' days'), $to];
    }

    /**
     * Desde cuándo hay datos si el registro está RECORTADO para el periodo: lleno (`$stored` ≥ `$maxRows`) y con el más
     * viejo posterior al principio del periodo; null si no. Puro, para probarlo sin llenar la tabla.
     *
     * @param \DateTimeImmutable|null $oldest
     * @param \DateTimeImmutable $from
     * @param int $stored
     * @param int $maxRows
     * @return \DateTimeImmutable|null
     */
    public static function trimmedSince(?\DateTimeImmutable $oldest, \DateTimeImmutable $from, int $stored, int $maxRows): ?\DateTimeImmutable
    {
        return $oldest !== null && $oldest > $from && $stored >= $maxRows ? $oldest : null;
    }

    /**
     * Cuántos intentos guarda la tabla, de todos: el recorte (trim()) los cuenta así.
     *
     * @return int
     */
    public static function storedCount(): int
    {
        $model = self::model();
        $model->select(['COUNT(*) AS stored']);
        $model->execute();
        $result = $model->result();
        $row = is_array($result) && isset($result[0]) && is_object($result[0]) ? (array) $result[0] : [];
        return is_numeric($row['stored'] ?? null) ? (int) $row['stored'] : 0;
    }

    /**
     * La fecha del intento más viejo que se guarda (el recorte, trim(), es de toda la tabla); null sin intentos.
     *
     * @return \DateTimeImmutable|null
     */
    public static function oldestDate(): ?\DateTimeImmutable
    {
        $model = self::model();
        $model->select(['MIN(date) AS oldest']);
        $model->execute();
        $result = $model->result();
        $oldest = is_array($result) && isset($result[0]) && is_object($result[0]) ? ($result[0]->oldest ?? null) : null;
        return is_string($oldest) && $oldest !== '' ? new \DateTimeImmutable($oldest) : null;
    }

    /**
     * Las cifras de Accesos › Intentos en [desde, hasta): por resultado, los usuarios ESCRITOS distintos (sin distinguir
     * mayúsculas) y la serie por día, con un punto también para los días sin intentos. Misma visibilidad que all() (P133).
     *
     * @param \DateTimeImmutable $from
     * @param \DateTimeImmutable $to Exclusivo
     * @param object|null $actor Quién mira; null, el usuario de la sesión
     * @return array{total:int,success:int,error:int,uniqueUsers:int,days:array<string,array{success:int,error:int}>}
     */
    public static function periodStats(\DateTimeImmutable $from, \DateTimeImmutable $to, ?object $actor = null): array
    {
        $actor ??= getLoggedFrameworkUser();

        $days = [];
        for ($day = $from; $day < $to; $day = $day->modify('+1 day')) {
            $days[$day->format('Y-m-d')] = ['success' => 0, 'error' => 0];
        }
        $stats = ['total' => 0, 'success' => 0, 'error' => 0, 'uniqueUsers' => 0, 'days' => $days];

        $model = self::model();
        $table = $model->getTable();
        [$organizationID, $userType] = self::userSubqueries();
        $model->select([
            "{$table}.userID",
            "({$organizationID}) AS organizationID",
            "({$userType}) AS userType",
            "{$table}.success",
            "DATE({$table}.date) AS day",
            "LOWER({$table}.usernameAttempt) AS typedUser",
            'COUNT(*) AS amount',
        ]);
        $model->where(self::rangeSegment($from, $to));
        $model->groupBy(["{$table}.userID", "{$table}.success", 'organizationID', 'userType', 'day', 'typedUser']);
        $model->execute();

        $groups = $model->result();
        $typedUsers = [];
        foreach (is_array($groups) ? $groups : [] as $group) {
            if (!is_object($group) || !self::groupIsVisible($actor, $group)) {
                continue;
            }
            $row = (array) $group;
            $amount = is_numeric($row['amount'] ?? null) ? (int) $row['amount'] : 0;
            $day = is_string($row['day'] ?? null) ? $row['day'] : '';
            $successValue = is_numeric($row['success'] ?? null) ? (int) $row['success'] : null;
            $key = $successValue === 1 ? 'success' : ($successValue === 0 ? 'error' : null);
            $stats['total'] += $amount;
            if ($key !== null) {
                $stats[$key] += $amount;
                if (isset($stats['days'][$day])) {
                    $stats['days'][$day][$key] += $amount;
                }
            }
            $typedUsers[is_scalar($row['typedUser'] ?? null) ? (string) $row['typedUser'] : ''] = true;
        }
        $stats['uniqueUsers'] = count($typedUsers);

        return $stats;
    }

    /**
     * [desde, hasta) sobre la fecha del intento, por marcador.
     *
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return WhereSegment
     */
    protected static function rangeSegment(\DateTimeInterface $from, \DateTimeInterface $to): WhereSegment
    {
        $table = self::TABLE;
        $segment = new WhereSegment();
        $segment->addGroup(new WhereItemGroup([
            new WhereItem("{$table}.date", WhereItem::GREATER_OR_EQUAL_OPERATOR, $from->format('Y-m-d H:i:s'), WhereItem::AND_OPERATOR),
            new WhereItem("{$table}.date", WhereItem::LESS_THAN_OPERATOR, $to->format('Y-m-d H:i:s'), WhereItem::AND_OPERATOR),
        ]));
        return $segment;
    }

    /**
     * La misma regla que all() (P133) sobre un grupo con userID, userType y organizationID.
     *
     * @param object|null $actor
     * @param object $group
     * @return bool
     */
    protected static function groupIsVisible(?object $actor, object $group): bool
    {
        return UserHierarchy::canSee(
            $actor,
            is_numeric($group->userID ?? null) ? (int) $group->userID : null,
            is_numeric($group->userType ?? null) ? (int) $group->userType : null,
            is_numeric($group->organizationID ?? null) ? (int) $group->organizationID : null,
            true
        );
    }

    /**
     * La fecha de un intento, relativa («hace 3 horas»), en `<time>` con la exacta en `datetime` y en `title`. Escapada.
     *
     * @param string $date Y-m-d H:i:s
     * @param \DateTimeInterface $now
     * @return string
     */
    public static function relativeDateHTML(string $date, \DateTimeInterface $now): string
    {
        $time = strtotime($date);
        if ($time === false) {
            return '';
        }
        $seconds = max(0, $now->getTimestamp() - $time);
        $minutes = intdiv($seconds, 60);
        $hours = intdiv($seconds, 3600);
        $days = intdiv($seconds, 86400);
        $text = match (true) {
            $minutes < 1 => __(LOGIN_REPORT_LANG_GROUP, 'hace un momento'),
            $minutes === 1 => __(LOGIN_REPORT_LANG_GROUP, 'hace 1 minuto'),
            $minutes < 60 => sprintf(__(LOGIN_REPORT_LANG_GROUP, 'hace %d minutos'), $minutes),
            $hours === 1 => __(LOGIN_REPORT_LANG_GROUP, 'hace 1 hora'),
            $hours < 24 => sprintf(__(LOGIN_REPORT_LANG_GROUP, 'hace %d horas'), $hours),
            $days === 1 => __(LOGIN_REPORT_LANG_GROUP, 'hace 1 día'),
            default => sprintf(__(LOGIN_REPORT_LANG_GROUP, 'hace %d días'), $days),
        };
        return '<time datetime="' . escape_html(date('Y-m-d\TH:i:s', $time)) . '" title="' . escape_html(date('d-m-Y H:i', $time)) . '">' . escape_html($text) . '</time>';
    }

    /**
     * Las tres cifras del resumen de Accesos sin cargar los intentos: cuenta en SQL por usuario y resultado, y aplica a
     * cada grupo la misma regla que all() (P133). La regla solo mira usuario, tipo y organización, así que el resultado
     * es el mismo que contar sobre all().
     *
     * @param object|null $actor Quién mira; null, el usuario de la sesión
     * @param \DateTimeInterface|null $from Con `$to`, solo los intentos de [desde, hasta); sin ellos, todos (como siempre)
     * @param \DateTimeInterface|null $to Exclusivo
     * @return array{total:int,success:int,error:int}
     */
    public static function countsBySuccess(?object $actor = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $actor ??= getLoggedFrameworkUser();

        $model = self::model();
        $table = $model->getTable();
        [$organizationID, $userType] = self::userSubqueries();
        $model->select([
            "{$table}.userID",
            "({$organizationID}) AS organizationID",
            "({$userType}) AS userType",
            "{$table}.success",
            'COUNT(*) AS amount',
        ]);
        if ($from !== null && $to !== null) {
            $model->where(self::rangeSegment($from, $to));
        }
        $model->groupBy(["{$table}.userID", "{$table}.success", 'organizationID', 'userType']);
        $model->execute();

        $groups = $model->result();
        $counts = ['total' => 0, 'success' => 0, 'error' => 0];
        foreach (is_array($groups) ? $groups : [] as $group) {
            $visible = UserHierarchy::canSee(
                $actor,
                is_numeric($group->userID ?? null) ? (int) $group->userID : null,
                is_numeric($group->userType ?? null) ? (int) $group->userType : null,
                is_numeric($group->organizationID ?? null) ? (int) $group->organizationID : null,
                true
            );
            if (!$visible) {
                continue;
            }
            $amount = is_numeric($group->amount ?? null) ? (int) $group->amount : 0;
            $counts['total'] += $amount;
            //Como contaba la vista: 1 es exitoso y 0 erróneo.
            if ($group->success == 1) {
                $counts['success'] += $amount;
            } elseif ($group->success == 0) {
                $counts['error'] += $amount;
            }
        }

        return $counts;
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new LoginAttemptsModel())->getModel();
    }
}
