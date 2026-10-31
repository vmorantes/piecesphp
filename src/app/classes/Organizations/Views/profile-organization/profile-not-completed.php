<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var \PiecesPHP\UserSystem\UserDataPackage $currentUser
 * @var UserDataPackage|null $adminUser
 * @var OrganizationMapper $organizationMapper
 * @var string $action
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \Organizations\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$currentUserIsAdmin = $adminUser !== null && $currentUser->id == $adminUser->id;
//Editar es de su encargado y de root: lleva a Mi organización, con la organización en la URL si puede editar cualquiera.
$editParams = MyOrganizationProfileController::hasSuperPrivileges($currentUser) ? ['organizationID' => $organizationMapper->id] : [];
$secondaryActions = ($currentUserIsAdmin || (int) $currentUser->type === UsersModel::TYPE_USER_ROOT) && MyOrganizationProfileController::allowedRoute('my-organization-profile', $editParams) ? [
    ['text' => __($langGroup, 'Editar'), 'url' => MyOrganizationProfileController::routeName('my-organization-profile', $editParams), 'icon' => 'edit'],
] : [];
?>
<section class="module-view-container profile-detail">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
                'secondaryActions' => $secondaryActions,
            ]);
        ?>

        <div class="profile-content">

            <div class="main-content">

                <div class="section organization-data">
                    <div class="avatar">
                        <img src="<?= $organizationMapper->getLogoURL(); ?>" alt="<?= escape_html($organizationMapper->currentLangData('name')); ?>">
                    </div>
                    <div class="data">
                        <div class="name"><?= escape_html($organizationMapper->currentLangData('name')); ?></div>
                    </div>
                </div>
            </div>

            <div class="secondary-content"></div>
        </div>

    </div>

</section>
