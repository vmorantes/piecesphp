<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */;
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
<form method='POST' action="<?= $action; ?>" class="ui form <?= $standalone ? 'standard-form' : ''; ?>" person-form>

    <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

    <div class="two fields">
        <div class="field">

            <div class="field required">
                <label><?= __($langGroup, 'Tipo de documento'); ?></label>
                <select required name="documentType" class="ui dropdown search auto"><?= $optionsDocumentTypes; ?></select>
            </div>
            <br>

            <div class="field required">
                <label><?= __($langGroup, 'Primer nombre'); ?></label>
                <input type="text" name="personName1" required>
            </div>
            <br>

            <div class="field required">
                <label><?= __($langGroup, 'Primer apellido'); ?></label>
                <input type="text" name="personLastName1" required>
            </div>
            <br>

        </div>

        <div class="field">

            <div class="field required">
                <label><?= __($langGroup, 'Número de identificación'); ?></label>
                <input type="text" name="documentNumber" required>
            </div>
            <br>

            <div class="field">
                <label><?= __($langGroup, 'Segundo nombre'); ?></label>
                <input type="text" name="personName2">
            </div>
            <br>

            <div class="field">
                <label><?= __($langGroup, 'Segundo apellido'); ?></label>
                <input type="text" name="personLastName2">
            </div>
            <br>

        </div>
    </div>

    <div class="field">
        <button class="ui button green" type="submit"><?= $submitButtonText; ?></button>
    </div>

</form>
