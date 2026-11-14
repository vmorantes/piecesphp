<?php

/**
 * HelperController.php
 */

namespace SystemApprovals\Controllers;

use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Config;

/**
 * HelperController.
 *
 * @package     SystemApprovals\Controllers
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
     * @param \stdClass $user Usuario logueado
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

    /**
     * Obtiene los idiomas disponibles para crear en un select
     * @return array
     */
    public static function getLangsForSelect()
    {
        $langs = Config::get_allowed_langs();
        $langsForSelect = [
            '' => __('lang', 'Idioma principal'),
        ];
        foreach ($langs as $lang) {
            $langsForSelect[$lang] = __('lang', $lang);
        }
        return $langsForSelect;
    }

}
