<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$standalone = isset($standalone) && is_bool($standalone) ? $standalone : true;
$submitButtonText = isset($submitButtonText) ? $submitButtonText : __($langGroup, 'Guardar');
?>

<?php if($standalone): ?>
<section class="module-view-container limit-size">
    <?php endif; ?>

    <?php if($standalone): ?>
    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Agregar'); ?></div>
                </div>

            </div>

        </div>

    </div>
    <?php endif; ?>

    <div class="container-standard-form <?= !$standalone ? 'mw-1200' : ''; ?>">

        <form method='POST' action="<?= $action; ?>" class="ui form" add-image-repository>

            <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

            <div class="fields">

                <div class="ten wide field">

                    <h4 class="ui dividing header alt"><?= __($langGroup, 'Formulario'); ?></h4>

                    <div class="two fields">

                        <div class="field">

                            <div class="field required">
                                <label><?= __(LOCATIONS_LANG_GROUP, 'Departamento'); ?></label>
                                <select required with-dropdown name="state" locations-component-auto-filled-state></select>
                            </div>
                            <br>

                            <div class="field required">
                                <label><?= __(LOCATIONS_LANG_GROUP, 'Ciudad'); ?></label>
                                <select required with-dropdown name="city" locations-component-auto-filled-city></select>
                            </div>
                            <br>

                            <div class="field required">
                                <label><?= __($langGroup, 'Autor de la imagen'); ?></label>
                                <input required type="text" name="author">
                            </div>
                            <br>

                            <div class="field required" calendar-js calendar-type="date">
                                <label><?= __($langGroup, 'Fecha de captura'); ?></label>
                                <input required type="text" name="captureDate">
                            </div>
                            <br>

                            <div class="field required">
                                <label><?= __($langGroup, 'Descripción'); ?></label>
                                <textarea required name="description" cols="30" rows="7" minlength="100" placeholder="<?= __($langGroup, 'Mínimo de 100 caracteres'); ?>"></textarea>
                            </div>
                            <br>

                            <div class="field" simple-upload-placeholder-file>
                                <?php 
                                    $this->helpController->render(
                                        'panel/built-in/utilities/simple-upload-placeholder/workspace',
                                        [
                                            'onlyButton' => true,
                                            'inputNameAttr' => 'authorization',
                                            'buttonText' => __($langGroup, 'Agregar consentimiento'),
                                            'required' => false,
                                            'multiple' => false,
                                            'icon' => 'file outline',
                                            'accept' => implode(',', [
                                                '.doc',
                                                '.docx',
                                                '.pdf',
                                                '.xls',
                                                '.xlsx',
                                            ]),
                                        ]
                                    ); 
                                ?>
                            </div>

                        </div>

                        <div class="field">

                            <div class="field required">
                                <label><?= __($langGroup, 'Tamaño'); ?></label>
                                <input type="hidden" name="size">
                                <div size-display>
                                    <span class="text">&nbsp;0</span>
                                    <span class="unit">MB</span>
                                </div>
                            </div>

                            <div class="field required">
                                <label><?= __($langGroup, 'Resolución'); ?></label>
                                <input type="hidden" name="resolution">
                                <div resolution-display>
                                    <span class="text">&nbsp;0x0</span>
                                    <span class="unit">px</span>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <div class="five wide field">

                    <h4 class="ui dividing header"><?= __($langGroup, 'Imagen'); ?></h4>

                    <div class="field" simple-upload-placeholder-image>
                        <?php 
                            $this->helpController->render(
                                'panel/built-in/utilities/simple-upload-placeholder/workspace',
                                [
                                    'inputNameAttr' => 'image',
                                    'buttonText' => __($langGroup, 'Seleccionar imagen'),
                                    'required' => true,
                                    'multiple' => false,
                                    'icon' => 'image outline',
                                    'accept' => implode(',', [
                                        '.jpg',
                                        '.jpeg',
                                        '.png',
                                        '.webp',
                                    ]),
                                ]
                            ); 
                        ?>
                    </div>

                    <br>

                    <div class="field">
                        <button class="ui button green" type="submit"><?= $submitButtonText; ?></button>
                    </div>

                </div>

            </div>

        </form>

    </div>
    <?php if($standalone): ?>
</section>
<?php endif; ?>
