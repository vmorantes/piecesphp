<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use SystemApprovals\SystemApprovalsLang;

/**
 * @var UsersModel $mapper
 */;
 
$canModifyAll = OrganizationMapper::canModifyAnyOrganization(getLoggedFrameworkUserOrFail()->type);
$organizationMapper = $mapper->organization !== null ? OrganizationMapper::objectToMapper(OrganizationMapper::getBy($mapper->organization, 'id')) : null;
$getExcerpt = function(string $str, int $maxLength = 300){
    $strLength = mb_strlen($str);
    return $strLength <= $maxLength ? $str : substr($str, 0, ($maxLength >= 6 ? $maxLength - 3 : $maxLength)) . '...';
};
$isActive = $mapper->status == UsersModel::STATUS_USER_ACTIVE;
$statusText = UsersModel::statuses()[$mapper->status];
$statusClass = "status-{$mapper->status}-number";
$userTypeText = UsersModel::getTypeUserName($mapper->type);
$isBaseOrganization = $organizationMapper !== null && $organizationMapper->id == OrganizationMapper::INITIAL_ID_GLOBAL;
if($mapper->type == UsersModel::TYPE_USER_GENERAL){
    if($isBaseOrganization && ORGANIZATIONS_MODULE){
        $userTypeText = __(SystemApprovalsLang::LANG_GROUP, 'Usuario independiente');
    }
}
?>

<div class="ui card user">

    <div class="content">

        <div class="head">

            <div class="user-info <?= $statusClass; ?>" data-tooltip="<?= $statusText; ?>">
                <div class="image">
                    <?php if(AvatarModel::getAvatar($mapper->id)): ?>
                    <img src="<?= AvatarModel::getAvatar($mapper->id) ?>">
                    <?php else: ?>
                    <div class="defauld">
                        <i class="user outline icon"></i>
                    </div>
                    <?php endif; ?>
                    <div class="status"></div>
                </div>
                <div class="info">
                    <span><?= escape_html(($getExcerpt)($mapper->getFullName(), 30)); ?></span>
                </div>
            </div>

            <?php //Pieza compartida: sin permiso de edición, routeName() da '' y el enlace no se pinta. ?>
            <?php $editURL = \PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-edit', ['id' => $mapper->id]); ?>
            <?php if ($editURL !== ''): ?>
            <a href="<?= $editURL; ?>">
                <i class="ellipsis vertical icon"></i>
            </a>
            <?php endif; ?>

        </div>

        <div class="body">
            <div class="item">
                <img src="<?= base_url('statics/images/dashboard/user_type.svg') ?>">
                <span><?= $userTypeText; ?></span>
            </div>
            <div class="item">
                <img src="<?= base_url('statics/images/dashboard/user.svg') ?>">
                <span data-tooltip="<?= escape_html($mapper->username); ?>"><?= escape_html(($getExcerpt)($mapper->username, 30)); ?></span>
            </div>
            <div class="item">
                <img src="<?= base_url('statics/images/dashboard/email.svg') ?>">
                <span data-tooltip="<?= escape_html($mapper->email); ?>"><?= escape_html(($getExcerpt)($mapper->email, 30)); ?></span>
            </div>
            <?php if($canModifyAll && $organizationMapper !== null && ORGANIZATIONS_MODULE): ?>
            <div class="item">
                <img src="<?= base_url('statics/images/dashboard/user_organization.svg') ?>">
                <span data-tooltip="<?= escape_html($organizationMapper->currentLangData('name')); ?>"><?= escape_html(($getExcerpt)($organizationMapper->currentLangData('name'), 30)); ?></span>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>
