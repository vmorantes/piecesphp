<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Publications\Mappers\PublicationCategoryMapper;
use PiecesPHP\Core\Config;

/**
 * @var PublicationCategoryMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */

$langs = Config::get_allowed_langs();
$defaultLang = Config::get_default_lang();
$langsTabs = [];
$translatableProperties = $element->getTranslatableProperties();
array_map(function ($lang) use (&$langsTabs) {
    $langsTabs[$lang] = __('lang', $lang);
}, $langs);
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

            <form method='POST' action="<?= $action; ?>" class="ui form publications-categories <?= $detailMode ? 'detail-mode' : ''; ?>">

                <input type="hidden" name="id" value="<?= $element->id; ?>">

                <div class="field required">
                    <label>
                        <?= __($langGroup, 'Nombre'); ?>
                        <small>(<?= __('lang', $defaultLang); ?>)</small>
                    </label>
                    <input required type="text" name="name[<?= $defaultLang ?>]" maxlength="300" value="<?= $element->getLangData($defaultLang, 'name', false, ''); ?>">
                </div>

                <?php foreach($langsTabs as $langCode => $langName): ?>
                <?php if($defaultLang !== $langCode): ?>
                <div class="field">
                    <label>
                        <?= __($langGroup, 'Nombre'); ?>
                        <small>(<?= $langName; ?>)</small>
                    </label>
                    <input type="text" name="name[<?= $langCode ?>]" maxlength="300" value="<?= $element->getLangData($langCode, 'name', false, ''); ?>">
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <br>

                <div class="field">
                    <div class="ui buttons">
                        <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                        <?php if($allowDelete): ?>
                        <button type="submit" class="ui button brand-color alt2" delete-publication-category-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>

            </form>

        </div>

    </div>

</section>
