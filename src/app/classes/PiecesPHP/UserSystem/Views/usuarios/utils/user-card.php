<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\SystemApprovalsLang;

/**
 * @var UsersModel $mapper
 */;

$canModifyAll = OrganizationMapper::canModifyAnyOrganization(getLoggedFrameworkUserOrFail()->type);
$organizationMapper = $mapper->organization !== null ? OrganizationMapper::objectToMapper(OrganizationMapper::getBy($mapper->organization, 'id')) : null;
$statusColors = [
    UsersModel::STATUS_USER_ACTIVE => StatusLabel::POSITIVE,
    UsersModel::STATUS_USER_APPROVED_PENDING => StatusLabel::WARNING,
];
$statusText = UsersModel::statuses()[$mapper->status];
//Bloqueado, corto en la tarjeta; la descripción larga, en el title de la etiqueta.
$statusTitle = $statusText;
if ((int) $mapper->status === UsersModel::STATUS_USER_ATTEMPTS_BLOCK) {
    $statusText = __(\PiecesPHP\UserSystem\Controllers\UsersController::LANG_GROUP, 'Bloqueado');
}
$userTypeText = UsersModel::getTypeUserName($mapper->type) ?? __(\PiecesPHP\UserSystem\Controllers\UsersController::LANG_GROUP, 'Tipo desconocido');
$isBaseOrganization = $organizationMapper !== null && $organizationMapper->id == OrganizationMapper::INITIAL_ID_GLOBAL;
if ($mapper->type == UsersModel::TYPE_USER_GENERAL) {
    if ($isBaseOrganization && ORGANIZATIONS_MODULE) {
        $userTypeText = __(SystemApprovalsLang::LANG_GROUP, 'Usuario independiente');
    }
}
$fullName = $mapper->getFullName();
$avatar = AvatarModel::getAvatar($mapper->id);
//Pieza compartida: sin permiso de edición, routeName() da '' y el enlace no se pinta.
$editURL = \PiecesPHP\UserSystem\Controllers\UsersController::routeName('form-edit', ['id' => $mapper->id]);
$editText = __(\PiecesPHP\UserSystem\Controllers\UsersController::LANG_GROUP, 'Editar');
$organizationName = $canModifyAll && $organizationMapper !== null && ORGANIZATIONS_MODULE ? (string) $organizationMapper->currentLangData('name') : '';
?>
<article class="card" aria-label="<?= escape_html($fullName); ?>">
    <div class="ui vertical relaxed flex content">
        <div class="ui nowrap relaxed flex">
            <?php if ($avatar): ?>
            <img class="ui avatar image" src="<?= $avatar; ?>" alt="">
            <?php endif; ?>
            <h2 class="fluid ui small header" title="<?= escape_html($fullName); ?>"><?= escape_html($fullName); ?></h2>
            <?php if ($editURL !== ''): ?>
            <a class="ui icon button" href="<?= $editURL; ?>" aria-label="<?= escape_html($editText . ': ' . $fullName); ?>" data-tooltip="<?= escape_html($editText); ?>" data-position="top center" data-inverted=""><i class="pencil alternate icon" aria-hidden="true"></i></a>
            <?php endif; ?>
        </div>
        <div class="fluid ui double top aligned flex">
            <span title="<?= escape_html($statusTitle); ?>" data-user-status><?= StatusLabel::html($statusText, $statusColors[$mapper->status] ?? StatusLabel::NEGATIVE); ?></span>
            <span class="ui label" title="<?= escape_html($userTypeText); ?>"><?= escape_html($userTypeText); ?></span>
        </div>
        <div class="ui fitted divider"></div>
        <div class="ui vertical tight flex">
            <small class="ui nowrap muted text flex" title="<?= escape_html($mapper->username); ?>"><i class="user icon" aria-hidden="true"></i><span class="fluid ui truncated text"><?= escape_html($mapper->username); ?></span></small>
            <small class="ui nowrap muted text flex" title="<?= escape_html($mapper->email); ?>"><i class="envelope icon" aria-hidden="true"></i><span class="fluid ui truncated text"><?= escape_html($mapper->email); ?></span></small>
            <?php if ($organizationName !== ''): ?>
            <small class="ui nowrap muted text flex" title="<?= escape_html($organizationName); ?>"><i class="building icon" aria-hidden="true"></i><span class="fluid ui truncated text"><?= escape_html($organizationName); ?></span></small>
            <?php endif; ?>
        </div>
    </div>
</article>
