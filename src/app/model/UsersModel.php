<?php

/**
 * UsersModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * UsersModel.
 *
 * Modelo de Usuarios.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @property int $id
 * @property string $password
 * @property string $username
 * @property string $firstname
 * @property string $secondname
 * @property string $first_lastname
 * @property string $second_lastname
 * @property string $email
 * @property string|array|\stdClass $meta
 * @property int $type
 * @property int $status
 * @property int $failed_attempts
 * @property string|\DateTime $created_at
 * @property string|\DateTime $modified_at
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

    const TYPES_USER_PRIORITY = [
        self::TYPE_USER_ROOT => 3,
        self::TYPE_USER_ADMIN => 2,
        self::TYPE_USER_GENERAL => 1,
    ];

    const LANG_GROUP = 'usersModule';
    const TABLE = 'pcsphp_users';

    protected $table = self::TABLE;

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
     * hasAuthorityOver
     *
     * @param int $type
     * @return bool
     */
    public function hasAuthorityOver(int $type)
    {
        $typesOver = $this->getHigherPriorityTypes();
        $hasAuthority = !in_array((int) $type, $typesOver);
        return $hasAuthority;
    }

    /**
     * getHigherPriorityTypes
     *
     * @return array
     */
    public function getHigherPriorityTypes()
    {
        $allTypes = [];

        array_map(function ($type) use (&$allTypes) {
            $allTypes[] = $type;
        }, array_flip(self::TYPES_USERS));

        if ($this->id !== null) {

            $types = [];

            $prioritiesByType = self::TYPES_USER_PRIORITY;
            $typesByPriorities = array_flip(self::TYPES_USER_PRIORITY);
            $currentPriority = self::TYPES_USER_PRIORITY[$this->type];

            $higherPrioritiesThanCurrentOne = array_filter($prioritiesByType, function ($priority) use ($currentPriority) {
                return $priority > $currentPriority;
            });

            foreach ($higherPrioritiesThanCurrentOne as $priority) {
                $types[] = $typesByPriorities[$priority];
            }

            return $types;

        }

        return $allTypes;
    }

    /**
     * getFullName
     *
     * @return string
     */
    public function getFullName()
    {

        $fullname = [
            $this->firstname,
            $this->secondname,
            $this->first_lastname,
            $this->secondname,
        ];

        $fullname = implode(' ', array_filter($fullname, function ($e) {
            return is_string($e) && mb_strlen(trim($e)) > 0;
        }));

        return $fullname;

    }

    /**
     * getPublicData
     *
     * @return array
     */
    public function getPublicData()
    {
        $data = $this->humanReadable();
        unset($data['password']);
        unset($data['modified_at']);
        unset($data['created_at']);
        unset($data['failed_attempts']);
        unset($data['status']);
        return $data;
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
     * changeFirstLastname
     *
     * @param mixed $fristLastname
     * @param mixed $id
     * @return bool
     */
    public function changeFirstLastname($fristLastname, $id)
    {
        $this->updateModifiedAt($id);
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'fristLastname' => $fristLastname,
        ])->where(['id' => $id])->execute();
    }

    /**
     * changeSecondLastname
     *
     * @param mixed $second_lastname
     * @param mixed $id
     * @return bool
     */
    public function changeSecondLastname($second_lastname, $id)
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
     * getUsersByType
     *
     * @param int $type
     * @param int[] $ignoreIDs
     * @return \stdClass[]
     */
    public static function getUsersByType(int $type, array $ignoreIDs = [])
    {

        $model = (new static())->getModel();

        $where = [
            "type = {$type}",
        ];

        if (count($ignoreIDs) > 0) {

            $ignoreIDs = implode(', ', $ignoreIDs);
            $where[] = "AND id NOT IN ({$ignoreIDs})";
        }

        $model->select()->where(implode(' ', $where));

        $model->execute();

        return $model->result();

    }

    /**
     * getUsersByTypes
     *
     * @param int[] $types
     * @param int[] $ignoreIDs
     * @return \stdClass[]
     */
    public static function getUsersByTypes(array $types, array $ignoreIDs = [])
    {

        $model = (new static())->getModel();

        $where = [
            '(type = ' . implode(' OR type = ', $types) . ')',
        ];

        if (count($ignoreIDs) > 0) {

            $ignoreIDs = implode(', ', $ignoreIDs);
            $where[] = "AND id NOT IN ({$ignoreIDs})";
        }

        $model->select()->where(implode(' ', $where));

        $model->execute();

        return $model->result();

    }

    /**
     * getUsersByIDs
     *
     * @param int[] $ids
     * @return \stdClass[]
     */
    public static function getUsersByIDs(array $ids = [])
    {

        $model = (new static())->getModel();

        $ids = count($ids) > 0 ? implode(', ', $ids) : -1;
        $where = [
            "id IN ({$ids})",
        ];

        $model->select()->where(implode(' ', $where));

        $model->execute();

        return $model->result();

    }

    /**
     * getTypesUser
     *
     * @return array
     */
    public static function getTypesUser()
    {

        $types = [];

        foreach (self::TYPES_USERS as $key => $value) {

            $types[$key] = __(self::LANG_GROUP, $value);

        }

        return $types;

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
     * @param int $id
     * @return bool
     */
    public static function isDuplicateEmail(string $email, int $id = -1)
    {
        $model = (new static())->getModel();
        $model->resetAll();

        $model->select()->where([
            'email' => [
                '=' => $email,
            ],
            'id' => [
                '!=' => $id,
            ],
        ])->execute();

        return count($model->result()) > 0;
    }

    /**
     * isDuplicateUsername
     *
     * @param string $username
     * @param int $id
     * @return bool
     */
    public static function isDuplicateUsername(string $username, int $id = -1)
    {
        $model = (new static())->getModel();
        $model->resetAll();

        $model->select()->where([
            'username' => [
                '=' => $username,
            ],
            'id' => [
                '!=' => $id,
            ],
        ]);

        $model->execute();

        return count($model->result()) > 0;
    }

    /**
     * @return string[]
     */
    public static function fieldsToSelect()
    {

        $table = self::TABLE;
        $secondNameSegment = "IF({$table}.secondname IS NOT NULL, CONCAT(' ', {$table}.secondname), '')";
        $secondLastNameSegment = "IF({$table}.second_lastname IS NOT NULL, CONCAT(' ', {$table}.second_lastname), '')";

        $fields = array_map(function ($f) use ($table) {
            return "{$table}.{$f}";
        }, array_keys((new static )->getFields()));
        $fieldsToAdd = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "TRIM(CONCAT(TRIM({$table}.firstname), {$secondNameSegment}, ' ', {$table}.first_lastname, {$secondLastNameSegment})) AS fullname",
        ];

        foreach ($fieldsToAdd as $fieldToAdd) {
            $fields[] = $fieldToAdd;
        }

        return $fields;

    }

    /**
     * model
     *
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new static())->getModel();
    }

}
