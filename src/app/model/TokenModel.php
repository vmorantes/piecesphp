<?php
/**
 * TokenModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseModel;

/**
 * TokenModel.
 * 
 * Controlador de Tokens.
 * 
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class TokenModel extends BaseModel
{
    /** @ignore */
    function __construct()
    {
        parent::__construct();
    }

    /** @ignore */
    protected $prefix_table = 'pcsphp_';
    protected $table = 'tokens';
}
