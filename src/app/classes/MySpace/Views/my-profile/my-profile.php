<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use MySpace\Controllers\ProfileController;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UserDataPackage $currentUser
 * @var string $action
 */
$affiliatedInstitutions = $currentUser->profile->affiliatedInstitutions;
$affiliatedInstitutions = $affiliatedInstitutions !== null ? $affiliatedInstitutions : [];
$affiliatedInstitutionsOptions = [
    '' => __($langGroup, 'Añada las instituciones'),
];
foreach($affiliatedInstitutions as $affiliatedInstitution){
    $affiliatedInstitutionsOptions[$affiliatedInstitution] = $affiliatedInstitution;
}
$interestResearhAreas = $currentUser->profile->interestResearhAreas;
$interestResearhAreas = $interestResearhAreas !== null ? array_map(fn($e) => $e->id, $interestResearhAreas) : [];
$esShort = ucfirst(strtolower(__('langShort', 'es')));
$frShort = ucfirst(strtolower(__('langShort', 'fr')));
$langGroupDatatables = 'datatables';
?>
<section class="module-view-container">

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

        <br>

        <div class="tabs-controls">
            <div class="active" data-tab="personalData"><?= __($langGroup, 'Datos personales'); ?></div>
            <div data-tab="professionalData"><?= __($langGroup, 'Datos profesionales'); ?></div>
            <div class="actions-buttons">
                <a class="ui right labeled icon button blue" href="<?= ProfileController::routeName('profile', ['userID' => $currentUser->id]); ?>">
                    <i class="address card outline icon"></i><?= __($langGroup, 'Ver mi perfil'); ?>
                </a>
            </div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form my-profile">

            <div class="ui tab active personal-data-form" data-tab="personalData">

                <div class="container-standard-form">

                    <div class="identity-profile-card">
                        <div class="avatar">
                            <img src="<?= $currentUser->getAvatarURL(); ?>" alt="<?= $currentUser->getMapper()->getFullName(); ?>">
                        </div>
                        <div class="data">
                            <div class="name"><?= $currentUser->getMapper()->getFullName(); ?></div>
                            <div class="meta email"><?= $currentUser->getMapper()->email; ?></div>
                            <div class="actions">
                                <button class="ui right labeled icon button green" external-trigger-edit-account>
                                    <?= __($langGroup, 'Editar'); ?>
                                    <i class="icon edit"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="inputs-personal-data">

                        <div class="section-fields-divider">
                            <div class="title s20"><?= __($langGroup, 'Información complementaria'); ?></div>
                        </div>

                        <div class="two fields">
                            <div class="field required">
                                <label><?= __($langGroup, 'Cargo'); ?></label>
                                <input type="text" required name="jobPosition" value="<?= $currentUser->profile->currentLangData('jobPosition'); ?>" placeholder=" ">
                            </div>
                            <div class="field">
                                <label><?= __($langGroup, 'Teléfono'); ?></label>
                                <div class="fields">
                                    <div class="two wide field">
                                        <select name="phoneCode" class="ui dropdown auto"><?= array_to_html_options(getPhoneAreas(), $currentUser->profile->currentLangData('phoneCode')); ?></select>
                                    </div>
                                    <div class="fourteen wide field">
                                        <input type="tel" name="phoneNumber" value="<?= $currentUser->profile->currentLangData('phoneNumber'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field required">
                                <label><?= __($langGroup, 'Nacionalidad'); ?></label>
                                <select name="nationality" class="ui dropdown auto" required><?= array_to_html_options(getNationalities(true, null, true), $currentUser->profile->currentLangData('nationality')); ?></select>
                            </div>
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace LinkedIn'); ?></label>
                                <input type="url" name="linkedinLink" value="<?= $currentUser->profile->currentLangData('linkedinLink'); ?>">
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace página web'); ?></label>
                                <input type="url" name="websiteLink" value="<?= $currentUser->profile->currentLangData('websiteLink'); ?>">
                            </div>
                        </div>
                        <div class="horizontal-space"></div>

                        <div class="ui stackable two column grid">
                            <div class="row">
                                <div class="column">
                                    <div class="section-fields-divider">
                                        <div class="title s20">
                                            <?= __($langGroup, 'Ubicación'); ?>
                                        </div>
                                        <div class="description fs16 black">
                                            <?= __($langGroup, 'Se requiere la ubicación para la búsqueda en la vista cartográfica'); ?>
                                        </div>
                                    </div>
                                    <div class="field required">
                                        <label><?= __(LOCATIONS_LANG_GROUP, 'País'); ?></label>
                                        <select required name="country" locations-component-auto-filled-country="<?= $currentUser->profile->currentLangData('country') !== null ? $currentUser->profile->currentLangData('country')->id : null; ?>" class="ui dropdown" with-dropdown></select>
                                    </div>

                                    <div class="field required">
                                        <label><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></label>
                                        <select required name="city" locations-component-auto-filled-city="<?= $currentUser->profile->currentLangData('city') !== null ? $currentUser->profile->currentLangData('city')->id : null; ?>" class="ui dropdown" with-dropdown></select>
                                    </div>
                                    <input latitude-mapbox-handler name='latitude' type='hidden' required value="<?= $currentUser->profile->currentLangData('latitude'); ?>">
                                    <input longitude-mapbox-handler name='longitude' type='hidden' required value="<?= $currentUser->profile->currentLangData('longitude'); ?>">
                                </div>
                                <div class="column">
                                    <div class="section-fields-divider">
                                        <div class="title s20">
                                            <?= __($langGroup, 'Ubique el marcador su la ubicación'); ?>
                                        </div>
                                    </div>
                                    <div class="field">
                                        <div id="map"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <br>

                    <div class="field">
                        <div class="ui right floated buttons">
                            <button type="submit" class="ui button white" cancel><?= __($langGroup, 'Cancelar'); ?></button>
                            <button type="submit" class="ui button brand-color" save><?= __($langGroup, 'Guardar'); ?></button>
                        </div>
                    </div>

                </div>

            </div>

            <div class="ui tab" data-tab="professionalData">

                <div class="container-standard-form">
                    <div class="field required">
                        <label class="section-fields-divider">
                            <div class="title s20"><?= __($langGroup, 'Áreas de investigación de interés'); ?></div>
                        </label>

                        <select name="interestResearhAreas[]" multiple class="ui dropdown multiple auto search" required><?= array_to_html_options(getInteresResearchAreas(), $interestResearhAreas, true); ?></select>
                    </div>
                </div>

                <br>

                <div class="container-standard-form">
                    <div class="field">
                        <label class="section-fields-divider">
                            <div class="title s20"><?= __($langGroup, 'Instituciones a las que pertenece actualmente'); ?></div>
                        </label>

                        <select name="affiliatedInstitutions[]" multiple class="ui dropdown multiple auto additions search"><?= array_to_html_options($affiliatedInstitutionsOptions, $affiliatedInstitutions, true); ?></select>
                    </div>
                </div>

            </div>

        </form>

        <br>


        <br>

        <div data-tab-related="professionalData" class="field global-clearfix" style="display: none;">
            <div class="ui right floated buttons">
                <button type="submit" class="ui button white" external-cancel><?= __($langGroup, 'Cancelar'); ?></button>
                <button type="submit" class="ui button brand-color" external-save><?= __($langGroup, 'Guardar'); ?></button>
            </div>
        </div>

        <br>


    </div>

</section>