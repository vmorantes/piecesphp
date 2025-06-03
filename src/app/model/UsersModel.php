<?php

/**
 * UsersModel.php
 */

namespace App\Model;

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * UsersModel.
 *
 * Modelo de Usuarios.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @property int|null $id
 * @property int $organization Es el ID de OrganizationMapper, no puede ser instanciado porque se vuelve circular
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
class UsersModel extends EntityMapperExtensible
{

    //Criterios de login
    const REQUIRE_APPROBATION_FOR_LOGIN = false;

    //Constantes de status de usuario
    const STATUS_USER_INACTIVE = 0;
    const STATUS_USER_ACTIVE = 1;
    const STATUS_USER_ATTEMPTS_BLOCK = 2;
    const STATUS_USER_APPROVED_PENDING = 3;
    const STATUS_USER_REJECTED = 4;
    const STATUSES = [
        self::STATUS_USER_INACTIVE => 'Inactivo',
        self::STATUS_USER_ACTIVE => 'Activo',
        self::STATUS_USER_ATTEMPTS_BLOCK => 'Bloqueado por intentos fallidos',
        self::STATUS_USER_APPROVED_PENDING => 'Por aprobar',
        self::STATUS_USER_REJECTED => 'No aprobado',
    ];
    const STATUSES_FOR_DISPLAY_QUERY = [
        self::STATUS_USER_INACTIVE => 'No',
        self::STATUS_USER_ACTIVE => 'Sí',
        self::STATUS_USER_ATTEMPTS_BLOCK => 'Bloqueado por intentos fallidos',
        self::STATUS_USER_APPROVED_PENDING => 'Por aprobar',
        self::STATUS_USER_REJECTED => 'No aprobado',
    ];
    const STATUSES_OK_FOR_LOGIN_ON_REQUIRE_APPROBATION = [
        self::STATUS_USER_ACTIVE,
    ];
    const STATUSES_OK_FOR_LOGIN_ON_NO_REQUIRE_APPROBATION = [
        self::STATUS_USER_ACTIVE,
        self::STATUS_USER_APPROVED_PENDING,
        self::STATUS_USER_REJECTED,
    ];
    const ARE_AUTO_APPROVAL = [
        self::TYPE_USER_ROOT,
        self::TYPE_USER_ADMIN_GRAL,
        self::TYPE_USER_INSTITUCIONAL,
        self::TYPE_USER_COMUNICACIONES,
    ];

    //Constantes de tipos de usuario
    const TYPE_USER_ROOT = 0;
    const TYPE_USER_ADMIN_GRAL = 1;
    const TYPE_USER_ADMIN_ORG = 12;
    const TYPE_USER_GENERAL = 2;
    const TYPE_USER_INSTITUCIONAL = 3;
    const TYPE_USER_COMUNICACIONES = 4;

    /**
     * @var array<int,string>
     */
    const TYPES_USERS = [
        self::TYPE_USER_ROOT => 'Principal',
        self::TYPE_USER_ADMIN_GRAL => 'Administrador general',
        self::TYPE_USER_ADMIN_ORG => 'Administrador de organización',
        self::TYPE_USER_GENERAL => 'Usuario general',
        self::TYPE_USER_INSTITUCIONAL => 'Institucional',
        self::TYPE_USER_COMUNICACIONES => 'Comunicaciones',
    ];

    const TYPES_USER_PRIORITY = [
        self::TYPE_USER_ROOT => 500,
        self::TYPE_USER_ADMIN_GRAL => 400,
        self::TYPE_USER_ADMIN_ORG => 300,
        self::TYPE_USER_GENERAL => 1,
        self::TYPE_USER_INSTITUCIONAL => 1,
        self::TYPE_USER_COMUNICACIONES => 1,
    ];

    const TYPES_USER_DO_NOT_HAVE_AUTHORITY_OVER_SAME_TYPE = [
        self::TYPE_USER_ADMIN_ORG,
    ];

    const TYPES_USER_DONT_REQUIRE_ORGANIZATION = [
        self::TYPE_USER_ROOT,
        self::TYPE_USER_ADMIN_GRAL,
    ];

    const TYPES_USER_SHOULD_HAVE_PROFILE = [
        self::TYPE_USER_ADMIN_ORG,
        self::TYPE_USER_GENERAL,
    ];

    const LANG_GROUP = UserDataPackage::LANG_GROUP;
    const TABLE = 'pcsphp_users';

    protected $table = self::TABLE;

    protected $fields = [
        'id' => [
            'type' => 'bigint',
            'primary_key' => true,
        ],
        'organization' => [
            'type' => 'int',
            'null' => true,
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
     * @param integer $id
     * @return static
     */
    public function __construct(int $id = null)
    {
        parent::__construct($id);
    }

    /**
     * @inheritDoc
     */
    public function save()
    {

        if (self::isDuplicateUsername($this->username, -1)) {
            throw new \Exception(__(self::LANG_GROUP, "Ya existe el nombre de usuario."));
        }

        $saveResult = parent::save();

        return $saveResult;

    }

    /**
     * @inheritDoc
     */
    public function update()
    {

        if (self::isDuplicateUsername($this->username, $this->id)) {
            throw new \Exception(__(self::LANG_GROUP, "Ya existe el nombre de usuario."));
        }

        return parent::update();

    }

    /**
     * @param int $type
     * @return bool
     */
    public function hasAuthorityOver(int $type)
    {
        $typesOver = $this->getHigherPriorityTypes();
        $hasAuthority = !in_array((int) $type, $typesOver);
        //Verificar si el tipo tiene autoridad o no sobre su mismo tipo
        if (in_array($type, self::TYPES_USER_DO_NOT_HAVE_AUTHORITY_OVER_SAME_TYPE)) {
            $hasAuthority = $hasAuthority && $type != $this->type;
        }
        return $hasAuthority;
    }

    /**
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
     * @return string
     */
    public function getNames()
    {

        $fullname = [
            $this->firstname,
            $this->secondname,
        ];

        $fullname = implode(' ', array_filter($fullname, function ($e) {
            return is_string($e) && mb_strlen(trim($e)) > 0;
        }));

        return $fullname;

    }

    /**
     * @return string
     */
    public function getLastNames()
    {

        $fullname = [
            $this->first_lastname,
            $this->second_lastname,
        ];

        $fullname = implode(' ', array_filter($fullname, function ($e) {
            return is_string($e) && mb_strlen(trim($e)) > 0;
        }));

        return $fullname;

    }

    /**
     * @return string
     */
    public function getFullName()
    {

        $fullname = [
            $this->firstname,
            $this->secondname,
            $this->first_lastname,
            $this->second_lastname,
        ];

        $fullname = implode(' ', array_filter($fullname, function ($e) {
            return is_string($e) && mb_strlen(trim($e)) > 0;
        }));

        return $fullname;

    }

    /**
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
     * @param mixed $where
     * @return \stdClass|null
     */
    public function getWhere($where)
    {
        $model = $this->getModel();
        $model->resetAll();
        $result = $model
            ->select()
            ->where($where)
            ->row();
        return is_object($result) ? $result : null;
    }

    /**
     * @param mixed $username
     * @return \stdClass|null
     */
    public function getByUsername($username)
    {
        $model = $this->getModel();
        $model->resetAll();
        $result = $model
            ->select()
            ->where(['username' => $username])
            ->row();
        return is_object($result) ? $result : null;
    }

    /**
     * @param mixed $id
     * @return \stdClass|null
     */
    public function getByID($id)
    {
        $model = $this->getModel();
        $model->resetAll();
        $result = $model
            ->select()
            ->where("id = '" . $id . "'")
            ->row();
        return is_object($result) ? $result : null;
    }

    /**
     * @param mixed $email
     * @return \stdClass|null
     */
    public function getByEmail($email)
    {
        $model = $this->getModel();
        $model->resetAll();
        $result = $model
            ->select()
            ->where(['email' => $email])
            ->row();
        return is_object($result) ? $result : null;
    }

    /**
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
     * @param mixed $id
     * @return int Los intentos fallidos
     */
    public function updateAttempts($id)
    {
        $user = $this->getByID($id);
        if (is_object($user)) {
            $model = $this->getModel();
            $model->resetAll();
            $model->update([
                'failed_attempts' => ($user->failed_attempts + 1),
            ])->where(['id' => $id])->execute();
            $user = $this->getByID($id);
            return is_object($user) ? $user->failed_attempts : 0;
        } else {
            return 0;
        }
    }

    /**
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
     * Devuelve los valores de STATUSES pasados por función __()
     *
     * @return array<string,string>
     */
    public static function statuses()
    {
        $options = self::STATUSES;
        foreach ($options as $key => $value) {
            $options[$key] = __(self::LANG_GROUP, $value);
        }
        return $options;
    }

    /**
     * Devuelve los valores de STATUSES_FOR_DISPLAY_QUERY pasados por función __()
     *
     * @return array<string,string>
     */
    public static function statusesForDisplayQuery()
    {
        $options = self::STATUSES_FOR_DISPLAY_QUERY;
        foreach ($options as $key => $value) {
            $options[$key] = __(self::LANG_GROUP, $value);
        }
        return $options;
    }

    /**
     * @param int $type
     * @param int[] $ignoreIDs
     * @return \stdClass[]
     */
    public static function getUsersByType(int $type, array $ignoreIDs = [])
    {

        $model = self::model();

        $where = [
            "type = {$type}",
        ];

        if (!empty($ignoreIDs)) {

            $ignoreIDs = implode(', ', $ignoreIDs);
            $where[] = "AND id NOT IN ({$ignoreIDs})";
        }

        $model->select()->where(implode(' ', $where));

        $model->execute();

        return $model->result();

    }

    /**
     * @param int[] $types
     * @param int[] $ignoreIDs
     * @return \stdClass[]
     */
    public static function getUsersByTypes(array $types, array $ignoreIDs = [])
    {

        $model = self::model();

        $where = [
            '(type = ' . implode(' OR type = ', $types) . ')',
        ];

        if (!empty($ignoreIDs)) {

            $ignoreIDs = implode(', ', $ignoreIDs);
            $where[] = "AND id NOT IN ({$ignoreIDs})";
        }

        $model->select()->where(implode(' ', $where));

        $model->execute();

        return $model->result();

    }

    /**
     * @param int[] $ids
     * @return \stdClass[]
     */
    public static function getUsersByIDs(array $ids = [])
    {

        $model = self::model();

        $ids = !empty($ids) ? implode(', ', $ids) : -1;
        $where = [
            "id IN ({$ids})",
        ];

        $model->select()->where(implode(' ', $where));

        $model->execute();

        return $model->result();

    }

    /**
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
     * @return string|null
     */
    public static function getTypeUserName(int $type)
    {
        $userTypes = self::getTypesUser();
        return array_key_exists($type, $userTypes) ? $userTypes[$type] : null;
    }

    /**
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function typesUserForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Tipos de usuario');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        $types = self::getTypesUser();

        foreach ($types as $k => $i) {
            $options[$k] = $i;
        }

        return $options;
    }

    /**
     * Un array listo para ser usado en array_to_html_options de los usuario de una organización que pueden ser administradores
     * @param int $organizationID
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allOrganizationUsersCanBeAdminForSelect(int $organizationID, string $defaultLabel = '', string $defaultValue = '')
    {

        $model = self::model();
        $table = self::TABLE;
        $allowerdTypes = implode(', ', OrganizationMapper::PROFILE_EDITOR);

        $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(self::LANG_GROUP, 'Usuarios');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        $model->select(self::fieldsToSelect())->where(implode(' ', [
            "{$table}.organization = {$organizationID} AND",
            "{$table}.type IN ({$allowerdTypes})",
        ]));
        $model->execute();
        $users = $model->result();

        foreach ($users as $user) {
            $userMapper = new UsersModel($user->id);
            $options[$userMapper->id] = $userMapper->getFullName() . " ({$userMapper->username})";
        }

        return $options;
    }

    /**
     * @param string $username
     * @param string $email
     * @return bool
     */
    public static function isDuplicate(string $username, string $email)
    {
        $model = self::model();
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

        return !empty($model->result());
    }

    /**
     * @param string $email
     * @param int $id
     * @return bool
     */
    public static function isDuplicateEmail(string $email, int $id = -1)
    {
        $model = self::model();
        $model->resetAll();

        $model->select()->where([
            'email' => [
                '=' => $email,
            ],
            'id' => [
                '!=' => $id,
            ],
        ])->execute();

        return !empty($model->result());
    }

    /**
     * @param string $username
     * @param int $id
     * @return bool
     */
    public static function isDuplicateUsername(string $username, int $id = -1)
    {
        $model = self::model();
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

        return !empty($model->result());
    }

    /**
     * Campos extra:
     *  - idPadding
     *  - fullname
     *  - names
     *  - lastNames
     *  - typeName
     *  - statusText
     * @return string[]
     */
    protected static function fieldsToSelect()
    {

        $table = self::TABLE;
        $secondNameSegment = "IF({$table}.secondname IS NOT NULL, CONCAT(' ', {$table}.secondname), '')";
        $secondLastNameSegment = "IF({$table}.second_lastname IS NOT NULL, CONCAT(' ', {$table}.second_lastname), '')";
        $typesJSON = json_encode((object) self::TYPES_USERS, \JSON_UNESCAPED_UNICODE);
        $statusDisplay = self::statusesForDisplayQuery();
        $statusDisplayJSON = json_encode((object) $statusDisplay, \JSON_UNESCAPED_UNICODE);
        $statusDisplayJSON = addslashes($statusDisplayJSON);

        $fields = array_map(function ($f) use ($table) {
            return "{$table}.{$f}";
        }, array_keys((new UsersModel)->getFields()));
        $fieldsToAdd = [
            "LPAD({$table}.id, 5, 0) AS idPadding",
            "TRIM(CONCAT(TRIM({$table}.firstname), {$secondNameSegment}, ' ', {$table}.first_lastname, {$secondLastNameSegment})) AS fullname",
            "TRIM(CONCAT(TRIM({$table}.firstname), {$secondNameSegment})) AS names",
            "TRIM(CONCAT({$table}.first_lastname, {$secondLastNameSegment})) AS lastNames",
            "JSON_UNQUOTE(JSON_EXTRACT('{$typesJSON}', CONCAT('$.', {$table}.type))) AS typeName",
            "JSON_UNQUOTE(JSON_EXTRACT('{$statusDisplayJSON}', CONCAT('$.', {$table}.status))) AS statusText",
        ];

        foreach ($fieldsToAdd as $fieldToAdd) {
            $fields[] = $fieldToAdd;
        }

        return $fields;

    }

    /**
     * @param UserDataPackage $currentUser
     * @param bool $asMapper
     * @return static[]|array
     */
    public static function all(UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $currentUser = $currentUser !== null ? $currentUser : getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization;

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);

        $where = [];

        if ($currentUser !== null) {
            $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
            if (!$canModifyOrganizations) {
                $criteryValue = $currentOrganizationID;
                $beforeOperator = !empty($where) ? 'AND' : '';
                $critery = "organization = {$criteryValue}";
                $where[] = "{$beforeOperator} ({$critery})";
            }
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
            $model->where($whereString);
        }

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            foreach ($result as $key => $value) {
                $result[$key] = new UsersModel($value->id);
            }
        }

        return $result;
    }

    /**
     * @param array[] $criteries Cada elemento debe tener las claves value, column y opcionalmente beforeOperator
     * @param string[] $orderBy
     * @param UserDataPackage $currentUser
     * @param bool $asMapper
     * @return static[]|array
     */
    public static function allByMultipleCriteries(array $criteries = [], array $orderBy = [], UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $orderBy = array_map(fn($e) => is_string($e) && mb_strlen($e) > 1 ? $e : null, $orderBy);
        $orderBy = array_filter($orderBy, fn($e) => $e !== null);
        $currentUser = $currentUser !== null ? $currentUser : getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization;

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);
        $where = [];
        $criteriesAdded = 0;

        if (!empty($criteries)) {
            foreach ($criteries as $critery) {
                $column = array_key_exists('column', $critery) ? $critery['column'] : null;
                $value = array_key_exists('value', $critery) ? $critery['value'] : null;
                $beforeOperatorBase = array_key_exists('beforeOperator', $critery) ? $critery['beforeOperator'] : 'AND';
                if ($column !== null && $value !== null) {
                    $isNumber = is_double($value) || is_int($value);
                    $criteryValue = $isNumber ? $value : "'" . escapeString($value) . "'";
                    $beforeOperator = !empty($where) ? $beforeOperatorBase : '';
                    $critery = "{$column}  = {$criteryValue}";
                    $where[] = "{$beforeOperator} ({$critery})";
                    $criteriesAdded++;
                }
            }
        }

        if ($criteriesAdded > 0) {

            if ($currentUser !== null) {
                $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
                if (!$canModifyOrganizations) {
                    $criteryValue = $currentOrganizationID;
                    $beforeOperator = !empty($where) ? 'AND' : '';
                    $critery = "organization = {$criteryValue}";
                    $where[] = "{$beforeOperator} ({$critery})";
                }
            }

            if (!empty($where)) {
                $whereString = trim(implode(' ', $where));
                $model->where($whereString);
            }

            if (!empty($orderBy)) {
                $model->orderBy($orderBy);
            }

            $model->execute();

            $result = $model->result();

            if ($asMapper) {
                foreach ($result as $key => $value) {
                    $result[$key] = new UsersModel($value->id);
                }
            }

            return $result;
        } else {
            return null;
        }
    }

    /**
     * @param mixed $value
     * @param string $column
     * @param string[] $orderBy
     * @param UserDataPackage $currentUser
     * @param bool $asMapper
     * @return \stdClass|static|null
     */
    public static function getBy($value, string $column = 'id', array $orderBy = [], UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $orderBy = array_map(fn($e) => is_string($e) && mb_strlen($e) > 1 ? $e : null, $orderBy);
        $orderBy = array_filter($orderBy, fn($e) => $e !== null);
        $currentUser = $currentUser !== null ? $currentUser : getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization;

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);

        $isNumber = is_double($value) || is_int($value);
        $value = $isNumber ? $value : "'" . escapeString($value) . "'";
        $where = [
            "{$column} = {$value}",
        ];

        if ($currentUser !== null) {
            $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
            if (!$canModifyOrganizations) {
                $criteryValue = $currentOrganizationID;
                $beforeOperator = !empty($where) ? 'AND' : '';
                $critery = "organization = {$criteryValue}";
                $where[] = "{$beforeOperator} ({$critery})";
            }
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
            $model->where($whereString);
        }

        if (!empty($orderBy)) {
            $model->orderBy($orderBy);
        }

        $model->execute();

        $result = $model->result();
        $result = !empty($result) ? $result[0] : null;

        if ($asMapper && $result !== null) {
            $result = new UsersModel($result->id);
        }

        return $result;
    }

    /**
     * @param array[] $criteries Cada elemento debe tener las claves value, column y opcionalmente beforeOperator
     * @param string[] $orderBy
     * @param UserDataPackage $currentUser
     * @param bool $asMapper
     * @return \stdClass|static|null
     */
    public static function getByMultipleCriteries(array $criteries = [], array $orderBy = [], UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $orderBy = array_map(fn($e) => is_string($e) && mb_strlen($e) > 1 ? $e : null, $orderBy);
        $orderBy = array_filter($orderBy, fn($e) => $e !== null);
        $currentUser = $currentUser !== null ? $currentUser : getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser->organization;

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);
        $where = [];
        $criteriesAdded = 0;

        if (!empty($criteries)) {
            foreach ($criteries as $critery) {
                $column = array_key_exists('column', $critery) ? $critery['column'] : null;
                $value = array_key_exists('value', $critery) ? $critery['value'] : null;
                $beforeOperatorBase = array_key_exists('beforeOperator', $critery) ? $critery['beforeOperator'] : 'AND';
                if ($column !== null && $value !== null) {
                    $isNumber = is_double($value) || is_int($value);
                    $criteryValue = $isNumber ? $value : "'" . escapeString($value) . "'";
                    $beforeOperator = !empty($where) ? $beforeOperatorBase : '';
                    $critery = "{$column}  = {$criteryValue}";
                    $where[] = "{$beforeOperator} ({$critery})";
                    $criteriesAdded++;
                }
            }
        }

        if ($criteriesAdded > 0) {

            if ($currentUser !== null) {
                $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
                if (!$canModifyOrganizations) {
                    $criteryValue = $currentOrganizationID;
                    $beforeOperator = !empty($where) ? 'AND' : '';
                    $critery = "organization = {$criteryValue}";
                    $where[] = "{$beforeOperator} ({$critery})";
                }
            }

            if (!empty($where)) {
                $whereString = trim(implode(' ', $where));
                $model->where($whereString);
            }

            if (!empty($orderBy)) {
                $model->orderBy($orderBy);
            }

            $model->execute();

            $result = $model->result();
            $result = !empty($result) ? $result[0] : null;

            if ($asMapper && $result !== null) {
                $result = new UsersModel($result->id);
            }

            return $result;
        } else {
            return null;
        }
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new UsersModel())->getModel();
    }
}
