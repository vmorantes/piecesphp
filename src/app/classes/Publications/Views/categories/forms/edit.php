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
$baseLang = $element->baseLang;
$langsTabs = [];
$translatableProperties = $element->getTranslatableProperties();
//== Ordenar con el idioma base primero ==
//==Se agrega el idioma base
$langsTabs[$baseLang] = __('lang', $baseLang);
//==Se agregan el resto de idiomas ordenados alfabéticamente
$otherLangs = array_filter($langs, function($lang) use ($baseLang) {
    return $lang !== $baseLang;
});
sort($otherLangs);
//==Se mezclan los idiomas
foreach ($otherLangs as $lang) {
    $langsTabs[$lang] = __('lang', $lang);
}
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
                <input type="hidden" name="baseLang" value="<?= $baseLang; ?>">

                <div class="field required">
                    <label>
                        <?= __($langGroup, 'Nombre'); ?>
                        <small>(<?= __('lang', $baseLang); ?>)</small>
                    </label>
                    <input required type="text" name="name[<?= $baseLang ?>]" maxlength="300" value="<?= $element->getLangData($baseLang, 'name', false, ''); ?>">
                </div>

                <?php foreach($langsTabs as $langCode => $langName): ?>
                <?php if($baseLang !== $langCode): ?>
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