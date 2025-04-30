<?php
/**
 * TokenModel.php
 */
namespace App\Model;

use App\Controller\TokenController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;

/**
 * TokenModel.
 *
 * Controlador de Tokens.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class TokenModel extends BaseModel
{

    const KEY_BASE_JWT = 'IVNTMTYTEOGRAFBPPTHGEAWIIEMGNMEOMHKI';

    /** @ignore */
    protected $table = 'pcsphp_tokens';

    /** @ignore */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $token
     * @param string $type
     * @return int|null El ID del token o null si no se creó
     */
    public static function add(string $token, string $type = TokenController::TOKEN_GENERIC_CONTROLLER)
    {
        $model = new static();
        $wasInserted = $model->insert([
            'token' => $token,
            'type' => $type,
        ])->execute();
        $tokenID = $model->lastInsertId();
        return $wasInserted ? $tokenID : null;
    }

    /**
     * @param string $token
     * @return \stdClass|null
     */
    public static function getRecord(string $token)
    {
        $model = new static();
        $model->select()->where([
            'token' => $token,
        ])->execute();
        $tokenRecord = $model->result();
        $tokenRecord = is_array($tokenRecord) && !empty($tokenRecord) ? $tokenRecord[0] : null;
        return $tokenRecord;
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function exists(string $token)
    {
        $model = new static();
        $model->select()->where([
            "token" => $token,
        ])->execute();
        $result = $model->result();
        return is_array($result) && !empty($result);
    }

    /**
     * @param string $type
     * @param string|null $token Si se define evalúa que exista el token del tipo escogido, si no simplemente evalua la existencia de cualquier token del tipo
     * @return bool
     */
    public static function existsByType(string $type, string $token = null)
    {
        $model = new static();
        $where = [
            'type' => $type,
        ];
        if ($token !== null) {
            $where['token'] = $token;
        }
        $model->select()->where($where)->execute();
        $result = $model->result();
        return is_array($result) && !empty($result);
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function existsByID(int $id)
    {
        $model = new static();
        $model->select()->where([
            'id' => $id,
        ])->execute();
        $result = $model->result();
        return is_array($result) && !empty($result);
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function deleteByToken(string $token)
    {
        $model = new static();
        return $model->delete("token = '{$token}'")->execute();
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function deleteByID(int $id)
    {
        $model = new static();
        return $model->delete("id = {$id}")->execute();
    }

    /**
     * @param string $token
     * @return mixed|null Devuelve null si ya expiró
     */
    public static function getDataJWT(string $token)
    {
        $tokenRecord = self::getRecord($token);
        if ($tokenRecord !== null) {
            $jwt = $tokenRecord->token;
            $tokenData = BaseToken::getData($jwt, self::KEY_BASE_JWT, null, true);
            $tokenExpired = BaseToken::isExpire($jwt, self::KEY_BASE_JWT, null);
            return !$tokenExpired ? $tokenData : null;
        }
        return null;
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function checkTokenIsExpired(string $token)
    {
        $tokenRecord = self::getRecord($token);
        if ($tokenRecord !== null) {
            $jwt = $tokenRecord->token;
            $tokenExpired = BaseToken::isExpire($jwt, self::KEY_BASE_JWT, null);
            return $tokenExpired;
        }
        return false;
    }

    /**
     * @param string $token
     * @param string $code
     * @return bool Devuelve false tanto si no coincide como si ha expirado
     */
    public static function checkJWTWithCode(string $token, string $code)
    {
        $tokenRecord = self::getRecord($token);
        if ($tokenRecord !== null) {
            $jwt = $tokenRecord->token;
            $tokenData = BaseToken::getData($jwt, self::KEY_BASE_JWT, null, true);
            $tokenExpired = BaseToken::isExpire($jwt, self::KEY_BASE_JWT, null);
            $tokenCode = property_exists($tokenData, 'code') ? $tokenData->code : uniqid();
            return $tokenCode == $code && !$tokenExpired;
        }
        return false;
    }

    /**
     * Los datos del JWT siempre tendrán el valor code, por defecto un número de 6 dígitos
     *
     * @param array $data
     * @param string|null $code
     * @param int $duration Minutos
     * @param string $type
     * @return array{id:int|null,code:string,jwt:string}
     */
    public static function addJWTWithCode(array $data = [], string $code = null, int $duration = 60, string $type = TokenController::TOKEN_GENERIC_CONTROLLER)
    {
        $code = !is_null($code) ? $code : generate_code(6, true);
        $data['code'] = $code;
        $jwt = self::generateJWT($data, $duration);
        $addedTokenID = self::add($jwt, $type);
        return [
            'id' => $addedTokenID,
            'code' => $code,
            'jwt' => $jwt,
        ];
    }

    /**
     * @param array $data
     * @param int $duration Minutos
     * @return string
     */
    public static function generateJWT(array $data, int $duration = 60)
    {
        $time = time();
        $duration = $duration * 60 + $time;
        $token = BaseToken::setToken($data, self::KEY_BASE_JWT, $time, $duration);
        return $token;
    }

}
