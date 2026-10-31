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
                'description' => $description,
                'primaryAction' => $hasPermissionsAdd ? ['text' => __($langGroup, 'Agregar organización'), 'url' => $addLink, 'icon' => 'plus'] : null,
            ]);
        ?>

        <div class="ui secondary pointing menu tabs-controls content-tabs">
            <div class="active item" data-tab="actives"><?= __($langGroup, 'Activas'); ?></div>
            <div class="item" data-tab="inactives"><?= __($langGroup, 'Inactivas'); ?></div>
            <div class="item" data-tab="pendings"><?= __($langGroup, 'Pendientes de aprobación'); ?></div>
        </div>

        <div class="ui tab tab-element active" data-tab="actives">

            <div class="mirror-scroll-x actives" mirror-scroll-target=".container-standard-table.actives">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="ui fluid card container-standard-table actives">

                <table url="<?= $processTableActivesLink; ?>" class="ui selectable very basic table content-table actives">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Código'); ?></th>
                            <th><?= __($langGroup, 'NIT'); ?></th>
                            <th><?= __($langGroup, 'Nombre de la organización'); ?></th>
                            <th><?= __($langGroup, 'País'); ?></th>
                            <th><?= __($langGroup, 'Ciudad'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="inactives">

            <div class="mirror-scroll-x inactives" mirror-scroll-target=".container-standard-table.inactives">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="ui fluid card container-standard-table inactives">

                <table url="<?= $processTableInactivesLink; ?>" class="ui selectable very basic table content-table inactives">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Código'); ?></th>
                            <th><?= __($langGroup, 'NIT'); ?></th>
                            <th><?= __($langGroup, 'Nombre de la organización'); ?></th>
                            <th><?= __($langGroup, 'Departamento'); ?></th>
                            <th><?= __($langGroup, 'Ciudad'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="pendings">

            <div class="mirror-scroll-x pendings" mirror-scroll-target=".container-standard-table.pendings">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="ui fluid card container-standard-table pendings">

                <table url="<?= $processTablePendingsLink; ?>" class="ui selectable very basic table content-table pendings">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Código'); ?></th>
                            <th><?= __($langGroup, 'NIT'); ?></th>
                            <th><?= __($langGroup, 'Nombre de la organización'); ?></th>
                            <th><?= __($langGroup, 'Departamento'); ?></th>
                            <th><?= __($langGroup, 'Ciudad'); ?></th>
                            <th order="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>
