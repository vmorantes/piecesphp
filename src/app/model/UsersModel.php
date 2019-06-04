<?php

/**
 * UsersModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * UsersModel.
 *
 * Modelo de Usuarios.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class UsersModel extends BaseEntityMapper
{

    //Constantes de status de usuario
    const STATUS_USER_ACTIVE = 1;
    const STATUS_USER_INACTIVE = 0;

    //Constantes de tipos de usuario
    const TYPE_USER_ROOT = 0;
    const TYPE_USER_ADMIN = 1;
	const TYPE_USER_GENERAL = 2;
	
    const TYPES_USERS = [
        self::TYPE_USER_ROOT => 'Usuario principal',
        self::TYPE_USER_ADMIN => 'Usuario administrador',
        self::TYPE_USER_GENERAL => 'Usuario general',
    ];

    protected $table = 'pcsphp_users';

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'password' => [
            'type' => 'varchar',
        ],
        'username' => [
            'type' => 'varchar',
        ],
        'firstname' => [
            'type' => 'varchar',
        ],
        'secondname' => [
            'type' => 'varchar',
            'null' => true,
            'default' => '',
        ],
        'first_lastname' => [
            'type' => 'varchar',
        ],
        'second_lastname' => [
            'type' => 'varchar',
            'null' => true,
            'default' => '',
        ],
        'email' => [
            'type' => 'varchar',
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
        ],
        'type' => [
            'type' => 'int',
        ],
        'status' => [
            'type' => 'int',
        ],
        'failed_attempts' => [
            'type' => 'int',
        ],
        'created_at' => [
            'type' => 'datetime',
        ],
        'modified_at' => [
            'type' => 'datetime',
        ],
    ];

    /**
     * __construct
     *
     * @param integer $id
     * @return static
     */
    public function __construct(int $id = null)
    {
        parent::__construct($id);
    }

    /**
     * getWhere
     *
     * @param mixed $where
     * @return object|bool
     */
    public function getWhere($where)
    {
        $model = $this->getModel();
        $model->resetAll();
        return $model
            ->select()
            ->where($where)
            ->row();
    }

    /**
     * getByUsername
     *
     * @param mixed $username
     * @return object|bool
     */
    public function getByUsername($username)
    {
        $model = $this->getModel();
        $model->resetAll();
        return $model
            ->select()
            ->where(['username' => $username])
            ->row();
    }

    /**
     * getByID
     *
     * @param mixed $id
     * @return object|bool
     */
    public function getByID($id)
    {
        $model = $this->getModel();
        $model->resetAll();
        return $model
            ->select()
            ->where("id = '" . $id . "'")
            ->row();
    }

    /**
     * getByEmail
     *
     * @param mixed $email
     * @return object|bool
     */
    public function getByEmail($email)
    {
        $model = $this->getModel();
        $model->resetAll();
        return $model
            ->select()
            ->where(['email' => $email])
            ->row();
    }

    /**
     * changeUsername
     *
     * @param mixed $username
     * @param mixed $id
     * @return bool
     */
    public function changeUsername($username, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'username' => $username,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changePassword
     *
     * @param mixed $criterio
     * @param mixed $password
     * @param mixed $isEmail
     * @return bool
     */
    public function changePassword($criterio, $password, $isEmail = true)
    {
        $this->updateModifiedAt($criterio, $isEmail);
        if ($isEmail) {
            $model = $this->getModel();
            $model->resetAll();
            return $model->update([
                'password' => $password,
            ])->where(['email' => $criterio])->execute();
        } else {
            $model = $this->getModel();
            $model->resetAll();
            return $model->update([
                'password' => $password,
            ])->where(['id' => $criterio])->execute();
        }
    }

    /**
     * changeFirstName
     *
     * @param mixed $firstname
     * @param mixed $id
     * @return bool
     */
    public function changeFirstName($firstname, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'firstname' => $firstname,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changeSecondName
     *
     * @param mixed $secondname
     * @param mixed $id
     * @return bool
     */
    public function changeSecondName($secondname, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'secondname' => $secondname,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changefirst_lastname
     *
     * @param mixed $fristLastname
     * @param mixed $id
     * @return bool
     */
    public function changefirst_lastname($fristLastname, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'fristLastname' => $fristLastname,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changesecond_lastname
     *
     * @param mixed $lastname
     * @param mixed $id
     * @return bool
     */
    public function changesecond_lastname($second_lastname, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'second_lastname' => $second_lastname,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changeEmail
     *
     * @param mixed $email
     * @param mixed $id
     * @return bool
     */
    public function changeEmail($email, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'email' => $email,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changeType
     *
     * @param mixed $type
     * @param mixed $id
     * @return bool
     */
    public function changeType($type, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'type' => $type,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changeStatus
     *
     * @param mixed $status
     * @param mixed $id
     * @return bool
     */
    public function changeStatus($status, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'status' => $status,
        ])->where(['id' => $id])->execute();
    }

    /**
     * updateAttempts
     *
     * @param mixed $id
     * @return int Los intentos fallidos
     */
    public function updateAttempts($id)
    {
        $user = $this->getByID($id);
        $model = $this->getModel();
        $model->resetAll();
        $model->update([
            'failed_attempts' => ($user->failed_attempts + 1),
        ])->where(['id' => $id])->execute();
        $user = $this->getByID($id);
        return $user->failed_attempts;
    }

    /**
     * updateModifiedAt
     *
     * @param mixed $criterio
     * @return bool
     */
    public function updateModifiedAt($criterio, $isEmail = false)
    {
        if ($isEmail) {
            $model = $this->getModel();
            $model->resetAll();
            return $model->update([
                'modified_at' => date('Y-m-d h:i:s'),
            ])->where(['email' => $criterio])->execute();
        } else {
            $model = $this->getModel();
            $model->resetAll();
            return $model->update([
                'modified_at' => date('Y-m-d h:i:s'),
            ])->where(['id' => $criterio])->execute();
        }
    }

    /**
     * resetAttempts
     *
     * @param mixed $id
     * @return bool
     */
    public function resetAttempts($id)
    {
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'failed_attempts' => 0,
        ])->where(['id' => $id])->execute();
    }

    /**
     * isDuplicate
     *
     * @param string $username
     * @param string $email
     * @return bool
     */
    public static function isDuplicate(string $username, string $email)
    {
        $model = (new static())->getModel();
        $model->resetAll();

        $model->select()->where([
            'username' => [
                '=' => $username,
                'and_or' => 'OR',
            ],
            'email' => [
                '=' => $email,
            ],
        ])->execute();

        return count($model->result()) > 0;
    }

    /**
     * isDuplicateEmail
     *
     * @param string $email
     * @return bool
     */
    public static function isDuplicateEmail(string $email)
    {
        $model = (new static())->getModel();
        $model->resetAll();

        $model->select()->where([
            'email' => [
                '=' => $email,
            ],
        ])->execute();

        return count($model->result()) > 0;
    }

    /**
     * isDuplicateUsername
     *
     * @param string $username
     * @return bool
     */
    public static function isDuplicateUsername(string $username)
    {
        $model = (new static())->getModel();
        $model->resetAll();

        $model->select()->where([
            'username' => [
                '=' => $username,
            ],
        ])->execute();

        return count($model->result()) > 0;
    }
}
