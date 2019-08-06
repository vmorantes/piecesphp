<?php

/**
 * TokenController.php
 */

namespace App\Controller;

use PiecesPHP\Core\BaseController;

/**
 * TokenController.
 *
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

    public function newToken($token, $type)
    {
        return $this->model->insert([
            'token' => $token,
            'type' => $type,
        ])->execute();
    }
    public function tokenExists($token)
    {
        $result = $this->model
            ->select(['token'])
            ->where("token = '" . $token . "'")
            ->execute();
        $result = $this->model->result();
        return ($result !== false && count($result) > 0);
    }
    public function tokenExistsByType($code)
    {
        $result = $this->model
            ->select(['token'])
            ->where("type = '" . $code . "'")
            ->execute();
        $result = $this->model->result();
        return ($result !== false && count($result) > 0);
    }
    public function deleteToken($token)
    {
        $this->model
            ->delete("token = '" . $token . "'")
            ->execute();
    }

    const TOKEN_PASSWORD_RECOVERY = 'TOKEN_PASSWORD_RECOVERY';
    const TOKEN_PASSWORD_RECOVERY_CODE = 'TOKEN_PASSWORD_RECOVERY_CODE';
    const TOKEN_GENERIC_CONTROLLER = 'TOKEN_GENERIC_CONTROLLER';
}
