<?php

/**
 * TimerController.php
 */

namespace App\Controller;

use App\Model\TimeOnPlatformModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use \PiecesPHP\Core\Routing\RequestRoutePiecesPHP as Request;
use \PiecesPHP\Core\Routing\ResponseRoutePiecesPHP as Response;

/**
 * TimerController.
 *
 * Controlador de tiempo en plataforma
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class TimerController extends \PiecesPHP\Core\BaseController
{
    /** @ignore */
    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function timeOnPlatform(Request $request, Response $response)
    {
        $seconds = $request->getParsedBodyParam('seconds', null);
        $user_id = $request->getParsedBodyParam('user_id', null);
        $valid_user = ctype_digit($user_id) && !is_null((new UsersModel($user_id))->id);
        $valid_time = is_numeric($seconds);

        $response_json = [
            'success' => true,
        ];

        if ($valid_time && $valid_user) {
            $seconds = (double) $seconds;
            $response_json['success'] = TimeOnPlatformModel::addTime($user_id, $seconds / 60);
            return $response->withJson($response_json);
        } else {

            $response_json['success'] = false;
            return $response->withJson($response_json);
        }
    }

    /**
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $groupSegmentURL = $group->getGroupSegment();
        $lastIsBar = last_char($groupSegmentURL) == '/';
        $startRoute = $lastIsBar ? '' : '/';
        $classname = self::class;
        /**
         * @var array<string>
         */
        $all_roles = array_keys(UsersModel::TYPES_USERS);

        //──── GET ─────────────────────────────────────────────────────────────────────────

        //──── POST ─────────────────────────────────────────────────────────────────────────

        $group->register([
            new Route(
                "{$startRoute}add[/]",
                $classname . ':timeOnPlatform',
                'timing-add',
                'POST',
                true,
                null,
                $all_roles
            ),
        ]);

        return $group;
    }
}
