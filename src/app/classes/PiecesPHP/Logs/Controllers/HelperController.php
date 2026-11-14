<?php

/**
 * HelperController.php
 */

namespace PiecesPHP\Logs\Controllers;

use PiecesPHP\Core\BaseController;

/**
 * HelperController - Pinta el layout del panel y las vistas del módulo sin vaciar los estáticos ya añadidos.
 *
 * @package     PiecesPHP\Logs\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class HelperController extends BaseController
{
    /**
     * @var \stdClass|null
     */
    protected $user = null;

    /**
     * @param mixed $user
     * @param array $globalVariables
     */
    public function __construct($user = null, array $globalVariables = [])
    {
        $previousLockAssets = get_config('lock_assets');
        set_config('lock_assets', true);
        parent::__construct(false);
        $this->user = $user instanceof \stdClass ? $user : null;
        $this->setVariables($globalVariables);
        set_config('lock_assets', $previousLockAssets);
    }
}
