<?php
//El cargador por zona (helpers-lib/ZoneLoader.js): el atenuador del tema sobre la parte que se recarga, no la pantalla
//entera. showZoneLoader(zona) y removeZoneLoader(zona); la zona es un elemento, un selector o un jQuery.
?>
<div class="ui segment" example-zone>
    <p><?= __($langGroup, 'Una zona de contenido: un listado, unas tarjetas o un formulario que se recarga.'); ?></p>
    <p><?= __($langGroup, 'Fuera de ella, la página se sigue usando.'); ?></p>
</div>
<button class="ui basic button" type="button" example-zone-show><?= __($langGroup, 'Cargar esta zona'); ?></button>
<button class="ui basic button" type="button" example-zone-hide><?= __($langGroup, 'Quitar'); ?></button>
