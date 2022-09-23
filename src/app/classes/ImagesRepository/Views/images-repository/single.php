<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ImagesRepository\Controllers\ImagesRepositoryController;
use ImagesRepository\Mappers\ImagesRepositoryMapper;

/**
 * @var ImagesRepositoryMapper $element
 * @var ImagesRepositoryController $this
 */
/**
 * @var string $langGroup
 * @var string $action
 */
?>

<?php if(mb_strlen($toListLink) > 0 || mb_strlen($toManageLink) > 0): ?>
<div class="ui buttons">

    <?php if(mb_strlen($toListLink) > 0): ?>
    <a href="<?= $toListLink; ?>" class="ui labeled icon button">
        <i class="icon left arrow"></i>
        <?= __($langGroup, 'Regresar'); ?>
    </a>
    <?php endif;?>

    <?php if(mb_strlen($toManageLink) > 0): ?>
    <a href="<?= $toManageLink; ?>" class="ui labeled icon button custom-color">
        <i class="icon left edit"></i>
        <?= __($langGroup, 'Gestionar'); ?>
    </a>
    <?php endif;?>

</div>
<br>
<br>
<?php endif;?>

<h3 class="title-form subtitle">
    <?= $title; ?>
    <div class="subtitle"><?= $element->city->state->name; ?>, <?= $element->city->name; ?></div>
</h3>

<br>

<div class="ui form mw-800 block-centered">

    <div class="ui grid stackable">

        <div class="two column row">

            <div class="column">
                <img class="ui fluid image" src="<?= $element->getImagePublicURL(ImagesRepositoryController::MODE_PUBLIC_IMAGE_FIXED, 400); ?>" alt="<?= $element->getFriendlyImageName(false); ?>">
            </div>

            <div class="column">

                <div class="field required">
                    <label><?= __($langGroup, 'Autor de la imagen'); ?></label>
                    <div>
                        <span><?= $element->author; ?></span>
                    </div>
                </div>

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

                <div class="field required">
                    <label><?= __($langGroup, 'Fecha de captura'); ?></label>
                    <div>
                        <span><?= $element->captureDateFormat('d-m-Y'); ?></span>
                    </div>
                </div>

                <div class="field required">
                    <label><?= __($langGroup, 'Descripción'); ?></label>
                    <div>
                        <?= $element->currentLangData('description'); ?>
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
                    <a href="<?= $element->getAuthorizationPublicURL(); ?>" download="<?= $element->authorizationName(false); ?>" target="_blank">
                        <i class="icon <?= $element->authorizationIconByExtension(); ?>"></i>
                        <?= __($langGroup, 'Descargar consentimiento'); ?>
                    </a>
                    <?php endif;?>
                </div>

                <div class="field clearfix">
                    <label><?= __($langGroup, 'Descargar fotografía'); ?></label>
                    <a href="<?= $element->getImagePublicURL(); ?>" download="<?= $element->getFriendlyImageName(false); ?>" class="ui button green labeled icon">
                        <i class="icon download"></i>
                        100%
                    </a>
                    <a href="<?= $element->getImagePublicURL(ImagesRepositoryController::MODE_PUBLIC_IMAGE_PERCENT, 75); ?>" download="<?= $element->getFriendlyImageName(false); ?>" class="ui button green labeled icon">
                        <i class="icon download"></i>
                        75%
                    </a>
                    <a href="<?= $element->getImagePublicURL(ImagesRepositoryController::MODE_PUBLIC_IMAGE_PERCENT, 50); ?>" download="<?= $element->getFriendlyImageName(false); ?>" class="ui button green labeled icon">
                        <i class="icon download"></i>
                        50%
                    </a>
                </div>

                <div class="two fields">

                    <?php if($hasEdit): ?>
                    <div class="field">
                        <a class="fluid ui custom-color button" href="<?= $editLink; ?>">
                            <?= __($langGroup, 'Editar'); ?>
                        </a>
                    </div>
                    <?php endif;?>

                    <?php if($hasDelete): ?>
                    <div class="field">
                        <a class="fluid ui grey button" delete-images-repository-button data-route="<?= $deleteRoute; ?>">
                            <?= __($langGroup, 'Eliminar'); ?>
                        </a>
                    </div>
                    <?php endif;?>

                </div>

            </div>

        </div>

    </div>

</div>
