<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Persons\Controllers\PersonsController;
/**
 * @var PersonsController $this
 */
/**
 * @var string $langGroup
 * @var string $editLink
 */
$langGroupDatatables = 'datatables';
?>

<div class="header-list">

    <div class="global-clearfix">

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

    <div class="content">
        <?php $this->render($this::BASE_VIEW_DIR . '/forms/add', $formVariables); ?>
    </div>

</div>

<br>
<br>
<?php endif; ?>

<div class="mirror-scroll-x" mirror-scroll-target=".container-table-standard-list">
    <div class="mirror-scroll-x-content"></div>
</div>

<div class="container-table-standard-list">

    <table url="<?= $processTableLink; ?>" class="ui table stripped celled">

        <thead>

            <tr>
                <th><?= __($langGroup, 'Identificación'); ?></th>
                <th><?= __($langGroup, 'Nombres y apellidos'); ?></th>
                <th><?= __($langGroup, 'Acciones'); ?></th>
            </tr>

        </thead>

    </table>

</div>
