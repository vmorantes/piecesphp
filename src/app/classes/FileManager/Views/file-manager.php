<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $configurationRoute
 */;
$langGroup;
$configurationRoute;

?>

<div style="max-width:850px;">

    <h3>
        <strong><?= $title; ?></strong>
    </h3>

    <div>

        <a href="<?= $backLink; ?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

    </div>

</div>

<br>
<br>

<div class="filemanager-container">
    <div class="filemanager-component" data-route="<?= $configurationRoute; ?>" data-base-url="<?= baseurl('statics/plugins/elfinder'); ?>"></div>
</div>
