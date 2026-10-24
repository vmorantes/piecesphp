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

    <div class="limiter-content">

        <?php
        //La cabecera del panel (diseno/01-contenidos.md §2.1).
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description ?? '',
        ]);
        ?>


        <form method='POST' action="<?= $action; ?>" class="ui form initial newsletter" data-content-form>

            <div class="container-standard-form">

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

            </div>

            <?php $this->helpController->_render('panel/built-in/content/form-action-bar.php', ['saveText' => __($langGroup, 'Guardar')]); ?>

        </form>

    </div>

</section>
