<?php

    $defaultControls = [
        'rotate' => true, 
        'flip' => true, 
        'adjust' => true, 
    ];

    $controls = isset($controls) && is_array($controls) ? $controls : [];

    $settedControls = [];

    $control = 'rotate';
    $settedControls[$control] = isset($controls[$control]) && is_bool($controls[$control]) ? $controls[$control] : $defaultControls[$control];

    $control = 'flip';
    $settedControls[$control] = isset($controls[$control]) && is_bool($controls[$control]) ? $controls[$control] : $defaultControls[$control];

    $control = 'adjust';
    $settedControls[$control] = isset($controls[$control]) && is_bool($controls[$control]) ? $controls[$control] : $defaultControls[$control];

?>
<div class="options">

    <?php if($settedControls['rotate']): ?>
    <div class="option" data-option="rotate">

        <div class="icon">
            <i class="sync alternate icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Girar'); ?></div>

    </div>
    <?php endif; ?>

    <?php if($settedControls['flip']): ?>
    <div class="option" data-option="flip">

        <div class="icon">
            <i class="exchange alternate icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Voltear'); ?></div>

    </div>
    <?php endif; ?>

    <?php if($settedControls['adjust']): ?>
    <div class="option" data-option="adjust">

        <div class="icon">
            <i class="expand icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Ajustar'); ?></div>

    </div>
    <?php endif; ?>

    <div class="option" load-image>

        <div class="icon">
            <i class="image outline icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Cambiar'); ?></div>

    </div>

</div>

<div class="sub-options" data-name="rotate">

    <div class="option" back-options>

        <div class="icon">
            <i class="arrow left icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Atrás'); ?></div>

    </div>

    <div class="option" action-rotate-left>

        <div class="icon">
            <i class="undo icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Izquierda'); ?></div>

    </div>

    <div class="option" action-rotate-right>

        <div class="icon">
            <i class="redo icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Derecha'); ?></div>

    </div>

</div>

<div class="sub-options" data-name="flip">

    <div class="option" back-options>

        <div class="icon">
            <i class="arrow left icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Atrás'); ?></div>

    </div>

    <div class="option" action-flip-horizontal>

        <div class="icon">
            <i class="arrows alternate horizontal icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Horizontal'); ?></div>

    </div>

    <div class="option" action-flip-vertical>

        <div class="icon">
            <i class="arrows alternate vertical icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Vertical'); ?></div>

    </div>

</div>

<div class="sub-options" data-name="adjust">

    <div class="option" back-options>

        <div class="icon">
            <i class="arrow left icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Atrás'); ?></div>

    </div>

    <div class="option" action-move-up>

        <div class="icon">
            <i class="arrow alternate circle up outline icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Arriba'); ?></div>

    </div>

    <div class="option" action-move-down>

        <div class="icon">
            <i class="arrow alternate circle down outline icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Abajo'); ?></div>

    </div>

    <div class="option" action-move-left>

        <div class="icon">
            <i class="arrow alternate circle left outline icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Izquierda'); ?></div>

    </div>

    <div class="option" action-move-right>

        <div class="icon">
            <i class="arrow alternate circle right outline icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Derecha'); ?></div>

    </div>

    <div class="option" action-zoom-out>

        <div class="icon">
            <i class="search minus icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Alejar'); ?></div>

    </div>

    <div class="option" action-zoom-in>

        <div class="icon">
            <i class="search plus icon"></i>
        </div>
        <div class="text"><?= __(CROPPER_ADAPTER_LANG_GROUP,'Acercar'); ?></div>

    </div>

</div>
