<?php
use App\Controller\AppConfigController;
$langGroup = AppConfigController::LANG_GROUP;
$content = isset($content) && is_string($content) ? $content : '';
$modalContainerAttrs = isset($modalContainerAttrs) && is_string($modalContainerAttrs) ? $modalContainerAttrs : '';
$modalContainerClasses = isset($modalContainerClasses) && is_string($modalContainerClasses) ? $modalContainerClasses : '';
$modalContentElementAttrs = isset($modalContentElementAttrs) && is_string($modalContentElementAttrs) ? $modalContentElementAttrs : '';
$informationContentMainClass = isset($informationContentMainClass) && is_string($informationContentMainClass) ? $informationContentMainClass : 'cropper-info-content';
$informationContentClasses = isset($informationContentClasses) && is_string($informationContentClasses) ? $informationContentClasses : '';
$titleModal = isset($titleModal) && is_string($titleModal)  ? $titleModal : __($langGroup, 'Editar imagen');
$descriptionModal = isset($descriptionModal) && is_string($descriptionModal)  ? $descriptionModal : __($langGroup, 'Edite la imagen moviendo el recuadro de recorte, girándola o redimensionándola');
?>
<div <?= $modalContainerAttrs; ?> class="<?= $modalContainerClasses; ?>">
    <div <?= $modalContentElementAttrs; ?> class="content">
        <div class="<?= $informationContentMainClass; ?> <?= $informationContentClasses; ?>">
            <span><?= $titleModal; ?></span>
            <p><?= $descriptionModal;?></p>
        </div>
        <?= $content; ?>
    </div>
</div>
