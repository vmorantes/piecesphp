<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ImagesRepository\Controllers\ImagesRepositoryController;
/**
 * @var ImagesRepositoryController $this
 */
/**
 * @var string $langGroup
 * @var string $editLink
 */
$langGroupDatatables = 'datatables';
?>

<div class="header-list">

    <div>

        <a href="<?=$backLink;?>" class="ui labeled icon button">
            <i class="icon left arrow"></i>
            <?=__($langGroup, 'Regresar');?>
        </a>

    </div>

    <h3 class="title-list subtitle small">
        <?=$title;?>
        <span class="subtitle"><?= $subtitle; ?></span>
    </h3>

</div>

<br>

<?php if(mb_strlen($formVariables['action']) > 0): ?>
<div class="ui card fluid content-form-card wide">

    <div class="content bg-grey">
        <div class="header"><?=__($langGroup, 'Cargar Imagen');?></div>
    </div>

    <div class="content">
        <?php $this->render($this::BASE_VIEW_DIR . '/forms/add', $formVariables); ?>
    </div>

</div>

<br>
<br>
<?php endif; ?>

<div class="container-cards-images-list">

    <div class="table-to-cards">

        <div class="ui form component-controls">

            <div class="fields">

                <div class="field">

                    <label><?= __($langGroupDatatables, 'Buscador') ?></label>

                    <div class="ui icon input">
                        <input type="search" placeholder="<?= __($langGroupDatatables, 'Buscar') ?>">
                        <i class="search icon"></i>
                    </div>

                </div>

                <div class="field">

                    <label><?= __($langGroupDatatables, 'Resultados visibles') ?></label>
                    <input type="number" length-pagination placeholder="10">

                </div>

                <div class="field">

                    <label><?= __($langGroupDatatables, 'Ordenar por') ?>:</label>
                    <select class="ui dropdown" options-order></select>

                </div>

                <div class="field">

                    <label>&nbsp;</label>
                    <select class="ui dropdown" options-order-type>
                        <option selected value="ASC"><?= __($langGroupDatatables, 'ASC') ?></option>
                        <option value="DESC"><?= __($langGroupDatatables, 'DESC') ?></option>
                    </select>

                </div>

            </div>

        </div>

        <table url="<?= $processTableLink; ?>" style='display:none;'>

            <thead>

                <tr>
                    <th><?=__($langGroup, 'N°');?></th>
                    <th order="false"><?=__($langGroup, 'Descripción');?></th>
                    <th order="false"><?=__($langGroup, 'Autor');?></th>
                </tr>

            </thead>

        </table>

    </div>

</div>
