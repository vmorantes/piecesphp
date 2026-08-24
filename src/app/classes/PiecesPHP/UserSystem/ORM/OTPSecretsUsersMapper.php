<?php
/**
 * OTPSecretsUsersMapper.php
 */
namespace PiecesPHP\UserSystem\ORM;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\Authentication\TOTPStandard;

/**
 * OTPSecretsUsersMapper.
 *
 * @package     PiecesPHP\UserSystem\ORM
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 * @property int|null $id
 * @property int|UsersModel $user
 * @property string $secret
 * @property int $intervalTOTP Solo para METHOD_TOTP
 * @property string $oneUseCode Solo para METHOD_ONE_USE_CODE
 * @property \DateTime|string|null $maxDate Fecha máxima solo para METHOD_ONE_USE_CODE
 * @property string $method Método de código
 * @property string $twoAuthFactor Define si tiene el 2FA activado este usuario
 * @property string $twoAuthFactorQRViewed 1|0 Define si ya código QR fue visto para su configuración
 * @property string|null $twoAuthFactorAlias Define el alias para el QR en las aplicaciones
 * @property string $twoAuthFactorSecurityCode Define el código de respaldo en caso de perder la app 2FA (HASH)
 */
class OTPSecretsUsersMapper extends BaseEntityMapper
{

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'user' => [
            'type' => 'bigint',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
            'human_readable_reference_field' => 'username',
            'mapper' => UsersModel::class,
        ],
        'secret' => [
            'type' => 'text',
        ],
        'intervalTOTP' => [
            'type' => 'int',
        ],
        'oneUseCode' => [
            'type' => 'text',
        ],
        'maxDate' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'method' => [
            'type' => 'text',
        ],
        'twoAuthFactor' => [
            'type' => 'text',
            'default' => self::TWOAF_STATUS_DISABLED,
        ],
        'twoAuthFactorQRViewed' => [
            'type' => 'int',
            'default' => 0,
        ],
        'twoAuthFactorAlias' => [
            'type' => 'text',
            'null' => true,
            'default' => null,
        ],
        'twoAuthFactorSecurityCode' => [
            'type' => 'text',
            'default' => '',
        ],
    ];

    const TABLE = 'pcsphp_users_otp_secrets';

    const DEFAULT_INTERVAL_TOTP = 30;
    const METHOD_TOTP = 'TOTP';
    const METHOD_ONE_USE_CODE = 'ONE_USE_CODE';
    const METHODS = [
        self::METHOD_TOTP => 'TOTP',
        self::METHOD_ONE_USE_CODE => 'Código temporal de un uso',
    ];

    const TWOAF_STATUS_ENABLED = 'ENABLED';
    const TWOAF_STATUS_DISABLED = 'DISABLED';
    const TWOAF_STATUSES = [
        self::TWOAF_STATUS_ENABLED => 'Activado',
        self::TWOAF_STATUS_DISABLED => 'Desactivado',
    ];

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * @param int $value
     * @param string $fieldCompare
     * @return static
     */
    public function __construct(?int $value = null, string $fieldCompare = 'primary_key')
    {
        parent::__construct($value, $fieldCompare);
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $userID = is_object($this->user) ? $this->user->id : $this->user;
        $userID = Validator::isInteger($userID) ? (int) $userID : null;
        $saveResult = true;
        if ($userID !== null) {
            if (!self::existsUserByMethod($userID, $this->method, -1)) {
                if (!empty(UsersModel::getUsersByIDs([$userID]))) {
                    $saveResult = parent::save();
                    if ($saveResult) {
                        $idInserted = $this->getLastInsertID();
                        $this->id = $idInserted;
                    }
                }
            }
        }
        return $saveResult;

    }

    /**
     * @param bool $noDateUpdate
     * @inheritDoc
     */
    public function update(bool $noDateUpdate = false)
    {
        $getCurrentData = self::getBy($this->id, 'id');
        if ($getCurrentData !== null) {
            $this->method = $getCurrentData->method;
        }
        return parent::update();
    }
    /**
     * @param string $column
     * @param int $value
     * @return \stdClass[]
     */
    public static function allBy(string $column, $value)
    {
        $model = self::model();

        $model->select()->where([
            $column => $value,
        ])->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        return $result;
    }

    /**
     * @param mixed $value
     * @param string $column
     * @return \stdClass|null
     */
    public static function getBy($value, string $column = 'id')
    {
        $model = self::model();

        $where = [
            $column => $value,
        ];

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        $result = !empty($result) ? $result[0] : null;

        return $result;
    }

    /**
     * @param int $userID
     * @param string $code
     * @param string $method
     * @param int $durationInMinutes
     * @return bool
     */
    public static function setOTP(int $userID, string $code, string $method, int $durationInMinutes = 20)
    {
        if ($method == self::METHOD_ONE_USE_CODE) {
            return self::setOneUseCode($userID, $code, $durationInMinutes);
        } else {
            return false;
        }
    }

    /**
     * @param int $userID
     * @param string $code
     * @param int $durationInMinutes
     * @return bool
     */
    public static function setOneUseCode(int $userID, string $code, int $durationInMinutes = 20)
    {
        $model = self::model();
        $method = self::METHOD_ONE_USE_CODE;
        $where = [
            "user" => $userID,
            "method" => "{$method}",
        ];
        $model->select()->where($where);
        $model->execute();
        $result = $model->result();
        $result = !empty($result) ? $result[0] : null;
        $exists = $result !== null;
        $mapper = null;
        $expiration = new \DateTime();
        $expiration->modify("+{$durationInMinutes} minutes");

        if (!$exists) {
            $mapper = new OTPSecretsUsersMapper();
            $mapper->user = $userID;
            $mapper->secret = "";
            $mapper->intervalTOTP = self::DEFAULT_INTERVAL_TOTP;
            $mapper->oneUseCode = "";
            $mapper->maxDate = null;
            $mapper->method = $method;
            $mapper->save();
        } else {
            $mapper = new OTPSecretsUsersMapper($result->id);
        }

        if ($mapper->id !== null) {
            $mapper->oneUseCode = $code;
            $mapper->maxDate = $expiration;
            return $mapper->update();
        } else {
            return false;
        }
    }

    /**
     * Buscador de SOLO LECTURA. No lo conviertas en get-or-create: lo alcanzan rutas de
     * login sin autenticar. Para crear, {@see self::createOTPData()}.
     *
     * @param int $userID
     * @param string $method
     * @return OTPSecretsUsersMapper|null null si no hay registro o el método no es válido
     */
    public static function getOTPData(int $userID, string $method)
    {
        if ($method !== self::METHOD_ONE_USE_CODE) {
            return null;
        }
        $model = self::model();
        $model->select()->where([
            "user" => $userID,
            "method" => "{$method}",
        ]);
        $model->execute();
        $result = $model->result();
        if (empty($result)) {
            return null;
        }
        $mapper = new OTPSecretsUsersMapper($result[0]->id);
        return $mapper->id !== null ? $mapper : null;
    }

    /**
     * Mitad de ESCRITURA de {@see self::getOTPData()}. Solo desde un camino autenticado.
     *
     * @param int $userID
     * @param string $method
     * @return OTPSecretsUsersMapper|null
     */
    public static function createOTPData(int $userID, string $method)
    {
        if ($method !== self::METHOD_ONE_USE_CODE) {
            return null;
        }
        //Llama a getOTPData(): si el buscador vuelve a crear, esto se cuelga.
        $existing = self::getOTPData($userID, $method);
        if ($existing !== null) {
            return $existing;
        }
        $mapper = new OTPSecretsUsersMapper();
        $mapper->user = $userID;
        $mapper->secret = "";
        $mapper->intervalTOTP = self::DEFAULT_INTERVAL_TOTP;
        $mapper->oneUseCode = "";
        $mapper->maxDate = null;
        $mapper->method = $method;
        $mapper->save();
        return $mapper->id !== null ? $mapper : null;
    }

    /**
     * Buscador de SOLO LECTURA. Lo llama el constructor de `UserDataPackage`: si vuelve a
     * escribir, construir un paquete de usuario escribe. Para crear,
     * {@see self::createTOTPData()}.
     *
     * @param int $userID
     * @return OTPSecretsUsersMapper|null null si el usuario no tiene registro TOTP
     */
    public static function getTOTPData(int $userID)
    {
        $model = self::model();
        $method = self::METHOD_TOTP;
        $model->select()->where([
            "user" => $userID,
            "method" => "{$method}",
        ]);
        $model->execute();
        $result = $model->result();
        if (empty($result)) {
            return null;
        }
        $mapper = new OTPSecretsUsersMapper($result[0]->id);
        return $mapper->id !== null ? $mapper : null;
    }

    /**
     * Mitad de ESCRITURA de {@see self::getTOTPData()}. El secreto se genera aquí, en un
     * camino autenticado, nunca al leer.
     *
     * @param int $userID
     * @return OTPSecretsUsersMapper|null
     */
    public static function createTOTPData(int $userID)
    {
        //Llama a getTOTPData(): si el buscador vuelve a crear, esto se cuelga.
        $existing = self::getTOTPData($userID);
        if ($existing !== null) {
            return $existing;
        }
        $mapper = new OTPSecretsUsersMapper();
        $mapper->user = $userID;
        $mapper->secret = TOTPStandard::generateSecret();
        $mapper->intervalTOTP = self::DEFAULT_INTERVAL_TOTP;
        $mapper->oneUseCode = "";
        $mapper->maxDate = null;
        $mapper->method = self::METHOD_TOTP;
        $mapper->save();
        return $mapper->id !== null ? $mapper : null;
    }

    /**
     * Activa o desactiva el 2FA del usuario. Único sitio autorizado a crear el registro
     * TOTP: aquí el usuario ya está autenticado y lo está pidiendo.
     *
     * @param int $userID
     * @param bool $enable
     * @param string $securityCode
     * @param string|null $alias
     * @return bool false si hubo algún error
     */
    public static function toggle2FA(int $userID, bool $enable, string $securityCode, ?string $alias = null)
    {
        $totpElement = self::createTOTPData($userID);
        if ($totpElement === null) {
            return false;
        }
        $totpElement->secret = TOTPStandard::generateSecret();
        $totpElement->twoAuthFactorAlias = $alias;
        $totpElement->twoAuthFactorQRViewed = 0;

        //No pongas twoAuthFactor en ENABLED aquí: preparar no es activar. Lo activa confirm2FA().
        $totpElement->twoAuthFactorSecurityCode = $enable ? password_hash($securityCode, \PASSWORD_DEFAULT) : "";
        $totpElement->twoAuthFactor = self::TWOAF_STATUS_DISABLED;

        return $totpElement->update();
    }

    /**
     * CONFIRMAR: es aquí, y solo aquí, donde el segundo factor pasa a ENABLED.
     *
     * Lo llama el botón de confirmar del flujo del QR, después de que el usuario haya
     * escaneado. Antes de esto la cuenta NO pide código, así que abandonar el flujo a medias
     * no deja a nadie fuera.
     *
     * @param int $userID
     * @return bool false si no hay registro TOTP o si no se pudo guardar
     */
    public static function confirm2FA(int $userID)
    {
        $totpElement = self::getTOTPData($userID);

        if ($totpElement === null) {
            return false;
        }

        $totpElement->twoAuthFactorQRViewed = 1;
        $totpElement->twoAuthFactor = self::TWOAF_STATUS_ENABLED;

        return $totpElement->update();
    }

    /**
     * @param int $userID
     * @param string $method
     * @param int $ignoreID
     * @return bool
     */
    public static function existsUserByMethod(int $userID, string $method, int $ignoreID = -1)
    {
        $model = self::model();
        $where = [
            "user = {$userID}",
            "AND method = '{$method}'",
            "AND id != {$ignoreID}",
        ];
        $model->select()->where(implode(' ', $where));
        $model->execute();
        $result = $model->result();
        return !empty($result);
    }

    /**
     * @param int $userID
     * @return bool
     */
    public static function isEnabled2FA(int $userID)
    {
        $model = self::model();
        $enabled = self::TWOAF_STATUS_ENABLED;
        $where = [
            "user" => $userID,
            "twoAuthFactor" => "{$enabled}",
        ];
        $model->select()->where($where);
        $model->execute();
        $result = $model->result();
        return !empty($result);
    }

    /**
     * Inventario de SOLO LECTURA. No debe crear nada: la tarea de terminal lo usa para
     * informar antes de decidir si escribe.
     *
     * @return array<string,int[]> método => ids de usuario sin registro
     */
    public static function missingOTPRecords(): array
    {
        $table = self::TABLE;
        $usersTable = UsersModel::TABLE;
        $missing = [];

        foreach (array_keys(self::METHODS) as $otpAuthMethod) {
            $modelUsers = UsersModel::model();
            $modelUsers
                ->select("GROUP_CONCAT({$usersTable}.id SEPARATOR ',') AS usersIDs")
                ->leftJoin(
                    $table,
                    "{$usersTable}.id = {$table}.user AND {$table}.method = '{$otpAuthMethod}'"
                )
                ->where("{$table}.user IS NULL")
                ->execute();
            $result = $modelUsers->result();
            $ids = isset($result[0]) ? $result[0]->usersIDs : null;
            $missing[$otpAuthMethod] = is_string($ids) && $ids !== ''
                ? array_map('intval', explode(',', $ids))
                : [];
        }

        return $missing;
    }

    /**
     * Crea los registros OTP que falten, uno por usuario y método.
     *
     * NO LA LLAMES DESDE EL REGISTRO DE RUTAS NI DE UNA PETICIÓN: recorre la tabla entera
     * de usuarios. Su sitio es la tarea `bin/cli sync-otp-records`.
     *
     * @return int cuántos registros se crearon
     */
    public static function createOTPAlternativesRecords(): int
    {
        $created = 0;

        foreach (self::missingOTPRecords() as $otpAuthMethod => $userIDs) {
            foreach ($userIDs as $userID) {
                $mapper = new OTPSecretsUsersMapper();
                $mapper->user = $userID;
                $mapper->secret = "";
                $mapper->intervalTOTP = self::DEFAULT_INTERVAL_TOTP;
                $mapper->oneUseCode = "";
                $mapper->maxDate = null;
                $mapper->method = $otpAuthMethod;
                if ($otpAuthMethod == self::METHOD_TOTP) {
                    $mapper->secret = TOTPStandard::generateSecret();
                }
                if ($mapper->save()) {
                    $created++;
                }
            }
        }

        return $created;
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new OTPSecretsUsersMapper)->getModel();
    }
}
