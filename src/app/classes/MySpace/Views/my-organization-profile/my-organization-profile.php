<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\UsersModel;
use MySpace\Controllers\OrganizationProfileController;
use MySpace\Controllers\ProfileController;
use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsLang;
use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UsersModel $organizationAdminMapper
 * @var OrganizationMapper $organizationMapper
 * @var string $action
 */

$langGroupOrganizations = OrganizationsLang::LANG_GROUP;
$adminUser = new UserDataPackage($organizationAdminMapper->id);
$affiliatedInstitutions = $organizationMapper->affiliatedInstitutions;
$affiliatedInstitutions = $affiliatedInstitutions !== null ? $affiliatedInstitutions : [];
$affiliatedInstitutionsOptions = [
    '' => __($langGroup, 'Añada las instituciones'),
];
foreach($affiliatedInstitutions as $affiliatedInstitution){
    $affiliatedInstitutionsOptions[$affiliatedInstitution] = $affiliatedInstitution;
}

$langGroupDatatables = 'datatables';

$allowedLangs = Config::get_allowed_langs();
$classNumberFieldByQty = [
    0 => '',
    1 => '',
    2 => 'two',
    3 => 'three',
    4 => 'four',
    5 => 'five',
    6 => 'six',
    7 => 'seven',
    8 => 'eight',
    9 => 'nine',
    10 => 'ten',
];
$classNumberFieldByQty = $classNumberFieldByQty[count($allowedLangs)];

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
            <div class="active" data-tab="generalData"><?= __($langGroup, 'Datos generales'); ?></div>
            <div data-tab="professionalData"><?= __($langGroup, 'Datos profesionales'); ?></div>
            <div class="actions-buttons">
                <a class="ui right labeled icon button blue" href="<?= OrganizationProfileController::routeName('profile', ['organizationID' => $organizationMapper->id]); ?>">
                    <i class="address card outline icon"></i><?= __($langGroup, 'Ver perfil'); ?>
                </a>
            </div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form my-organization-profile">

            <div class="ui tab active general-data-form" data-tab="generalData">

                <div class="container-standard-form">

                    <div class="inputs-general-data">

                        <div class="ui stackable two column grid">
                            <div class="row">
                                <div class="eleven wide column">

                                    <div class="field required">
                                        <label><?= __($langGroupOrganizations, 'Nombre de la organización'); ?></label>
                                        <input required type="text" name="name" maxlength="300" value="<?= $organizationMapper->currentLangData('name'); ?>">
                                    </div>

                                    <div class="<?= $classNumberFieldByQty; ?> fields">
                                        <?php foreach($allowedLangs as $allowedLang): ?>
                                        <?php $shortLang = ucfirst(strtolower(__('langShort', $allowedLang))); ?>
                                        <div class="field required">
                                            <label><?= __($langGroupOrganizations, 'Sector de actividad'); ?> - <?= $shortLang; ?></label>
                                            <input required type="text" name="activitySector[<?= $allowedLang; ?>]" value="<?= $organizationMapper->getLangData($allowedLang, 'activitySector', false); ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="five wide column">
                                    <div class="form-attachments-regular">
                                        <div class="attach-placeholder logo">
                                            <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                                            <div class="ui top right attached label green">
                                                <i class="paperclip icon"></i>
                                            </div>
                                            <label for="<?= $uniqueIdentifier; ?>">
                                                <div data-image="<?= $organizationMapper->currentLangData('logo'); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                                    <i class="icon upload"></i>
                                                    <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                                                </div>
                                                <div class="text">
                                                    <div class="filename"></div>
                                                    <div class="header">
                                                        <div class="title"><?= __($langGroup, 'Logo'); ?></div>
                                                        <div class="meta"><?= __($langGroup, 'Tamaño 400x400'); ?></div>
                                                    </div>
                                                    <div class="description"><?= __($langGroup, 'Imagen preferiblemente en formato .jpg'); ?></div>
                                                </div>
                                            </label>
                                            <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field required">
                                <label><?= __($langGroupOrganizations, 'Correo electrónico'); ?></label>
                                <input type="text" required name="informativeEmail" value="<?= $organizationMapper->currentLangData('informativeEmail'); ?>">
                            </div>
                            <div class="field">
                                <label><?= __($langGroupOrganizations, 'Teléfono'); ?></label>
                                <div class="fields">
                                    <div class="two wide field">
                                        <select name="phoneCode" class="ui dropdown auto"><?= array_to_html_options(getPhoneAreas(), $organizationMapper->phoneCode); ?></select>
                                    </div>
                                    <div class="fourteen wide field">
                                        <input type="tel" name="phone" value="<?= $organizationMapper->currentLangData('phone'); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field">
                                <label><?= __($langGroupOrganizations, 'Enlace LinkedIn'); ?></label>
                                <input type="url" name="linkedinLink" value="<?= $organizationMapper->currentLangData('linkedinLink'); ?>">
                            </div>
                            <div class="field">
                                <label><?= __($langGroupOrganizations, 'Enlace página web'); ?></label>
                                <input type="url" name="websiteLink" value="<?= $organizationMapper->currentLangData('websiteLink'); ?>">
                            </div>
                        </div>

                        <div class="horizontal-space"></div>

                        <div class="section-fields-divider">
                            <div class="title s20"><?= __($langGroup, 'Contacto de la organización'); ?></div>
                        </div>

                        <div class="identity-profile-card">
                            <div class="avatar">
                                <img src="<?= $adminUser->getAvatarURL(); ?>" alt="<?= $adminUser->getMapper()->getFullName(); ?>">
                            </div>
                            <div class="data">
                                <div class="name"><?= $adminUser->getMapper()->getFullName(); ?></div>
                                <div class="meta email"><?= $adminUser->getMapper()->email; ?></div>
                                <div class="actions">
                                    <a class="ui right labeled icon button brand-color" href="<?= ProfileController::routeName('profile', ['userID' => $adminUser->id]); ?>">
                                        <?= __($langGroup, 'Ver perfil'); ?>
                                        <i class="angle right icon"></i>
                                    </a>
                                    <button class="ui button blue" change-organization-admin-trigger>
                                        <?= __($langGroup, 'Cambiar'); ?>
                                    </button>
                                </div>
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
                                        <select required name="country" locations-component-auto-filled-country="<?= $organizationMapper->currentLangData('country') !== null ? $organizationMapper->currentLangData('country')->id : ''; ?>" class="ui dropdown" with-dropdown></select>
                                    </div>

                                    <div class="field required">
                                        <label><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></label>
                                        <select required name="city" locations-component-auto-filled-city="<?= $organizationMapper->currentLangData('city') !== null ? $organizationMapper->currentLangData('city')->id : ''; ?>" class="ui dropdown" with-dropdown></select>
                                    </div>
                                    <input latitude-mapbox-handler name='latitude' type='hidden' required value="<?= $organizationMapper->latitude; ?>">
                                    <input longitude-mapbox-handler name='longitude' type='hidden' required value="<?= $organizationMapper->longitude; ?>">
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

<?php
    //Modal para logo
    $idLogoElements = 'logo-cropper';
    modalImageUploaderForCropperAdminViews([
        //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
        'content' => simpleCropperAdapterWorkSpace([
            'type' => 'image/*',
            'required' => false,
            'selectorAttr' => $idLogoElements,
            'referenceW' => '400',
            'referenceH' => '400',
            'image' => $organizationMapper->currentLangData('logo'),
            'imageName' => $organizationMapper->currentLangData('logo') === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
        ], false),
        //Atributos que se asignarán al modal (el contenedor principal), string
        'modalContainerAttrs' => "modal='{$idLogoElements}'",
        //Clases que se asignarán al modal (el contenedor principal), string
        'modalContainerClasses' => "ui tiny modal",
        //Atributos que se asignarán al elemento de contenido del modal (modal > .content), string
        'modalContentElementAttrs' => null,
        //Clase por defecto del elemento informativo del modal (donde están el título y la descripcion, por omisión cropper-info-content), string
        'informationContentMainClass' => null,
        //Clases que se asignarán al elemento informativo del modal (donde están el título y la descripcion), string
        'informationContentClasses' => null,
        //Título del modal, string
        'titleModal' => null,
        //Descripción del modal, string
        'descriptionModal' => null,
    ]);
?>

<div class="ui modal" change-organization-admin-modal>
    <div class="content">
        <form action="<?= $actionChangeAdministrator; ?>" class="ui form">
            <div class="section-fields-divider">
                <div class="title s24"><?= __($langGroup, 'Cambiar persona encargada'); ?></div>
            </div>
            <div class="field">
                <label style="display: none;"><?= __($langGroup, 'Persona encargada'); ?></label>
                <select required name="newUserAdminID" class="ui dropdown search auto"><?= $optionsUsersAdministrators; ?></select>
            </div>
            <div class="field">
                <button type="submit" class="ui button big brand-color"><?= __($langGroup, 'Cambiar'); ?></button>
            </div>
        </form>
    </div>
</div>