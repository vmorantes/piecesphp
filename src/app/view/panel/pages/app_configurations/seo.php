<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Controller\AppConfigController;
use PiecesPHP\Core\Config;
$getName = function ($name, $lang) {
    $dl = Config::get_default_lang();
    return $lang == $dl ? $name : "{$name}_$lang";
};
?>

<div class="ui header"><?=  __($langGroup, 'Ajustes SEO'); ?></div>

<div class="container-seo">

    <div class="ui top attached tabular menu">
        <?php $isFirstTab = true; ?>
        <?php foreach($SEOValues as $lang => $values): ?>
        <a class="<?= $isFirstTab ? 'active ' : ''; ?>item" data-tab="<?= $lang; ?>"><?= __('lang', $lang); ?></a>
        <?php $isFirstTab = false; ?>
        <?php endforeach; ?>
    </div>


    <?php $isFirstTab = true; ?>
    <?php foreach($SEOValues as $lang => $values): ?>
    <div class="ui bottom attached<?= $isFirstTab ? ' active ' : ' '; ?>tab segment" data-tab="<?= $lang; ?>">

        <form action="<?= $actionURL; ?>" method="POST" class="ui form seo" lang="<?= $lang; ?>">

            <input type="hidden" name="lang" value="<?= $lang; ?>">

            <div class="field">

                <div class="two fields">

                    <div class="field required">
                        <label><?= __($langGroup, 'Título del sitio'); ?></label>
                        <?php $property = AppConfigController::SEO_OPTION_TITLE_APP_ON_FORM; ?>
                        <?php $propertyLang = ($getName)($property, $lang); ?>
                        <input type="text" name="<?= $property; ?>" value="<?= $values[$propertyLang]; ?>" placeholder="<?= __($langGroup, 'Nombre'); ?>" required>
                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Propietario'); ?></label>
                        <?php $property = AppConfigController::SEO_OPTION_OWNER_ON_FORM; ?>
                        <?php $propertyLang = ($getName)($property, $lang); ?>
                        <input type="text" name="<?= $property; ?>" value="<?= $values[$propertyLang]; ?>" placeholder="<?= __($langGroup, 'Propietario'); ?>" required>
                    </div>

                </div>

            </div>

            <div class="field required">
                <label><?= __($langGroup, 'Descripción'); ?></label>
                <?php $property = AppConfigController::SEO_OPTION_DESCRIPTION_ON_FORM; ?>
                <?php $propertyLang = ($getName)($property, $lang); ?>
                <textarea required name="<?= $property; ?>" placeholder="<?= __($langGroup, 'Descripción de la página.'); ?>" required><?= $values[$propertyLang]; ?></textarea>
            </div>

            <div class="field">
                <label><?= __($langGroup, 'Palabras clave'); ?></label>
                <?php $property = AppConfigController::SEO_OPTION_KEYWORDS_ON_FORM; ?>
                <?php $propertyLang = ($getName)($property, $lang); ?>
                <select name="<?= $property; ?>[]" multiple class="ui dropdown multiple search selection keywords">
                    <?= $values[$propertyLang]; ?>
                </select>
            </div>

            <div class="field">
                <label><?= __($langGroup, 'Scripts adicionales'); ?></label>
                <?php $property = AppConfigController::SEO_OPTION_EXTRA_SCRIPTS_ON_FORM; ?>
                <?php $propertyLang = ($getName)($property, $lang); ?>
                <textarea name="<?= $property; ?>" placeholder="<?= __($langGroup, "<script src='ejemplo.js'></script>"); ?>"><?= $values[$propertyLang]; ?></textarea>
            </div>

            <div class="field">

                <div class="ui card fluid">

                    <div class="content">

                        <div class="ui form cropper-adapter">

                            <?php $property = AppConfigController::SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <input type="file" accept="image/*">
                            <?php 
                            cropperAdapterWorkSpace([
                                'withTitle'=> false,
                                'image'=> $values[$propertyLang],
                                'referenceW'=> '1200',
                                'referenceH'=> '630',
                                'cancelButtonText' => null,
                                'saveButtonText' => __($langGroup, 'Seleccionar imagen'),                            
                                'controls' =>[
                                    'rotate' => false, 
                                    'flip' => false, 
                                    'adjust' => false, 
                                ],
                            ]); 
                        ?>
                        </div>

                    </div>

                    <div class="content">
                        <label class="header"><?= __($langGroup, 'Imagen Open graph'); ?></label>

                        <div class="meta">
                            <span><?= strReplaceTemplate(__($langGroup, 'Tamaño de la imagen {dimensions}'), ['{dimensions}' => "1200x630px",])?></span>
                        </div>

                    </div>

                </div>

                <div class="field">

                    <button class="ui button green" type="submit">
                        <?= __($langGroup, 'Guardar'); ?>
                    </button>

                </div>

            </div>

        </form>

    </div>
    <?php $isFirstTab = false; ?>
    <?php endforeach; ?>

</div>
