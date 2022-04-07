<?php
/**
 * UserDataPackage.php
 */
namespace PiecesPHP\UserSystem;

use App\Model\UsersModel;

/**
 * UserDataPackage.
 *
 * @package     PiecesPHP\UserSystem
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 * @property-read int $id
 * @property-read UsersModel $userMapper
 * @property-read \stdClass $userStdClass
 * @property-read string $password
 * @property-read string $username
 * @property-read string $firstname
 * @property-read string $secondname
 * @property-read string $firstLastname
 * @property-read string $secondLastname
 * @property-read string $fullName
 * @property-read string $email
 * @property-read string|array|\stdClass $meta
 * @property-read int $type
 * @property-read int $status
 * @property-read int $failedAttempts
 * @property-read \DateTime $createdAt
 * @property-read \DateTime|null $modifiedAt
 * @property-read string $createdAtString
 * @property-read string $modifiedAtString
 * @property-read \DateTime $dateInstantiated
 */
class UserDataPackage
{
    /**
     * @var int
     */
    protected $id = null;
    /**
     * @var UsersModel
     */
    protected $userMapper = null;
    /**
     * @var \stdClass
     */
    protected $userStdClass = null;
    /**
     * @var string
     */
    protected $password = null;
    /**
     * @var string
     */
    protected $username = null;
    /**
     * @var string
     */
    protected $firstname = null;
    /**
     * @var string
     */
    protected $secondname = null;
    /**
     * @var string
     */
    protected $firstLastname = null;
    /**
     * @var string
     */
    protected $secondLastname = null;
    /**
     * @var string
     */
    protected $fullName = null;
    /**
     * @var string
     */
    protected $email = null;
    /**
     * @var string|array|\stdClass
     */
    protected $meta = null;
    /**
     * @var int
     */
    protected $type = null;
    /**
     * @var int
     */
    protected $status = null;
    /**
     * @var int
     */
    protected $failedAttempts = null;
    /**
     * @var \DateTime
     */
    protected $createdAt = null;
    /**
     * @var \DateTime|null
     */
    protected $modifiedAt = null;
    /**
     * @var string
     */
    protected $createdAtString = null;
    /**
     * @var string
     */
    protected $modifiedAtString = null;
    /**
     * @var \DateTime
     */
    protected $dateInstantiated = null;

    /**
     * @var array<string,string>
     */
    protected static $aliasToReal = [
        'first_lastname' => 'firstLastname',
        'second_lastname' => 'secondLastname',
        'failed_attempts' => 'failedAttempts',
        'created_at' => 'createdAt',
        'modified_at' => 'modifiedAt',
        'fullname' => 'fullName',
        'full_name' => 'fullName',
    ];

    const LANG_GROUP = 'usersModule';

    /**
     * @param int $userID
     * @throws \Exception Si el usuario no existe
     */
    public function __construct(int $userID)
    {
        $this->setUserID($userID);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        $parseName = $name;
        if (array_key_exists($name, self::$aliasToReal)) {
            $parseName = self::$aliasToReal[$name];
        }
        return $this->$parseName;
    }

    /**
     * @param integer $userID
     * @return UserDataPackage
     * @throws \Exception Si el usuario no existe
     */
    protected function setUserID(int $userID)
    {
        $this->id = $userID;
        $this->userMapper = new UsersModel($userID);
        $userStdClass = (new UsersModel())->getByID($userID);
        if ($userStdClass instanceof \stdClass) {
            $this->userStdClass = $userStdClass;
        }

        if ($this->userMapper->id === null && $this->userStdClass instanceof \stdClass) {
            throw new \Exception(__(self::LANG_GROUP, "El usuario no existe."));
        }

        $this->dateInstantiated = new \DateTime();
        $this->password = $this->userMapper->password;
        $this->username = $this->userMapper->username;
        $this->firstname = $this->userMapper->firstname;
        $this->secondname = $this->userMapper->secondname;
        $this->firstLastname = $this->userMapper->first_lastname;
        $this->secondLastname = $this->userMapper->second_lastname;
        $this->fullName = $this->userMapper->getFullName();
        $this->email = $this->userMapper->email;
        $this->meta = $this->userMapper->meta;
        $this->type = $this->userMapper->type;
        $this->status = $this->userMapper->status;
        $this->failedAttempts = $this->userMapper->failed_attempts;
        $this->createdAt = $this->userMapper->created_at instanceof \DateTime ? $this->userMapper->created_at : new \DateTime($this->userMapper->created_at);
        $this->modifiedAt = null;
        if ($this->userMapper->modified_at instanceof \DateTime) {
            $this->modifiedAt = $this->userMapper->modified_at;
        } elseif (is_string($this->userMapper->modified_at)) {
            $this->modifiedAt = new \DateTime($this->userMapper->modified_at);
        }
        $this->createdAtString = $this->createdAt->format('Y-m-d H:i:s');
        $this->modifiedAtString = $this->modifiedAt !== null ? $this->modifiedAt->format('Y-m-d H:i:s') : '';

        $fromInstanceToStdClass = [
            'firstLastname',
            'secondLastname',
            'fullName',
            'failedAttempts',
            'createdAt',
            'modifiedAt',
            'createdAtString',
            'modifiedAtString',
        ];

        foreach ($fromInstanceToStdClass as $i) {
            $this->userStdClass->$i = $this->$i;
        }

        return $this;
    }
}
