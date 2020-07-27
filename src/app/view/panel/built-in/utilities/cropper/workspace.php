<?php

$image = isset($image) && is_string($image) ? $image : '';

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
    <button class="ui button blue" type="button" start></button>
</div>

<div class="workspace">

    <div class="steps">

        <div class="step add">

            <div class="ui header medium centered"><?= __('cropper', 'Agregar imagen'); ?></div>

            <div class="placeholder">

                <div class="content">
                    <div>
                        <i class="upload icon"></i>
                        <button class="ui button blue fluid" type="button" load-image><?= __('cropper', 'Seleccionar imagen'); ?></button>
                    </div>
                </div>

            </div>

        </div>

        <div class="step edit">

            <?php if ($withTitle): ?>
            <div class="field required">
                <label><?= __('cropper', 'Título de la imagen'); ?></label>
                <input type="text" cropper-title-export>
            </div>
			<?php else:?>
				<input type="hidden" cropper-title-export>
            <?php endif;?>

            <div class="field">
                <canvas data-image='<?=$image?>'></canvas>
            </div>

        </div>

    </div>

    <?php $this->_render('panel/built-in/utilities/cropper/controls.php');?>
    <?php $this->_render('panel/built-in/utilities/cropper/main-buttons.php');?>

</div>
