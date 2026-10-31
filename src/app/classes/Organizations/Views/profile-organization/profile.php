<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Directory\Controllers\ProfileController;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\LocationsLang;
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
$affiliatedInstitutions = $organizationMapper->affiliatedInstitutions;
$affiliatedInstitutions ??= [];
$currentUserIsAdmin = $adminUser !== null && $currentUser->id == $adminUser->id;
//Editar es de su encargado y de root: lleva a Mi organización, con la organización en la URL si puede editar cualquiera.
$editParams = MyOrganizationProfileController::hasSuperPrivileges($currentUser) ? ['organizationID' => $organizationMapper->id] : [];
$secondaryActions = ($currentUserIsAdmin || (int) $currentUser->type === UsersModel::TYPE_USER_ROOT) && MyOrganizationProfileController::allowedRoute('my-organization-profile', $editParams) ? [
    ['text' => __($langGroup, 'Editar'), 'url' => MyOrganizationProfileController::routeName('my-organization-profile', $editParams), 'icon' => 'edit'],
] : [];
$phone = trim(($organizationMapper->phoneCode ?? '') . ' ' . ($organizationMapper->phone ?? ''));

$contactInformation = [
    [
        'text' => $phone,
        'icon' => '<i class="phone alternate icon"></i>',
        'parse' => function (string $value, string $icon) {
            $originalValue = escape_html($value);
            $value = escape_html(str_replace([' ', '(', ')'], '', $value));
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$originalValue}</div>";
            return "<a href='tel:{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
        },
    ],
    [
        'text' => $organizationMapper->informativeEmail,
        'icon' => '<i class="envelope outline icon"></i>',
        'parse' => function (string $value, string $icon) {
            $value = escape_html($value);
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$value}</div>";
            return "<a href='mailto:{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
        },
    ],
    [
        'text' => $organizationMapper->websiteLink,
        'icon' => '<i class="globe icon"></i>',
        'parse' => function (string $value, string $icon) {
            //El enlace lo escribe el usuario: sin http(s) no hay enlace, y así javascript: no llega al href.
            $isWebLink = preg_match('/^https?:\/\//i', $value) === 1;
            $value = escape_html($value);
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$value}</div>";
            return $isWebLink ? "<a href='{$value}' target='_blank' class='item'>{$icon} {$text}</a>" : "<div class='item'>{$icon} {$text}</div>";
        },
    ],
    [
        'text' => $organizationMapper->linkedinLink,
        'icon' => '<i class="linkedin in icon"></i>',
        'parse' => function (string $value, string $icon) {
            //El enlace lo escribe el usuario: sin http(s) no hay enlace, y así javascript: no llega al href.
            $isWebLink = preg_match('/^https?:\/\//i', $value) === 1;
            $value = escape_html($value);
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$value}</div>";
            return $isWebLink ? "<a href='{$value}' target='_blank' class='item'>{$icon} {$text}</a>" : "<div class='item'>{$icon} {$text}</div>";
        },
    ],
];
$contactInformation = array_map(fn($e) => (object) $e, $contactInformation);
$contactInformation = array_filter($contactInformation, fn($e) => is_string($e->text) && mb_strlen($e->text) > 0);
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

            <input type="hidden" longitude-mapbox-handler value="<?= $organizationMapper->longitude; ?>">
            <input type="hidden" latitude-mapbox-handler value="<?= $organizationMapper->latitude; ?>">

            <div class="main-content">

                <div class="section organization-data">
                    <div class="avatar">
                        <img src="<?= $organizationMapper->getLogoURL(); ?>" alt="<?= escape_html($organizationMapper->currentLangData('name')); ?>">
                    </div>
                    <div class="data">
                        <div class="name"><?= escape_html($organizationMapper->currentLangData('name')); ?></div>
                        <div class="meta location">
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->country->name); ?>,
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->city->name); ?>
                            |
                            <?= escape_html($organizationMapper->currentLangData('activitySector')); ?>
                        </div>
                    </div>
                </div>

                <div class="section contact-data mobile">
                    <div class="title"><?= __($langGroup, 'Contacto'); ?></div>
                    <?php if($adminUser !== null): ?>
                    <div class="person-contact-data">
                        <div class="topbar">
                            <div class="avatar">
                                <img src="<?= $adminUser->getAvatarURL(); ?>" alt="<?= escape_html($adminUser->getMapper()->getFullName()); ?>">
                            </div>
                            <div class="actions">
                                <a class="button-link" href="<?= ProfileController::routeName('profile', ['userID' => $adminUser->id]); ?>">
                                    <i class="icon plus"></i>
                                    <?= __($langGroup, 'Ver perfil'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="data">
                            <div class="name"><?= escape_html($adminUser->getMapper()->getFullName()); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="information-list">
                        <?php foreach($contactInformation as $contactInformationElement): ?>
                        <?= ($contactInformationElement->parse)($contactInformationElement->text, $contactInformationElement->icon); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if(!empty($affiliatedInstitutions)): ?>
                <div class="section institutions">
                    <div class="title"><?= __($langGroup, 'Instituciones a las que pertenece'); ?></div>
                    <div class="container-tags">
                        <?php foreach($affiliatedInstitutions as $institution): ?>
                        <div class="tag"><?= escape_html($institution); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>


                <div class="section location-data mobile">
                    <div class="title no-m-b"><?= __($langGroup, 'Ubicación'); ?></div>
                    <div class="subtitle small-m-b">
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->country->name); ?>,
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->city->name); ?>
                    </div>
                    <div id="map-mobile" class="map-profile-mobile"></div>
                </div>


            </div>

            <div class="secondary-content">

                <div class="section contact-data">
                    <div class="title small-m-b"><?= __($langGroup, 'Contacto'); ?></div>
                    <?php if($adminUser !== null): ?>
                    <div class="person-contact-data">
                        <div class="topbar">
                            <div class="avatar">
                                <img src="<?= $adminUser->getAvatarURL(); ?>" alt="<?= escape_html($adminUser->getMapper()->getFullName()); ?>">
                            </div>
                            <div class="actions">
                                <a class="button-link" href="<?= ProfileController::routeName('profile', ['userID' => $adminUser->id]); ?>">
                                    <i class="icon plus"></i>
                                    <?= __($langGroup, 'Ver perfil'); ?>
                                </a>
                            </div>
                        </div>
                        <div class="data">
                            <div class="name"><?= escape_html($adminUser->getMapper()->getFullName()); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="information-list">
                        <?php foreach($contactInformation as $contactInformationElement): ?>
                        <?= ($contactInformationElement->parse)($contactInformationElement->text, $contactInformationElement->icon); ?>
                        <?php endforeach; ?>
                    </div>
                </div>


                <div class="section location-data">
                    <div class="title no-m-b"><?= __($langGroup, 'Ubicación'); ?></div>
                    <div class="subtitle small-m-b">
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->country->name); ?>,
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->city->name); ?>
                    </div>
                    <div id="map" class="map-profile"></div>
                </div>

            </div>
        </div>

    </div>

</section>
