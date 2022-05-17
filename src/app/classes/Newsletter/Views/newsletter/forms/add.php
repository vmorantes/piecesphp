<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Newsletter\Mappers\NewsletterSuscriberMapper;

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container">

    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Agregar'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <div class="container-standard-form">

        <form method='POST' action="<?= $action; ?>" class="ui form newsletter">

            <div class="field required">
                <label><?= __($langGroup, 'Nombre'); ?></label>
                <input required type="text" name="name" maxlength="200">
            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Email'); ?></label>
                <input required type="email" name="email" maxlength="200">
            </div>

            <div class="field">
                <div class="ui toggle checkbox">
                    <input type="checkbox" name="acceptUpdates" value="<?= NewsletterSuscriberMapper::ACCEPT_UPDATES_YES; ?>" checked>
                    <label><?= __($langGroup, 'Acepta recibir correos'); ?></label>
                </div>
            </div>

            <br><br>

            <div class="field">
                <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
            </div>

        </form>

    </div>

</section>
