<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\BuiltIn\Shop\SubCategory\Mappers\SubCategoryMapper;

/**
 * @var SubCategoryMapper $mapper
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
            <img src="<?= $mapper->image; ?>">
        </div>

        <br>

        <div class="description">

            <div>
                <strong><?= __($langGroup, 'Categoría'); ?>:</strong> <?= $mapper->category->name; ?>
                <br>
                <?= $mapper->description; ?>
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
