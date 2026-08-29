<?php
namespace SystemApprovals\Util;

use SystemApprovals\Util\Packages\OrganizationApprovalHandler;
use SystemApprovals\Util\Packages\PublicationsApprovalHandler;
use SystemApprovals\Util\Packages\UsersApprovalHandler;

$configurations = [
    OrganizationApprovalHandler::class,
    UsersApprovalHandler::class,
    PublicationsApprovalHandler::class,
];

return $configurations;
