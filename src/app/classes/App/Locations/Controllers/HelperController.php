<?php

/**
 * HelperController.php
 */

namespace App\Locations\Controllers;

use PiecesPHP\Core\BaseController;

/**
 * HelperController.
 *
 * @package     App\Locations\Controllers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class HelperController extends BaseController
{

    /**
     *
     * @var \stdClass|null Usuario logueado
     */
    protected $user = null;
    /**
     *
     * @var ?string Directorio de vistas locales
     */
    public ?string $viewLocalDir = null;
    /**
     *
     * @var ?BaseController Controlador base
     */
    public ?BaseController $viewLocalRender = null;

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

        $this->viewLocalRender = new BaseController(false);
        $this->viewLocalDir = realpath(__DIR__ . '/../Views/');

    }

    /**
     * @param string $view
     * @param array $data
     * @param boolean $mode
     * @param boolean $format
     * @return void
     */
    public function localRender(string $view, array $data = [], bool $mode = true, bool $format = false)
    {
        $this->viewLocalRender->setInstanceViewDir($this->viewLocalDir);
        $this->viewLocalRender->setVariables($data);
        return $this->viewLocalRender->render($view, $data, $mode, $format);
    }

}
