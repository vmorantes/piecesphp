<?php
/**
 * RecoveryPasswordModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;

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
    public function __construct(int $id = null)
    {
        parent::__construct($id);
    }

    /**
     * @param mixed $code
     * @return bool
     */
    public static function exist($code)
    {
        $instance = new RecoveryPasswordModel();

        return $instance->getModel()
            ->select()
            ->where([
                'code' => $code,
            ])
            ->row() !== -1;
    }

    /**
     * @param mixed $code
     * @return static|null
     */
    public static function instanceByCode($code)
    {
        $instance = new RecoveryPasswordModel();

        $data = $instance->getModel()->select()->where([
            'code' => $code,
        ])->row();

        return is_object($data) ? new RecoveryPasswordModel($data->id) : null;
    }
}
