<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Locations\LocationsLang;
use MySpace\Controllers\MyProfileController;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UserDataPackage $userOfProfile
 * @var UserDataPackage $currentUser
 * @var string $action
 */
$currentUserIsSameProfile = $currentUser->id == $userOfProfile->id;

?>
<section class="module-view-container profile-detail">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <div class="profile-content">
            <div class="main-content">
                <div class="section personal-data">
                    <div class="avatar">
                        <img src="<?= $userOfProfile->getAvatarURL(); ?>" alt="<?= $userOfProfile->getMapper()->getFullName(); ?>">
                    </div>
                    <div class="data">
                        <div class="name"><?= $userOfProfile->getMapper()->getFullName(); ?></div>
                    </div>
                    <?php if($currentUserIsSameProfile): ?>
                    <div class="actions">
                        <a class="ui right labeled icon button green" href="<?= MyProfileController::routeName('my-profile'); ?>">
                            <?= __($langGroup, 'Editar'); ?>
                            <i class="icon edit"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="secondary-content"></div>
        </div>

    </div>

</section>