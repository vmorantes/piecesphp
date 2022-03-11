<?php
/**
 * UserProblemsModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;

/**
 * UserProblemsModel.
 *
 * Modelo de problemas de con el usuario
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @property int $id
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
        $instance = new static();

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
        $instance = new static();

        $data = $instance->getModel()->select()->where([
            'code' => $code,
        ])->row();

        return new static($data->id);
    }
}
