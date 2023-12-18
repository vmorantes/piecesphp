<?php
$selectorAttr = isset($selectorAttr) && is_string($selectorAttr) ? $selectorAttr : 'simple-cropper';
$image = isset($image) && is_string($image) ? $image : '';
$required = isset($required) && $required === true ? $required : false;
$type = isset($type) &&  is_string($type) ? $type : null;
$referenceImage = mb_strlen($image) > 0 ? $image : baseurl("img-gen/{$referenceW}/{$referenceH}");
$referenceW = isset($referenceW) && is_string($referenceW)  && is_numeric($referenceW) ? (int) $referenceW : 400;
$referenceH = isset($referenceH) && is_string($referenceH)  && is_numeric($referenceH) ? (int) $referenceH : 400;
$loadText = isset($loadText) && is_string($loadText)  ? $loadText : __(CROPPER_ADAPTER_LANG_GROUP, 'Cargar imagen');
$cancelText = isset($cancelText) && is_string($cancelText)  ? $cancelText : __(CROPPER_ADAPTER_LANG_GROUP, 'Cancelar');
$cropText = isset($cropText) && is_string($cropText)  ? $cropText : __(CROPPER_ADAPTER_LANG_GROUP, 'Guardar');
?>
<div class="simple-cropper centered" <?= $selectorAttr; ?>>

    <div class="container">

        <input type="file" file<?= $type !== null ? " accept={$type}" : ''; ?><?= $required ? ' required' : ''; ?>>

        <div class="image-container">
            <img class="preview" src="<?= $referenceImage; ?>" <?= mb_strlen($image) > 0 ? ' is-final' : ''; ?> />
        </div>

        <div class="controls edition">
            <div class="rotate-buttons">
                <span rotate-right>
                    <i class="redo icon"></i>
                </span>
                <span rotate-left>
                    <i class="undo icon"></i>
                </span>
            </div>

            <div class="resize-slider">
                <i class="icon image out"></i>
                <div class="ui slider" resize-slider></div>
                <i class="icon image in"></i>
            </div>

        </div>

        <div class="controls finalize">
            <a href="#" load-image>
                <i class="icon image"></i>
                <?= $loadText; ?>
            </a>
            <div class="crop-buttons">
                <button class="ui basic button" cancel><?= $cancelText; ?></button>
                <button class="ui brand-color button" crop><?= $cropText; ?></button>
            </div>
        </div>

    </div>
</div>
