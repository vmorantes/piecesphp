<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Locations\LocationsLang;
use MySpace\Controllers\MyProfileController;
use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
use PiecesPHP\UserSystem\Profile\SubMappers\PreviousExperiencesMapper;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UserDataPackage $userOfProfile
 * @var UserDataPackage $currentUser
 * @var string $action
 */
$affiliatedInstitutions = $userOfProfile->profile->affiliatedInstitutions;
$affiliatedInstitutions = $affiliatedInstitutions !== null ? $affiliatedInstitutions : [];
$interestResearhAreas = $userOfProfile->profile->interestResearhAreas;
/**
 * @var PreviousExperiencesMapper[]
 */
$previousExperiences = PreviousExperiencesMapper::allBy('profile', $userOfProfile->profile->id);
$currentUserIsSameProfile = $currentUser->id == $userOfProfile->id;

$contactInformation = [
    [
        'text' => $userOfProfile->profile->getPhone(),
        'icon' => '<i class="phone alternate icon"></i>',
        'parse' => function(string $value, string $icon) {
            $originalValue = $value;
            $value = str_replace([' ', '(', ')'], '', $value);
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$originalValue}</div>";
            return "<a href='tel:{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
        },
    ],
    [
        'text' => $userOfProfile->email,
        'icon' => '<i class="envelope outline icon"></i>',
        'parse' => function(string $value, string $icon) {
            $originalValue = $value;
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$originalValue}</div>";
            return "<a href='mailto:{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
        },
    ],
    [
        'text' => $userOfProfile->profile->getWebsiteLink(),
        'icon' => '<i class="globe icon"></i>',
        'parse' => function(string $value, string $icon) {
            $originalValue = $value;
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$originalValue}</div>";
            return "<a href='{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
        },
    ],
    [
        'text' => $userOfProfile->profile->getLinkedinLink(),
        'icon' => '<i class="linkedin in icon"></i>',
        'parse' => function(string $value, string $icon) {
            $originalValue = $value;
            $icon = "<div class='icon'>{$icon}</div>";
            $text = "<div class='text'>{$originalValue}</div>";
            return "<a href='{$value}' target='_blank' class='item'>{$icon} {$text}</a>";
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
                        <img src="<?= $userOfProfile->getAvatarURL(); ?>" alt="<?= $userOfProfile->getMapper()->getFullName(); ?>">
                    </div>
                    <div class="data">
                        <div class="name"><?= $userOfProfile->getMapper()->getFullName(); ?></div>
                        <div class="meta location">
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->country->name); ?>,
                            <?= __(LocationsLang::LANG_GROUP_NAMES, $userOfProfile->profile->city->name); ?>
                            |
                            <?= $userOfProfile->profile->currentLangData('jobPosition'); ?>
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
                        <div class="tag"><?= $institution; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($interestResearhAreas)): ?>
                <div class="section interest-research-areas mobile">
                    <div class="title"><?= __($langGroup, 'Áreas de investigación de interés'); ?></div>
                    <div class="container-tags">
                        <?php foreach($interestResearhAreas as $researchArea): ?>
                        <div class="tag-special" style="--tag-color: <?= $researchArea->color; ?>;"><?= $researchArea->currentLangData('areaName'); ?></div>
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

                <?php if(!empty($previousExperiences)): ?>
                <div class="section experiences">
                    <div class="title no-m-b"><?= __($langGroup, 'Experiencias previas'); ?></div>
                    <?php foreach($previousExperiences as $experienceRecord): ?>
                    <?php
                        $experience = new PreviousExperiencesMapper($experienceRecord->id);
                        $researchAreasExperience = array_map(fn($e) => (new InterestResearchAreasMapper($e)), $experience->researchAreas);
                        $institutionsExperience = array_map(fn($e) => is_string($e) ? trim($e) : null, $experience->institutionsParticipated);
                        $institutionsExperience = array_filter(
                            $experience->institutionsParticipated, 
                            fn($e) => is_string($e) && mb_strlen($e) > 0
                        );
                    ?>
                    <div class="experience-row">

                        <div class="topbar">
                            <div class="experience-type">
                                <div class="icon <?= $experience->experienceTypeIcon(); ?>" data-tooltip="<?= $experience->experienceTypeDisplayText(); ?>">
                                    <i class="icon <?= $experience->experienceTypeIcon(); ?>"></i>
                                </div>
                            </div>
                            <div class="data">
                                <div class="experience-title"><?= $experience->currentLangData('experienceName'); ?></div>
                                <div class="meta">
                                    <div class="item">
                                        <div class="icon">
                                            <i class="calendar alternate outline icon"></i>
                                            <div class="text"><?= __($langGroup, 'Inició'); ?></div>
                                        </div>
                                        <div class="data"><?= $experience->startDateFormat(); ?></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon">
                                            <i class="calendar alternate outline icon"></i>
                                            <div class="text"><?= __($langGroup, 'Finalizó'); ?></div>
                                        </div>
                                        <div class="data"><?= $experience->endDateFormat(); ?></div>
                                    </div>
                                    <div class="item">
                                        <div class="icon">
                                            <i class="map outline icon"></i>
                                        </div>
                                        <div class="data big">
                                            <?= __(LocationsLang::LANG_GROUP_NAMES, $experience->country->name); ?>,
                                            <?= __(LocationsLang::LANG_GROUP_NAMES, $experience->city->name); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="description"><?= $experience->currentLangData('description'); ?></div>

                        <?php if(!empty($institutionsExperience)): ?>
                        <div class="institutions">
                            <?= implode(', ', $institutionsExperience); ?>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($researchAreasExperience)): ?>
                        <div class="research-areas-title"><?= __($langGroup, 'Áreas de investigación'); ?></div>
                        <div class="research-areas">
                            <?php foreach($researchAreasExperience as $researchAreaExperience): ?>
                            <div class="area" style="--area-color: <?= $researchAreaExperience->color; ?>;"><?= $researchAreaExperience->currentLangData('areaName'); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

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

                <?php if(!empty($interestResearhAreas)): ?>
                <div class="section interest-research-areas">
                    <div class="title small-m-b"><?= __($langGroup, 'Áreas de investigación de interés'); ?></div>
                    <div class="container-tags">
                        <?php foreach($interestResearhAreas as $researchArea): ?>
                        <div class="tag-special" style="--tag-color: <?= $researchArea->color; ?>;"><?= $researchArea->currentLangData('areaName'); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

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