<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\BuiltIn\Shop\Category\Controllers\CategoryMapper;
use PiecesPHP\BuiltIn\Shop\SubCategory\Controllers\SubCategoryController;

/**
 * @var CategoryMapper $mapper
 */
$mapper;

/**
 * @var string $langGroup
 * @var string $editLink
 */;
$langGroup;
$editLink;

$subCategoriesLink = SubCategoryController::routeName('list') . "?category={$mapper->id}";

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
                <?= $mapper->description; ?>
            </div>

            <br>

            <div>

                <?php if(mb_strlen($editLink) > 0): ?>
                <a class="fluid ui olive button icon" href="<?= $editLink; ?>">
                    <i class="icon plus"></i> &nbsp; <?= __($langGroup, 'Editar'); ?>
                </a>
                <?php endif;?>

                <?php if(mb_strlen($subCategoriesLink) > 0): ?>
                <a class="fluid ui blue button icon" href="<?= $subCategoriesLink; ?>">
                    <i class="icon search"></i> &nbsp; <?= __($langGroup, 'Subcategorías'); ?>
                </a>
                <?php endif;?>

            </div>

        </div>

    </div>

</div>
