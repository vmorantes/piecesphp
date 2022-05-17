<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $editLink
 */

$langGroupDatatables = 'datatables';

?>
<section class="module-view-container">

    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns two">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Listado'); ?></div>
                </div>

            </div>

            <div class="column bottom right">

                <?php if ($hasPermissionsAdd):  ?>
                <a href="<?= $addLink; ?>" class="ui button brand-color"><?= __($langGroup, 'Agregar categoría'); ?></a>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="cards-container-standard">

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
    
</section>
