<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Locations\LocationsLang;
use ContentNavigationHub\ContentNavigationHubLang;
use MySpace\Controllers\OrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
/**
 * @var OrganizationMapper $mapper
 */
$avatar = $mapper->getLogoURL();
$location = [
    $mapper->city !== null ? __(LocationsLang::LANG_GROUP_NAMES, $mapper->city->name) : null,
    $mapper->country !== null ? __(LocationsLang::LANG_GROUP_NAMES, $mapper->country->name) : null,
];
$location = array_filter($location, fn($e) => $e !== null);
$location = !empty($location) ? trim(implode(', ', $location)) : '';
$activitySector = $mapper->currentLangData('activitySector');
$activitySector = $activitySector !== null ? $activitySector : '';
?>
<div class='custom-card profile-org'>
    <div class="avatar">
        <img src='<?= $avatar; ?>'>
    </div>
    <div class="content">
        <div class="title">
            <?= $mapper->currentLangData('name'); ?>
        </div>
        <?php if(mb_strlen($activitySector) > 0): ?>
        <div class="subtitle"><?= $activitySector; ?></div>
        <?php endif; ?>
        <div class="meta">
            <?php if(mb_strlen($location) > 0): ?>
            <div class="item"><?= $location; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="actions">
        <a class="action" target="_blank" href="<?= OrganizationProfileController::routeName('profile', ['organizationID' => $mapper->id]); ?>">
            <i class="icon plus"></i>
            <?= __(ContentNavigationHubLang::LANG_GROUP, 'Ver perfil'); ?>
        </a>
    </div>
</div>