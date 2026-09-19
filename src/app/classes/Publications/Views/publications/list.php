<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $editLink
 */

?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="main-buttons">

            <?php if ($hasPermissionsAdd) :  ?>
            <a href="<?= $addLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Agregar publicación'); ?></a>
            <?php endif; ?>

            <?php if ($hasPermissionsListCategories) :  ?>
            <a href="<?= $listCategoriesLink; ?>" class="ui button brand-color"><?= __($langGroup, 'Categorías'); ?></a>
            <?php endif; ?>

            <?php if ($hasPermissionsAddCategory) :  ?>
            <a href="<?= $addCategoryLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Agregar categoría'); ?></a>
            <?php endif; ?>

        </div>

        <br>

        <div class="tabs-controls">
            <div class="active" data-tab="all"><?= __($langGroup, 'TODAS_LAS_PUBLICACIONES'); ?></div>
            <div data-tab="publicated"><?= __($langGroup, 'PUBLICACIONES_PUBLICADAS'); ?></div>
            <div data-tab="scheduled"><?= __($langGroup, 'PUBLICACIONES_PROGRAMADAS'); ?></div>
            <div data-tab="draft"><?= __($langGroup, 'PUBLICACIONES_BORRADOR'); ?></div>
        </div>

        <div class="ui tab tab-element active" data-tab="all">

            <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table all">

                <table url="<?= $processTableLink; ?>" class="ui basic table all">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th><?= __($langGroup, 'Categoría'); ?></th>
                            <th><?= __($langGroup, 'Visitas'); ?></th>
                            <th><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th><?= __($langGroup, 'Status'); ?></th>
                            <th><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="publicated">

            <div class="mirror-scroll-x publicated" mirror-scroll-target=".container-standard-table.publicated">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table publicated">

                <table url="<?= $processTablePublicatedLink; ?>" class="ui basic table publicated">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th><?= __($langGroup, 'Categoría'); ?></th>
                            <th><?= __($langGroup, 'Visitas'); ?></th>
                            <th><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th><?= __($langGroup, 'Status'); ?></th>
                            <th><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="scheduled">

            <div class="mirror-scroll-x scheduled" mirror-scroll-target=".container-standard-table.scheduled">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table scheduled">

                <table url="<?= $processTableScheduledLink; ?>" class="ui basic table scheduled">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th><?= __($langGroup, 'Categoría'); ?></th>
                            <th><?= __($langGroup, 'Visitas'); ?></th>
                            <th><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th><?= __($langGroup, 'Status'); ?></th>
                            <th><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="draft">

            <div class="mirror-scroll-x draft" mirror-scroll-target=".container-standard-table.draft">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table draft">

                <table url="<?= $processTableDraftLink; ?>" class="ui basic table draft">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th><?= __($langGroup, 'Categoría'); ?></th>
                            <th><?= __($langGroup, 'Visitas'); ?></th>
                            <th><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th><?= __($langGroup, 'Status'); ?></th>
                            <th><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>
