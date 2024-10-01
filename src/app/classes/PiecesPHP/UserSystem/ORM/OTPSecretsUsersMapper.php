<?php
/**
 * OTPSecretsUsersMapper.php
 */
namespace PiecesPHP\UserSystem\ORM;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseEntityMapper;
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
 * @property string $intervalTOTP Solo para METHOD_TOTP
 * @property string $oneUseCode Solo para METHOD_ONE_USE_CODE
 * @property string $maxDate Fecha máxima solo para METHOD_ONE_USE_CODE
 * @property string $method Método de código
 * @property string $twoAuthFactor Define si tiene el 2FA activado este usuario
 * @property string $twoAuthFactorQRViewed 1|0 Define si ya código QR fue visto para su configuración
 * @property string $twoAuthFactorAlias Define el alias para el QR en las aplicaciones
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
            'type' => 'int',
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
    public function __construct(int $value = null, string $fieldCompare = 'primary_key')
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
     * @return object|null
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
     * @param int $userID
     * @param string $method
     * @return OTPSecretsUsersMapper|null
     */
    public static function getOTPData(int $userID, string $method)
    {
        $model = self::model();
        $method = in_array($method, [
            self::METHOD_ONE_USE_CODE,
        ]) ? $method : null;
        if ($method !== null) {
            $where = [
                "user" => $userID,
                "method" => "{$method}",
            ];
            $model->select()->where($where);
            $model->execute();
            $result = $model->result();
            $result = !empty($result) ? $result[0]->id : null;
            $mapper = new OTPSecretsUsersMapper($result);
            if ($mapper->id === null) {
                $mapper = new OTPSecretsUsersMapper();
                $mapper->user = $userID;
                $mapper->secret = TOTPStandard::generateSecret();
                $mapper->intervalTOTP = self::DEFAULT_INTERVAL_TOTP;
                $mapper->oneUseCode = "";
                $mapper->maxDate = null;
                $mapper->method = $method;
                $mapper->save();
            }
            return $mapper->id !== null ? $mapper : null;
        } else {
            return null;
        }
    }

    /**
     * @param int $userID
     * @return OTPSecretsUsersMapper|null
     */
    public static function getTOTPData(int $userID)
    {
        $model = self::model();
        $method = self::METHOD_TOTP;
        $where = [
            "user" => $userID,
            "method" => "{$method}",
        ];
        $model->select()->where($where);
        $model->execute();
        $result = $model->result();
        $result = !empty($result) ? $result[0]->id : null;
        $mapper = new OTPSecretsUsersMapper($result);
        if ($mapper->id === null) {
            $mapper = new OTPSecretsUsersMapper();
            $mapper->user = $userID;
            $mapper->secret = TOTPStandard::generateSecret();
            $mapper->intervalTOTP = self::DEFAULT_INTERVAL_TOTP;
            $mapper->oneUseCode = "";
            $mapper->maxDate = null;
            $mapper->method = $method;
            $mapper->save();
        }
        return $mapper->id !== null ? $mapper : null;
    }

    /**
     * @param int $userID
     * @param bool $enable
     * @param string $securityCode
     * @param string|null $alias
     * @return bool false si hubo algún error
     */
    public static function toggle2FA(int $userID, bool $enable, string $securityCode, string $alias = null)
    {
        $result = false;
        $totpElement = self::getTOTPData($userID);
        if ($totpElement !== null) {
            $totpElement->secret = TOTPStandard::generateSecret();
            $totpElement->twoAuthFactorAlias = $alias;
            $totpElement->twoAuthFactorQRViewed = 0;
            if ($enable) {
                $totpElement->twoAuthFactor = self::TWOAF_STATUS_ENABLED;
                $totpElement->twoAuthFactorSecurityCode = password_hash($securityCode, \PASSWORD_DEFAULT);
            } else {
                $totpElement->twoAuthFactor = self::TWOAF_STATUS_DISABLED;
                $totpElement->twoAuthFactorSecurityCode = "";
            }
            $totpElement->update();
        }
        return $result;
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
            "user" => $userID,
            "method" => "{$method}",
            "id != {$ignoreID}",
        ];
        $model->select()->where($where);
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
     * Crea los registros para cada tipo de autenticación OTP disponible para los usuarios existentes
     */
    public static function createOTPAlternativesRecords()
    {
        $modelUsers = UsersModel::model();
        $table = self::TABLE;
        $usersTable = UsersModel::TABLE;

        //Métodos disponibles
        $otpAuthMethods = array_keys(self::METHODS);
        //Obtener los usuarios (IDs)
        $modelUsers->select("GROUP_CONCAT(id SEPARATOR ',') AS usersIDs")->execute();
        $usersIDs = $modelUsers->result()[0]->usersIDs;
        $usersIDs = $usersIDs !== null ? $usersIDs : '-1';
        //Verificar los ids que carecen de registros por método
        foreach ($otpAuthMethods as $otpAuthMethod) {
            $modelUsers
                ->select("GROUP_CONCAT({$usersTable}.id SEPARATOR ',') AS usersIDs")
                ->leftJoin(
                    $table,
                    "{$usersTable}.id = {$table}.user AND {$table}.method = '{$otpAuthMethod}'"
                )
                ->where("{$table}.user IS NULL")
                ->execute();
            $userIDsWhitoutMethod = $modelUsers->result()[0]->usersIDs;
            $userIDsWhitoutMethod = $userIDsWhitoutMethod !== null ? $userIDsWhitoutMethod : null;
            $userIDsWhitoutMethod = is_string($userIDsWhitoutMethod) ? explode(',', $userIDsWhitoutMethod) : [];

            foreach ($userIDsWhitoutMethod as $userIDWhitoutMethod) {
                $mapper = new OTPSecretsUsersMapper();
                $mapper->user = $userIDWhitoutMethod;
                $mapper->secret = "";
                $mapper->intervalTOTP = self::DEFAULT_INTERVAL_TOTP;
                $mapper->oneUseCode = "";
                $mapper->maxDate = null;
                $mapper->method = $otpAuthMethod;
                if ($otpAuthMethod == self::METHOD_TOTP) {
                    $mapper->secret = TOTPStandard::generateSecret();
                }
                $mapper->save();
            }

        }
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new OTPSecretsUsersMapper)->getModel();
    }
}
