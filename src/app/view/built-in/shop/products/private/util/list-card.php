<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\BuiltIn\Shop\Product\Mappers\ProductMapper;

/**
 * @var ProductMapper $mapper
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
            <?= $mapper->name; ?>
        </div>

        <br>

        <div class="image">
            <img src="<?= $mapper->main_image; ?>">
        </div>

        <br>

        <div class="description">

            <div>
                <strong><?= __($langGroup, 'Marca'); ?>:</strong> <?= $mapper->brand->name; ?>
                <br>
                <strong><?= __($langGroup, 'Categoría'); ?>:</strong> <?= $mapper->category->name; ?>
                <br>
                <?php if($mapper->subcategory !== null):?>
                <strong><?= __($langGroup, 'Subcategoría'); ?>:</strong> <?= $mapper->subcategory->name; ?>
                <br>
                <?php endif;?>
                <strong><?= __($langGroup, 'Precio'); ?>:</strong> $<?= $mapper->price; ?>
                <br>
                <strong><?= __($langGroup, 'Referencia'); ?>:</strong> <?= $mapper->reference_code; ?>
                <br>
                <?= $mapper->description; ?>
            </div>

            <br>

            <div>

                <?php if(mb_strlen($editLink) > 0): ?>
                <a class="fluid ui olive button icon" href="<?= $editLink; ?>">
                    <i class="icon plus"></i> &nbsp; <?= __($langGroup, 'Editar'); ?>
                </a>
                <?php endif;?>

            </div>

        </div>

    </div>

</div>
