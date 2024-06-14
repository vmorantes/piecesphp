<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 */
$langGroupDatatables = 'datatables';
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

            <?php if ($hasPermissionsAdd):  ?>
            <a href="<?= $addLink; ?>" class="ui button brand-color"><?= __($langGroup, 'Agregar categoría'); ?></a>
            <?php endif; ?>

        </div>

        <br>

        <div class="cards-container-standard">

            <div class="table-to-cards">

                <div class="ui form component-controls">

                    <div class="flex-fields">

                        <div class="field">

                            <div class="length-pagination">
                                <span><?= __($langGroupDatatables, 'Ver') ?></span>
                                <input type="number" length-pagination placeholder="10">
                                <span><?= __($langGroupDatatables, 'elementos') ?></span>
                            </div>

                        </div>

                        <div class="field">

                            <div class="ui icon input">
                                <input type="search" placeholder="<?= __($langGroupDatatables, 'Buscar') ?>">
                                <i class="search icon"></i>
                            </div>

                        </div>

                    </div>

                </div>

                <table url="<?= $processTableLink; ?>" style='display:none;'>

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Nombre'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>
    </div>

</section>
