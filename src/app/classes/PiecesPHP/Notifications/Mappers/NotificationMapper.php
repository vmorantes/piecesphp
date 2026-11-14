<?php

/**
 * NotificationMapper.php
 */

namespace PiecesPHP\Notifications\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * NotificationMapper - Una notificación para un usuario (ADR 0073). Guarda las claves y los parámetros, nunca el texto
 * traducido ni una URL: el texto se pinta al leer, en el idioma de quien lee, y el enlace se resuelve al leer.
 *
 * Lo leen y lo escriben Notifications y su fuente con sentencias preparadas: este mapper declara la tabla para
 * `scheme-create` y la siembra, no se hidrata fila a fila.
 *
 * @property int|null $id
 * @property int $user
 * @property string $kind
 * @property string $level
 * @property string $langGroup
 * @property string $titleKey
 * @property string|null $bodyKey
 * @property string|null $params JSON
 * @property string|null $routeName
 * @property string|null $routeParams JSON
 * @property string $createdAt
 * @property string|null $readAt
 * @property string|null $meta JSON
 * @package     PiecesPHP\Notifications\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class NotificationMapper extends BaseEntityMapper
{

    const TABLE_PREFIX = 'pcsphp_';
    const TABLE_NAME = 'notifications';
    const TABLE = self::TABLE_PREFIX . self::TABLE_NAME;

    /**
     * Las leídas de más de estos días se purgan al escribir otra.
     */
    const RETENTION_DAYS = 90;

    /**
     * El archivo que un clon aplica para tener la tabla.
     */
    const UPDATE_FILE = 'databases/actualizaciones/2026-11-11-centro-de-notificaciones.sql';

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * Los índices (user, readAt) y (user, createdAt) y el ON DELETE CASCADE no los emite `scheme-create`: van a mano en
     * el esquema y en el archivo de actualización.
     *
     * @var array<string,array<string,mixed>>
     */
    protected $fields = [
        'id' => [
            'type' => 'bigint',
            'primary_key' => true,
        ],
        'user' => [
            'type' => 'bigint',
            'reference_table' => UsersModel::TABLE,
            'reference_field' => 'id',
            'reference_primary_key' => 'id',
        ],
        'kind' => [
            'type' => 'varchar',
            'length' => 64,
        ],
        'level' => [
            'type' => 'varchar',
            'length' => 16,
        ],
        'langGroup' => [
            'type' => 'varchar',
            'length' => 64,
        ],
        'titleKey' => [
            'type' => 'text',
        ],
        'bodyKey' => [
            'type' => 'text',
            'null' => true,
        ],
        'params' => [
            'type' => 'json',
            'null' => true,
        ],
        'routeName' => [
            'type' => 'varchar',
            'length' => 191,
            'null' => true,
        ],
        'routeParams' => [
            'type' => 'json',
            'null' => true,
        ],
        'createdAt' => [
            'type' => 'datetime',
        ],
        'readAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'meta' => [
            'type' => 'json',
            'null' => true,
        ],
    ];
}
