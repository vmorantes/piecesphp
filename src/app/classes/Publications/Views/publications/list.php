<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $editLink
 */

?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1): una acción principal y las demás, básicas.
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
            'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Nueva publicación'), 'url' => $addLink, 'icon' => 'plus'] : null,
            'secondaryActions' => $hasPermissionsListCategories ? [['text' => __($langGroup, 'Categorías'), 'url' => $listCategoriesLink, 'icon' => 'tags']] : [],
        ]);
        ?>

        <div class="tabs-controls content-tabs">
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

                <table url="<?= $processTableLink; ?>" class="ui basic table content-table all">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Status'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Visitas'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
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

                <table url="<?= $processTablePublicatedLink; ?>" class="ui basic table content-table publicated">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Status'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Visitas'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
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

                <table url="<?= $processTableScheduledLink; ?>" class="ui basic table content-table scheduled">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Status'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Visitas'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
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

                <table url="<?= $processTableDraftLink; ?>" class="ui basic table content-table draft">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Status'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Autor'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Visitas'); ?></th>
                            <th class-name="collapsing"><?= __($langGroup, 'Destacado'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>
