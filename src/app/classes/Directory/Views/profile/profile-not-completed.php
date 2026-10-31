<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use MySpace\Controllers\MyProfileController;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UserDataPackage $userOfProfile
 * @var UserDataPackage $currentUser
 * @var string $action
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \Directory\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$currentUserIsSameProfile = $currentUser->id == $userOfProfile->id;
//Editar es del dueño: lleva a Mi perfil.
$secondaryActions = $currentUserIsSameProfile && MyProfileController::allowedRoute('my-profile') ? [
    ['text' => __($langGroup, 'Editar'), 'url' => MyProfileController::routeName('my-profile'), 'icon' => 'edit'],
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
                <div class="section personal-data">
                    <div class="avatar">
                        <img src="<?= $userOfProfile->getAvatarURL(); ?>" alt="<?= escape_html($userOfProfile->getMapper()->getFullName()); ?>">
                    </div>
                    <div class="data">
                        <div class="name"><?= escape_html($userOfProfile->getMapper()->getFullName()); ?></div>
                    </div>
                </div>
            </div>

            <div class="secondary-content"></div>
        </div>

    </div>

</section>
