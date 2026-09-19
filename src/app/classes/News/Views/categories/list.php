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

        <?php if (mb_strlen($formVariables['action']) > 0) : ?>
        <?php $this->render('forms/add', $formVariables); ?>
        <br>
        <br>
        <?php endif; ?>

        <br>

        <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="container-standard-table all">

            <table url="<?= $processTableLink; ?>" class="ui basic table all">

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

    </div>

</section>