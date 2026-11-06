<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Directory\Controllers\ProfileController;
use Organizations\Controllers\OrganizationProfileController;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsLang;
use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var UsersModel $organizationAdminMapper
 * @var OrganizationMapper $organizationMapper
 * @var string $action
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \Organizations\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
//La cuenta y el perfil público son dos ámbitos, con los mismos nombres e iconos en personas y organizaciones (PO, 2026-10-10).
$secondaryActions = [];
$editParams = ['id' => $organizationMapper->id, 'lang' => Config::get_default_lang()];
if (OrganizationsController::allowedRoute('forms-edit', $editParams)) {
    $secondaryActions[] = ['text' => __($langGroup, 'Cuenta'), 'url' => OrganizationsController::routeName('forms-edit', $editParams), 'icon' => 'user circle'];
}
if (OrganizationProfileController::allowedRoute('profile', ['organizationID' => $organizationMapper->id])) {
    $secondaryActions[] = ['text' => __($langGroup, 'Ver perfil'), 'url' => OrganizationProfileController::routeName('profile', ['organizationID' => $organizationMapper->id]), 'icon' => 'eye'];
}

$langGroupOrganizations = OrganizationsLang::LANG_GROUP;
$adminUser = new UserDataPackage($organizationAdminMapper->id);
$affiliatedInstitutions = $organizationMapper->affiliatedInstitutions;
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
            <div class="active item" data-tab="generalData"><?= __($langGroup, 'Datos generales'); ?></div>
            <div class="item" data-tab="professionalData"><?= __($langGroup, 'Datos profesionales'); ?></div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form initial my-organization-profile" data-content-form>

            <div class="ui tab active general-data-form" data-tab="generalData">

                <div class="container-standard-form">

                    <div class="inputs-general-data">

                        <div class="ui stackable two column grid">
                            <div class="row">
                                <div class="eleven wide column">

                                    <div class="field required">
                                        <label><?= __($langGroupOrganizations, 'Nombre de la organización'); ?></label>
                                        <input required type="text" name="name" maxlength="300" value="<?= escape_html($organizationMapper->currentLangData('name')); ?>">
                                    </div>

                                    <div class="<?= $classNumberFieldByQty; ?> fields">
                                        <?php foreach($allowedLangs as $allowedLang): ?>
                                        <?php $shortLang = ucfirst(strtolower(__('langShort', $allowedLang))); ?>
                                        <div class="field required">
                                            <label><?= __($langGroupOrganizations, 'Sector de actividad'); ?> - <?= $shortLang; ?></label>
                                            <input required type="text" name="activitySector[<?= $allowedLang; ?>]" value="<?= escape_html($organizationMapper->getLangData($allowedLang, 'activitySector', false)); ?>">
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="five wide column">
                                    <?php
                                        $helpController->_render('panel/built-in/content/image-field.php', [
                                            'card' => [
                                                'id' => 'organization-logo',
                                                'title' => __($langGroup, 'Logo'),
                                                'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                                'accept' => 'image/jpeg,image/png,image/webp',
                                                'image' => (string) $organizationMapper->currentLangData('logo'),
                                                'field' => 'logo',
                                                'width' => 400,
                                                'height' => 400,
                                            ],
                                        ]);
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field required">
                                <label><?= __($langGroupOrganizations, 'Correo electrónico'); ?></label>
                                <input type="text" required name="informativeEmail" value="<?= escape_html($organizationMapper->currentLangData('informativeEmail')); ?>">
                            </div>
                            <div class="field">
                                <label><?= __($langGroupOrganizations, 'Teléfono'); ?></label>
                                <div class="fields">
                                    <div class="six wide field">
                                        <select name="phoneCode" class="ui dropdown auto"><?= array_to_html_options(\PiecesPHP\App\Locations\Mappers\CountryMapper::phoneCodeOptions($organizationMapper->phoneCode, true), $organizationMapper->phoneCode !== null ? escape_html($organizationMapper->phoneCode) : null); ?></select>
                                    </div>
                                    <div class="ten wide field">
                                        <input type="tel" name="phone" value="<?= escape_html($organizationMapper->currentLangData('phone')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="two fields">
                            <div class="field">
                                <label><?= __($langGroupOrganizations, 'Enlace LinkedIn'); ?></label>
                                <input type="url" name="linkedinLink" value="<?= escape_html($organizationMapper->currentLangData('linkedinLink')); ?>">
                            </div>
                            <div class="field">
                                <label><?= __($langGroupOrganizations, 'Enlace página web'); ?></label>
                                <input type="url" name="websiteLink" value="<?= escape_html($organizationMapper->currentLangData('websiteLink')); ?>">
                            </div>
                        </div>

                        <div class="horizontal-space"></div>

                        <h2 class="ui header"><?= __($langGroup, 'Encargado de la organización'); ?></h2>

                        <div class="ui items identity-profile-card">
                            <div class="item">
                                <div class="ui small circular image">
                                    <img src="<?= $adminUser->getAvatarURL(); ?>" alt="<?= escape_html($adminUser->getMapper()->getFullName()); ?>">
                                </div>
                                <div class="middle aligned content">
                                    <div class="ui header name"><span class="ui breaking text"><?= escape_html($adminUser->getMapper()->getFullName()); ?></span></div>
                                    <div class="meta email"><?= escape_html($adminUser->getMapper()->email); ?></div>
                                    <div class="extra actions">
                                        <a class="ui right labeled icon primary button" href="<?= ProfileController::routeName('profile', ['userID' => $adminUser->id]); ?>">
                                            <?= __($langGroup, 'Ver perfil'); ?>
                                            <i class="angle right icon"></i>
                                        </a>
                                        <button class="ui button blue" change-organization-admin-trigger>
                                            <?= __($langGroup, 'Cambiar'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="horizontal-space"></div>

                        <div class="ui stackable two column grid">
                            <div class="row">
                                <div class="column">
                                    <h2 class="ui header">
                                        <?= __($langGroup, 'Ubicación'); ?>
                                        <div class="sub header"><?= __($langGroup, 'Se requiere la ubicación para la búsqueda en la vista cartográfica'); ?></div>
                                    </h2>
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
                                    <h2 class="ui header"><?= __($langGroup, 'Ubique el marcador en la ubicación'); ?></h2>
                                    <div class="field">
                                        <div id="map"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

            <div class="ui tab" data-tab="professionalData">

                <div class="container-standard-form">
                    <div class="field">
                        <h2 class="ui header"><?= __($langGroup, 'Instituciones a las que pertenece actualmente'); ?></h2>

                        <select name="affiliatedInstitutions[]" multiple class="ui dropdown multiple auto additions search"><?= array_to_html_options($affiliatedInstitutionsOptions, $affiliatedInstitutions, true); ?></select>
                    </div>
                    <div class="field">
                        <?php //P136: el oculto manda «0» cuando la casilla no va marcada; marcada, gana el «1» que va detrás. ?>
                        <input type="hidden" name="publicVisible" value="0">
                        <div class="ui checkbox">
                            <input type="checkbox" name="publicVisible" value="1" id="my-organization-public-visible"<?= (int) ($organizationMapper->publicVisible ?? 1) === 1 ? ' checked' : ''; ?>>
                            <label for="my-organization-public-visible"><?= __($langGroup, 'Mostrar el perfil de la organización en el directorio y en el mapa'); ?></label>
                        </div>
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

<?php $helpController->_render('panel/built-in/content/image-editor.php'); ?>

<div class="ui tiny modal" change-organization-admin-modal data-confirm-message="<?= escape_html(__($langGroup, '¿Cambiar a la persona encargada de esta organización?')); ?>">
    <i class="close icon"></i>
    <div class="header"><?= __($langGroup, 'Cambiar persona encargada'); ?></div>
    <div class="content">
        <form action="<?= $actionChangeAdministrator; ?>" class="ui form" id="change-organization-admin-form">
            <div class="field">
                <label hidden><?= __($langGroup, 'Persona encargada'); ?></label>
                <select required name="newUserAdminID" class="ui dropdown search auto"><?= $optionsUsersAdministrators; ?></select>
            </div>
        </form>
    </div>
    <div class="actions">
        <button type="submit" form="change-organization-admin-form" class="ui primary button"><?= __($langGroup, 'Cambiar'); ?></button>
    </div>
</div>
