<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Locations\LocationsLang;
use App\Locations\Mappers\CityMapper;
use App\Locations\Mappers\CountryMapper;
use App\Model\AvatarModel;
use ContentNavigationHub\ContentNavigationHubLang;
use MySpace\Controllers\ProfileController;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
/**
 * @var \stdClass $element
 */
$mapper = UserProfileMapper::objectToMapper($element);
$avatar = AvatarModel::getUserAvatarNameURLOrDefault($mapper->belongsTo);
$location = [
    $mapper->city !== null ? __(LocationsLang::LANG_GROUP_NAMES, (new CityMapper($mapper->city))->name) : null,
    $mapper->country !== null ? __(LocationsLang::LANG_GROUP_NAMES, (new CountryMapper($mapper->country))->name) : null,
];
$location = array_filter($location, fn($e) => $e !== null);
$location = !empty($location) ? trim(implode(', ', $location)) : '';
$jobPosition = $mapper->currentLangData('jobPosition');
$jobPosition = $jobPosition !== null ? $jobPosition : '';
?>
<div class='custom-card profile-user'>
    <div class="avatar">
        <img src='<?= $avatar; ?>'>
    </div>
    <div class="content">
        <div class="title">
            <?= $element->fullname; ?>
        </div>
        <?php if(mb_strlen($jobPosition) > 0): ?>
        <div class="subtitle"><?= $jobPosition; ?></div>
        <?php endif; ?>
        <div class="meta">
            <?php if(mb_strlen($location) > 0): ?>
            <div class="item"><?= $location; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="actions">
        <a class="action" target="_blank" href="<?= ProfileController::routeName('profile', ['userID' => $mapper->belongsTo]); ?>">
            <i class="icon plus"></i>
            <?= __(ContentNavigationHubLang::LANG_GROUP, 'Ver perfil'); ?>
        </a>
    </div>
</div>