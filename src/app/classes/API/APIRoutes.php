<?php

/**
 * APIRoutes.php
 */

namespace API;

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

            $groupAdministration->addMiddleware(function (\PiecesPHP\Core\Routing\RequestRoutePiecesPHP $request, $handler) {
                $response = $handler->handle($request);
                return $response;
            });

            \PiecesPHP\Core\Routing\InvocationStrategyPiecesPHP::appendBeforeCallMethod(function () {
                self::init();
            });

        }

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
