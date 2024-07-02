<?php

/**
 * APIRoutes.php
 */

namespace API;

use API\Adapters\BlobStorageAzureAdapter;
use API\Controllers\APIController;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;

/**
 * APIRoutes.
 *
 * @package     API
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2023
 */
class APIRoutes
{

    /**
     * @var boolean
     */
    private static $init = false;

    const ENABLE = API_MODULE;

    /**
     * @param RouteGroup $groupAdministration
     * @return RouteGroup[] Con los índices groupAdministration
     */
    public static function routes(RouteGroup $groupAdministration)
    {
        if (self::ENABLE) {

            $groupAdministration = APIController::routes($groupAdministration);

            APILang::injectLang();

            \PiecesPHP\Core\Routing\InvocationStrategy::appendBeforeCallMethod(function () {
                self::init();
            });

        }

        //Inicializaciones independientes de la API
        BlobStorageAzureAdapter::init();

        return [
            'groupAdministration' => $groupAdministration,
        ];
    }

    /**
     * @return void|null
     */
    public static function init()
    {

        if (!self::$init) {

            $currentUser = getLoggedFrameworkUser();

            if ($currentUser === null) {
                return null;
            }

            $currentUserType = (int) $currentUser->type;

        }

        self::$init = true;

    }

}
