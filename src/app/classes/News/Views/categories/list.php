<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use News\Controllers\NewsCategoryController;

/**
 * @var NewsCategoryController $this
 */
/**
 * @var string $langGroup
 * @var string $editLink
 */
$langGroupDatatables = 'datatables';
?>
<section class="module-view-container limit-size">

    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Listado'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <?php if (mb_strlen($formVariables['action']) > 0) : ?>
        <?php $this->render($this::BASE_VIEW_DIR . '/forms/add', $formVariables); ?>
        <br>
        <br>
    <?php endif; ?>

    <div class="mirror-scroll-x" mirror-scroll-target=".container-standard-table">
        <div class="mirror-scroll-x-content"></div>
    </div>

    <div class="container-standard-table">

        <table style="max-width: 100%;" url="<?= $processTableLink; ?>" class="ui basic table">

            <thead>

                <tr>
                    <th><?= __($langGroup, '#'); ?></th>
                    <th><?= __($langGroup, 'Categoría'); ?></th>
                    <th order="false"><?= __($langGroup, 'Color'); ?></th>
                    <th order="false"><?= __($langGroup, 'Imagen'); ?></th>
                    <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                </tr>

            </thead>

        </table>

    </div>

</section>
