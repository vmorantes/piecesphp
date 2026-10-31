<?php
/**
 * UserProblemsModel.php
 */
namespace PiecesPHP\UserSystem\ORM;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;

/**
 * UserProblemsModel.
 *
 * Modelo de problemas de con el usuario
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @property int|null $id
 * @property string $email
 * @property string $code
 * @property \DateTime|string $created
 * @property \DateTime|string $expired
 * @property string $type
 */
class UserProblemsModel extends BaseEntityMapper
{
    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'email' => [
            'type' => 'varchar',
        ],
        'code' => [
            'type' => 'varchar',
        ],
        'created' => [
            'type' => 'datetime',
        ],
        'expired' => [
            'type' => 'datetime',
        ],
        'type' => [
            'type' => 'varchar',
        ],
    ];
    protected $table = 'pcsphp_user_problems';

    /**
     * @param integer $id
     * @return static
     */
    public function __construct(?int $id = null)
    {
        parent::__construct($id);
    }

    /**
     * @param mixed $code
     * @return bool
     */
    public static function exist($code)
    {
        $instance = new UserProblemsModel();

        return $instance->getModel()
            ->select()
            ->where([
                'code' => $code,
            ])
            ->row() !== -1;
    }

    /**
     * @param mixed $code
     * @return UserProblemsModel|null
     */
    public static function instanceByCode($code)
    {
        $instance = new UserProblemsModel();

        $data = $instance->getModel()->select()->where([
            'code' => $code,
        ])->row();

        return is_object($data) ? new UserProblemsModel($data->id) : null;
    }

    /**
     * Cuántos códigos se crearon para ese correo desde esa fecha, de cualquier tipo.
     *
     * @param string $email
     * @param \DateTime $since
     * @return int
     */
    public static function countSince(string $email, \DateTime $since): int
    {
        $instance = new UserProblemsModel();
        $data = $instance->getModel()->select(['COUNT(id) AS total'])->where(new WhereSegment([
            WhereItem::isEqual('email', $email, WhereItem::AND_OPERATOR),
            WhereItem::isGreaterThanOrEqualTo('created', $since->format('Y-m-d H:i:s')),
        ]))->row();

        return is_object($data) ? (int) (((array) $data)['total'] ?? 0) : 0;
    }
}
