<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $editLink
 */

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

                <?php if ($hasPermissionsListCategories) :  ?>
                    <a href="<?= $listCategoriesLink; ?>" class="ui button brand-color"><?= __($langGroup, 'Gestionar categorías'); ?></a>
                <?php endif; ?>

                <?php if ($hasPermissionsAdd) :  ?>
                    <a href="<?= $addLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Nueva noticia'); ?></a>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="container-standard-table">

        <table style="max-width: 100%;" url="<?= $processTableLink; ?>" class="ui basic table">

            <thead>

                <tr>
                    <th><?= __($langGroup, '#'); ?></th>
                    <th><?= __($langGroup, 'Título'); ?></th>
                    <th><?= __($langGroup, 'Categoría'); ?></th>
                    <th><?= __($langGroup, 'Fecha de inicio'); ?></th>
                    <th><?= __($langGroup, 'Fecha de final'); ?></th>
                    <th><?= __($langGroup, 'Estado'); ?></th>
                    <th order="no"><?= __($langGroup, 'Acciones'); ?></th>
                </tr>

            </thead>

        </table>

    </div>

</section>
