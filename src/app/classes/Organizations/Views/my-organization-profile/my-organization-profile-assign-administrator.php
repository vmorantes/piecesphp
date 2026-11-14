<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Organizations\Controllers\OrganizationProfileController;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsLang;
use PiecesPHP\Core\Config;

/**
 * @var string $langGroup
 * @var OrganizationMapper $organizationMapper
 * @var string $action
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \Organizations\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$langGroupOrganizations = OrganizationsLang::LANG_GROUP;
//Los mismos dos ámbitos que el formulario del perfil: la cuenta de la organización y su perfil público.
$secondaryActions = [];
$editParams = ['id' => $organizationMapper->id, 'lang' => Config::get_default_lang()];
if (OrganizationsController::allowedRoute('forms-edit', $editParams)) {
    $secondaryActions[] = ['text' => __($langGroup, 'Cuenta'), 'url' => OrganizationsController::routeName('forms-edit', $editParams), 'icon' => 'user circle'];
}
if (OrganizationProfileController::allowedRoute('profile', ['organizationID' => $organizationMapper->id])) {
    $secondaryActions[] = ['text' => __($langGroup, 'Ver perfil'), 'url' => OrganizationProfileController::routeName('profile', ['organizationID' => $organizationMapper->id]), 'icon' => 'eye'];
}
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
        </div>

        <form action="." class="ui form my-organization-profile-assign-administrator">

            <div class="ui tab active general-data-form" data-tab="generalData">

                <div class="container-standard-form">

                    <h2 class="ui header"><?= escape_html($organizationMapper->currentLangData('name')); ?></h2>

                    <div class="inputs-general-data">

                        <h2 class="ui header"><?= __($langGroup, 'Encargado de la organización'); ?></h2>

                        <div class="ui items identity-profile-card">
                            <div class="item">
                                <div class="ui small circular image">
                                    <img src="statics/images/default-avatar.png" alt="<?= __($langGroup, 'Persona encargada'); ?>">
                                </div>
                                <?php if($hasAdminOptions): ?>
                                <div class="middle aligned content">
                                    <div class="ui header name"><?= __($langGroup, 'Persona encargada'); ?></div>
                                    <div class="meta"><?= __($langGroup, 'Debe seleccionar una persona encargada para modificar el perfil'); ?></div>
                                    <div class="extra actions">
                                        <button class="ui basic button" change-organization-admin-trigger>
                                            <?= __($langGroup, 'Cambiar'); ?>
                                        </button>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="middle aligned content">
                                    <div class="meta"><?= __($langGroup, 'No hay usuarios asociados a esta organización que puedan ser asignados como encargados, asocia algún usuario a esta organización desde el listado de usuarios en el siguiente botón'); ?></div>
                                    <div class="extra actions">
                                        <a class="ui basic button" href="<?= \PiecesPHP\UserSystem\Controllers\UsersController::routeName('list'); ?>" target="_blank">
                                            <?= __($langGroup, 'Ver usuarios'); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

</section>

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
