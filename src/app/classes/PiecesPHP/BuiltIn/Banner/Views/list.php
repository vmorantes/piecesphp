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
            //La cabecera del panel (diseno/01-contenidos.md §2.1): el alta, arriba, como acción principal.
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
                'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Agregar banner'), 'url' => $addLink, 'icon' => 'plus'] : null,
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs">
            <div class="active item" data-tab="all"><?= __($langGroup, 'Todos'); ?></div>
        </div>

        <div class="ui tab tab-element active" data-tab="all">

            <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="ui fluid card container-standard-table all">

                <table url="<?= $processTableLink; ?>" class="ui selectable very basic table content-table all">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'Título'); ?></th>
                            <th><?= __($langGroup, 'Posición'); ?></th>
                            <th order="false"><?= __($langGroup, 'Imagen'); ?></th>
                            <th order="false"><?= __($langGroup, 'Imagen móviles'); ?></th>
                            <th order="false"><?= __($langGroup, 'Fecha activa'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>
