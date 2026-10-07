<?php

/**
 * UsersModel.php
 */

namespace PiecesPHP\UserSystem\ORM;

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\Roles;
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
 * @property string $firstLastname
 * @property string $secondLastname
 * @property string $email
 * @property string|array|\stdClass $meta
 * @property int $type
 * @property int $status
 * @property int $failedAttempts
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $modifiedAt
 * @property string|\DateTime|null $sessionsValidFrom Un token del usuario vale si nació DESPUÉS de esta fecha
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
    const STATUS_USER_DELETED = 6;
    const STATUSES_VALUES = [
        self::STATUS_USER_INACTIVE,
        self::STATUS_USER_ACTIVE,
        self::STATUS_USER_ATTEMPTS_BLOCK,
        self::STATUS_USER_APPROVED_PENDING,
        self::STATUS_USER_REJECTED,
        self::STATUS_USER_DELETED,
    ];
    const STATUSES = [
        self::STATUS_USER_INACTIVE => 'Inactivo',
        self::STATUS_USER_ACTIVE => 'Activo',
        self::STATUS_USER_ATTEMPTS_BLOCK => 'Bloqueado por intentos fallidos',
        self::STATUS_USER_APPROVED_PENDING => 'Por aprobar',
        self::STATUS_USER_REJECTED => 'No aprobado',
        self::STATUS_USER_DELETED => 'Eliminado',
    ];
    const STATUSES_FOR_DISPLAY_QUERY = [
        self::STATUS_USER_INACTIVE => 'No',
        self::STATUS_USER_ACTIVE => 'Sí',
        self::STATUS_USER_ATTEMPTS_BLOCK => 'Bloqueado por intentos fallidos',
        self::STATUS_USER_APPROVED_PENDING => 'Por aprobar',
        self::STATUS_USER_REJECTED => 'No aprobado',
        self::STATUS_USER_DELETED => 'Eliminado',
    ];
    const STATUSES_OK_FOR_LOGIN_ON_REQUIRE_APPROBATION = [
        self::STATUS_USER_ACTIVE,
    ];
    const STATUSES_OK_FOR_LOGIN_ON_NO_REQUIRE_APPROBATION = [
        self::STATUS_USER_ACTIVE,
        self::STATUS_USER_APPROVED_PENDING,
        self::STATUS_USER_REJECTED,
    ];
    const STATUSES_HIDDEN_ON_CREATION = [
        self::STATUS_USER_ATTEMPTS_BLOCK,
        self::STATUS_USER_DELETED,
    ];
    const STATUSES_HIDDEN_ON_EDIT = [
        self::STATUS_USER_ATTEMPTS_BLOCK,
        self::STATUS_USER_APPROVED_PENDING,
        self::STATUS_USER_REJECTED,
        self::STATUS_USER_DELETED,
    ];
    /**
     * Clave de `meta` con el estado que tenía el usuario al bloquearse por intentos.
     */
    const META_STATUS_BEFORE_BLOCK = 'statusBeforeBlock';
    const STATUSES_INACTIVE_EQUIVALENT = [
        self::STATUS_USER_INACTIVE,
        self::STATUS_USER_DELETED,
    ];
    /**
     * Entran, pero solo a lo suyo (P103/P104): su rol se reduce a las rutas generales y a keepsWhenRestricted().
     * Lo aplica index.php §8, con el módulo de aprobaciones encendido o apagado.
     */
    const STATUSES_RESTRICTED_TO_OWN = [
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
    const TYPE_USER_GOOGLE_PLAY = 50;

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
        //self::TYPE_USER_GOOGLE_PLAY => 'Google Play',
    ];

    const TYPES_USER_PRIORITY = [
        self::TYPE_USER_ROOT => 500,
        self::TYPE_USER_ADMIN_GRAL => 400,
        self::TYPE_USER_ADMIN_ORG => 1,
        self::TYPE_USER_GENERAL => 1,
        self::TYPE_USER_INSTITUCIONAL => 100,
        self::TYPE_USER_COMUNICACIONES => 2,
        self::TYPE_USER_GOOGLE_PLAY => -50,
    ];

    const TYPES_USER_DO_NOT_HAVE_AUTHORITY_OVER_SAME_TYPE = [
        self::TYPE_USER_ADMIN_ORG,
    ];

    const TYPES_USER_DONT_REQUIRE_ORGANIZATION = [
        self::TYPE_USER_ROOT,
        self::TYPE_USER_ADMIN_GRAL,
        self::TYPE_USER_GOOGLE_PLAY,
    ];

    const TYPES_USER_SHOULD_HAVE_PROFILE = [
        self::TYPE_USER_ADMIN_ORG,
        self::TYPE_USER_GENERAL,
    ];

    const TYPES_WITH_EXTERNAL_LOGIN = [
        self::TYPE_USER_GENERAL,
        self::TYPE_USER_GOOGLE_PLAY,
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
        'firstLastname' => [
            'type' => 'varchar',
        ],
        'secondLastname' => [
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
        'failedAttempts' => [
            'type' => 'int',
        ],
        'createdAt' => [
            'type' => 'datetime',
        ],
        'modifiedAt' => [
            'type' => 'datetime',
        ],
        //Un token de este usuario vale si nació DESPUÉS de esta fecha. NULL es «sin revocaciones».
        'sessionsValidFrom' => [
            'type' => 'datetime',
            'null' => true,
        ],
    ];

    /**
     * @param integer $id
     * @return static
     */
    public function __construct(?int $id = null)
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

        //Desde database 5.2.0 el padre deja en ->id el de la inserción: el perfil lo lee tras el alta.
        return parent::save();

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
     * Las rutas de su rol que conserva un usuario acotado a lo suyo (sin aprobar por el módulo, o en un estado de
     * STATUSES_RESTRICTED_TO_OWN), además de las generales.
     *
     * @param string $routeName
     * @return bool
     */
    public static function keepsWhenRestricted(string $routeName): bool
    {
        return $routeName === 'configurations-integrations-mapbox-key'
            || str_starts_with($routeName, 'my-profile-admin-')
            || str_starts_with($routeName, 'my-organization-profile-admin-')
            || str_starts_with($routeName, 'profile-organization-admin-')
            || str_starts_with($routeName, 'profile-admin-')
            //Solo enviar la edición del propio perfil y los ajustes de su cuenta: «users-» entero concedería la gestión de usuarios.
            || $routeName === 'users-edit-request'
            || str_starts_with($routeName, 'user-system-features-')
            || str_starts_with($routeName, 'my-space-admin-')
            || str_starts_with($routeName, 'api-admin-')
            || str_starts_with($routeName, 'SAMPLE');
    }

    /**
     * El recorte: las rutas generales más las del rol que conserva keepsWhenRestricted(). Aplicarlo dos veces da lo mismo.
     *
     * @param string[] $allowedRoutes Las rutas del rol
     * @return string[]
     */
    public static function restrictRoutes(array $allowedRoutes): array
    {
        $generals = get_config('roles')['baseInitialSegmentedPermissions']['generals'] ?? [];
        $kept = array_filter($allowedRoutes, fn($e) => self::keepsWhenRestricted((string) $e));
        return array_values(array_unique(array_merge(is_array($generals) ? $generals : [], $kept)));
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
            $this->firstLastname,
            $this->secondLastname,
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
            $this->firstLastname,
            $this->secondLastname,
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
        unset($data['modifiedAt']);
        unset($data['createdAt']);
        unset($data['failedAttempts']);
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
     * @return object|null
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
     * @return object|null
     */
    public function getByID($id)
    {
        //El id se valida antes de consultar: ligado, una cadena como "1' OR ..." se convierte en el entero 1.
        if (!(is_int($id) || (is_string($id) && ctype_digit($id))) || (int) $id <= 0) {
            return null;
        }
        $model = $this->getModel();
        $model->resetAll();
        $result = $model
            ->select()
            ->where(new WhereSegment([WhereItem::isEqual('id', (int) $id)]))
            ->row();
        return is_object($result) ? $result : null;
    }

    /**
     * @param mixed $email
     * @return object|null
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
            'secondLastname' => $second_lastname,
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
        //Bloquear guarda en `meta` el estado de antes, en la MISMA sentencia: unblockFromAttempts() lo devuelve, y una
        //escritura de `meta` entre leer y escribir (los filtros guardados) no se pierde.
        if ((int) $status === self::STATUS_USER_ATTEMPTS_BLOCK) {
            //`meta` antes que `status`: las asignaciones de un UPDATE se evalúan de izquierda a derecha. Un `meta` que no es
            //un objeto JSON se deja como está (sin estado guardado, el desbloqueo usa su respaldo).
            $statement = self::model()->prepare('UPDATE `' . self::TABLE . '` SET meta = CASE'
                . " WHEN meta IS NULL OR meta = '' OR meta = '[]' THEN JSON_OBJECT(?, status)"
                . " WHEN JSON_VALID(meta) AND JSON_TYPE(meta) = 'OBJECT' THEN JSON_SET(meta, ?, status)"
                . ' ELSE meta END, status = ? WHERE id = ? AND status <> ?');
            return $statement->execute([
                self::META_STATUS_BEFORE_BLOCK,
                '$.' . self::META_STATUS_BEFORE_BLOCK,
                self::STATUS_USER_ATTEMPTS_BLOCK,
                (int) $id,
                self::STATUS_USER_ATTEMPTS_BLOCK,
            ]);
        }
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'status' => $status,
        ])->where(['id' => $id])->execute();
    }

    /**
     * Desbloquea al bloqueado por intentos y le devuelve el estado que tenía al bloquearse. A quien no está bloqueado
     * no lo toca.
     *
     * @param int $id
     * @param int $statusIfNoneSaved Para un bloqueo sin estado guardado (anterior a guardarlo)
     * @param int|null $statusOverride Manda sobre lo guardado (un perfil rechazado vuelve rechazado). Los dos los decide
     *                                 quien llama: el modelo no conoce las aprobaciones
     * @return bool Si desbloqueó: false también si otra petición lo cambió antes
     */
    public function unblockFromAttempts(int $id, int $statusIfNoneSaved, ?int $statusOverride = null): bool
    {
        $path = '$.' . self::META_STATUS_BEFORE_BLOCK;
        //El estado Y lo guardado que se leyó, en el WHERE: si una resolución lo cambió, no se pisa; se relee una vez y, si
        //no casa, no se desbloquea. La clave se quita en la base, sin reescribir el resto de `meta`.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $fresh = static::freshStatusAndMeta($id);
            if ($fresh === null || $fresh['status'] !== self::STATUS_USER_ATTEMPTS_BLOCK) {
                return false;
            }
            $rawSaved = $fresh['meta'][self::META_STATUS_BEFORE_BLOCK] ?? null;
            $status = $statusOverride ?? self::validStatusBeforeBlock($rawSaved) ?? $statusIfNoneSaved;
            $this->updateModifiedAt($id);
            $statement = self::model()->prepare('UPDATE `' . self::TABLE . '` SET status = ?, failedAttempts = 0,'
                . ' meta = IF(JSON_VALID(meta), JSON_REMOVE(meta, ?), meta) WHERE id = ? AND status = ? AND ' . self::sqlSavedStatusIs());
            $statement->execute([$status, $path, $id, self::STATUS_USER_ATTEMPTS_BLOCK, $path, $path, $path, self::validStatusBeforeBlock($rawSaved) ?? -1]);
            if ($statement->rowCount() > 0) {
                return true;
            }
        }
        //RETORNO-IGNORADO: lo que importa es dejar constancia; la referencia no tiene a quién darse.
        log_exception(new \RuntimeException("Desbloqueo no aplicado al usuario {$id}: su estado guardado cambió dos veces entre la lectura y la escritura."));
        return false;
    }

    /**
     * El estado que tenía al bloquearse, si sigue bloqueado y lo tiene guardado.
     *
     * @param int $id
     * @return int|null
     */
    public static function statusBeforeBlock(int $id): ?int
    {
        $fresh = static::freshStatusAndMeta($id);
        if ($fresh === null || $fresh['status'] !== self::STATUS_USER_ATTEMPTS_BLOCK) {
            return null;
        }
        return self::validStatusBeforeBlock($fresh['meta'][self::META_STATUS_BEFORE_BLOCK] ?? null);
    }

    /**
     * Una transición de estado sobre un bloqueado: se aplica a su estado guardado y el usuario sigue bloqueado. Así una
     * aprobación o un rechazo resueltos durante el bloqueo no se pierden al desbloquear.
     *
     * @param int $id
     * @param int[] $from Los estados guardados que la transición mueve
     * @param int $to
     * @return bool Si la aplicó
     */
    public static function transitionStatusBeforeBlock(int $id, array $from, int $to): bool
    {
        $path = '$.' . self::META_STATUS_BEFORE_BLOCK;
        //Con el valor leído en el WHERE: si cambió, no se pisa; se relee una vez y, si no casa, no se transiciona. CAST: el
        //marcador llega como cadena y JSON_SET guardaría "1", que validStatusBeforeBlock() no reconoce.
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $saved = static::statusBeforeBlock($id);
            if ($saved === null || !in_array($saved, $from, true)) {
                return false;
            }
            $statement = self::model()->prepare('UPDATE `' . self::TABLE . '` SET meta = JSON_SET(meta, ?, CAST(? AS SIGNED))'
                . ' WHERE id = ? AND status = ? AND JSON_VALID(meta) AND JSON_EXTRACT(meta, ?) = CAST(? AS SIGNED)');
            $statement->execute([$path, $to, $id, self::STATUS_USER_ATTEMPTS_BLOCK, $path, $saved]);
            if ($statement->rowCount() > 0) {
                return true;
            }
        }
        //RETORNO-IGNORADO: lo que importa es dejar constancia; la referencia no tiene a quién darse.
        log_exception(new \RuntimeException("Transición no aplicada al usuario {$id}: su estado guardado cambió dos veces entre la lectura y la escritura."));
        return false;
    }

    /**
     * Si un usuario entra solo a lo suyo: pendiente o rechazado, y el bloqueado que lo era al bloquearse o del que no se
     * guardó nada. Un bloqueado que era activo conserva su rol: el bloqueo protege la contraseña, no recorta.
     *
     * @param int $id
     * @param int $status
     * @return bool
     */
    public static function isRestrictedToOwn(int $id, int $status): bool
    {
        if (in_array($status, self::STATUSES_RESTRICTED_TO_OWN, true)) {
            return true;
        }
        if ($status !== self::STATUS_USER_ATTEMPTS_BLOCK) {
            return false;
        }
        $saved = static::statusBeforeBlock($id);
        return $saved === null || in_array($saved, self::STATUSES_RESTRICTED_TO_OWN, true);
    }

    /**
     * Lo guardado en `meta`, en SQL con la misma regla que validStatusBeforeBlock(): un entero JSON de los que entran, y
     * si no (sin clave, otro tipo, otro valor, `meta` no JSON), -1, que es «sin guardado». Por tipo y no por texto: como
     * texto, un `true` o un `1.0` no casaban nunca y el desbloqueo fallaba para siempre. Cuatro marcadores: la ruta tres
     * veces y el valor de validStatusBeforeBlock() ?? -1.
     *
     * @return string
     */
    private static function sqlSavedStatusIs(): string
    {
        //La pertenencia, sobre el texto JSON exacto: `3e0` es INTEGER para MariaDB y CAST lo da como 3, pero PHP lo decodifica
        //como float y no lo reconoce.
        $allowed = implode(', ', array_map(fn ($status) => "'" . (int) $status . "'", self::STATUSES_OK_FOR_LOGIN_ON_NO_REQUIRE_APPROBATION));
        return "IF(JSON_VALID(meta) AND JSON_TYPE(JSON_EXTRACT(meta, ?)) = 'INTEGER' AND CAST(JSON_EXTRACT(meta, ?) AS CHAR) IN ({$allowed}),"
            . ' CAST(JSON_EXTRACT(meta, ?) AS SIGNED), -1) = ?';
    }

    /**
     * Una transición de estado, en la base: si está bloqueado, sobre lo guardado (sigue en 2); si no, sobre el estado vivo,
     * con el leído en el WHERE. Así una resolución que pierde la carrera con el desbloqueo no se pierde: se aplica al
     * estado al que volvió.
     *
     * @param int $id
     * @param int[] $from
     * @param int $to
     * @return bool Si la aplicó
     */
    public static function applyStatusTransition(int $id, array $from, int $to): bool
    {
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $fresh = static::freshStatusAndMeta($id);
            if ($fresh === null) {
                return false;
            }
            if ($fresh['status'] === self::STATUS_USER_ATTEMPTS_BLOCK) {
                $saved = self::validStatusBeforeBlock($fresh['meta'][self::META_STATUS_BEFORE_BLOCK] ?? null);
                if ($saved === null || !in_array($saved, $from, true)) {
                    return false;
                }
                if (static::transitionStatusBeforeBlock($id, $from, $to)) {
                    return true;
                }
                continue;
            }
            if (!in_array($fresh['status'], $from, true)) {
                return false;
            }
            $statement = self::model()->prepare('UPDATE `' . self::TABLE . '` SET status = ? WHERE id = ? AND status = ?');
            $statement->execute([$to, $id, $fresh['status']]);
            if ($statement->rowCount() > 0) {
                (new static())->updateModifiedAt($id);
                return true;
            }
        }
        //RETORNO-IGNORADO: lo que importa es dejar constancia; la referencia no tiene a quién darse.
        log_exception(new \RuntimeException("Transición de estado no aplicada al usuario {$id}: su estado cambió dos veces entre la lectura y la escritura."));
        return false;
    }

    /**
     * El estado que cuenta para una resolución: el vivo, o el guardado si está bloqueado (null si no hay guardado).
     *
     * @param int $id
     * @return int|null
     */
    public static function effectiveStatus(int $id): ?int
    {
        $fresh = static::freshStatusAndMeta($id);
        if ($fresh === null) {
            return null;
        }
        return $fresh['status'] === self::STATUS_USER_ATTEMPTS_BLOCK ? self::validStatusBeforeBlock($fresh['meta'][self::META_STATUS_BEFORE_BLOCK] ?? null) : $fresh['status'];
    }

    /**
     * Solo se bloquea a quien puede iniciar sesión: otro valor guardado no es de un bloqueo y no se devuelve.
     *
     * @param mixed $saved
     * @return int|null
     */
    private static function validStatusBeforeBlock($saved): ?int
    {
        return is_int($saved) && in_array($saved, self::STATUSES_OK_FOR_LOGIN_ON_NO_REQUIRE_APPROBATION, true) ? $saved : null;
    }

    /**
     * El estado y el `meta` del usuario, frescos de la base (no los que este mapper leyó antes).
     *
     * @param int $id
     * @return array{status:int,meta:array<string,mixed>}|null
     */
    protected static function freshStatusAndMeta(int $id): ?array
    {
        $model = self::model();
        $model->resetAll();
        $model->select(['status', 'meta'])->where(new WhereSegment([WhereItem::isEqual('id', $id)]))->execute();
        $row = ((array) $model->result())[0] ?? null;
        if (!is_object($row)) {
            return null;
        }
        $row = get_object_vars($row);
        $raw = $row['meta'] ?? null;
        $meta = is_string($raw) && $raw !== '' ? json_decode($raw, true) : $raw;
        $meta = is_object($meta) ? (array) json_decode((string) json_encode($meta), true) : $meta;
        return [
            'status' => (int) ($row['status'] ?? -1),
            'meta' => is_array($meta) ? $meta : [],
        ];
    }

    /**
     * Cierra todas las sesiones del usuario: mueve su marca `sessionsValidFrom` a ahora (ADR 0026).
     *
     * Solo esa columna: `update()` reescribiría la fila entera con lo que el mapper leyó antes. La hora sale de PHP
     * y no de la base porque `index.php` la compara con el `created` del token, que también es de PHP; un token nacido
     * en este mismo segundo queda revocado (`<=`).
     *
     * @param int $id
     * @return bool
     */
    public function revokeSessions(int $id): bool
    {
        $model = $this->getModel();
        $model->resetAll();
        return $model->update([
            'sessionsValidFrom' => date('Y-m-d H:i:s'),
        ])->where(['id' => $id])->execute() === true;
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
                'failedAttempts' => ($user->failedAttempts + 1),
            ])->where(['id' => $id])->execute();
            $user = $this->getByID($id);
            return is_object($user) ? $user->failedAttempts : 0;
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
                'modifiedAt' => date('Y-m-d H:i:s'),
            ])->where(['email' => $criterio])->execute();
        } else {
            $model = $this->getModel();
            $model->resetAll();
            return $model->update([
                'modifiedAt' => date('Y-m-d H:i:s'),
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
            'failedAttempts' => 0,
        ])->where(['id' => $id])->execute();
    }

    /**
     * Devuelve los valores de STATUSES pasados por función __()
     *
     * @return array<int,string>
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
     * @return array<int,string>
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

        $result = $model->result();

        return is_array($result) ? $result : [];

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

        $result = $model->result();

        return is_array($result) ? $result : [];

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

        $result = $model->result();

        return is_array($result) ? $result : [];

    }

    /**
     * @return array
     */
    public static function getTypesUser()
    {

        $types = [];

        foreach (self::TYPES_USERS as $key => $value) {

            if (Roles::roleExists($key)) {
                $types[$key] = __(self::LANG_GROUP, $value);
            }

        }

        return $types;

    }

    /**
     * @return string|null
     */
    public static function getTypeUserName(int $type)
    {
        $userTypes = self::getTypesUser();
        return $userTypes[$type] ?? null;
    }

    /**
     * Un array listo para ser usado en array_to_html_options
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function typesUserForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = $defaultLabel !== '' ? $defaultLabel : __(self::LANG_GROUP, 'Tipos de usuario');
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

        $defaultLabel = $defaultLabel !== '' ? $defaultLabel : __(self::LANG_GROUP, 'Usuarios');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        $model->select(self::fieldsToSelect())->where(implode(' ', [
            "{$table}.organization = {$organizationID} AND",
            "{$table}.type IN ({$allowerdTypes})",
        ]));
        $model->execute();
        $users = $model->result();

        foreach ($users ?? [] as $user) {
            $userMapper = new UsersModel($user->id);
            $options[(string) $userMapper->id] = $userMapper->getFullName() . " ({$userMapper->username})";
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
        $secondLastNameSegment = "IF({$table}.secondLastname IS NOT NULL, CONCAT(' ', {$table}.secondLastname), '')";
        //Literal hexadecimal: la etiqueta es del SERVIDOR, pero editable por traducción dinámica (ADR 0009, T2 de #040).
        $typesJSON = json_encode((object) self::TYPES_USERS, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
        $statusDisplay = self::statusesForDisplayQuery();
        $statusDisplayJSON = json_encode((object) $statusDisplay, \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        //El hash de la contraseña nunca sale en un SELECT de listado: se leía en rawData (#054).
        $columns = array_values(array_filter(array_keys((new UsersModel)->getFields()), fn ($f) => $f !== 'password'));
        $fields = array_map(function ($f) use ($table) {
            return "{$table}.{$f}";
        }, $columns);
        $fieldsToAdd = [
            "LPAD({$table}.id, GREATEST(5, CHAR_LENGTH({$table}.id)), '0') AS idPadding",
            "TRIM(CONCAT(TRIM(CONCAT({$table}.firstname, {$secondNameSegment})), ' ', TRIM(CONCAT({$table}.firstLastname, {$secondLastNameSegment})))) AS fullname",
            "TRIM(CONCAT(TRIM({$table}.firstname), {$secondNameSegment})) AS names",
            "TRIM(CONCAT({$table}.firstLastname, {$secondLastNameSegment})) AS lastNames",
            "JSON_UNQUOTE(JSON_EXTRACT(" . sqlStringLiteral($typesJSON) . ", CONCAT('$.', {$table}.type))) AS typeName",
            "JSON_UNQUOTE(JSON_EXTRACT(" . sqlStringLiteral($statusDisplayJSON) . ", CONCAT('$.', {$table}.status))) AS statusText",
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
    public static function all(?UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $currentUser ??= getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser !== null ? $currentUser->organization : null;

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
     * @return static[]|array|null
     */
    public static function allByMultipleCriteries(array $criteries = [], array $orderBy = [], ?UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $orderBy = array_map(fn($e) => is_string($e) && mb_strlen($e) > 1 ? $e : null, $orderBy);
        $orderBy = array_filter($orderBy, fn($e) => $e !== null);
        $currentUser ??= getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser !== null ? $currentUser->organization : null;

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);
        $where = [];
        $criteriesAdded = 0;

        if (!empty($criteries)) {
            foreach ($criteries as $critery) {
                $column = $critery['column'] ?? null;
                $value = $critery['value'] ?? null;
                $beforeOperatorBase = array_key_exists('beforeOperator', $critery) ? $critery['beforeOperator'] : 'AND';
                if ($column !== null && $value !== null) {
                    //Por marcador (ADR 0009). El operador que lo une al anterior es el `after` de ese.
                    if (!empty($where)) {
                        $where[count($where) - 1]->setAfterOperator($beforeOperatorBase);
                    }
                    $where[] = WhereItem::isEqual($column, $value);
                    $criteriesAdded++;
                }
            }
        }

        if ($criteriesAdded > 0) {

            if ($currentUser !== null) {
                $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
                if (!$canModifyOrganizations) {
                    if (!empty($where)) {
                        $where[count($where) - 1]->setAfterOperator(WhereItem::AND_OPERATOR);
                    }
                    $where[] = WhereItem::isEqual('organization', $currentOrganizationID);
                }
            }

            if (!empty($where)) {
                $model->where(new WhereSegment($where));
            }

            if (!empty($orderBy)) {
                $model->orderBy($orderBy);
            }

            $model->execute();

            $result = $model->result();

            if ($asMapper) {
                foreach ($result ?? [] as $key => $value) {
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
     * @return ($asMapper is true ? static : \stdClass)|null
     */
    public static function getBy($value, string $column = 'id', array $orderBy = [], ?UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $orderBy = array_map(fn($e) => is_string($e) && mb_strlen($e) > 1 ? $e : null, $orderBy);
        $orderBy = array_filter($orderBy, fn($e) => $e !== null);
        $currentUser ??= null;
        $currentOrganizationID = $currentUser !== null ? $currentUser->organization : null;

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);

        //Por marcador: el valor viaja como dato y no depende de sql_mode (ADR 0009).
        $where = [
            WhereItem::isEqual($column, $value),
        ];

        if ($currentUser !== null) {
            $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
            if (!$canModifyOrganizations) {
                if (!empty($where)) {
                    $where[count($where) - 1]->setAfterOperator(WhereItem::AND_OPERATOR);
                }
                $where[] = WhereItem::isEqual('organization', $currentOrganizationID);
            }
        }

        if (!empty($where)) {
            $model->where(new WhereSegment($where));
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
     * @return ($asMapper is true ? static : \stdClass)|null
     */
    public static function getByMultipleCriteries(array $criteries = [], array $orderBy = [], ?UserDataPackage $currentUser = null, bool $asMapper = false)
    {

        $orderBy = array_map(fn($e) => is_string($e) && mb_strlen($e) > 1 ? $e : null, $orderBy);
        $orderBy = array_filter($orderBy, fn($e) => $e !== null);
        $currentUser ??= getLoggedFrameworkUser();
        $currentOrganizationID = $currentUser !== null ? $currentUser->organization : null;

        $model = self::model();
        $selectFields = self::fieldsToSelect();
        $model->select($selectFields);
        $where = [];
        $criteriesAdded = 0;

        if (!empty($criteries)) {
            foreach ($criteries as $critery) {
                $column = $critery['column'] ?? null;
                $value = $critery['value'] ?? null;
                $beforeOperatorBase = array_key_exists('beforeOperator', $critery) ? $critery['beforeOperator'] : 'AND';
                if ($column !== null && $value !== null) {
                    //Por marcador (ADR 0009). El operador que lo une al anterior es el `after` de ese.
                    if (!empty($where)) {
                        $where[count($where) - 1]->setAfterOperator($beforeOperatorBase);
                    }
                    $where[] = WhereItem::isEqual($column, $value);
                    $criteriesAdded++;
                }
            }
        }

        if ($criteriesAdded > 0) {

            if ($currentUser !== null) {
                $canModifyOrganizations = OrganizationMapper::canModifyAnyOrganization($currentUser->type);
                if (!$canModifyOrganizations) {
                    if (!empty($where)) {
                        $where[count($where) - 1]->setAfterOperator(WhereItem::AND_OPERATOR);
                    }
                    $where[] = WhereItem::isEqual('organization', $currentOrganizationID);
                }
            }

            if (!empty($where)) {
                $model->where(new WhereSegment($where));
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
