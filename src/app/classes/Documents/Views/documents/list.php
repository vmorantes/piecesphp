<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Documents\Controllers\DocumentsController;

/**
 * @var DocumentsController $this
 */
/**
 * @var string $langGroup
 * @var string $processTableLink
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
                'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Nuevo documento'), 'url' => $addLink, 'icon' => 'plus'] : null,
                'secondaryActions' => $hasPermissionsListDocumentsTypesLink ? [['text' => __($langGroup, 'Tipos de documento'), 'url' => $listDocumentsTypesLink, 'icon' => 'tags']] : [],
            ]);
        ?>

        <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="ui fluid card container-standard-table">

            <table url="<?= $processTableLink; ?>" class="ui selectable very basic table content-table">

                <thead>

                    <tr>
                        <th><?= __($langGroup, 'Nombre del documento'); ?></th>
                        <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
