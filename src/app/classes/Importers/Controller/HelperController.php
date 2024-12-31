<?php

/**
 * HelperController.php
 */

namespace Importers\Controller;

use PiecesPHP\Core\BaseController;

/**
 * HelperController.
 *
 * @package     Importers\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class HelperController extends BaseController
{

    /**
     *
     * @var \stdClass|null Usuario logueado
     */
    protected $user = null;

    /**
     * @param \stdClass $user Usuario logueado
     * @param array $globalVariables
     */
    public function __construct($user = null, array $globalVariables = [])
    {
        set_config('lock_assets', true);
        parent::__construct(false);
        $this->user = $user instanceof \stdClass  ? $user : null;
        $this->setVariables($globalVariables);
        set_config('lock_assets', false);
    }

}
