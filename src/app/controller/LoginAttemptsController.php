<?php

/**
 * LoginAttemptsController.php
 */

namespace App\Controller;

use App\Model\LoginAttemptsModel;
use App\Model\UsersModel;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\RouteGroup;
use Slim\Exception\NotFoundException;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * LoginAttemptsController.
 *
 * Controlador de informes de intentos de inicio
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class LoginAttemptsController extends AdminPanelController
{
    /** @ignore */
    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * reportsLogin
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function reportsAccess(Request $request, Response $response, array $args)
    {
        if ($request->isXhr()) {

            $type = $request->getAttribute('type', null);

            if ($type == 'logged') {
                return $response->withJson(LoginAttemptsModel::getLoggedUsers($request)->getValues());
            } elseif ($type == 'not-logged') {
                return $response->withJson(LoginAttemptsModel::getNotLoggedUsers($request)->getValues());
            } elseif ($type == 'attempts') {
                return $response->withJson(LoginAttemptsModel::getAttempts($request)->getValues());
            } else {
                throw new NotFoundException($request, $response);
            }

        } else {
            $this->render('panel/layout/header');
            $this->render('panel/pages/login-reports');
            $this->render('panel/layout/footer');
        }
    }

    /**
     * routes
     *
     * @param RouteGroup $group
     * @return RouteGroup
     */
    public static function routes(RouteGroup $group)
    {
        $group->register([
            new Route(
                '/reports-access[/]',
                self::class . ':reportsAccess',
                'informes-acceso',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ),
            (new Route(
                '/reports-access/{type}[/]',
                self::class . ':reportsAccess',
                'informes-acceso-ajax',
                'GET',
                true,
                null,
                [
                    UsersModel::TYPE_USER_ROOT,
                    UsersModel::TYPE_USER_ADMIN,
                ]
            ))->setParameterValue('type', 'not-logged'),
        ]);

        return $group;
    }

}
