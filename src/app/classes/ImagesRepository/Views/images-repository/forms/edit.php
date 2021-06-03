<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ImagesRepository\Controllers\ImagesRepositoryController;
use ImagesRepository\Mappers\ImagesRepositoryMapper;

/**
 * @var ImagesRepositoryMapper $element
 * @var ImagesRepositoryController $this
 */;
 $element;
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */;
$langGroup;
$backLink;
$action;
?>

<div class="ui buttons">

    <a href="<?= $backLink; ?>" class="ui labeled icon button">
        <i class="icon left arrow"></i>
        <?= __($langGroup, 'Regresar'); ?>
    </a>

</div>

<br>
<br>

<h3 class="title-form"><?= __($langGroup, 'Editar'); ?>
    <?= $title; ?>
</h3>

<?php if($manyLangs): ?>
<div class="ui form">
    <div class="field required">
        <label><?= __($langGroup, 'Idiomas'); ?></label>
        <select required class="ui dropdown search langs">
            <?= $allowedLangs; ?>
        </select>
    </div>
</div>
<?php endif; ?>

<br>

<form method='POST' action="<?= $action; ?>" class="ui form" add-image-repository>

    <input type="hidden" name="id" value="<?= $element->id; ?>">
    <input type="hidden" name="lang" value="<?= $lang; ?>">

    <div class="ui grid stackable">
        <div class="three column row">

            <div class="column">
                <div class="field" simple-upload-placeholder-image>
                    <?php 
                        $this->helpController->render(
                            'panel/built-in/utilities/simple-upload-placeholder/workspace',
                            [
                                'inputNameAttr' => 'image',
                                'imagePreview' => $element->image,
                                'buttonText' => __($langGroup, 'Seleccionar imagen'),
                                'required' => false,
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
                    <input required type="text" name="author" value="<?= $element->author; ?>">
                </div>

                <div class="field required" calendar-js calendar-type="date">
                    <label><?= __($langGroup, 'Fecha de captura'); ?></label>
                    <input required type="text" name="captureDate" value="<?= $element->captureDate->format('Y-m-d H:i:s'); ?>">
                </div>

                <div class="field required">
                    <label><?= __($langGroup, 'Descripción'); ?></label>
                    <textarea required name="description" cols="30" rows="7" minlength="100" placeholder="<?= __($langGroup, 'Mínimo de 100 caracteres'); ?>"><?= $element->getLangData($lang, 'description'); ?></textarea>
                </div>

                <div class="field" simple-upload-placeholder-file>
                    <?php 
                        $this->helpController->render(
                            'panel/built-in/utilities/simple-upload-placeholder/workspace',
                            [
                                'onlyButton' => true,
                                'inputNameAttr' => 'authorization',
                                'buttonText' => $element->hasAuthorization() ? __($langGroup, 'Cambiar consentimiento') : __($langGroup, 'Agregar consentimiento'),
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
                    <label><?= __($langGroup, 'Quién cargó la imagen'); ?></label>
                    <div>
                        <span><?= $element->createdByFullName(); ?></span>
                    </div>
                </div>

                <div class="field required">
                    <label><?= __($langGroup, 'Tamaño'); ?></label>
                    <div size-display>
                        <span class="text"><?= $element->size; ?></span>
                        <span class="unit">MB</span>
                    </div>
                </div>

                <div class="field required">
                    <label><?= __($langGroup, 'Resolución'); ?></label>
                    <input type="hidden" name="resolution" value="<?= $element->resolution; ?>">
                    <div resolution-display>
                        <span class="text"><?= $element->resolution; ?></span>
                        <span class="unit">px</span>
                    </div>
                </div>

                <div class="field">
                    <label><?= __($langGroup, 'Coordenadas'); ?></label>
                    <div>
                        <span><?= $element->getCoordinates(__($langGroup, 'Sin información'), true); ?></span>
                    </div>
                </div>

                <div class="field">
                    <?php if($element->hasAuthorization()): ?>
                    <a href="<?= $element->getAuthorizationPublicURL(); ?>" target="_blank" class="ui button custom-color labeled icon">
                        <i class="icon <?= $element->authorizationIconByExtension(); ?>"></i>
                        <?= __($langGroup, 'Ver consentimiento'); ?>
                    </a>
                    <?php endif;?>
                </div>

            </div>

        </div>

    </div>

    <div class="ui grid stackable">
        <div class="row">
            <div class="column">
                <div class="field">
                    <div class="ui buttons">
                        <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
                        <?php if($allowDelete): ?>
                        <button type="submit" class="ui button red" delete-images-repository-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</form>
