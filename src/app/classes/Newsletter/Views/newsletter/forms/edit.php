<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Newsletter\Mappers\NewsletterSuscriberMapper;

/**
 * @var NewsletterSuscriberMapper $element
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

<div>

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

    <br>

    <form method='POST' action="<?= $action; ?>" class="ui form newsletter standard-form">

        <input type="hidden" name="id" value="<?= $element->id; ?>">

        <div class="field required">
            <label><?= __($langGroup, 'Nombre'); ?></label>
            <input required type="text" name="name" maxlength="200" value="<?= $element->name; ?>">
        </div>

        <div class="field required">
            <label><?= __($langGroup, 'Email'); ?></label>
            <input required type="email" name="email" maxlength="200" value="<?= $element->email; ?>">
        </div>

        <div class="field">
            <div class="ui toggle checkbox">
                <input type="checkbox" name="acceptUpdates" value="<?= NewsletterSuscriberMapper::ACCEPT_UPDATES_YES; ?>" <?= $element->acceptUpdates() ? 'checked' : ''; ?>>
                <label><?= __($langGroup, 'Acepta recibir correos'); ?></label>
            </div>
        </div>

        <br><br>

        <div class="field">
            <div class="ui buttons">
                <button type="submit" class="ui button green"><?= __($langGroup, 'Guardar'); ?></button>
                <?php if($allowDelete): ?>
                <button type="submit" class="ui button red" delete-publication-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                <?php endif; ?>
            </div>
        </div>

    </form>

</div>
