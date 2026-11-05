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
            $text = "<div class='content'><span class='ui breaking text'>{$originalValue}</span></div>";
            return "<a href='tel:{$value}' target='_blank' class='item'>{$icon}{$text}</a>";
        },
    ],
    [
        'text' => $organizationMapper->informativeEmail,
        'icon' => '<i class="envelope outline icon"></i>',
        'parse' => function (string $value, string $icon) {
            $value = escape_html($value);
            $text = "<div class='content'><span class='ui breaking text'>{$value}</span></div>";
            return "<a href='mailto:{$value}' target='_blank' class='item'>{$icon}{$text}</a>";
        },
    ],
    [
        'text' => $organizationMapper->websiteLink,
        'icon' => '<i class="globe icon"></i>',
        'parse' => function (string $value, string $icon) {
            //El enlace lo escribe el usuario: sin http(s) no hay enlace, y así javascript: no llega al href.
            $isWebLink = preg_match('/^https?:\/\//i', $value) === 1;
            $value = escape_html($value);
            $text = "<div class='content'><span class='ui breaking text'>{$value}</span></div>";
            return $isWebLink ? "<a href='{$value}' target='_blank' class='item'>{$icon}{$text}</a>" : "<div class='item'>{$icon}{$text}</div>";
        },
    ],
    [
        'text' => $organizationMapper->linkedinLink,
        'icon' => '<i class="linkedin in icon"></i>',
        'parse' => function (string $value, string $icon) {
            //El enlace lo escribe el usuario: sin http(s) no hay enlace, y así javascript: no llega al href.
            $isWebLink = preg_match('/^https?:\/\//i', $value) === 1;
            $value = escape_html($value);
            $text = "<div class='content'><span class='ui breaking text'>{$value}</span></div>";
            return $isWebLink ? "<a href='{$value}' target='_blank' class='item'>{$icon}{$text}</a>" : "<div class='item'>{$icon}{$text}</div>";
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

        <input type="hidden" longitude-mapbox-handler value="<?= $organizationMapper->longitude; ?>">
        <input type="hidden" latitude-mapbox-handler value="<?= $organizationMapper->latitude; ?>">

        <?php //Contacto y ubicación van dos veces (columna lateral en escritorio, bajo la ficha en tableta y móvil): profile-organization.js monta un mapa en cada una. ?>
        <div class="profile-content ui stackable grid">

            <div class="eleven wide computer sixteen wide tablet column main-content">

                <div class="ui segment organization-data">
                    <div class="ui items">
                        <div class="item">
                            <div class="ui small circular image">
                                <img src="<?= $organizationMapper->getLogoURL(); ?>" alt="<?= escape_html($organizationMapper->currentLangData('name')); ?>">
                            </div>
                            <div class="middle aligned content">
                                <h2 class="ui header"><span class="ui breaking text"><?= escape_html($organizationMapper->currentLangData('name')); ?></span></h2>
                                <div class="meta">
                                    <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->country->name); ?>,
                                    <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->city->name); ?>
                                    |
                                    <?= escape_html($organizationMapper->currentLangData('activitySector')); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if(!empty($affiliatedInstitutions)): ?>
                <div class="ui segment institutions">
                    <h3 class="ui header"><?= __($langGroup, 'Instituciones a las que pertenece'); ?></h3>
                    <div class="ui labels container-tags">
                        <?php foreach($affiliatedInstitutions as $institution): ?>
                        <div class="ui label"><?= escape_html($institution); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <div class="five wide computer only column secondary-content">

                <div class="ui segment contact-data">
                    <h3 class="ui header"><?= __($langGroup, 'Contacto'); ?></h3>
                    <?php if($adminUser !== null): ?>
                    <div class="ui unstackable items person-contact-data">
                        <div class="item">
                            <div class="ui tiny circular image">
                                <img src="<?= $adminUser->getAvatarURL(); ?>" alt="<?= escape_html($adminUser->getMapper()->getFullName()); ?>">
                            </div>
                            <div class="middle aligned content">
                                <div class="header"><span class="ui breaking text"><?= escape_html($adminUser->getMapper()->getFullName()); ?></span></div>
                                <div class="extra">
                                    <a class="ui basic small labeled icon button" href="<?= ProfileController::routeName('profile', ['userID' => $adminUser->id]); ?>">
                                        <i class="plus icon"></i>
                                        <?= __($langGroup, 'Ver perfil'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="ui relaxed list information-list">
                        <?php foreach($contactInformation as $contactInformationElement): ?>
                        <?= ($contactInformationElement->parse)($contactInformationElement->text, $contactInformationElement->icon); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ui segment location-data">
                    <h3 class="ui header">
                        <?= __($langGroup, 'Ubicación'); ?>
                        <div class="sub header">
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->country->name); ?>,
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->city->name); ?>
                        </div>
                    </h3>
                    <div id="map" class="map-profile"></div>
                </div>

            </div>

            <div class="sixteen wide tablet mobile only column secondary-content mobile">

                <div class="ui segment contact-data mobile">
                    <h3 class="ui header"><?= __($langGroup, 'Contacto'); ?></h3>
                    <?php if($adminUser !== null): ?>
                    <div class="ui unstackable items person-contact-data">
                        <div class="item">
                            <div class="ui tiny circular image">
                                <img src="<?= $adminUser->getAvatarURL(); ?>" alt="<?= escape_html($adminUser->getMapper()->getFullName()); ?>">
                            </div>
                            <div class="middle aligned content">
                                <div class="header"><span class="ui breaking text"><?= escape_html($adminUser->getMapper()->getFullName()); ?></span></div>
                                <div class="extra">
                                    <a class="ui basic small labeled icon button" href="<?= ProfileController::routeName('profile', ['userID' => $adminUser->id]); ?>">
                                        <i class="plus icon"></i>
                                        <?= __($langGroup, 'Ver perfil'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="ui relaxed list information-list">
                        <?php foreach($contactInformation as $contactInformationElement): ?>
                        <?= ($contactInformationElement->parse)($contactInformationElement->text, $contactInformationElement->icon); ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="ui segment location-data mobile">
                    <h3 class="ui header">
                        <?= __($langGroup, 'Ubicación'); ?>
                        <div class="sub header">
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->country->name); ?>,
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $organizationMapper->city->name); ?>
                        </div>
                    </h3>
                    <div id="map-mobile" class="map-profile-mobile"></div>
                </div>

            </div>

        </div>

    </div>

</section>
