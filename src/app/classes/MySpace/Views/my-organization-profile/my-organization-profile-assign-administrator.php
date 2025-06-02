<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use Organizations\OrganizationsLang;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * @var string $langGroup
 * @var OrganizationMapper $organizationMapper
 * @var string $action
 */
$langGroupOrganizations = OrganizationsLang::LANG_GROUP;
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
        </div>

        <form action="." class="ui form my-organization-profile-assign-administrator">

            <div class="ui tab active general-data-form" data-tab="generalData">

                <div class="container-standard-form">

                    <div class="section-fields-divider">
                        <div class="title s20"><?= $organizationMapper->currentLangData('name'); ?></div>
                    </div>

                    <div class="inputs-general-data">

                        <div class="section-fields-divider">
                            <div class="title s20"><?= __($langGroup, 'Contacto de la organización'); ?></div>
                        </div>

                        <div class="identity-profile-card">
                            <div class="avatar">
                                <img src="statics/images/default-avatar.png" alt="<?= __($langGroup, 'Persona encargada'); ?>">
                            </div>
                            <?php if($hasAdminOptions): ?>
                            <div class="data">
                                <div class="name"><?= __($langGroup, 'Persona encargada'); ?></div>
                                <div class="meta"><?= __($langGroup, 'Debe seleccionar una persona encargada para modificar el perfil'); ?></div>
                                <div class="actions">
                                    <button class="ui button blue" change-organization-admin-trigger>
                                        <?= __($langGroup, 'Cambiar'); ?>
                                    </button>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="data">
                                <div class="meta"><?= __($langGroup, 'No hay usuarios asociados a esta organización que puedan ser asignados como encargados, asocia algún usuario a esta organización desde el listado de usuarios en el siguiente botón'); ?></div>
                                <div class="actions">
                                    <a class="ui button blue" href="<?= get_route('users-list'); ?>" target="_blank">
                                        <?= __($langGroup, 'Ver usuarios'); ?>
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

</section>

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