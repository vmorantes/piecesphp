<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Persons\Controllers\PersonsController;
use Persons\Mappers\PersonsMapper;

/**
 * @var PersonsMapper $element
 * @var PersonsController $this
 */
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
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

<form method='POST' action="<?= $action; ?>" class="ui form" person-form>

    <input type="hidden" name="id" value="<?= $element->id; ?>">
    <input type="hidden" name="lang" value="<?= $lang; ?>">

    <div class="two fields">
        <div class="field">

            <div class="field required">
                <label><?= __($langGroup, 'Tipo de documento'); ?></label>
                <select required name="documentType" class="ui dropdown search auto"><?= $optionsDocumentTypes; ?></select>
            </div>
            <br>

            <div class="field required">
                <label><?= __($langGroup, 'Primer nombre'); ?></label>
                <input type="text" name="personName1" required value="<?= $element->personName1; ?>">
            </div>
            <br>

            <div class="field required">
                <label><?= __($langGroup, 'Primer apellido'); ?></label>
                <input type="text" name="personLastName1" required value="<?= $element->personLastName1; ?>">
            </div>
            <br>

        </div>

        <div class="field">

            <div class="field required">
                <label><?= __($langGroup, 'Número de identificación'); ?></label>
                <input type="text" name="documentNumber" required value="<?= $element->documentNumber; ?>">
            </div>
            <br>

            <div class="field">
                <label><?= __($langGroup, 'Segundo nombre'); ?></label>
                <input type="text" name="personName2" value="<?= $element->personName2; ?>">
            </div>
            <br>

            <div class="field">
                <label><?= __($langGroup, 'Segundo apellido'); ?></label>
                <input type="text" name="personLastName2" value="<?= $element->personLastName2; ?>">
            </div>
            <br>

        </div>
    </div>

    <div class="field">
        <div class="ui buttons">
            <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
            <?php if($allowDelete): ?>
            <button type="submit" class="ui button red" delete-persons-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
            <?php endif; ?>
        </div>
    </div>

</form>
