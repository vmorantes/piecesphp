<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Config;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
/**
 * @var GenericContentPseudoMapper $element
 */
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
$defaultLang = Config::get_default_lang();
$allowedLangs = Config::get_allowed_langs();
$contentName = $element->userSetContentName();
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

        <div class="container-standard-form mw-800">

            <form method='POST' action="<?= $action; ?>" class="ui form generic">

                <input type="hidden" name="configName" value="<?= $contentName; ?>">
                <?php foreach($allowedLangs as $lang): ?>
                <?php $value = $element->getLangData($lang, $contentName, true, ''); ?>
                <?php $isMultilang = $element->isTranslatable($contentName); ?>
                <?php $langSuffix = $isMultilang ? " ({$lang})" : ''; ?>
                <?php if($isMultilang || $lang === $defaultLang): ?>
                <div class="field required">
                    <label><?= __($langGroup, 'Límite de tokens') . $langSuffix; ?></label>
                    <input type="number" step="1" name="<?= $contentName; ?>[<?= $lang; ?>]" value="<?= $value; ?>" required>
                </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <br><br>

                <div class="field">
                    <div class="ui buttons">
                        <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                    </div>
                </div>

            </form>

        </div>

    </div>

</section>