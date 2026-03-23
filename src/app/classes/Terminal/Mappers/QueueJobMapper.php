<?php
/**
 * QueueJobMapper.php
 */
namespace Terminal\Mappers;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * QueueJobMapper
 *
 * @package     Terminal\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @property int $id
 * @property string $name
 * @property string|\stdClass|array|null $data
 * @property string $status
 * @property int $attempts
 * @property int $maxAttempts
 * @property string $errorMessage
 * @property \DateTime|string|null $createdAt
 * @property \DateTime|string|null $updatedAt
 * @property \DateTime|string|null $scheduledAt
 * @property \DateTime|string|null $startedAt
 * @property \DateTime|string|null $finishedAt
 */
class QueueJobMapper extends BaseEntityMapper
{
    const TABLE_PREFIX = 'pcsphp_';
    const TABLE_NAME = 'jobs_queue';
    const TABLE = self::TABLE_PREFIX . self::TABLE_NAME;

    protected $table = self::TABLE;
    protected $primaryKey = 'id';
    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'name' => [
            'type' => 'text',
        ],
        'data' => [
            'type' => 'json',
            'null' => true,
        ],
        'status' => [
            'type' => 'text',
            'default' => self::STATUS_PENDING,
        ],
        'attempts' => [
            'type' => 'int',
            'default' => 0,
        ],
        'maxAttempts' => [
            'type' => 'int',
            'default' => 3,
        ],
        'errorMessage' => [
            'type' => 'text',
            'null' => true,
        ],
        'createdAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'updatedAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'scheduledAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'startedAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
        'finishedAt' => [
            'type' => 'datetime',
            'null' => true,
        ],
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    /**
     * @param int|null $id
     * @param string $field_compare
     */
    public function __construct($id = null, string $field_compare = 'primary_key')
    {
        parent::__construct($id, $field_compare);
    }

    /**
     * Migra la tabla a la base de datos
     * @return bool
     */
    public static function migrate()
    {
        if (is_local()) {
            $model = self::model();
            $sql = (new \PiecesPHP\Core\Database\SchemeCreator(new QueueJobMapper()))->getSQL();
            $pdo = $model->prepare($sql);
            return $pdo->execute();
        }
        return false;
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }
}
