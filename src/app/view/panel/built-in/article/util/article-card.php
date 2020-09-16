<?php
use PiecesPHP\BuiltIn\Article\Controllers\ArticleController;
$langGroup = ArticleController::LANG_GROUP;
?>
<div class="ui card">

    <div class="content">

        <div class="header">
            <?=  mb_strlen($mapper->title) > 50 ? trim(mb_substr($mapper->title, 0, 50)) . '...' : $mapper->title; ?>
        </div>

        <div class="ui divider"></div>

        <div class="description">

            <div><strong><?=__($langGroup, 'ID');?>:</strong> <?=  $mapper->id; ?></div>

            <div><strong><?=__($langGroup, 'Fechas');?>:</strong> </div>

            <?php if(!is_null($mapper->start_date)): ?>
            <div class="date">
                <?=__($langGroup, 'Inicio');?>:
                <?=  $mapper->start_date->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>
            <?php endif;?>

            <?php if(!is_null($mapper->end_date)): ?>
            <div class="date">
                <?=__($langGroup, 'Fin');?>:
                <?=  $mapper->end_date->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>
            <?php endif;?>

            <div class="date">
                <?=__($langGroup, 'Creado');?>:
                <?=  $mapper->created->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>

            <?php if(!is_null($mapper->updated)): ?>
            <div class="date">
                <?=__($langGroup, 'Modificado');?>:
                <?=  $mapper->updated->format(__('formatsDate', 'd-m-Y h:i:s A')); ?>
            </div>
            <?php endif;?>

            <div><strong><?=__($langGroup, 'Autor');?>:</strong> <?=  $mapper->author->username; ?></div>
            <div><strong><?=__($langGroup, 'Categoría');?>:</strong> <?=  $mapper->category->getName(); ?></div>
            <div><strong><?=__($langGroup, 'Visitas');?>:</strong> <?=  $mapper->visits > 0 ? $mapper->visits : '-'; ?></div>

        </div>
    </div>

    <?php if(mb_strlen($editLink) > 0): ?>
    <div class="extra content">

        <a class="fluid ui green button" href="<?= $editLink; ?>"><?= __($langGroup, 'Editar'); ?></a>

    </div>
    <?php endif;?>

</div>
