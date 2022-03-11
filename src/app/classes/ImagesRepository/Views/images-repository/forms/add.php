<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ImagesRepository\Controllers\ImagesRepositoryController;
/**
 * @var ImagesRepositoryCo
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$langGroup;
$backLink;
$action;
$standalone = isset($standalone) && is_bool($standalone) ? $standalone : true;
$submitButtonText = isset($submitButtonText) ? $submitButtonText : __($langGroup, 'Guardar');
?>

<?php if($standalone): ?>
<div class="ui buttons">

    <a href="<?= $backLink; ?>" class="ui labeled icon button">
        <i class="icon left arrow"></i>
        <?= __($langGroup, 'Regresar'); ?>
    </a>

</div>

<br><br>

<h3 class="title-form"><?= __($langGroup, 'Agregar'); ?>
    <?= $title; ?>
</h3>
<?php endif; ?>
<form method='POST' action="<?= $action; ?>" class="ui form <?= $standalone ? 'standard-form' : ''; ?>" add-image-repository>

    <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

    <div class="ui grid stackable">
        <div class="three column row">

            <div class="column">
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
            </div>

            <div class="column">
                <div class="field required">
                    <label><?= __($langGroup, 'Autor de la imagen'); ?></label>
                    <input required type="text" name="author">
                </div>

                <div class="field required" calendar-js calendar-type="date">
                    <label><?= __($langGroup, 'Fecha de captura'); ?></label>
                    <input required type="text" name="captureDate">
                </div>

                <div class="field required">
                    <label><?= __($langGroup, 'Descripción'); ?></label>
                    <textarea required name="description" cols="30" rows="7" minlength="100" placeholder="<?= __($langGroup, 'Mínimo de 100 caracteres'); ?>"></textarea>
                </div>

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

            <div class="column">

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

    <div class="ui grid stackable">
        <div class="row">
            <div class="column">
                <div class="field">
                    <button class="ui button green" type="submit"><?= $submitButtonText; ?></button>
                </div>
            </div>
        </div>
    </div>

</form>
