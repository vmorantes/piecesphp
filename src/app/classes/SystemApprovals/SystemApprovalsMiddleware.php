<?php

/**
 * SystemApprovalsMiddleware.php
 */

namespace SystemApprovals;

use App\Model\UsersModel;
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
    public static function handle(Request $request, Response $response, array $args, RequestHandlerInterface $handler = null): ?Response
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
                        $otherAllowedRoutes = array_filter($roleConfig['allowed_routes'], function ($e) {
                            $allowed = [
                                $e == 'configurations-mapbox-key',
                                str_starts_with($e, 'my-profile-admin-'),
                                str_starts_with($e, 'my-organization-profile-admin-'),
                                str_starts_with($e, 'profile-organization-admin-'),
                                str_starts_with($e, 'profile-admin-'),
                                str_starts_with($e, 'user-'),
                                str_starts_with($e, 'my-space-admin-'),
                                str_starts_with($e, 'SAMPLE'),
                            ];
                            foreach ($allowed as $i) {
                                if ($i) {
                                    return $i;
                                }
                            }
                            return false;
                        });
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
}
