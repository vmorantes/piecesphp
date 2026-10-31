<?php //Cada panel lleva step; los botones, data-to-step (un paso, next o previous). Lo mueve GenericStepsViewHandler. ?>
<div example-steps>
    <div class="ui three steps" example-steps-indicators>
        <div class="step" data-step="1"><div class="content"><div class="title"><?= __($langGroup, 'Datos'); ?></div></div></div>
        <div class="step" data-step="2"><div class="content"><div class="title"><?= __($langGroup, 'Imágenes'); ?></div></div></div>
        <div class="step" data-step="3"><div class="content"><div class="title"><?= __($langGroup, 'Revisar'); ?></div></div></div>
    </div>
    <div class="ui segment" step="1" example-step><?= __($langGroup, 'Paso 1: los datos del registro.'); ?></div>
    <div class="ui segment" step="2" example-step><?= __($langGroup, 'Paso 2: sus imágenes.'); ?></div>
    <div class="ui segment" step="3" example-step><?= __($langGroup, 'Paso 3: revisar antes de guardar.'); ?></div>
    <button type="button" class="ui basic button" data-to-step="previous"><?= __($langGroup, 'Anterior'); ?></button>
    <button type="button" class="ui primary button" data-to-step="next"><?= __($langGroup, 'Siguiente'); ?></button>
</div>
