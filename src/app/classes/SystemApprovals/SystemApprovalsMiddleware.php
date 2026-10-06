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

            $rolesBasePermissions = get_config('roles')['baseInitialSegmentedPermissions'];
            $allRolesConfig = Roles::getRoles();
            $currentType = $currentUser->type;

            foreach ($allRolesConfig as $roleConfigKey => $roleConfig) {
                if ($currentType == $roleConfig['code']) {
                    $isApproved = SystemApprovalManager::getInstance()->isApproved(UsersModel::class, $currentUser->id);
                    $isApproved = $isApproved ? $isApproved : UsersApprovalHandler::isAutoApproval($currentUser->getMapper());
                    if (!$isApproved) {
                        $otherAllowedRoutes = array_filter($roleConfig['allowed_routes'], fn($e) => self::keepsWhenNotApproved((string) $e));
                        $roleConfig['allowed_routes'] = array_merge($rolesBasePermissions['generals'], $otherAllowedRoutes);
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
        return $routeName === 'configurations-integrations-mapbox-key'
            || str_starts_with($routeName, 'my-profile-admin-')
            || str_starts_with($routeName, 'my-organization-profile-admin-')
            || str_starts_with($routeName, 'profile-organization-admin-')
            || str_starts_with($routeName, 'profile-admin-')
            //Solo enviar la edición del propio perfil y los ajustes de su cuenta: «users-» entero concedería la gestión de usuarios.
            || $routeName === 'users-edit-request'
            || str_starts_with($routeName, 'user-system-features-')
            || str_starts_with($routeName, 'my-space-admin-')
            || str_starts_with($routeName, 'api-admin-')
            || str_starts_with($routeName, 'SAMPLE');
    }
}
