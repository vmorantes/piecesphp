<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\App\Locations\LocationsLang;
use MySpace\Controllers\MyProfileController;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UserDataPackage $userOfProfile
 * @var UserDataPackage $currentUser
 * @var string $action
 */
$affiliatedInstitutions = $userOfProfile->profile->affiliatedInstitutions;
$affiliatedInstitutions ??= [];
$currentUserIsSameProfile = $currentUser->id == $userOfProfile->id;

$contactInformation = [
    [
        'text' => $userOfProfile->profile->getPhone(),
        'icon' => '<i class="phone alternate icon"></i>',
        'parse' => function(string $value, string $icon) {
            $originalValue = escape_html($value);
            $value = escape_html(str_replace([' ', '(', ')'], '', $value));
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$originalValue}</div>";
            return "<a href='tel:{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
        },
    ],
    [
        'text' => $userOfProfile->email,
        'icon' => '<i class="envelope outline icon"></i>',
        'parse' => function(string $value, string $icon) {
            $value = escape_html($value);
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$value}</div>";
            return "<a href='mailto:{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
        },
    ],
    [
        'text' => $userOfProfile->profile->getWebsiteLink(),
        'icon' => '<i class="globe icon"></i>',
        'parse' => function(string $value, string $icon) {
            //El enlace lo escribe el usuario: sin http(s) no hay enlace, y así javascript: no llega al href.
            $isWebLink = preg_match('/^https?:\/\//i', $value) === 1;
            $value = escape_html($value);
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$value}</div>";
            return $isWebLink ? "<a href='{$value}' target='_blank' class='item'>{$icon} {$text}</a>" : "<div class='item'>{$icon} {$text}</div>";
        },
    ],
    [
        'text' => $userOfProfile->profile->getLinkedinLink(),
        'icon' => '<i class="linkedin in icon"></i>',
        'parse' => function(string $value, string $icon) {
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

            <input type="hidden" longitude-mapbox-handler value="<?= $userOfProfile->profile->longitude; ?>">
            <input type="hidden" latitude-mapbox-handler value="<?= $userOfProfile->profile->latitude; ?>">

            <div class="main-content">

                <div class="section personal-data">
                    <div class="avatar">
                        <img src="<?= $userOfProfile->getAvatarURL(); ?>" alt="<?= escape_html($userOfProfile->getMapper()->getFullName()); ?>">
                    </div>
                    <div class="data">
                        <div class="name"><?= escape_html($userOfProfile->getMapper()->getFullName()); ?></div>
                        <div class="meta location">
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->country->name); ?>,
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->city->name); ?>
                            |
                            <?= escape_html($userOfProfile->profile->currentLangData('jobPosition')); ?>
                        </div>
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

                <div class="section contact-data mobile">
                    <div class="title"><?= __($langGroup, 'Contacto'); ?></div>
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
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->country->name); ?>,
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->city->name); ?>
                    </div>
                    <div id="map-mobile" class="map-profile-mobile"></div>
                </div>


            </div>

            <div class="secondary-content">

                <div class="section contact-data">
                    <div class="title small-m-b"><?= __($langGroup, 'Contacto'); ?></div>
                    <div class="information-list">
                        <?php foreach($contactInformation as $contactInformationElement): ?>
                        <?= ($contactInformationElement->parse)($contactInformationElement->text, $contactInformationElement->icon); ?>
                        <?php endforeach; ?>
                    </div>
                </div>


                <div class="section location-data">
                    <div class="title no-m-b"><?= __($langGroup, 'Ubicación'); ?></div>
                    <div class="subtitle small-m-b">
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->country->name); ?>,
                        <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->city->name); ?>
                    </div>
                    <div id="map" class="map-profile"></div>
                </div>

            </div>
        </div>

    </div>

</section>