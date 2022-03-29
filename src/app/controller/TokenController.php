<?php

/**
 * TokenController.php
 */

namespace App\Controller;

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
     * @param mixed $token
     * @param mixed $type
     * @return bool
     */
    public function newToken($token, $type)
    {
        $result = $this->model->insert([
            'token' => $token,
            'type' => $type,
        ])->execute();
        return $result === true;
    }

    /**
     * @param mixed $token
     * @return bool
     */
    public function tokenExists($token)
    {
        $result = $this->model
            ->select()
            ->where("token = '" . $token . "'")
            ->execute();
        $result = $this->model->result();
        return ($result !== false && !empty($result));
    }

    /**
     * @param mixed $code
     * @return bool
     */
    public function tokenExistsByType($code)
    {
        $result = $this->model
            ->select()
            ->where("type = '" . $code . "'")
            ->execute();
        $result = $this->model->result();
        return ($result !== false && !empty($result));
    }

    /**
     * @param mixed $id
     * @return bool
     */
    public static function tokenExistsByID($id)
    {
        $controller = new TokenController();
        $result = $controller->model
            ->select()
            ->where("id = '" . $id . "'")
            ->execute();
        $result = $controller->model->result();
        return ($result !== false && !empty($result));
    }

    /**
     * @param mixed $token
     * @return void
     */
    public function deleteToken($token)
    {
        $this->model
            ->delete("token = '" . $token . "'")
            ->execute();
    }

    /**
     * @param mixed $tokenID
     * @return void
     */
    public function deleteTokenByID($tokenID)
    {
        $this->model
            ->delete("id = {$tokenID}")
            ->execute();
    }

    const TOKEN_PASSWORD_RECOVERY = 'TOKEN_PASSWORD_RECOVERY';
    const TOKEN_PASSWORD_RECOVERY_CODE = 'TOKEN_PASSWORD_RECOVERY_CODE';
    const TOKEN_GENERIC_CONTROLLER = 'TOKEN_GENERIC_CONTROLLER';
}
