<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Config;
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

            <form method='POST' action="<?= $action; ?>" class="ui form publications-categories">

                <div class="field required">
                    <label><?= __('lang', 'Idioma principal'); ?></label>
                    <select class="ui dropdown search" name="baseLang" required>
                        <?= $langsOptions; ?>
                    </select>
                </div>

                <div class="field required">
                    <label>
                        <?= __($langGroup, 'Nombre'); ?>
                    </label>
                    <input required type="text" name="name" maxlength="300" placeholder=" ">
                </div>

                <div class="field">
                    <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>

        </div>

    </div>

</section>