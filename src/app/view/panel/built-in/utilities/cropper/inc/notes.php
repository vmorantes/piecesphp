<?php

    $defaultNotes = [
        'showCropDimensions' => true, 
        'showOutputDimensions' => true, 
        'showMinWidthOutput' => true, 
    ];

    $notes = isset($notes) && is_array($notes) ? $notes : [];

    $settedNotes = [];

    $note = 'showCropDimensions';
    $settedNotes[$note] = isset($notes[$note]) && is_bool($notes[$note]) ? $notes[$note] : $defaultNotes[$note];

    $note = 'showOutputDimensions';
    $settedNotes[$note] = isset($notes[$note]) && is_bool($notes[$note]) ? $notes[$note] : $defaultNotes[$note];

    $note = 'showMinWidthOutput';
    $settedNotes[$note] = isset($notes[$note]) && is_bool($notes[$note]) ? $notes[$note] : $defaultNotes[$note];

    $visible = $settedNotes['showCropDimensions'] || $settedNotes['showOutputDimensions'] || $settedNotes['showMinWidthOutput'];

?>

<?php if($visible): ?>
<p class="note">
    <?php if($settedNotes['showCropDimensions']): ?>
    <em><small><span show-crop-dimensions></span></small></em>
    <?php endif; ?>
    <?php if($settedNotes['showOutputDimensions']): ?>
    <strong><small><?= __(CROPPER_ADAPTER_LANG_GROUP, 'La imagen se guardará con las dimensiones:'); ?> <span show-output></span></small></strong>
    <?php endif; ?>
    <?php if($settedNotes['showMinWidthOutput']): ?>
    <strong><small><?= __(CROPPER_ADAPTER_LANG_GROUP, 'El ancho mínimo es'); ?> <span min-w-output></span></small></strong>
    <?php endif; ?>
</p>
<?php endif; ?>
