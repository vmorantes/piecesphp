<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\Core\Config;
use Publications\Mappers\PublicationCategoryMapper;

/**
 * @var PublicationCategoryMapper $element
 */

/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */

$langs = Config::get_allowed_langs();
$manyLangs = count($langs) > 1;
$baseLang = $element->baseLang;
$langsTabs = [];
$translatableProperties = $element->getTranslatableProperties();
//== Ordenar con el idioma base primero ==
//==Se agrega el idioma base
$langsTabs[$baseLang] = __('lang', $baseLang);
//==Se agregan el resto de idiomas ordenados alfabéticamente
$otherLangs = array_filter($langs, function ($lang) use ($baseLang) {
    return $lang !== $baseLang;
});
sort($otherLangs);
//==Se mezclan los idiomas
foreach ($otherLangs as $lang) {
    $langsTabs[$lang] = __('lang', $lang);
}
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            //La cabecera del diseño de contenidos (diseno/01-contenidos.md §2.1).
            $this->helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description ?? '',
            ]);
        ?>

        <form method='POST' action="<?= $action; ?>" class="ui form initial publications-categories <?= $detailMode ? 'detail-mode' : ''; ?>" data-content-form>

            <div class="container-standard-form">

                <input type="hidden" name="id" value="<?= $element->id; ?>">
                <input type="hidden" name="baseLang" value="<?= $baseLang; ?>">

                <div class="field required">
                    <label>
                        <?= __($langGroup, 'Nombre'); ?>
                        <?php if($manyLangs): ?>
                        <small>(<?= __('lang', $baseLang); ?>)</small>
                        <?php endif; ?>
                    </label>
                    <input required type="text" name="name[<?= $baseLang ?>]" maxlength="300" value="<?= escape_html($element->getLangData($baseLang, 'name', false, '')); ?>">
                </div>

                <?php foreach($langsTabs as $langCode => $langName): ?>
                <?php if($baseLang !== $langCode): ?>
                <div class="field">
                    <label>
                        <?= __($langGroup, 'Nombre'); ?>
                        <small>(<?= $langName; ?>)</small>
                    </label>
                    <input type="text" name="name[<?= $langCode ?>]" maxlength="300" value="<?= escape_html($element->getLangData($langCode, 'name', false, '')); ?>">
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

            </div>

            <?php
                //La barra al final del formulario (§2.3): Eliminar es `button` y delete-config.js lo intercepta en el clic.
                $secondaryActions = [['text' => __($langGroup, 'Cancelar'), 'url' => $backLink, 'icon' => 'times']];
                if ($allowDelete) {
                    $secondaryActions[] = ['text' => __($langGroup, 'Eliminar'), 'type' => 'button', 'icon' => 'trash alternate', 'danger' => true, 'attributes' => ['delete-publication-category-button' => '', 'data-route' => $deleteRoute]];
                }
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => $secondaryActions,
                ]);
            ?>

        </form>

    </div>

</section>
