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
            <a href="<?= $addLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Agregar organización'); ?></a>
            <?php endif; ?>

        </div>

        <br>

        <div class="tabs-controls">
            <div class="active" data-tab="actives"><?= __($langGroup, 'Activas'); ?></div>
            <div data-tab="inactives"><?= __($langGroup, 'Inactivas'); ?></div>
        </div>

        <div class="ui tab tab-element active" data-tab="actives">

            <div class="mirror-scroll-x actives" mirror-scroll-target=".container-standard-table.actives">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table actives">

                <table url="<?= $processTableActivesLink; ?>" class="ui basic table actives">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'NIT'); ?></th>
                            <th><?= __($langGroup, 'Nombre de la organización'); ?></th>
                            <th><?= __($langGroup, 'País'); ?></th>
                            <th><?= __($langGroup, 'Ciudad'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

        <div class="ui tab tab-element" data-tab="inactives">

            <div class="mirror-scroll-x inactives" mirror-scroll-target=".container-standard-table.inactives">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table inactives">

                <table url="<?= $processTableInactivesLink; ?>" class="ui basic table inactives">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, '#'); ?></th>
                            <th><?= __($langGroup, 'NIT'); ?></th>
                            <th><?= __($langGroup, 'Nombre de la organización'); ?></th>
                            <th><?= __($langGroup, 'Departamento'); ?></th>
                            <th><?= __($langGroup, 'Ciudad'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>
