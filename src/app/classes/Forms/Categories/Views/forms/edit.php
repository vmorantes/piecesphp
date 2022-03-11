<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Forms\Categories\Controllers\CategoriesController;
use Forms\Categories\Mappers\CategoriesMapper;

/**
 * @var CategoriesMapper $element
 * @var CategoriesController $this
 */
 $element;
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$langGroup;
$backLink;
$action;
?>

<div class="ui buttons">

    <a href="<?= $backLink; ?>" class="ui labeled icon button">
        <i class="icon left arrow"></i>
        <?= __($langGroup, 'Regresar'); ?>
    </a>

</div>

<br>
<br>

<h3 class="title-form"><?= __($langGroup, 'Editar'); ?>
    <?= $title; ?>
</h3>

<?php if($manyLangs): ?>
<div class="ui form">
    <div class="field required">
        <label><?= __($langGroup, 'Idiomas'); ?></label>
        <select required class="ui dropdown search langs">
            <?= $allowedLangs; ?>
        </select>
    </div>
</div>
<?php endif; ?>

<br>

<form method='POST' action="<?= $action; ?>" class="ui form" category-form>

    <input type="hidden" name="id" value="<?= $element->id; ?>">
    <input type="hidden" name="lang" value="<?= $lang; ?>">

    <div class="two fields">

        <div class="field required">
            <label><?= __($langGroup, 'Nombre'); ?></label>
            <input type="text" name="categoryName" required value="<?= $element->getLangData($lang, 'categoryName'); ?>">
        </div>

        <div class="field">
            <label>&nbsp;</label>
            <div class="ui buttons">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
                <?php if($allowDelete): ?>
                <button type="submit" class="ui button red" delete-categories-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                <?php endif; ?>
            </div>
        </div>

    </div>

</form>
