<?php
/**
 * RecoveryPasswordModel.php
 */
namespace PiecesPHP\UserSystem\ORM;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;

/**
 * RecoveryPasswordModel.
 *
 * Modelo de recuperación de contraseñas.
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @property int|null $id
 * @property string $email
 * @property string $code
 * @property \DateTime|string $created
 * @property \DateTime|string $expired
 */
class RecoveryPasswordModel extends BaseEntityMapper
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
    ];
    protected $table = 'pcsphp_recovery_password';

    /**
     * @param integer $id
     * @return static
     */
    public function __construct(?int $id = null)
    {
        parent::__construct($id);
    }

    /**
     * El código vigente de ese correo: ligado a su usuario y sin expirar.
     *
     * @param string $email
     * @param string $code
     * @return self|null
     */
    public static function findValid(string $email, string $code): ?self
    {
        $instance = new RecoveryPasswordModel();
        $data = $instance->getModel()->select()->where(new WhereSegment([
            WhereItem::isEqual('email', $email, WhereItem::AND_OPERATOR),
            WhereItem::isEqual('code', $code, WhereItem::AND_OPERATOR),
            WhereItem::isGreaterThan('expired', date('Y-m-d H:i:s')),
        ]))->row();

        return is_object($data) ? new RecoveryPasswordModel((int) $data->id) : null;
    }

    /**
     * Si ese código existe para ese correo, vigente o no: separa «caducado» de «inexistente o ya usado».
     *
     * @param string $email
     * @param string $code
     * @return bool
     */
    public static function existsCode(string $email, string $code): bool
    {
        $instance = new RecoveryPasswordModel();
        $data = $instance->getModel()->select()->where(new WhereSegment([
            WhereItem::isEqual('email', $email, WhereItem::AND_OPERATOR),
            WhereItem::isEqual('code', $code),
        ]))->row();

        return is_object($data);
    }

    /**
     * Cuántos códigos se crearon para ese correo desde esa fecha.
     *
     * @param string $email
     * @param \DateTime $since
     * @return int
     */
    public static function countSince(string $email, \DateTime $since): int
    {
        $instance = new RecoveryPasswordModel();
        $data = $instance->getModel()->select(['COUNT(id) AS total'])->where(new WhereSegment([
            WhereItem::isEqual('email', $email, WhereItem::AND_OPERATOR),
            WhereItem::isGreaterThanOrEqualTo('created', $since->format('Y-m-d H:i:s')),
        ]))->row();

        return is_object($data) ? (int) (((array) $data)['total'] ?? 0) : 0;
    }

    /**
     * Borra todos los códigos de ese correo.
     *
     * @param string $email
     * @return bool
     */
    public static function deleteByEmail(string $email): bool
    {
        $instance = new RecoveryPasswordModel();

        return $instance->getModel()->delete(new WhereSegment([
            WhereItem::isEqual('email', $email),
        ]))->execute() === true;
    }
}
