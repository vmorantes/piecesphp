<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$standalone = isset($standalone) && is_bool($standalone) ? $standalone : true;
$submitButtonText = isset($submitButtonText) ? $submitButtonText : __($langGroup, 'Guardar');
?>
<?php if($standalone): ?>
<section class="module-view-container">
    <?php endif; ?>

    <?php if($standalone): ?>
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
    <?php endif; ?>

    <div class="container-standard-form max-w-800 <?= !$standalone ? 'block-centered' : ''; ?>">
        <form method='POST' action="<?= $action; ?>" class="ui form" category-form>

            <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">

            <div class="two fields">

                <div class="field required">
                    <label><?= __($langGroup, 'Nombre'); ?></label>
                    <input type="text" name="categoryName" required placeholder="Nombre de la categoría">
                </div>

                <div class="field">
                    <label>&nbsp;</label>
                    <button class="ui button brand-color" type="submit"><?= $submitButtonText; ?></button>
                </div>

            </div>

        </form>
    </div>

    <?php if($standalone): ?>
</section>
<?php endif; ?>
