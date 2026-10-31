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
                'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Nueva noticia'), 'url' => $addLink, 'icon' => 'plus'] : null,
                'secondaryActions' => $hasPermissionsListCategories ? [['text' => __($langGroup, 'Categorías'), 'url' => $listCategoriesLink, 'icon' => 'tags']] : [],
            ]);
        ?>

        <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="ui fluid card container-standard-table all">

            <table url="<?= $processTableLink; ?>" class="ui selectable very basic table content-table all">

                <thead>

                    <tr>
                        <th><?= __($langGroup, 'Título'); ?></th>
                        <th class-name="collapsing"><?= __($langGroup, 'Estado'); ?></th>
                        <th class-name="collapsing"><?= __($langGroup, 'Fecha de inicio'); ?></th>
                        <th class-name="collapsing"><?= __($langGroup, 'Fecha de final'); ?></th>
                        <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
