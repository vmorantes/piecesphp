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

                <?php if ($hasPermissionsAdd):  ?>
                <a href="<?= $addLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Agregar publicación'); ?></a>
                <?php endif; ?>

                <?php if ($hasPermissionsListCategories):  ?>
                <a href="<?= $listCategoriesLink; ?>" class="ui button brand-color"><?= __($langGroup, 'Categorías'); ?></a>
                <?php endif; ?>

                <?php if ($hasPermissionsAddCategory):  ?>
                <a href="<?= $addCategoryLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Agregar categoría'); ?></a>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="container-standard-table">

        <table url="<?= $processTableLink; ?>" class="ui table striped celled">
            
            <thead>

                <tr>
                    <th><?= __($langGroup, '#'); ?></th>
                    <th><?= __($langGroup, 'Título'); ?></th>
                    <th><?= __($langGroup, 'Categoría'); ?></th>
                    <th><?= __($langGroup, 'Visitas'); ?></th>
                    <th><?= __($langGroup, 'Fecha'); ?></th>
                    <th><?= __($langGroup, 'Autor'); ?></th>
                    <th><?= __($langGroup, 'Estado'); ?></th>
                    <th><?= __($langGroup, 'Destacado'); ?></th>
                    <th order="no"><?= __($langGroup, 'Acciones'); ?></th>
                </tr>

            </thead>

        </table>

    </div>

</section>
