<?php

/**
 * SystemApprovalsMiddleware.php
 */

namespace SystemApprovals;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use Psr\Http\Server\RequestHandlerInterface;
use SystemApprovals\Util\Packages\UsersApprovalHandler;
use SystemApprovals\Util\SystemApprovalManager;

/**
 * SystemApprovalsMiddleware.
 *
 * @package     SystemApprovals
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class SystemApprovalsMiddleware
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @param RequestHandlerInterface|null $handler
     * @return Response|null
     */
    public static function handle(Request $request, Response $response, array $args, ?RequestHandlerInterface $handler = null): ?Response
    {

        $currentUser = getLoggedFrameworkUser();
        if ($currentUser !== null && $currentUser->type !== UsersModel::TYPE_USER_ROOT) {

            $allRolesConfig = Roles::getRoles();
            $currentType = $currentUser->type;

            foreach ($allRolesConfig as $roleConfigKey => $roleConfig) {
                if ($currentType == $roleConfig['code']) {
                    $isApproved = SystemApprovalManager::getInstance()->isApproved(UsersModel::class, $currentUser->id);
                    $isApproved = $isApproved ? $isApproved : UsersApprovalHandler::isAutoApproval($currentUser->getMapper());
                    if (!$isApproved) {
                        $roleConfig['allowed_routes'] = UsersModel::restrictRoutes($roleConfig['allowed_routes']);
                        $allRolesConfig[$roleConfigKey] = $roleConfig;
                    }
                    break;
                }
            }

            Roles::registerRoles($allRolesConfig, true);

        }
        return null;
    }

    /**
     * Las rutas de su rol que conserva un usuario NO aprobado, además de las generales.
     *
     * @param string $routeName
     * @return bool
     */
    public static function keepsWhenNotApproved(string $routeName): bool
    {
        //La lista vive en UsersModel: el recorte por estado (index.php §8) usa la misma.
        return UsersModel::keepsWhenRestricted($routeName);
    }
}
