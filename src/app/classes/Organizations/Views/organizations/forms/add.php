<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del panel (diseno/01-contenidos.md §2.1).
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
            ]);
        ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial organizations" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

                <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Datos de la empresa')]); ?>

                <div class="two fields">

                    <div class="field eleven wide">

                        <div class="field required">
                            <label><?= __($langGroup, 'Nombre de la organización'); ?></label>
                            <input required type="text" name="name" maxlength="300" placeholder="">
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Sector de actividad'); ?></label>
                            <input required type="text" name="activitySector" placeholder=" ">
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
                                <select required name="country" locations-component-auto-filled-country="" with-dropdown></select>
                            </div>

                            <div class="field required">
                                <label><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></label>
                                <select required name="city" locations-component-auto-filled-city="" with-dropdown></select>
                            </div>

                            <input type="hidden" name="latitude">
                            <input type="hidden" name="longitude">

                        </div>
                        <div class="two fields">

                            <div class="field">
                                <label><?= __($langGroup, 'Teléfono'); ?></label>
                                <div class="fields">
                                    <div class="nine wide field">
                                        <select name="phoneCode" class="ui dropdown auto"><?= array_to_html_options(\PiecesPHP\App\Locations\Mappers\CountryMapper::phoneCodeOptions(null, true), null); ?></select>
                                    </div>
                                    <div class="seven wide field">
                                        <input type="tel" name="phone" placeholder="">
                                    </div>
                                </div>
                            </div>

                            <div class="field">
                                <label><?= __($langGroup, 'Correo de la organización'); ?></label>
                                <input type="email" name="informativeEmail" placeholder="">
                            </div>

                        </div>
                        <div class="two fields">
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace LinkedIn'); ?></label>
                                <input type="url" name="linkedinLink">
                            </div>
                            <div class="field">
                                <label><?= __($langGroup, 'Enlace página web'); ?></label>
                                <input type="url" name="websiteLink">
                            </div>
                        </div>

                    </div>

                    <div class="field five wide">

                        <div class="field required">
                            <label><?= __($langGroup, 'NIT'); ?></label>
                            <input required type="text" name="nit" placeholder="">
                        </div>
                        <div class="field">
                            <label><?= __($langGroup, 'ESAL autorizado por DIAN'); ?></label>
                            <select name="esal" class="ui dropdown search"><?= $optionsEsal; ?></select>
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Dirección'); ?></label>
                            <input required type="text" name="address" placeholder="">
                        </div>
                        <div class="field required">
                            <label><?= __($langGroup, 'Correo de facturación'); ?></label>
                            <input required type="email" name="billingEmail" placeholder="">
                        </div>

                    </div>

                </div>

                <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Adjuntos')]); ?>

                <div class="two fields organization-attachments">
                    <?php
                        $this->helpController->_render('panel/built-in/content/image-field.php', [
                            'card' => [
                                'id' => 'organization-logo',
                                'title' => __($langGroup, 'Logo'),
                                'hint' => __($langGroup, 'Imagen preferiblemente en formato .jpg'),
                                'accept' => 'image/jpeg,image/png,image/webp',
                                'image' => '',
                                'field' => 'logo',
                                'width' => 400,
                                'height' => 400,
                            ],
                        ]);
                    ?>
                    <div class="field required" organization-rut>
                        <label hidden><?= __($langGroup, 'RUT'); ?></label>
                        <?php simpleUploadPlaceholderWorkSpace([
                                'title' => __($langGroup, 'RUT'),
                                'description' => __($langGroup, 'Tamaño máximo del archivo 2MB'),
                                'inputNameAttr' => 'rut',
                                'buttonText' => __($langGroup, 'Anexar'),
                                'required' => true,
                                'multiple' => false,
                                'icon' => 'file alternate outline',
                                'accept' => '.jpg,.jpeg,.png,.webp,.pdf',
                        ]); ?>
                    </div>
                </div>

                <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['title' => __($langGroup, 'Estado de organización')]); ?>

                <div class="field required">
                    <label><?= __($langGroup, 'Estado'); ?></label>
                    <select required name="status" class="ui dropdown search"><?= $optionsStatus; ?></select>
                </div>

            </div>

            <?php $this->helpController->_render('panel/built-in/content/form-action-bar.php', ['saveText' => __($langGroup, 'Guardar')]); ?>

        </form>

    </div>

</section>

<?php $this->helpController->_render('panel/built-in/content/image-editor.php'); ?>
