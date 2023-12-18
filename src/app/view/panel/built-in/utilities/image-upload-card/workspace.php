<?php
use App\Controller\AppConfigController;
$langGroup = AppConfigController::LANG_GROUP;
$image = isset($image) && is_string($image) ? $image : '';
$imageAlt = isset($imageAlt) && is_string($imageAlt) ? $imageAlt : '';
$classes = isset($classes) && is_string($classes) ? $classes : '';
$imageActionAttrs = isset($imageActionAttrs) && is_string($imageActionAttrs) ? $imageActionAttrs : '';
$changeImageText = isset($changeImageText) && is_string($changeImageText)  ? $changeImageText : __($langGroup, 'Cambiar imagen');
$title = isset($title) && is_string($title)  ? $title : __($langGroup, 'Imagen');
$description = isset($description) && is_string($description)  ? $description : __($langGroup, "Imagen preferiblemente de fondo transparente");
$width = isset($width) && is_numeric($width)  ? (int) $width : 400;
$height = isset($height) && is_numeric($height)  ? (int) $height : 400;
?>
<div class="input-image-card">
    <div <?= $imageActionAttrs; ?> class="image-action <?= $classes; ?>">
        <img src="<?= $image; ?>" alt="<?= $imageAlt; ?>">
        <div class="hover-content">
            <i class="upload icon"></i>
            <span><?= $changeImageText; ?></span>
        </div>
    </div>
    <div class="info">
        <span><?= $title; ?></span>
        <small><?= strReplaceTemplate(__($langGroup, 'Tamaño ${w}x${h}px'), ['${w}' => $width, '${h}' => $height,]); ?></small>
        <p><?= $description; ?></p>
    </div>
</div>
