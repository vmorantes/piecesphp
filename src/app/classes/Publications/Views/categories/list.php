<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $editLink
 */;
$langGroup;
$editLink;

$langGroupDatatables = 'datatables';

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
        <a href="<?= $addLink; ?>" class="ui button custom-color"><?= __($langGroup, 'Agregar categoría'); ?></a>
        <?php endif; ?>

    </div>

</div>

<br>
<br>

<div class="container-cards-standard-list">

    <div class="table-to-cards">

        <div class="ui form component-controls">

            <div class="fields">

                <div class="field">

                    <label><?= __($langGroupDatatables, 'Buscador') ?></label>

                    <div class="ui icon input">
                        <input type="search" placeholder="<?= __($langGroupDatatables, 'Buscar') ?>">
                        <i class="search icon"></i>
                    </div>

                </div>

                <div class="field">

                    <label><?= __($langGroupDatatables, 'Resultados visibles') ?></label>
                    <input type="number" length-pagination placeholder="10">

                </div>

                <div class="field">

                    <label><?= __($langGroupDatatables, 'Ordenar por') ?>:</label>
                    <select class="ui dropdown" options-order></select>

                </div>

                <div class="field">

                    <label>&nbsp;</label>
                    <select class="ui dropdown" options-order-type>
                        <option selected value="ASC"><?= __($langGroupDatatables, 'ASC') ?></option>
                        <option value="DESC"><?= __($langGroupDatatables, 'DESC') ?></option>
                    </select>

                </div>

            </div>

        </div>

        <table url="<?= $processTableLink; ?>" style='display:none;'>

            <thead>

                <tr>
                    <th><?= __($langGroup, 'Nombre'); ?></th>
                </tr>

            </thead>

        </table>

    </div>

</div>
