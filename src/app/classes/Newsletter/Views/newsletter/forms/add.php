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

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

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

    </div>

</section>
