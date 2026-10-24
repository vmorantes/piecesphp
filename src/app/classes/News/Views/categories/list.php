<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use News\Controllers\NewsCategoryController;

/**
 * @var NewsCategoryController $this
 */
/**
 * @var string $langGroup
 * @var string $processTableLink
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1). Categoría compleja (§2.6): alta en su página.
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Nueva categoría'), 'url' => $addLink, 'icon' => 'plus'] : null,
        ]);
        ?>

        <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="container-standard-table all">

            <table url="<?= $processTableLink; ?>" class="ui basic table content-table all">

                <thead>

                    <tr>
                        <th><?= __($langGroup, 'Categoría'); ?></th>
                        <th order="false" class-name="collapsing"><?= __($langGroup, 'Color'); ?></th>
                        <th order="false" class-name="collapsing"><?= __($langGroup, 'Imagen'); ?></th>
                        <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
