<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use MySpace\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UserDataPackage $currentUser
 * @var UserDataPackage $adminUser
 * @var OrganizationMapper $organizationMapper
 * @var string $action
 */
$currentUserIsAdmin = $currentUser->id == $adminUser->id;
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

                <div class="section organization-data">
                    <div class="avatar">
                        <img src="<?= $organizationMapper->getLogoURL(); ?>" alt="<?= $organizationMapper->currentLangData('name'); ?>">
                    </div>
                    <div class="data">
                        <div class="name"><?= $organizationMapper->currentLangData('name'); ?></div>
                    </div>
                    <?php if($currentUserIsAdmin): ?>
                    <div class="actions">
                        <a class="ui right labeled icon button green" href="<?= MyOrganizationProfileController::routeName('my-organization-profile'); ?>">
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