<?php

/**
 * TokenController.php
 */

namespace App\Controller;

use App\Model\TokenModel;
use PiecesPHP\Core\BaseController;

/**
 * TokenController.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class TokenController extends BaseController
{
    /** @ignore */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $token
     * @param string $type
     * @return bool
     */
    public function newToken(string $token, string $type)
    {
        $addedID = TokenModel::add($token, $type);
        return $addedID !== null;
    }

    /**
     * @param string $token
     * @return bool
     */
    public function tokenExists(string $token)
    {
        return TokenModel::exists($token);
    }

    /**
     * @param string $type
     * @return bool
     */
    public function tokenExistsByType(string $type)
    {
        return TokenModel::existsByType($type);
    }

    /**
     * @param int $id
     * @return bool
     */
    public static function tokenExistsByID(int $id)
    {
        return TokenModel::existsByID($id);
    }

    /**
     * @param string $token
     * @return bool
     */
    public function deleteToken(string $token)
    {
        return TokenModel::deleteByToken($token);
    }

    /**
     * @param int $tokenID
     * @return bool
     */
    public function deleteTokenByID(int $tokenID)
    {
        return TokenModel::deleteByID($tokenID);
    }

    const TOKEN_PASSWORD_RECOVERY = 'TOKEN_PASSWORD_RECOVERY';
    const TOKEN_PASSWORD_RECOVERY_CODE = 'TOKEN_PASSWORD_RECOVERY_CODE';
    const TOKEN_GENERIC_CONTROLLER = 'TOKEN_GENERIC_CONTROLLER';
}
