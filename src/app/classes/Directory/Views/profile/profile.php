<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use MySpace\Controllers\MyProfileController;
use PiecesPHP\App\Locations\LocationsLang;
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
$affiliatedInstitutions = $userOfProfile->profile->affiliatedInstitutions;
$affiliatedInstitutions ??= [];
$currentUserIsSameProfile = $currentUser->id == $userOfProfile->id;
//Editar es del dueño: lleva a Mi perfil.
$secondaryActions = $currentUserIsSameProfile && MyProfileController::allowedRoute('my-profile') ? [
    ['text' => __($langGroup, 'Editar'), 'url' => MyProfileController::routeName('my-profile'), 'icon' => 'edit'],
] : [];

$contactInformation = [
    [
        'text' => $userOfProfile->profile->getPhone(),
        'icon' => '<i class="phone alternate icon"></i>',
        'parse' => function (string $value, string $icon) {
            $originalValue = escape_html($value);
            $value = escape_html(str_replace([' ', '(', ')'], '', $value));
            $text = "<div class='content'><span class='ui breaking text'>{$originalValue}</span></div>";
            return "<a href='tel:{$value}' target='_blank' class='item'>{$icon}{$text}</a>";
        },
    ],
    [
        'text' => $userOfProfile->email,
        'icon' => '<i class="envelope outline icon"></i>',
        'parse' => function (string $value, string $icon) {
            $value = escape_html($value);
            $text = "<div class='content'><span class='ui breaking text'>{$value}</span></div>";
            return "<a href='mailto:{$value}' target='_blank' class='item'>{$icon}{$text}</a>";
        },
    ],
    [
        'text' => $userOfProfile->profile->getWebsiteLink(),
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
        'text' => $userOfProfile->profile->getLinkedinLink(),
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

        <input type="hidden" longitude-mapbox-handler value="<?= $userOfProfile->profile->longitude; ?>">
        <input type="hidden" latitude-mapbox-handler value="<?= $userOfProfile->profile->latitude; ?>">

        <?php //Contacto y ubicación van dos veces (columna lateral en escritorio, bajo la ficha en tableta y móvil): profile.js monta un mapa en cada una. ?>
        <div class="profile-content ui stackable grid">

            <div class="eleven wide computer sixteen wide tablet column main-content">

                <div class="ui segment personal-data">
                    <div class="ui items">
                        <div class="item">
                            <div class="ui small circular image">
                                <img src="<?= $userOfProfile->getAvatarURL(); ?>" alt="<?= escape_html($userOfProfile->getMapper()->getFullName()); ?>">
                            </div>
                            <div class="middle aligned content">
                                <h2 class="ui header"><span class="ui breaking text"><?= escape_html($userOfProfile->getMapper()->getFullName()); ?></span></h2>
                                <div class="meta">
                                    <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->country->name); ?>,
                                    <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->city->name); ?>
                                    |
                                    <?= escape_html($userOfProfile->profile->currentLangData('jobPosition')); ?>
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
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->country->name); ?>,
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->city->name); ?>
                        </div>
                    </h3>
                    <div id="map" class="map-profile"></div>
                </div>

            </div>

            <div class="sixteen wide tablet mobile only column secondary-content mobile">

                <div class="ui segment contact-data mobile">
                    <h3 class="ui header"><?= __($langGroup, 'Contacto'); ?></h3>
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
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->country->name); ?>,
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->city->name); ?>
                        </div>
                    </h3>
                    <div id="map-mobile" class="map-profile-mobile"></div>
                </div>

            </div>

        </div>

    </div>

</section>
