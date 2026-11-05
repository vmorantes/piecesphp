<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Directory\Controllers\ProfileController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UserDataPackage $currentUser
 * @var string $action
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \MySpace\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
//La cuenta y el perfil público son dos ámbitos, con los mismos nombres e iconos en personas y organizaciones (PO, 2026-10-10).
$secondaryActions = [];
if (UsersController::allowedRoute('form-profile')) {
    $secondaryActions[] = ['text' => __($langGroup, 'Cuenta'), 'url' => UsersController::routeName('form-profile'), 'icon' => 'user circle'];
}
if (ProfileController::allowedRoute('profile', ['userID' => $currentUser->id])) {
    $secondaryActions[] = ['text' => __($langGroup, 'Ver mi perfil'), 'url' => ProfileController::routeName('profile', ['userID' => $currentUser->id]), 'icon' => 'eye'];
}
$affiliatedInstitutions = $currentUser->profile->affiliatedInstitutions;
$affiliatedInstitutions = $affiliatedInstitutions !== null ? $affiliatedInstitutions : [];
$affiliatedInstitutionsOptions = [
    '' => __($langGroup, 'Añada las instituciones'),
];
//Valor y texto los escribe el usuario; la selección se compara contra el valor ya escapado.
$affiliatedInstitutions = array_map('escape_html', $affiliatedInstitutions);
foreach ($affiliatedInstitutions as $affiliatedInstitution) {
    $affiliatedInstitutionsOptions[$affiliatedInstitution] = $affiliatedInstitution;
}
$langGroupDatatables = 'datatables';
$currentPhoneCode = $currentUser->profile->currentLangData('phoneCode');
$currentPhoneCode = is_string($currentPhoneCode) ? $currentPhoneCode : null;
$currentNationality = $currentUser->profile->currentLangData('nationality');
$currentNationality = is_string($currentNationality) ? $currentNationality : null;
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description,
                'secondaryActions' => $secondaryActions,
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs">
            <div class="active item" data-tab="personalData"><?= __($langGroup, 'Datos personales'); ?></div>
            <div class="item" data-tab="professionalData"><?= __($langGroup, 'Datos profesionales'); ?></div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form initial segment my-profile" data-content-form>

            <div class="ui tab active personal-data-form" data-tab="personalData">

                    <div class="ui items identity-profile-card">
                        <div class="item">
                            <div class="ui small circular image">
                                <img src="<?= $currentUser->getAvatarURL(); ?>" alt="<?= escape_html($currentUser->getMapper()->getFullName()); ?>">
                            </div>
                            <div class="middle aligned content">
                                <div class="ui header name"><span class="ui breaking text"><?= escape_html($currentUser->getMapper()->getFullName()); ?></span></div>
                                <div class="meta email"><?= escape_html($currentUser->getMapper()->email); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="inputs-personal-data">

                        <?php $helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Información complementaria')]); ?>

                        <div class="two fields">
                            <div class="field required">
                                <label><?= __($langGroup, 'Cargo'); ?></label>
                                <input type="text" required name="jobPosition" value="<?= escape_html($currentUser->profile->currentLangData('jobPosition')); ?>" placeholder=" ">
                            </div>
                            <div class="field">
                                <label><?= __($langGroup, 'Teléfono'); ?></label>
                                <div class="fields">
                                    <div class="six wide field">
                                        <select name="phoneCode" class="ui dropdown auto"><?= array_to_html_options(\PiecesPHP\App\Locations\Mappers\CountryMapper::phoneCodeOptions($currentPhoneCode, true), $currentPhoneCode !== null ? escape_html($currentPhoneCode) : null); ?></select>
                                    </div>
                                    <div class="ten wide field">
                                        <input type="tel" name="phoneNumber" value="<?= escape_html($currentUser->profile->currentLangData('phoneNumber')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field required">
                                <label><?= __($langGroup, 'Nacionalidad'); ?></label>
                                <select name="nationality" class="ui dropdown auto" required><?= array_to_html_options(\PiecesPHP\App\Locations\Mappers\CountryMapper::demonymOptions($currentNationality, true), $currentNationality !== null ? escape_html($currentNationality) : null); ?></select>
                            </div>
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace LinkedIn'); ?></label>
                                <input type="url" name="linkedinLink" value="<?= escape_html($currentUser->profile->currentLangData('linkedinLink')); ?>">
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace página web'); ?></label>
                                <input type="url" name="websiteLink" value="<?= escape_html($currentUser->profile->currentLangData('websiteLink')); ?>">
                            </div>
                        </div>
                        <div class="ui section divider"></div>

                        <div class="ui stackable two column grid">
                            <div class="row">
                                <div class="column">
                                    <?php $helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Ubicación'), 'description' => __($langGroup, 'Se requiere la ubicación para la búsqueda en la vista cartográfica')]); ?>
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
                                    <?php $helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Ubique el marcador en la ubicación')]); ?>
                                    <div class="field">
                                        <div id="map"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

            </div>

            <div class="ui tab" data-tab="professionalData">

                    <div class="field">
                        <label><?= __($langGroup, 'Instituciones a las que pertenece actualmente'); ?></label>
                        <select name="affiliatedInstitutions[]" multiple class="ui dropdown multiple auto additions search"><?= array_to_html_options($affiliatedInstitutionsOptions, $affiliatedInstitutions, true); ?></select>
                    </div>
                    <div class="field">
                        <?php //P136: el oculto manda «0» cuando la casilla no va marcada; marcada, gana el «1» que va detrás. ?>
                        <input type="hidden" name="publicVisible" value="0">
                        <div class="ui checkbox">
                            <input type="checkbox" name="publicVisible" value="1" id="my-profile-public-visible"<?= (int) ($currentUser->profile->publicVisible ?? 1) === 1 ? ' checked' : ''; ?>>
                            <label for="my-profile-public-visible"><?= __($langGroup, 'Mostrar mi perfil en el directorio y en el mapa'); ?></label>
                        </div>
                    </div>

            </div>

            <?php
                //Una barra para las dos pestañas: guarda el formulario entero. Su JS busca [save] y [cancel].
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'saveAttributes' => ['save' => ''],
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'type' => 'submit', 'icon' => 'times', 'attributes' => ['cancel' => '']]],
                ]);
            ?>

        </form>

    </div>

</section>
