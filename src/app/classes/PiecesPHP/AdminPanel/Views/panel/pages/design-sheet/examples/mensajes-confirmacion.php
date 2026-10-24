<?php
//La única forma de preguntar (confirmAction, helpers-lib/confirmAction.js): el toast de Fomantic con sus dos botones.
//Nunca los diálogos del navegador (confirm, alert): verify-integrity los rechaza.
?>
<button class="ui basic button" type="button" example-confirm data-title="<?= escape_html(__($langGroup, 'Confirmación')); ?>" data-message="<?= escape_html(__($langGroup, '¿Seguro de eliminar el elemento?')); ?>" data-confirm-text="<?= escape_html(__($langGroup, 'Sí')); ?>" data-cancel-text="<?= escape_html(__($langGroup, 'No')); ?>"><?= __($langGroup, 'Eliminar'); ?></button>
<span class="ui small grey text" example-confirm-answer></span>
