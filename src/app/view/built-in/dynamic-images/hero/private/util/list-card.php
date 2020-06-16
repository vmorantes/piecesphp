<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\BuiltIn\DynamicImages\Informative\Mappers\ImageMapper;

/**
 * @var ImageMapper $mapper
 */
$mapper;

/**
 * @var string $langGroup
 * @var string $editLink
 */;
$langGroup;
$editLink;

?>

<div class="ui card">

    <div class="content">

        <div class="header">
            <?= $mapper->title; ?>
        </div>

        <br>

        <div class="image">
            <img src="<?= $mapper->image; ?>">
        </div>

        <br>

        <div class="description">

            <div>
                <?= $mapper->description; ?> <br>
                <?php if($mapper->start_date !== null): ?>
                <strong><?= __($langGroup, 'Fecha inicial'); ?>: </strong>
                <?= $mapper->start_date->format('d-m-Y h:i A'); ?>
                <br>
                <?php endif; ?>
                <?php if($mapper->end_date !== null): ?>
                <strong><?= __($langGroup, 'Fecha final'); ?>: </strong>
                <?= $mapper->end_date->format('d-m-Y h:i A'); ?>
                <br>
                <?php endif; ?>
                <strong><?= __($langGroup, 'Orden'); ?>: </strong>
                <?= $mapper->order > 0 ? $mapper->order . '.' : '<span>0.</span>'; ?>
                <br>
            </div>

            <br>

            <div>

                <?php if(strlen($editLink) > 0): ?>
                <a class="fluid ui olive button icon" href="<?= $editLink; ?>">
                    <i class="icon plus"></i> &nbsp; <?= __($langGroup, 'Editar'); ?>
                </a>
                <?php endif;?>

            </div>

        </div>

    </div>

</div>
