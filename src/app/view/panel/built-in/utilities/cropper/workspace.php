<?php

$image = isset($image) && is_string($image) ? $image : '';
$imageName = isset($imageName) && is_string($imageName) ? $imageName : '';
$hideStartButton = isset($hideStartButton) && $hideStartButton === true;

if (isset($withTitle)) {
    $withTitle = $withTitle === true;
} else {
    $withTitle = true;
}

if (isset($referenceW)) {
    if (is_string($referenceW) && is_numeric($referenceW)) {
        $referenceW = (int) $referenceW;
    }
}

if (isset($referenceH)) {
    if (is_string($referenceH) && is_numeric($referenceH)) {
        $referenceH = (int) $referenceH;
    }
}

$referenceW = !is_int($referenceW) ? 1920 : $referenceW;
$referenceH = !is_int($referenceH) ? 1080 : $referenceH;

?>

<div class="preview" w="<?=$referenceW;?>">
    <img src="<?="img-gen/$referenceW/$referenceH";?>">
    <button <?= $hideStartButton ? "style='display:none;'" : ''; ?> class="ui button blue" type="button" start></button>
</div>

<div class="workspace">

    <div class="steps">

        <div class="step add">

            <div class="ui header medium centered"><?= __(CROPPER_ADAPTER_LANG_GROUP, 'Agregar imagen'); ?></div>

            <div class="placeholder">

                <div class="content">
                    <div>
                        <i class="upload icon"></i>
                        <button class="ui button blue fluid" type="button" load-image><?= __(CROPPER_ADAPTER_LANG_GROUP, 'Seleccionar imagen'); ?></button>
                    </div>
                </div>

            </div>

        </div>

        <div class="step edit">

            <?php if ($withTitle): ?>
            <div class="field required">
                <label><?= __(CROPPER_ADAPTER_LANG_GROUP, 'Título de la imagen'); ?></label>
                <input type="text" cropper-title-export<?= mb_strlen($imageName)> 0 ? " value='{$imageName}'" : ''; ?>>
            </div>
            <?php else:?>
            <input type="hidden" cropper-title-export<?= mb_strlen($imageName) > 0 ? " value='{$imageName}'" : ''; ?>>
            <?php endif;?>

            <div class="field">
                <canvas data-image='<?=$image?>'></canvas>
            </div>

        </div>

    </div>

    <?php $this->_render('panel/built-in/utilities/cropper/controls.php', [
        'notes' => isset($notes) && is_array($notes) ? $notes : null,
        'controls' => isset($controls) && is_array($controls) ? $controls : null,
    ]);?>
    <?php $this->_render('panel/built-in/utilities/cropper/main-buttons.php', [
        'cancelButtonText' => isset($cancelButtonText) && is_string($cancelButtonText) ? $cancelButtonText : __(CROPPER_ADAPTER_LANG_GROUP, 'Cancelar'),
        'saveButtonText' => isset($saveButtonText) && is_string($saveButtonText) ? $saveButtonText : __(CROPPER_ADAPTER_LANG_GROUP, 'Guardar imagen'),
    ]);?>

</div>
