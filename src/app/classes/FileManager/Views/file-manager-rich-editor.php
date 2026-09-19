<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $configurationRoute
 */

?>
<div class="filemanager-container" style="min-height: 100vh;">
    <div style="height: 100% !important;" class="filemanager-component" data-route="<?= $configurationRoute; ?>" data-base-url="<?= baseurl('statics/plugins/elfinder'); ?>"></div>
</div>
