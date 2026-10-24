<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 * @var OrganizationMapper $element
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

                <div class="section-fields-divider">
                    <div class="title h6"><?= __($langGroup, 'Datos de la empresa'); ?></div>
                </div>

                <div class="two fields">

                    <div class="field eleven wide">

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre de la organización'); ?></label>
                            <input required type="text" name="name" maxlength="300" placeholder="" value="<?= escape_html($element->getLangData($lang, 'name', false, '')); ?>">
                        </div>
                        <br>
                        <div class="field required">
                            <label><?= __($langGroup, 'Sector de actividad'); ?></label>
                            <input required type="text" name="activitySector" value="<?= escape_html($element->getLangData($lang, 'activitySector', false, '')); ?>">
                        </div>
                        <br>
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
                        <br>
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
                        <br>
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
                        <br>
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
                        <br>
                        <div class="field required">
                            <label><?= __($langGroup, 'NIT'); ?></label>
                            <input required type="text" name="nit" placeholder="" value="<?= escape_html($element->getLangData($lang, 'nit', false, '')); ?>">
                        </div>
                        <br>
                        <div class="field">
                            <label><?= __($langGroup, 'ESAL autorizado por DIAN'); ?></label>
                            <select name="esal" class="ui dropdown search"><?= $optionsEsal; ?></select>
                        </div>
                        <br>
                        <div class="field required">
                            <label><?= __($langGroup, 'Dirección'); ?></label>
                            <input required type="text" name="address" placeholder="" value="<?= escape_html($element->getLangData($lang, 'address', false, '')); ?>">
                        </div>
                        <br>
                        <div class="field required">
                            <label><?= __($langGroup, 'Correo de facturación'); ?></label>
                            <input required type="email" name="billingEmail" placeholder="" value="<?= escape_html($element->getLangData($lang, 'billingEmail', false, '')); ?>">
                        </div>

                    </div>

                </div>

                <?php if($canModify): ?>
                <br>
                <div class="section-fields-divider">
                    <div class="title h6"><?= __($langGroup, 'Contacto de la organización'); ?></div>
                    <div class="description"><?= __($langGroup, 'Persona designada como punto de contacto principal.'); ?></div>
                </div>

                <div class="field required">
                    <label class="organization-hidden-label"><?= __($langGroup, 'Persona encargada'); ?></label>
                    <select required name="administrator" class="ui dropdown search"><?= $optionsUsersAdministrators; ?></select>
                </div>
                <?php endif;?>

                <div class="section-fields-divider">
                    <div class="title h6"><?= __($langGroup, 'Adjuntos'); ?></div>
                </div>

                <div class="form-attachments-regular">
                    <div class="attach-placeholder logo">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div data-image="<?= escape_html($element->getLangData($lang, 'logo', false, '')); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
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
                    <div class="attach-placeholder rut required">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div data-file="<?= escape_html($element->getLangData($lang, 'rut', false, '')); ?>" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
                                <i class="icon upload"></i>
                                <div class="caption"><?= __($langGroup, 'Anexar'); ?></div>
                            </div>
                            <div class="text">
                                <div class="filename"></div>
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'RUT'); ?></div>
                                </div>
                                <div class="description"><?= __($langGroup, 'Tamaño máximo del archivo 2MB'); ?></div>
                            </div>
                        </label>
                        <input <?= $element->getLangData($lang, 'rut', false, '') !== '' ? '' : 'required'; ?> type="file" accept="image/*,.pdf" id="<?= $uniqueIdentifier; ?>">
                    </div>
                </div>

                <?php if($canModify): ?>
                <br>

                <div class="section-fields-divider">
                    <div class="title h6"><?= __($langGroup, 'Estado de organización'); ?></div>
                </div>

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
            'image' => $element->getLangData($lang, 'logo'),
            'imageName' => $element->getLangData($lang, 'logo', false, null) === null ? 'image_' . str_replace('.', '', uniqid('', true)) : '',
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