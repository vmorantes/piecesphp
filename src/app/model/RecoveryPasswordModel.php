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
     * __construct
     *
     * @param integer $id
     * @return static
     */
    public function __construct(int $id = null)
    {
        parent::__construct($id);
    }

    /**
     * exist
     *
     * @param mixed $code
     * @return bool
     */
    public static function exist($code)
    {
        $instance = new static();

        return $instance->getModel()
            ->select()
            ->where([
                'code' => $code,
            ])
            ->row() !== -1;
    }

    /**
     * instanceByCode
     *
     * @param mixed $code
     * @return static|null
     */
    public static function instanceByCode($code)
    {
        $instance = new static();

        $data = $instance->getModel()->select()->where([
            'code' => $code,
        ])->row();

        return new static($data->id);
    }
}
