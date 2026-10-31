<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 * @var OrganizationMapper $element
 * @var bool $administratorMissing El encargado actual no está entre las opciones: el campo no es obligatorio.
 */
$canModify = OrganizationMapper::canModifyAnyOrganization(getLoggedFrameworkUserOrFail()->type);
//La cuenta enlaza con el perfil público de esa organización, y él con ella (PO, 2026-10-10).
$secondaryActions = [];
if (MyOrganizationProfileController::allowedRoute('my-organization-profile', ['organizationID' => $element->id])) {
    $secondaryActions[] = ['text' => __($langGroup, 'Perfil público'), 'url' => MyOrganizationProfileController::routeName('my-organization-profile', ['organizationID' => $element->id]), 'icon' => 'id card outline'];
}
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del panel (diseno/01-contenidos.md §2.1).
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
                'secondaryActions' => $secondaryActions,
            ]);
        ?>

        <?php if($manyLangs && false): //De momento, sin multi-idioma ?>
        <div class="ui form">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangs; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial organizations" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="lang" value="<?= $lang; ?>">

                <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Datos de la empresa')]); ?>

                <div class="two fields">

                    <div class="field eleven wide">

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre de la organización'); ?></label>
                            <input required type="text" name="name" maxlength="300" placeholder="" value="<?= escape_html($element->getLangData($lang, 'name', false, '')); ?>">
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Sector de actividad'); ?></label>
                            <input required type="text" name="activitySector" value="<?= escape_html($element->getLangData($lang, 'activitySector', false, '')); ?>">
                        </div>
                        <div class="two fields">

                            <div class="field">
                                <label><?= __($langGroup, 'Tamaño de la organización'); ?></label>
                                <select name="size" class="ui dropdown search"><?= $optionsSizes; ?></select>
                            </div>

                            <div class="field">
                                <label><?= __($langGroup, 'Líneas de acción'); ?></label>
                                <select name="actionLines[]" class="ui dropdown search multiple no-auto" multiple><?= $optionsActionLines; ?></select>
                            </div>

                        </div>
                        <div class="two fields">

                            <div class="field required">
                                <label><?= __(LOCATIONS_LANG_GROUP, 'País'); ?></label>
                                <select required name="country" class="no-auto" locations-component-auto-filled-country="<?= $element->country !== null ? $element->country->id : ''; ?>" with-dropdown></select>
                            </div>

                            <div class="field required">
                                <label><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></label>
                                <select required name="city" class="no-auto" locations-component-auto-filled-city="<?= $element->city !== null ? $element->city->id : ''; ?>" with-dropdown></select>
                            </div>

                            <input type="hidden" name="latitude" value="<?= $element->latitude; ?>">
                            <input type="hidden" name="longitude" value="<?= $element->longitude; ?>">

                        </div>
                        <div class="two fields">

                            <div class="field">
                                <label><?= __($langGroup, 'Teléfono'); ?></label>
                                <div class="fields">
                                    <div class="nine wide field">
                                        <select name="phoneCode" class="ui dropdown auto"><?= array_to_html_options(\PiecesPHP\App\Locations\Mappers\CountryMapper::phoneCodeOptions($element->phoneCode, true), $element->phoneCode !== null ? escape_html($element->phoneCode) : null); ?></select>
                                    </div>
                                    <div class="seven wide field">
                                        <input type="tel" name="phone" value="<?= escape_html($element->getLangData($lang, 'phone', false, '')); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label><?= __($langGroup, 'Correo de la organización'); ?></label>
                                <input type="email" name="informativeEmail" placeholder="" value="<?= escape_html($element->getLangData($lang, 'informativeEmail', false, '')); ?>">
                            </div>

                        </div>
                        <div class="two fields">
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace LinkedIn'); ?></label>
                                <input type="url" name="linkedinLink" value="<?= escape_html($element->getLangData($lang, 'linkedinLink', false)); ?>">
                            </div>
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace página web'); ?></label>
                                <input type="url" name="websiteLink" value="<?= escape_html($element->getLangData($lang, 'websiteLink', false)); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="field five wide">

                        <div class="field">
                            <label><?= __($langGroup, 'Código'); ?></label>
                            <input type="text" readonly disabled value="<?= htmlspecialchars((string) $element->code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'NIT'); ?></label>
                            <input required type="text" name="nit" placeholder="" value="<?= escape_html($element->getLangData($lang, 'nit', false, '')); ?>">
                        </div>
                        <div class="field">
                            <label><?= __($langGroup, 'ESAL autorizado por DIAN'); ?></label>
                            <select name="esal" class="ui dropdown search"><?= $optionsEsal; ?></select>
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Dirección'); ?></label>
                            <input required type="text" name="address" placeholder="" value="<?= escape_html($element->getLangData($lang, 'address', false, '')); ?>">
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Correo de facturación'); ?></label>
                            <input required type="email" name="billingEmail" placeholder="" value="<?= escape_html($element->getLangData($lang, 'billingEmail', false, '')); ?>">
                        </div>

                    </div>

                </div>

                <?php if($canModify): ?>
                <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Contacto de la organización'), 'description' => __($langGroup, 'Persona designada como punto de contacto principal.')]); ?>

                <div class="field<?= $administratorMissing ? '' : ' required'; ?>">
                    <label class="organization-hidden-label"><?= __($langGroup, 'Persona encargada'); ?></label>
                    <select<?= $administratorMissing ? '' : ' required'; ?> name="administrator" class="ui dropdown search"><?= $optionsUsersAdministrators; ?></select>
                    <?php if ($administratorMissing): ?>
                    <div class="ui small warning message visible"><?= __($langGroup, 'El encargado actual fue eliminado: elija otro cuando pueda.'); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif;?>

                <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Adjuntos')]); ?>

                <div class="two fields organization-attachments">
                    <?php
                        $this->helpController->_render('panel/built-in/content/image-field.php', [
                            'card' => [
                                'id' => 'organization-logo',
                                'title' => __($langGroup, 'Logo'),
                                'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                'accept' => 'image/jpeg,image/png,image/webp',
                                'image' => (string) $element->getLangData($lang, 'logo', false, ''),
                                'field' => 'logo',
                                'width' => 400,
                                'height' => 400,
                            ],
                        ]);
                    ?>
                    <div class="field<?= $element->getLangData($lang, 'rut', false, '') === '' ? ' required' : ''; ?>" organization-rut>
                        <label hidden><?= __($langGroup, 'RUT'); ?></label>
                        <?php simpleUploadPlaceholderWorkSpace([
                                'title' => __($langGroup, 'RUT'),
                                'description' => __($langGroup, 'Tamaño máximo del archivo 2MB'),
                                'inputNameAttr' => 'rut',
                                'buttonText' => __($langGroup, 'Anexar'),
                                'required' => $element->getLangData($lang, 'rut', false, '') === '',
                                'multiple' => false,
                                'icon' => 'file alternate outline',
                                'accept' => '.jpg,.jpeg,.png,.webp,.pdf',
                                'currentFileURL' => (string) $element->getLangData($lang, 'rut', false, ''),
                                'currentFileText' => __($langGroup, 'Ver el RUT'),
                        ]); ?>
                    </div>
                </div>

                <?php if($canModify): ?>
                <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Estado de organización')]); ?>

                <div class="field required">
                    <label><?= __($langGroup, 'Estado'); ?></label>
                    <select required name="status" class="ui dropdown search"><?= $optionsStatus; ?></select>
                </div>
                <?php endif;?>

            </div>

            <?php
                //La barra aparte (§2.3). «Eliminar», secundaria en rojo y type=button, con lo que busca delete-config.js.
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => $allowDelete ? [[
                        'text' => __($langGroup, 'Eliminar'),
                        'type' => 'button',
                        'icon' => 'trash alternate',
                        'danger' => true,
                        'attributes' => ['delete-organization-button' => '', 'data-route' => $deleteRoute],
                    ]] : [],
                ]);
            ?>

        </form>

    </div>

</section>
<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
