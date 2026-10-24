<?php

/**
 * LogsMapper.php
 */

namespace EventsLog\Mappers;

use PiecesPHP\UserSystem\ORM\UsersModel;
use EventsLog\LogsLang;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Database\Meta\MetaProperty;

/**
 * LogsMapper.
 *
 * @package     EventsLog\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @property int|null $id
 * @property string $textMessage
 * @property \stdClass|array|string $textMessageVariables
 * @property string|null $referenceColumn
 * @property string|null $referenceValue
 * @property string|null $referenceSource
 * @property int|UsersModel $createdBy
 * @property string|\DateTime $createdAt
 * @property string|null $ip
 * @property string|null $geolocationByIp
 * @property \stdClass|string|null $meta
 */
class LogsMapper extends EntityMapperExtensible
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'textMessage' => [
            'type' => 'text',
        ],
        'textMessageVariables' => [
            'type' => 'json',
            'null' => false,
        ],
        'referenceColumn' => [
            'type' => 'text',
            'null' => true,
        ],
        'referenceValue' => [
            'type' => 'text',
            'null' => true,
        ],
        'referenceSource' => [
            'type' => 'text',
            'null' => true,
        ],
        'createdBy' => [
            'type' => 'bigint',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'createdAt' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
            'dafault' => null,
        ],
    ];

    const TABLE = 'actions_log';
    const LANG_GROUP = LogsLang::LANG_GROUP;
    const ORDER_BY_PREFERENCE = [
        '`id` DESC',
        '`createdAt` DESC',
        '`createdBy` DESC',
    ];

    /**
     * La clave de `meta` que dice QUIÉN actuaba de verdad.
     *
     * NO es meta-propiedad: `objectToMapper()` las exige todas y las filas viejas no la tienen.
     */
    const META_ACTOR = 'actor';

    /**
     * Quien actúa es el usuario principal suplantando a otro: `createdBy` es el suplantado.
     */
    const ACTOR_IMPERSONATION = 'impersonation';

    /**
     * Sin nadie conectado. `createdBy` queda en el usuario de sistema (UsersModel::SYSTEM_USER_ID): la columna no acepta
     * nulo (medido) y antes se ponía 1, que es un root real.
     */
    const ACTOR_SYSTEM = 'system';

    const MSG_GENERIC = 'GENERIC';
    const MSG_UPDATE_PROFILE = 'UPDATE_PROFILE';
    const MSG_UPDATE_PROFILE_IMAGE = 'UPDATE_PROFILE_IMAGE';
    const MSG_REQUEST_PASSWORD_RECOVERY = 'REQUEST_PASSWORD_RECOVERY';
    const MSG_PASSWORD_RECOVERY_BY_CODE = 'PASSWORD_RECOVERY_BY_CODE';
    const MSG_REVOKE_OWN_SESSIONS = 'REVOKE_OWN_SESSIONS';
    const MSG_REVOKE_USER_SESSIONS = 'REVOKE_USER_SESSIONS';
    const MSG_REVOKE_ALL_SESSIONS = 'REVOKE_ALL_SESSIONS';
    const MSG_IMPERSONATION_START = 'IMPERSONATION_START';
    const MSG_IMPERSONATION_END = 'IMPERSONATION_END';
    const MSG_SYSTEM_ALERT_TOGGLED = 'SYSTEM_ALERT_TOGGLED';
    const MSG_BROKEN_LINKS_DELETED = 'BROKEN_LINKS_DELETED';
    const MSG_MAINTENANCE_LINKS_CLEANED = 'MAINTENANCE_LINKS_CLEANED';
    const MSG_MAINTENANCE_CACHE_CLEANED = 'MAINTENANCE_CACHE_CLEANED';
    const MSG_SITE_MAINTENANCE_TOGGLED = 'SITE_MAINTENANCE_TOGGLED';
    const MSG_MAIL_BODY_VIEWED = 'MAIL_BODY_VIEWED';
    const MSG_MAIL_BODY_UNREADABLE = 'MAIL_BODY_UNREADABLE';
    const MSG_DATA_TRANSFER_TOGGLED = 'DATA_TRANSFER_TOGGLED';
    const MSG_SESSION_CLOSED = 'SESSION_CLOSED';
    const MSG_SESSION_EXTENDED = 'SESSION_EXTENDED';
    const MSG_OTHER_SESSIONS_CLOSED = 'OTHER_SESSIONS_CLOSED';
    //Tokens y códigos (Registros › Tokens y códigos). Nunca con el valor del código en las variables.
    const MSG_TOKEN_ISSUED = 'TOKEN_ISSUED';
    const MSG_TOKEN_USED = 'TOKEN_USED';
    const MSG_TOKEN_REJECTED = 'TOKEN_REJECTED';
    const MSG_TOKEN_VALUE_VIEWED = 'TOKEN_VALUE_VIEWED';
    const MSG_TOKEN_INVALIDATED = 'TOKEN_INVALIDATED';
    const MSG_TWO_FACTOR_RESET = 'TWO_FACTOR_RESET';

    const MESSAGES = [
        self::MSG_GENERIC => '%message%',
        self::MSG_UPDATE_PROFILE => 'El usuario %username% ha actualizado su perfil',
        self::MSG_UPDATE_PROFILE_IMAGE => 'El usuario %username% ha actualizado su imagen de perfil',
        self::MSG_REQUEST_PASSWORD_RECOVERY => 'El usuario %username% ha solicitado recuperar su contraseña',
        self::MSG_PASSWORD_RECOVERY_BY_CODE => 'El usuario %username% ha recuperado su contraseña mediante un código',
        self::MSG_REVOKE_OWN_SESSIONS => 'El usuario %username% ha cerrado todas sus sesiones',
        self::MSG_REVOKE_USER_SESSIONS => 'El usuario %username% ha cerrado todas las sesiones de %target%',
        self::MSG_REVOKE_ALL_SESSIONS => 'Se cerraron las sesiones de todos los usuarios desde el terminal (%users% usuarios)',
        self::MSG_IMPERSONATION_START => 'El usuario %username% empezó a actuar como %target%',
        self::MSG_IMPERSONATION_END => 'El usuario %username% dejó de actuar como otro usuario',
        self::MSG_SYSTEM_ALERT_TOGGLED => '%username% %action% el aviso del sistema «%alert%».',
        self::MSG_BROKEN_LINKS_DELETED => '%username% borró %deleted% enlace(s) roto(s) de server-delegated y %directories% carpeta(s) vacía(s); %failed% no se pudieron borrar por falta de permiso. Enlaces: %links%',
        self::MSG_MAINTENANCE_LINKS_CLEANED => '%username% borró «%target%» desde mantenimiento: %deleted% enlace(s) borrado(s); %failed% no se pudieron borrar por falta de permiso.',
        self::MSG_MAINTENANCE_CACHE_CLEANED => '%username% borró «%target%» desde mantenimiento: %freed% byte(s) liberado(s).',
        self::MSG_SITE_MAINTENANCE_TOGGLED => '%username% %action% el modo mantenimiento del sitio.',
        self::MSG_MAIL_BODY_VIEWED => '%username% vio el cuerpo del correo #%id% del registro.',
        self::MSG_MAIL_BODY_UNREADABLE => '%username% pidió el cuerpo del correo #%id% del registro, y no se pudo descifrar.',
        self::MSG_DATA_TRANSFER_TOGGLED => '%username% %action% el %kind% «%key%» de Importar y exportar.',
        self::MSG_SESSION_CLOSED => '%username% cerró una sesión de %target% (%reason%).',
        self::MSG_SESSION_EXTENDED => '%username% extendió una sesión de %target% hasta el %until%.',
        self::MSG_OTHER_SESSIONS_CLOSED => '%username% cerró %count% de sus otras sesiones.',
        self::MSG_TOKEN_ISSUED => 'Se emitió un código (%kind%) para %target%.',
        self::MSG_TOKEN_USED => 'Se usó un código (%kind%) de %target%.',
        self::MSG_TOKEN_REJECTED => 'Se rechazó un código (%kind%) para %target%: %reason%.',
        self::MSG_TOKEN_VALUE_VIEWED => '%username% vio el valor de un código (%kind%) de %target%.',
        self::MSG_TOKEN_INVALIDATED => '%username% invalidó un código (%kind%) de %target%.',
        self::MSG_TWO_FACTOR_RESET => '%username% restableció el doble factor de %target%.',
    ];



    const MODULE_NAMES_EQUIVALENCES_BY_SOURCE = [
        UsersModel::TABLE => 'Usuarios',
    ];

    /**
     * @var string
     */
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
     * @param int $value
     * @param string $fieldCompare
     * @return static
     */
    public function __construct(?int $value = null, string $fieldCompare = 'primary_key')
    {
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, null, true), 'ip');
        $this->addMetaProperty(new MetaProperty(MetaProperty::TYPE_TEXT, null, true), 'geolocationByIp');
        parent::__construct($value, $fieldCompare);
    }

    /**
     * El nombre de cada tipo, ya traducido, para el filtro y el detalle del registro de acciones. Un tipo sin nombre sale
     * con su clave.
     *
     * @return array<string,string>
     */
    public static function messageLabels(): array
    {
        return [
            self::MSG_GENERIC => __(self::LANG_GROUP, 'Mensaje libre'),
            self::MSG_UPDATE_PROFILE => __(self::LANG_GROUP, 'Perfil actualizado'),
            self::MSG_UPDATE_PROFILE_IMAGE => __(self::LANG_GROUP, 'Imagen de perfil actualizada'),
            self::MSG_REQUEST_PASSWORD_RECOVERY => __(self::LANG_GROUP, 'Recuperación de contraseña pedida'),
            self::MSG_PASSWORD_RECOVERY_BY_CODE => __(self::LANG_GROUP, 'Contraseña recuperada con código'),
            self::MSG_REVOKE_OWN_SESSIONS => __(self::LANG_GROUP, 'Cierre de las sesiones propias'),
            self::MSG_REVOKE_USER_SESSIONS => __(self::LANG_GROUP, 'Cierre de las sesiones de otro usuario'),
            self::MSG_REVOKE_ALL_SESSIONS => __(self::LANG_GROUP, 'Cierre de todas las sesiones'),
            self::MSG_IMPERSONATION_START => __(self::LANG_GROUP, 'Empieza a actuar como otro usuario'),
            self::MSG_IMPERSONATION_END => __(self::LANG_GROUP, 'Deja de actuar como otro usuario'),
            self::MSG_SYSTEM_ALERT_TOGGLED => __(self::LANG_GROUP, 'Aviso del sistema mostrado u oculto'),
            self::MSG_BROKEN_LINKS_DELETED => __(self::LANG_GROUP, 'Enlaces rotos borrados'),
            self::MSG_MAINTENANCE_LINKS_CLEANED => __(self::LANG_GROUP, 'Accesos directos borrados desde mantenimiento'),
            self::MSG_MAINTENANCE_CACHE_CLEANED => __(self::LANG_GROUP, 'Caché borrada desde mantenimiento'),
            self::MSG_SITE_MAINTENANCE_TOGGLED => __(self::LANG_GROUP, 'Modo mantenimiento del sitio'),
            self::MSG_MAIL_BODY_VIEWED => __(self::LANG_GROUP, 'Cuerpo de un correo consultado'),
            self::MSG_MAIL_BODY_UNREADABLE => __(self::LANG_GROUP, 'Cuerpo de un correo ilegible'),
            self::MSG_DATA_TRANSFER_TOGGLED => __(self::LANG_GROUP, 'Importador o exportador encendido o apagado'),
            self::MSG_SESSION_CLOSED => __(self::LANG_GROUP, 'Sesión cerrada'),
            self::MSG_SESSION_EXTENDED => __(self::LANG_GROUP, 'Sesión extendida'),
            self::MSG_OTHER_SESSIONS_CLOSED => __(self::LANG_GROUP, 'Otras sesiones propias cerradas'),
            self::MSG_TOKEN_ISSUED => __(self::LANG_GROUP, 'Código emitido'),
            self::MSG_TOKEN_USED => __(self::LANG_GROUP, 'Código usado'),
            self::MSG_TOKEN_REJECTED => __(self::LANG_GROUP, 'Código rechazado'),
            self::MSG_TOKEN_VALUE_VIEWED => __(self::LANG_GROUP, 'Valor de un código consultado'),
            self::MSG_TOKEN_INVALIDATED => __(self::LANG_GROUP, 'Código invalidado'),
            self::MSG_TWO_FACTOR_RESET => __(self::LANG_GROUP, 'Doble factor restablecido'),
        ];
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        $textMessage = $this->textMessage;
        $textMessageVariables = $this->textMessageVariables;

        if (!is_object($textMessageVariables) && !is_array($textMessageVariables)) {
            $textMessageVariables = [];
        }

        return strReplaceTemplate(__(self::LANG_GROUP, $textMessage), (array) $textMessageVariables);
    }

    /**
     * @return string
     */
    public function getReadableMessage()
    {
        return $this->getMessage();
    }

    /**
     * @return string
     */
    public function createdByFullname()
    {
        $createdBy = $this->createdBy;

        if (!is_object($createdBy)) {
            $this->createdBy = new UsersModel($createdBy);
            $createdBy = $this->createdBy;
        }

        return $createdBy->getFullName();
    }

    /**
     * @param string $format
     * @return string
     */
    public function createdAtFormat(?string $format = null)
    {

        $formatDefault = __(self::LANG_GROUP, "F d {1} Y");
        $createdAt = $this->createdAt instanceof \DateTime  ? $this->createdAt : null;
        if ($format !== null) {
            $formated = localeDateFormat($format, $createdAt);
        } else {
            $formated = localeDateFormat($formatDefault, $createdAt);
            $formated = strReplaceTemplate($formated, [
                '{1}' => __(self::LANG_GROUP, 'de'),
            ]);
        }
        return $formated;
    }

    /**
     * Cómo se nombra en pantalla a quien hizo la acción.
     *
     * Sin nadie conectado dice «Sistema», no el nombre del usuario 1 que guarda `createdBy`.
     *
     * @param string|null $actorKind El `kind` de `meta.actor`, si lo hay.
     * @param string|null $actorUser El nombre de quien actuaba de verdad.
     * @param string|null $createdByUser El nombre de lo que guarda `createdBy`.
     * @return string
     */
    public static function actorLabel(?string $actorKind, ?string $actorUser, ?string $createdByUser): string
    {
        if ($actorKind === self::ACTOR_SYSTEM) {
            return __(self::LANG_GROUP, 'Sistema');
        }
        if ($actorKind === self::ACTOR_IMPERSONATION && $actorUser !== null && $actorUser !== '') {
            return sprintf(
                __(self::LANG_GROUP, '%s, en nombre de %s'),
                $actorUser,
                $createdByUser !== null && $createdByUser !== '' ? $createdByUser : __(self::LANG_GROUP, 'un usuario que ya no existe')
            );
        }
        return $createdByUser ?? '';
    }

    /**
     * Anota en `meta` quién actuaba de verdad, cuando `createdBy` no lo dice.
     *
     * Dos casos; en los demás no toca nada.
     *
     * @param mixed $loggedUser El usuario que la aplicación tiene por conectado, o null.
     * @return void
     */
    protected function declareActor($loggedUser): void
    {
        $rootOriginalId = get_config(ROOT_ORIGINAL_ID_CONFIG_NAME);
        $rootOriginalId = is_int($rootOriginalId) || (is_string($rootOriginalId) && ctype_digit($rootOriginalId)) ? (int) $rootOriginalId : null;
        //SIN `isset()`: `id` es mágica y la clase no implementa `__isset()`, así que daba false
        //con el valor presente. Y `->userMapper` es el mapper entero, no el id.
        $loggedIdRaw = $loggedUser !== null ? ($loggedUser->id ?? null) : null;
        $loggedId = is_int($loggedIdRaw) || (is_string($loggedIdRaw) && ctype_digit($loggedIdRaw)) ? (int) $loggedIdRaw : null;

        $actor = null;
        if ($loggedUser === null) {
            $actor = ['kind' => self::ACTOR_SYSTEM, 'id' => null, 'onBehalfOf' => null];
        } elseif ($rootOriginalId !== null && $loggedId !== null && $rootOriginalId !== $loggedId) {
            $actor = ['kind' => self::ACTOR_IMPERSONATION, 'id' => $rootOriginalId, 'onBehalfOf' => $loggedId];
        }

        if ($actor === null) {
            return;
        }

        //Se FUSIONA: `metaValueToSave()` parte de esta columna y añade encima las meta-propiedades,
        //así que lo que se ponga aquí convive con `ip` y `geolocationByIp`.
        $meta = $this->meta;
        $meta = $meta instanceof \stdClass ? $meta : (is_string($meta) && $meta !== '' ? json_decode($meta) : null);
        $meta = $meta instanceof \stdClass ? $meta : new \stdClass();
        $meta->{self::META_ACTOR} = (object) $actor;
        $this->meta = $meta;
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $loggedUser = getLoggedFrameworkUser(true);
        $this->createdAt = new \DateTime();
        $this->createdBy = $loggedUser !== null ? $loggedUser->userMapper : UsersModel::SYSTEM_USER_ID;
        $this->declareActor($loggedUser);
        $saveResult = parent::save();

        if ($saveResult) {
            $idInserted = $this->getInsertIDOnSave();
            $this->id = $idInserted;
        }

        return $saveResult;

    }

    /**
     * @param bool $noDateUpdate
     * @inheritDoc
     */
    public function update(bool $noDateUpdate = false)
    {
        return false;
    }

    /**
     * @param string $messageType
     * @param array $variables
     * @param string $referenceColumn
     * @param string $referenceValue
     * @param string $referenceSource
     * @return LogsMapper
     */
    public static function addLog(string $messageType, array $variables = [], ?string $referenceColumn = null, ?string $referenceValue = null, ?string $referenceSource = null)
    {
        $mapper = new LogsMapper;
        $notExistsMessage = true;

        //SIEMPRE las dos: objectToMapper() exige todas las meta, y sin la extensión geoip la fila quedaba ilegible.
        $ip = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $geolocationByIp = __(self::LANG_GROUP, 'Sin especificar');
        if (function_exists('geoip_record_by_name')) {
            try {
                $geoIP = call_user_func('geoip_record_by_name', $ip);
                if (is_array($geoIP) && isset($geoIP['country_name']) && is_string($geoIP['country_name'])) {
                    $geolocationByIp = $geoIP['country_name'];
                }
            } catch (\Throwable $e) {}
        }
        $mapper->geolocationByIp = $geolocationByIp;
        $mapper->ip = $ip;

        foreach (self::MESSAGES as $type => $text) {

            if ($type == $messageType) {
                $notExistsMessage = false;
                $mapper->textMessage = $text;
                $mapper->textMessageVariables = $variables;

                if ($referenceColumn !== null || $referenceValue !== null || $referenceSource !== null) {
                    $mapper->referenceColumn = $referenceColumn;
                    $mapper->referenceValue = $referenceValue;
                    $mapper->referenceSource = $referenceSource;
                }

                $mapper->save();
                self::trim();
                break;
            }

        }

        if ($notExistsMessage) {
            throw new \Exception(__(self::LANG_GROUP, "El tipo de mensaje '{$messageType}' no existe."));
        }
        return $mapper;
    }

    /**
     * Campos adicionales:
     * - idPadding
     * - createdByUser
     * - textMessageReplacement
     * - createdAtFormat
     * - ip
     * - geolocationByIp
     * - moduleNameFromSource
     * - moduleName
     * @return string[]
     */
    public static function fieldsToSelect()
    {

        $mapper = (new LogsMapper);
        $model = $mapper->getModel();
        $table = $model->getTable();

        $tableUser = UsersModel::TABLE;

        $createdAtFormatReplacements = json_encode([
            '{1}' => __(self::LANG_GROUP, 'de'),
        ], \JSON_UNESCAPED_SLASHES  | \JSON_UNESCAPED_UNICODE);
        $moduleNameBySourceJSON = json_encode((object) self::moduleNamesEquivalencesBySource(), \JSON_UNESCAPED_UNICODE);

        $fields = [
            "LPAD({$table}.id, GREATEST(5, CHAR_LENGTH({$table}.id)), '0') AS idPadding",
            "(SELECT {$tableUser}.username FROM {$tableUser} WHERE {$tableUser}.id = {$table}.createdBy) AS createdByUser",
            "strTemplateReplace({$table}.textMessage, {$table}.textMessageVariables) AS textMessageReplacement",
            "strTemplateReplace(DATE_FORMAT({$table}.createdAt, '%M %d {1} %Y %h:%i:%s %p'), '{$createdAtFormatReplacements}') AS createdAtFormat",
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.ip')) AS ip",
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$.geolocationByIp')) AS geolocationByIp",
            //Quién actuaba de verdad: `createdBy` es el suplantado, o un 1 que no significa nadie.
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$." . self::META_ACTOR . ".kind')) AS actorKind",
            "JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$." . self::META_ACTOR . ".id')) AS actorID",
            "(SELECT {$tableUser}.username FROM {$tableUser} WHERE {$tableUser}.id = JSON_UNQUOTE(JSON_EXTRACT({$table}.meta, '$." . self::META_ACTOR . ".id'))) AS actorUser",
            "JSON_UNQUOTE(JSON_EXTRACT('{$moduleNameBySourceJSON}', CONCAT('$.', {$table}.referenceSource))) AS moduleNameFromSource",
            "(SELECT IF(moduleNameFromSource IS NULL, {$table}.referenceSource, moduleNameFromSource)) AS moduleName",
        ];

        $allFields = array_keys(self::getFields());

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
        $model = self::model();

        $selectFields = [];

        $model->select($selectFields);

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = self::objectToMapper($value);
            }
        }

        return $result;
    }

    /**
     * @param string $column
     * @param int $value
     * @param bool $asMapper
     *
     * @return static[]|array
     */
    public static function allBy(string $column, $value, bool $asMapper = false)
    {
        $model = self::model();

        $model->select()->where([
            $column => $value,
        ])->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = self::objectToMapper($value);
            }
        }

        return $result;
    }

    /**
     * @param string|int $value
     * @param string $source
     * @param bool $asMapper
     * @return static[]|array
     */
    public static function allByReference($value, string $source, bool $asMapper = true)
    {
        $model = self::model();

        $model->select()->where([
            'referenceValue' => $value,
            'referenceSource' => $source,
        ])->orderBy(self::ORDER_BY_PREFERENCE)->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $v) {
                $result[$key] = self::objectToMapper($v);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    public static function moduleNamesEquivalencesBySource()
    {
        $options = self::MODULE_NAMES_EQUIVALENCES_BY_SOURCE;
        foreach ($options as $key => $value) {
            $options[$key] = __(self::LANG_GROUP, $value);
        }
        return $options;
    }

    /**
     * @param mixed $value
     * @param string $column
     * @param boolean $as_mapper
     * @return ($as_mapper is true ? static : \stdClass)|null
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

        $result = !empty($result) ? $result[0] : null;

        if (!is_null($result) && $as_mapper) {
            $result = self::objectToMapper($result);
        }

        return $result;
    }

    /**
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

        return !empty($result);
    }

    /**
     * Devuelve el mapeador desde un objeto
     *
     * @param \stdClass $element
     * @return LogsMapper|null
     */
    public static function objectToMapper(\stdClass $element)
    {

        $element = (array) $element;
        $mapper = new LogsMapper;
        //La foto es el argumento: ya se tiene la fila entera. Ver T87.
        $mapper->seedSnapshotFrom($element);
        $fieldsFilleds = [];
        $fields = array_merge(array_keys($mapper->fields), array_keys($mapper->getMetaProperties()));

        foreach ($element as $property => $value) {

            if (in_array($property, $fields)) {

                if ($property == 'meta') {

                    $value = $value instanceof \stdClass  ? $value : @json_decode($value);

                    if ($value instanceof \stdClass) {
                        foreach ($value as $metaPropertyName => $metaPropertyValue) {

                            if ($mapper->hasMetaProperty($metaPropertyName)) {
                                $mapper->$metaPropertyName = $metaPropertyValue;
                                $fieldsFilleds[] = $metaPropertyName;
                            }

                        }
                    }

                } else {
                    $mapper->$property = $value;
                }

                $fieldsFilleds[] = $property;

            }

        }

        $allFilled = count($fieldsFilleds) === count($fields);

        return $allFilled ? $mapper : null;

    }

    /**
     * @return bool
     */
    public static function jsonExtractExistsMySQL()
    {

        try {

            $json = [
                'ok' => true,
            ];
            $json = json_encode($json);
            $sql = "SELECT JSON_EXTRACT('{$json}'" . ', \'$.test\')';
            $prepared = self::model()->prepare($sql);
            $prepared->execute();
            return true;

        } catch (\PDOException $e) {

            if ($e->getCode() == 1305 || $e->getCode() == 42000) {
                return false;
            } else {
                throw $e;
            }

        }

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new LogsMapper)->getModel();
    }
}
