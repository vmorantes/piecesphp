<?php

$langGroup = 'NONE';
$buttonText = isset($buttonText) && is_string($buttonText) ? $buttonText : __($langGroup, 'Agregar archivo');
$inputNameAttr = isset($inputNameAttr) && is_string($inputNameAttr) ? $inputNameAttr : 'file';
$required = isset($required) && is_bool($required) ? $required : true;
$multiple = isset($multiple) && is_bool($multiple) ? $multiple : true;
$accept = isset($accept) && is_string($accept) ? $accept : null;
$icon = isset($icon) && is_string($icon) ? $icon : 'file outline';

?>

<div class="simple-upload-placeholder">
    <div class="preview">
        <div class="placeholder-icon">
            <i class="icon <?= $icon; ?>"></i>
        </div>
        <div class="overlay-element"></div>
    </div>
    <label file-label><?= $buttonText; ?></label>
    <input type="file" name="<?= $inputNameAttr; ?>" <?= $required ? 'required' : ''; ?> <?= $multiple ? 'multiple' : ''; ?> <?= $accept !== null ? "accept='{$accept}'" : ''; ?>>
    <button class="ui button trigger-file icon labeled">
        <i class="icon upload"></i>
        <span class="text"><?= $buttonText; ?></span>
    </button>
</div>
