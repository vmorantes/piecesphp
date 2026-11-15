<?php

/**
 * AIUsageMapper.php
 */

namespace PiecesPHP\AI\Mappers;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * AIUsageMapper - Lo gastado en IA por capacidad, proveedor, modelo y periodo (ADR 0074 y 0077): una fila por (capability,
 * provider, model, periodStart), con sus tokens (el total, la entrada y la salida) y sus peticiones. El periodo «acumulado» no tiene inicio: usa ACCUMULATED_PERIOD_START.
 *
 * Lo lee y lo escribe AIGateway con sentencias preparadas: este mapper declara la tabla para `scheme-create` y la
 * siembra, no se hidrata fila a fila.
 *
 * @property int|null $id
 * @property string $capability
 * @property string $provider
 * @property string $model
 * @property string $periodStart
 * @property int $tokens
 * @property int $inputTokens
 * @property int $outputTokens
 * @property int $requests
 * @property string $createdAt
 * @property string $updatedAt
 * @package     PiecesPHP\AI\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class AIUsageMapper extends BaseEntityMapper
{

    const TABLE_PREFIX = 'pcsphp_';
    const TABLE_NAME = 'ai_usage';
    const TABLE = self::TABLE_PREFIX . self::TABLE_NAME;

    /**
     * El inicio fijo del periodo «acumulado», que no empieza nunca: una sola fila por capacidad y proveedor.
     */
    const ACCUMULATED_PERIOD_START = '2000-01-01 00:00:00';

    /**
     * El archivo que un clon aplica para tener la tabla.
     */
    const UPDATE_FILE = 'databases/actualizaciones/2026-11-13-consumo-de-la-ia.sql';

    /**
     * El que añade la entrada, la salida y el modelo (después de UPDATE_FILE).
     */
    const UPDATE_FILE_BY_MODEL = 'databases/actualizaciones/2026-11-14-consumo-de-la-ia-por-modelo.sql';

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * El único (capability, provider, model, periodStart) no lo emite `scheme-create`: va a mano en el esquema y en el archivo
     * de actualización.
     *
     * @var array<string,array<string,mixed>>
     */
    protected $fields = [
        'id' => [
            'type' => 'bigint',
            'primary_key' => true,
        ],
        'capability' => [
            'type' => 'varchar',
            'length' => 64,
        ],
        'provider' => [
            'type' => 'varchar',
            'length' => 64,
        ],
        'model' => [
            'type' => 'varchar',
            'length' => 128,
            'default' => '',
        ],
        'periodStart' => [
            'type' => 'datetime',
        ],
        'tokens' => [
            'type' => 'bigint',
        ],
        'inputTokens' => [
            'type' => 'bigint',
            'default' => 0,
        ],
        'outputTokens' => [
            'type' => 'bigint',
            'default' => 0,
        ],
        'requests' => [
            'type' => 'int',
        ],
        'createdAt' => [
            'type' => 'datetime',
        ],
        'updatedAt' => [
            'type' => 'datetime',
        ],
    ];
}
