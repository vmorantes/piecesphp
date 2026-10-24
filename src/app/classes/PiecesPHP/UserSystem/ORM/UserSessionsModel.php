<?php

/**
 * UserSessionsModel.php
 */

namespace PiecesPHP\UserSystem\ORM;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * UserSessionsModel - Una fila por sesión (ADR 0026, fase B). La lógica vive en Authentication\UserSessions; este mapper
 * declara la tabla para los listados (DataTables) y para quien lea el esquema.
 *
 * Es del núcleo: su verdad es databases/piecesphp_structure.sql (con el índice ÚNICO de sessionHash, que scheme-create
 * no emite).
 *
 * @package     PiecesPHP\UserSystem\ORM
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @property int $id
 * @property int $userID
 * @property string $sessionHash sha256 del identificador; el identificador en claro solo viaja dentro del token
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $lastActivityAt
 * @property string|\DateTime $expiresAt
 * @property string|null $ip
 * @property string|null $userAgent
 * @property string|\DateTime|null $closedAt
 * @property int|null $closedBy
 * @property string|null $closeReason
 */
class UserSessionsModel extends BaseEntityMapper
{
    const TABLE = 'pcsphp_user_sessions';

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * @var array<string,array<string,mixed>>
     */
    protected $fields = [
        'id' => [
            'type' => 'bigint',
            'primary_key' => true,
        ],
        'userID' => [
            'type' => 'int',
        ],
        //En la tabla es char(64); el vocabulario de EntityMapper no tiene «char».
        'sessionHash' => [
            'type' => 'varchar',
            'length' => 64,
        ],
        'createdAt' => [
            'type' => 'datetime',
        ],
        'lastActivityAt' => [
            'type' => 'datetime',
        ],
        'expiresAt' => [
            'type' => 'datetime',
        ],
        'ip' => [
            'type' => 'varchar',
            'length' => 45,
            'null' => true,
        ],
        'userAgent' => [
            'type' => 'varchar',
            'length' => 255,
            'null' => true,
        ],
        'closedAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'closedBy' => [
            'type' => 'int',
            'null' => true,
        ],
        'closeReason' => [
            'type' => 'varchar',
            'length' => 32,
            'null' => true,
        ],
    ];
}
