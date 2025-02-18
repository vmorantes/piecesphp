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
            <a href="<?= $addLink; ?>" class="ui button brand-color alt"><?= __($langGroup, 'Agregar banner'); ?></a>
            <?php endif; ?>

        </div>

        <br>

        <div class="tabs-controls">
            <div class="active" data-tab="all"><?= __($langGroup, 'Todos'); ?></div>
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
                            <th><?= __($langGroup, 'Posición'); ?></th>
                            <th order="false"><?= __($langGroup, 'Imagen'); ?></th>
                            <th order="false"><?= __($langGroup, 'Imagen móviles'); ?></th>
                            <th order="false"><?= __($langGroup, 'Fecha activa'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>
