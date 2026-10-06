<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Core\Config;
/**
 * @var string|null $xAccount
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$getName = function ($name, $lang) {
    $dl = Config::get_default_lang();
    return $lang == $dl ? $name : "{$name}_$lang";
};
?>

<main class="seo-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Identidad y SEO'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Apariencia'); ?></span>
        </div>
        <div class="body-card no-gap">
            <div class="ui top attached tabular menu">
                <?php $isFirstTab = true; ?>
                <?php foreach ($SEOValues as $lang => $values) : ?>
                <a class="<?= $isFirstTab ? 'active ' : ''; ?>item" data-tab="<?= $lang; ?>"><?= __('lang', $lang); ?></a>
                <?php $isFirstTab = false; ?>
                <?php endforeach; ?>
            </div>


            <?php $isFirstTab = true; ?>
            <?php foreach ($SEOValues as $lang => $values) : ?>
            <div class="ui bottom attached<?= $isFirstTab ? ' active ' : ' '; ?>tab segment" data-tab="<?= $lang; ?>">

                <form action="<?= $actionURL; ?>" method="POST" class="ui form seo" lang="<?= $lang; ?>">

                    <input type="hidden" name="lang" value="<?= $lang; ?>">

                    <div class="field">

                        <div class="two fields">

                            <div class="field required">
                                <label><?= __($langGroup, 'Título del sitio'); ?></label>
                                <?php $property = SettingsController::SEO_OPTION_TITLE_APP_ON_FORM; ?>
                                <?php $propertyLang = ($getName)($property, $lang); ?>
                                <input type="text" name="<?= $property; ?>" value="<?= $values[$propertyLang]; ?>" placeholder="<?= __($langGroup, 'Nombre'); ?>" required>
                            </div>

                            <div class="field required">
                                <label><?= __($langGroup, 'Propietario'); ?></label>
                                <?php $property = SettingsController::SEO_OPTION_OWNER_ON_FORM; ?>
                                <?php $propertyLang = ($getName)($property, $lang); ?>
                                <input type="text" name="<?= $property; ?>" value="<?= $values[$propertyLang]; ?>" placeholder="<?= __($langGroup, 'Propietario'); ?>" required>
                            </div>

                        </div>

                    </div>

                    <div class="field required">
                        <label><?= __($langGroup, 'Descripción'); ?></label>
                        <?php $property = SettingsController::SEO_OPTION_DESCRIPTION_ON_FORM; ?>
                        <?php $propertyLang = ($getName)($property, $lang); ?>
                        <textarea required name="<?= $property; ?>" placeholder="<?= __($langGroup, 'Descripción de la página.'); ?>" required><?= $values[$propertyLang]; ?></textarea>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Palabras clave'); ?></label>
                        <?php $property = SettingsController::SEO_OPTION_KEYWORDS_ON_FORM; ?>
                        <?php $propertyLang = ($getName)($property, $lang); ?>
                        <select name="<?= $property; ?>[]" multiple class="ui dropdown multiple search selection keywords">
                            <?= $values[$propertyLang]; ?>
                        </select>
                    </div>

                    <div class="two fields">
                        <div class="field">
                            <label><?= __($langGroup, 'Título para compartir'); ?></label>
                            <?php $property = SettingsController::SEO_OPTION_SHARE_TITLE_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <input type="text" name="<?= $property; ?>" value="<?= $escape($values[$propertyLang] ?? ''); ?>">
                            <small><?= __($langGroup, 'Título de la tarjeta al compartir la portada. Vacío: el título normal.'); ?></small>
                        </div>
                        <?php if ($lang === Config::get_default_lang()): ?>
                        <div class="field">
                            <label><?= __($langGroup, 'Cuenta de X'); ?></label>
                            <input type="text" name="<?= SettingsController::SEO_OPTION_X_ACCOUNT_ON_FORM; ?>" value="<?= $escape($xAccount ?? ''); ?>" placeholder="@cuenta" pattern="@[A-Za-z0-9_]+">
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Descripción para compartir'); ?></label>
                        <?php $property = SettingsController::SEO_OPTION_SHARE_DESCRIPTION_ON_FORM; ?>
                        <?php $propertyLang = ($getName)($property, $lang); ?>
                        <textarea name="<?= $property; ?>" rows="3" ><?= $escape($values[$propertyLang] ?? ''); ?></textarea>
                        <small><?= __($langGroup, 'Descripción de la tarjeta al compartir. Vacía: la descripción del sitio.'); ?></small>
                    </div>

                    <div class="field">
                        <div class="content">
                            <?php $property = SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>

                            <?php
                                imageUploaderForCropperAdminViews([
                                    //Imagen de vista previa, string
                                    'image' => $values[$propertyLang],
                                    //Texto en alt de la etiqueta img, string
                                    'imageAlt' => null,
                                    // Por defecto tiene la clase image-action pueden añadirse más, string
                                    'classes' => null, 
                                    //Atributos que se añaden al contenedor .image-action, string
                                    'imageActionAttrs' => "seo-logo-item-{$lang}",
                                    //Texto que refiere al cambio de imagen, string
                                    'changeImageText' => null,
                                    //Título de la ficha, string
                                    'title' => __($langGroup, "Imagen Open Graph"),
                                    //Texto descriptivo de la ficha, string
                                    'description' => null,
                                    //Ancho de referencia, int
                                    'width' => 1200,
                                    //Alto de referencia, int
                                    'height' => 630,
                                ]);
                            ?>
                        </div>
                    </div>

                    <div class="save-button">
                        <button class="ui button primary" type="submit">
                            <?= __($langGroup, 'Guardar'); ?>
                        </button>
                    </div>

                    <?php
                        //Modal edición de imagen de SEO
                        modalImageUploaderForCropperAdminViews([
                            //El contenido (si se usa simpleCropperAdapterWorkSpace o similar debe ser con el parámetro $echo en false)
                            'content' => simpleCropperAdapterWorkSpace([
                                'type' => 'image/*',
                                'required' => false,
                                'selectorAttr' => 'simple-cropper-seo',
                                'referenceW' => '1200',
                                'referenceH' => '630',
                                'image' => $values[$propertyLang],
                            ], false),
                            //Atributos que se asignarán al modal (el contenedor principal), string
                            'modalContainerAttrs' => "seo-logo-modal-{$lang}",
                            //Clases que se asignarán al modal (el contenedor principal), string
                            'modalContainerClasses' => "ui tiny modal",
                            //Atributos que se asignarán al elemento de contenido del modal (modal > .content), string
                            'modalContentElementAttrs' => null,
                            //Clase por defecto del elemento informativo del modal (donde están el título y la descripcion, por omisión cropper-info-content), string
                            'informationContentMainClass' => null,
                            //Clases que se asignarán al elemento informativo del modal (donde están el título y la descripcion), string
                            'informationContentClasses' => null,
                            //Título del modal, string
                            'titleModal' => null,
                            //Descripción del modal, string
                            'descriptionModal' => null,
                        ]);
                    ?>
                </form>

            </div>
            <?php $isFirstTab = false; ?>
            <?php endforeach; ?>
        </div>
        <div class="divider"></div>
    </section>
</main>
