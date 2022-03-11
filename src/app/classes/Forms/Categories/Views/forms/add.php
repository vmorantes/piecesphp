<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var Categories
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$langGroup;
$backLink;
$action;
$standalone = isset($standalone) && is_bool($standalone) ? $standalone : true;
$submitButtonText = isset($submitButtonText) ? $submitButtonText : __($langGroup, 'Guardar');
?>

<?php if($standalone): ?>
<div class="ui buttons">

    <a href="<?= $backLink; ?>" class="ui labeled icon button">
        <i class="icon left arrow"></i>
        <?= __($langGroup, 'Regresar'); ?>
    </a>

</div>

<br><br>

<h3 class="title-form"><?= __($langGroup, 'Agregar'); ?>
    <?= $title; ?>
</h3>
<?php endif; ?>
<form method='POST' action="<?= $action; ?>" class="ui form <?= $standalone ? 'standard-form' : ''; ?>" category-form>

    <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

    <div class="two fields">

        <div class="field required">
            <label><?= __($langGroup, 'Nombre'); ?></label>
            <input type="text" name="categoryName" required placeholder="Nombre de la categoría">
        </div>

        <div class="field">
            <label>&nbsp;</label>
            <button class="ui button green" type="submit"><?= $submitButtonText; ?></button>
        </div>

    </div>

</form>
