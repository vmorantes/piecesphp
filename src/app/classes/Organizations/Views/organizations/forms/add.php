<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Organizations\Mappers\OrganizationMapper;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
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

        <div class="container-standard-form">

            <form method='POST' action="<?= $action; ?>" class="ui form organizations">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

                <div class="section-fields-divider">
                    <div class="title h6"><?= __($langGroup, 'Datos de la empresa'); ?></div>
                </div>

                <div class="two fields">

                    <div class="field eleven wide">

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre de la organización'); ?></label>
                            <input required type="text" name="name" maxlength="300" placeholder="">
                        </div>
                        <br>
                        <div class="two fields">

                            <div class="field required">
                                <label><?= __($langGroup, 'Tamaño de la organización'); ?></label>
                                <select required name="size" class="ui dropdown search"><?= $optionsSizes; ?></select>
                            </div>

                            <div class="field required">
                                <label><?= __($langGroup, 'Líneas de acción'); ?></label>
                                <select required name="actionLines[]" class="ui dropdown search multiple" multiple><?= $optionsActionLines; ?></select>
                            </div>

                        </div>
                        <br>
                        <div class="two fields">

                            <div class="field required">
                                <label><?= __(LOCATIONS_LANG_GROUP, 'Departamento'); ?></label>
                                <select required name="state" locations-component-auto-filled-state="" with-dropdown></select>
                            </div>

                            <div class="field required">
                                <label><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></label>
                                <select required name="city" locations-component-auto-filled-city="" with-dropdown></select>
                            </div>

                        </div>
                        <br>
                        <div class="two fields">

                            <div class="field required">
                                <label><?= __($langGroup, 'Teléfono'); ?></label>
                                <input required type="tel" name="phone" placeholder="">
                            </div>

                            <div class="field required">
                                <label><?= __($langGroup, 'Correo informativo'); ?></label>
                                <input required type="email" name="informativeEmail" placeholder="">
                            </div>

                        </div>

                    </div>

                    <div class="field five wide">

                        <div class="field required">
                            <label><?= __($langGroup, 'NIT'); ?></label>
                            <input required type="text" name="nit" placeholder="">
                        </div>
                        <br>
                        <div class="field required">
                            <label><?= __($langGroup, 'ESAL autorizado por DIAN'); ?></label>
                            <select required name="esal" class="ui dropdown search"><?= $optionsEsal; ?></select>
                        </div>
                        <br>
                        <div class="field required">
                            <label><?= __($langGroup, 'Dirección'); ?></label>
                            <input required type="text" name="address" placeholder="">
                        </div>
                        <br>
                        <div class="field required">
                            <label><?= __($langGroup, 'Correo de facturación'); ?></label>
                            <input required type="email" name="billingEmail" placeholder="">
                        </div>

                    </div>

                </div>

                <div class="section-fields-divider">
                    <div class="title h6"><?= __($langGroup, 'Contacto de la organización'); ?></div>
                    <div class="description"><?= __($langGroup, 'Persona designada como punto de contacto principal.'); ?></div>
                </div>

                <div class="three fields">

                    <div class="field required">
                        <label><?= __($langGroup, 'Nombre completo'); ?></label>
                        <input required type="text" name="contactName" placeholder="">
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Teléfono'); ?></label>
                        <input required type="tel" name="contactPhone" placeholder="">
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Correo informativo'); ?></label>
                        <input required type="email" name="contactEmail" placeholder="">
                    </div>

                </div>

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
                            <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
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
                            <div data-image="" class="image" data-on-change-text="<?= __($langGroup, 'Cambiar'); ?>">
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
                        <input required type="file" accept="image/*,.pdf" id="<?= $uniqueIdentifier; ?>">
                    </div>
                </div>

                <br><br>

                <div class="field">
                    <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>

        </div>

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
            'image' => '',
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
