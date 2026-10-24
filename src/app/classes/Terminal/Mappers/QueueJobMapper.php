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
 * @property string|null $errorMessage
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
            'type' => 'varchar',
            'length' => 191,
        ],
        'data' => [
            'type' => 'json',
            'null' => true,
        ],
        'status' => [
            'type' => 'varchar',
            'length' => 16,
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
     * Cuántos días se guarda lo terminado y lo fallido (purge()). Lo fallido, más: es lo que se investiga.
     */
    const PURGE_COMPLETED_DAYS = 30;
    const PURGE_FAILED_DAYS = 90;

    /**
     * @param int|null $id
     * @param string $field_compare
     */
    public function __construct($id = null, string $field_compare = 'primary_key')
    {
        parent::__construct($id, $field_compare);
    }

    /**
     * Toma un trabajo pendiente para este trabajador: lo pasa a «en curso» solo si sigue pendiente. Con dos
     * trabajadores a la vez, uno gana y el otro recibe false y lo salta.
     *
     * @param int $id
     * @return bool
     */
    public static function claim(int $id): bool
    {
        $database = self::model()->getDatabase();
        if ($database === null) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $statement = $database->prepare('UPDATE `' . self::TABLE . '` SET status = ?, startedAt = ?, updatedAt = ?, attempts = attempts + 1 WHERE id = ? AND status = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION; lo que decide es rowCount().
        $statement->execute([self::STATUS_RUNNING, $now, $now, $id, self::STATUS_PENDING]);
        return $statement->rowCount() === 1;
    }

    /**
     * Borra lo terminado de más de PURGE_COMPLETED_DAYS días y lo fallido de más de PURGE_FAILED_DAYS. Sin
     * `finishedAt` (filas de antes de que lo fallido lo llevara), cuenta su última modificación o su creación.
     *
     * @param \DateTime|null $now
     * @return array{completed: int, failed: int}
     */
    public static function purge(?\DateTime $now = null): array
    {
        $database = self::model()->getDatabase();
        if ($database === null) {
            return ['completed' => 0, 'failed' => 0];
        }
        $now = $now !== null ? clone $now : new \DateTime();
        $deleted = [];
        foreach ([self::STATUS_COMPLETED => self::PURGE_COMPLETED_DAYS, self::STATUS_FAILED => self::PURGE_FAILED_DAYS] as $status => $days) {
            $limit = (clone $now)->modify("-{$days} days")->format('Y-m-d H:i:s');
            $statement = $database->prepare('DELETE FROM `' . self::TABLE . '` WHERE status = ? AND COALESCE(finishedAt, updatedAt, createdAt) < ?');
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION; lo borrado lo dice rowCount().
            $statement->execute([$status, $limit]);
            $deleted[$status] = $statement->rowCount();
        }
        return ['completed' => $deleted[self::STATUS_COMPLETED], 'failed' => $deleted[self::STATUS_FAILED]];
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new static )->getModel();
    }
}
