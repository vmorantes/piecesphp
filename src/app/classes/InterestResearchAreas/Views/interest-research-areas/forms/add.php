<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use InterestResearchAreas\Mappers\InterestResearchAreasMapper;
use PiecesPHP\Core\Config;
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$langs = Config::get_allowed_langs(false, Config::get_default_lang());
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

        <div class="tabs-controls">
            <div class="active" data-tab="basic"><?= __($langGroup, 'Datos básicos'); ?></div>
        </div>

        <form method='POST' action="<?= $action; ?>" class="ui form interest-research-areas initial">

            <div class="container-standard-form">

                <input type="hidden" name="lang" value="<?= \PiecesPHP\Core\Config::get_lang(); ?>">
                <input type="hidden" name="baseLang" value="<?= \PiecesPHP\Core\Config::get_default_lang(); ?>">

                <div class="ui tab active" data-tab="basic">

                    <div class="field">
                        <div class="ui stackable grid">
                            <?php foreach($langs as $lang): ?>
                            <div class="eight wide column">
                                <div class="field required">
                                    <label><?= __($langGroup, 'Nombre'); ?> (<?= __('lang', $lang); ?>)</label>
                                    <input required type="text" name="areaName[<?= $lang; ?>]" maxlength="1200">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Color'); ?></label>
                        <input type="text" name="color" value="<?= get_config('main_brand_color'); ?>" color-picker-js>
                    </div>

                </div>

            </div>

            <br>

            <div class="field">
                <div class="ui right floated buttons">
                    <button data-tab-related="basic" type="submit" class="ui button brand-color" save><?= __($langGroup, 'Guardar'); ?></button>
                </div>
            </div>

        </form>

    </div>

</section>