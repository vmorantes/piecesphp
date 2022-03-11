<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $editLink
 */
$langGroup;
$editLink;

?>
<div class="header-list">

    <h3 class="title-list">
        <strong><?= $title; ?></strong>
    </h3>

    <div class="container-buttons">

        <a href="<?= $backLink; ?>" class="ui labeled icon button custom-color">
            <i class="icon left arrow"></i>
            <?= __($langGroup, 'Regresar'); ?>
        </a>

        <?php if ($hasPermissionsAdd):  ?>
        <a href="<?= $addLink; ?>" class="ui button custom-color"><?= __($langGroup, 'Agregar suscriptor'); ?></a>
        <?php endif; ?>

    </div>

</div>

<br>
<br>

<div class="mirror-scroll-x" mirror-scroll-target=".container-table-standard-list">
    <div class="mirror-scroll-x-content"></div>
</div>

<div class="container-table-standard-list">

    <table url="<?= $processTableLink; ?>" class="ui table stripped celled">

        <thead>

            <tr>
                <th><?= __($langGroup, '#'); ?></th>
                <th><?= __($langGroup, 'Nombre'); ?></th>
                <th><?= __($langGroup, 'Email'); ?></th>
                <th><?= __($langGroup, 'Acepta recibir correos'); ?></th>
                <th order="no" search="no"> <?= __($langGroup, 'Acciones'); ?></th>
            </tr>

        </thead>

    </table>

</div>
