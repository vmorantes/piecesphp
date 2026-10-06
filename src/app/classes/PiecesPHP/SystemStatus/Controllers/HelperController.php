<?php

/**
 * HelperController.php
 */

namespace PiecesPHP\SystemStatus\Controllers;

use PiecesPHP\Core\BaseController;

/**
 * HelperController - Pinta el layout del panel desde un controlador con su propia carpeta de vistas.
 *
 * @package     PiecesPHP\SystemStatus\Controllers
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
        set_config('lock_assets', true);
        parent::__construct(false);
        $this->user = $user instanceof \stdClass ? $user : null;
        $this->setVariables($globalVariables);
        set_config('lock_assets', false);
    }
}
