<?php

$langGroup = 'NONE';
$buttonText = isset($buttonText) && is_string($buttonText) ? $buttonText : __($langGroup, 'Agregar archivo');
$inputNameAttr = isset($inputNameAttr) && is_string($inputNameAttr) ? $inputNameAttr : 'file';
$required = isset($required) && is_bool($required) ? $required : true;
$multiple = isset($multiple) && is_bool($multiple) ? $multiple : true;
$accept = isset($accept) && is_string($accept) ? $accept : null;
$icon = isset($icon) && is_string($icon) ? $icon : 'file outline';
$onlyButton = isset($onlyButton) && is_bool($onlyButton) ? $onlyButton : false;
//Va dentro de url() en un style: solo una ruta sin comillas, paréntesis ni espacios; si no, sin fondo.
$imagePreview = isset($imagePreview) && is_string($imagePreview) && preg_match('/^(?:https?:\/\/)?[A-Za-z0-9\/._~%?&=-]+\z/', $imagePreview) === 1 ? "'{$imagePreview}'" : null;
$classesButton = isset($classesButton) && is_string($classesButton) ? $classesButton : null;
//Opcionales, dentro de la caja: el título y la frase bajo el icono, y el enlace al archivo actual.
$title = isset($title) && is_string($title) ? $title : '';
$description = isset($description) && is_string($description) ? $description : '';
$currentFileURL = isset($currentFileURL) && is_string($currentFileURL) ? $currentFileURL : '';
$currentFileText = isset($currentFileText) && is_string($currentFileText) ? $currentFileText : '';

?>

<div class="simple-upload-placeholder<?= $onlyButton ? ' only-button' : ' ui placeholder segment'; ?>">
    <div class="preview">
        <div class="ui icon header placeholder-icon">
            <i class="icon <?= $icon; ?>"></i>
            <?php if ($title !== ''): ?>
            <?= escape_html($title); ?>
            <?php endif; ?>
            <?php if ($description !== ''): ?>
            <div class="sub header"><?= escape_html($description); ?></div>
            <?php endif; ?>
        </div>
        <div class="overlay-element<?= $imagePreview !== null ? ' image' : ''; ?>" style="<?= $imagePreview !== null ? escape_html("background-image: url($imagePreview);") : ''; ?>"></div>
    </div>
    <label file-label><?= $buttonText; ?></label>
    <input type="file" name="<?= $inputNameAttr; ?>" <?= $required ? 'required' : ''; ?> <?= $multiple ? 'multiple' : ''; ?> <?= $accept !== null ? "accept='{$accept}'" : ''; ?>>
    <?php if ($currentFileURL !== ''): ?>
    <a class="ui basic labeled icon button" target="_blank" rel="noopener" href="<?= escape_html($currentFileURL); ?>"><i class="external alternate icon" aria-hidden="true"></i><?= escape_html($currentFileText); ?></a>
    <?php endif; ?>
    <button type="button" class="ui<?= $classesButton === null ? ' primary' : ''; ?> labeled icon button trigger-file<?= $classesButton !== null ? " $classesButton" : ''; ?>">
        <i class="icon upload"></i>
        <span class="text"><?= $buttonText; ?></span>
    </button>
</div>
